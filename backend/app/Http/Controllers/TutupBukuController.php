<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TutupBukuController extends Controller
{
    public function check($year)
    {
        $year = (int) $year;
        $currentYear = (int) date('Y');

        if ($year > $currentYear) {
            return response()->json([
                'success' => false,
                'data'    => [
                    'closed'  => false,
                    'message' => 'Tahun tidak valid (masa depan).',
                ],
            ], 422);
        }

        $closing = DB::table('transactions')
            ->where('reverence_type', 'tutup_buku')
            ->where('reverence_id', $year)
            ->whereNull('deleted_at')
            ->first();

        $isClosed = $closing ? true : false;

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'    => $year,
                'closed'   => $isClosed,
                'closed_at' => $isClosed ? $closing->tgl_transaksi : null,
                'can_edit' => ! $isClosed,
            ],
        ]);
    }

    public function accountsWithSaldo($year)
    {
        $year = (int) $year;

        $accounts = Account::select('id', 'kode_akun', 'nama_akun', 'lev1', 'lev2', 'lev3', 'lev4', 'jenis_mutasi')
            ->orderBy('kode_akun')
            ->get();

        $start = "{$year}-01-01";
        $end   = "{$year}-12-31";

        $closing = DB::table('transactions')
            ->where('reverence_type', 'tutup_buku')
            ->where('reverence_id', $year)
            ->whereNull('deleted_at')
            ->exists();

        $saldoMap = [];
        if ($closing) {
            $rows = DB::table('transactions')
                ->where('reverence_type', 'tutup_buku_snapshot')
                ->where('reverence_id', $year)
                ->whereNull('deleted_at')
                ->select('account_debet as kode_akun', 'saldo')
                ->get();
            foreach ($rows as $r) {
                $saldoMap[$r->kode_akun] = (float) $r->saldo;
            }
        } else {
            foreach ($accounts as $acc) {
                $debit  = (float) DB::table('transactions')
                    ->where('account_debet', $acc->kode_akun)
                    ->whereNull('deleted_at')
                    ->whereBetween('tgl_transaksi', [$start, $end])
                    ->sum('saldo');

                $kredit = (float) DB::table('transactions')
                    ->where('account_kredit', $acc->kode_akun)
                    ->whereNull('deleted_at')
                    ->whereBetween('tgl_transaksi', [$start, $end])
                    ->sum('saldo');

                if ($acc->jenis_mutasi === 'kredit') {
                    $saldoMap[$acc->kode_akun] = $kredit - $debit;
                } else {
                    $saldoMap[$acc->kode_akun] = $debit - $kredit;
                }
            }
        }

        $result = $accounts->map(function ($a) use ($saldoMap) {
            $saldo = $saldoMap[$a->kode_akun] ?? 0;
            return [
                'account_id' => $a->id,
                'kode'       => $a->kode_akun,
                'nama'       => $a->nama_akun,
                'lev1'       => (int) $a->lev1,
                'lev2'       => (int) $a->lev2,
                'lev3'       => (int) $a->lev3,
                'lev4'       => (int) $a->lev4,
                'jenis_mutasi' => $a->jenis_mutasi,
                'saldo'      => $saldo,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'    => $year,
                'closed'   => $closing,
                'accounts' => $result,
            ],
        ]);
    }

    public function close(Request $request)
    {
        $request->validate([
            'tahun'    => 'required|integer|min:2000',
            'overrides' => 'array',
            'overrides.*.kode'  => 'required_with:overrides|string',
            'overrides.*.saldo' => 'required_with:overrides|numeric',
        ]);

        $tahun = (int) $request->tahun;
        $currentYear = (int) date('Y');

        if ($tahun > $currentYear) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menutup buku untuk tahun mendatang.',
            ], 422);
        }

        $already = DB::table('transactions')
            ->where('reverence_type', 'tutup_buku')
            ->where('reverence_id', $tahun)
            ->whereNull('deleted_at')
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => "Buku untuk tahun {$tahun} sudah ditutup.",
            ], 422);
        }

        $cfg = $this->getClosingConfig();

        $overrides = [];
        if ($request->has('overrides')) {
            foreach ($request->overrides as $o) {
                $overrides[$o['kode']] = (float) $o['saldo'];
            }
        }

        $accounts = Account::orderBy('kode_akun')->get();
        $start = "{$tahun}-01-01";
        $end   = "{$tahun}-12-31";

        $saldoAkhir = [];
        foreach ($accounts as $a) {
            $debit  = (float) DB::table('transactions')
                ->where('account_debet', $a->kode_akun)
                ->whereNull('deleted_at')
                ->whereBetween('tgl_transaksi', [$start, $end])
                ->sum('saldo');

            $kredit = (float) DB::table('transactions')
                ->where('account_kredit', $a->kode_akun)
                ->whereNull('deleted_at')
                ->whereBetween('tgl_transaksi', [$start, $end])
                ->sum('saldo');

            $saldo = $a->jenis_mutasi === 'kredit' ? ($kredit - $debit) : ($debit - $kredit);

            if (array_key_exists($a->kode_akun, $overrides)) {
                $saldo = $overrides[$a->kode_akun];
            }

            $saldoAkhir[$a->kode_akun] = $saldo;
        }

        $labaRugi = 0.0;
        foreach ($saldoAkhir as $kode => $saldo) {
            $first = substr($kode, 0, 1);
            if ($first === '4') {
                $labaRugi += $saldo;
            } elseif ($first === '5') {
                $labaRugi -= $saldo;
            }
        }

        DB::beginTransaction();
        try {
            $userId = $request->user()->id ?? null;
            $tglTutup = $tahun . '-12-31';
            $group = (int) (microtime(true) * 1000);

            foreach ($saldoAkhir as $kode => $saldo) {
                if (abs($saldo) < 0.01) continue;

                if (substr($kode, 0, 1) === '4') {
                    $akunDebet  = $cfg['akun_laba_ditahan'];
                    $akunKredit = $kode;
                    $nominal = abs($saldo);
                    Transaction::create([
                        'tgl_transaksi'        => $tglTutup,
                        'account_debet'        => $akunDebet,
                        'account_kredit'       => $akunKredit,
                        'transaction_group'    => $group,
                        'reverence_type'       => 'tutup_buku_snapshot',
                        'reverence_id'         => $tahun,
                        'keterangan_transaksi' => "Tutup Buku {$tahun} - Penutupan Pendapatan {$kode}",
                        'saldo'                => $nominal,
                        'urutan'               => 1,
                        'id_user'              => $userId,
                    ]);
                } elseif (substr($kode, 0, 1) === '5') {
                    $akunDebet  = $kode;
                    $akunKredit = $cfg['akun_laba_ditahan'];
                    $nominal = abs($saldo);
                    Transaction::create([
                        'tgl_transaksi'        => $tglTutup,
                        'account_debet'        => $akunDebet,
                        'account_kredit'       => $akunKredit,
                        'transaction_group'    => $group,
                        'reverence_type'       => 'tutup_buku_snapshot',
                        'reverence_id'         => $tahun,
                        'keterangan_transaksi' => "Tutup Buku {$tahun} - Penutupan Beban {$kode}",
                        'saldo'                => $nominal,
                        'urutan'               => 1,
                        'id_user'              => $userId,
                    ]);
                }
            }

            Transaction::create([
                'tgl_transaksi'        => $tglTutup,
                'account_debet'        => $labaRugi >= 0 ? $cfg['akun_laba_ditahan'] : $cfg['akun_laba_rugi_berjalan'],
                'account_kredit'       => $labaRugi >= 0 ? $cfg['akun_laba_rugi_berjalan'] : $cfg['akun_laba_ditahan'],
                'transaction_group'    => $group,
                'reverence_type'       => 'tutup_buku_snapshot',
                'reverence_id'         => $tahun,
                'keterangan_transaksi' => "Tutup Buku {$tahun} - Saldo Laba/Rugi Tahun Berjalan",
                'saldo'                => abs($labaRugi),
                'urutan'               => 99,
                'id_user'              => $userId,
            ]);

            Transaction::create([
                'tgl_transaksi'        => $tglTutup,
                'account_debet'        => $cfg['akun_laba_rugi_berjalan'],
                'account_kredit'       => $cfg['akun_laba_ditahan'],
                'transaction_group'    => $group,
                'reverence_type'       => 'tutup_buku',
                'reverence_id'         => $tahun,
                'keterangan_transaksi' => "Tutup Buku Tahun {$tahun}",
                'saldo'                => abs($labaRugi),
                'urutan'               => 100,
                'id_user'              => $userId,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'      => "Buku tahun {$tahun} berhasil ditutup.",
                    'tahun'        => $tahun,
                    'laba_rugi'    => $labaRugi,
                    'group'        => $group,
                    'closed_at'    => $tglTutup,
                    'config_used'  => $cfg,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menutup buku: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $rows = DB::table('transactions')
            ->where('reverence_type', 'tutup_buku')
            ->whereNull('deleted_at')
            ->select('reverence_id as tahun', 'tgl_transaksi as closed_at', 'keterangan_transaksi', 'id_user')
            ->orderByDesc('reverence_id')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $rows,
        ]);
    }

    public function getConfig()
    {
        return response()->json([
            'success' => true,
            'data'    => $this->getClosingConfig(),
        ]);
    }

    public function saveConfig(Request $request)
    {
        $request->validate([
            'akun_laba_ditahan'     => 'required|string|exists:accounts,kode_akun',
            'akun_laba_rugi_berjalan' => 'required|string|exists:accounts,kode_akun',
        ]);

        $cfg = [
            'akun_laba_ditahan'       => $request->akun_laba_ditahan,
            'akun_laba_rugi_berjalan' => $request->akun_laba_rugi_berjalan,
        ];

        DB::table('settings')->updateOrInsert(
            ['key' => 'tutup_buku_config'],
            [
                'value'      => json_encode($cfg),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => $cfg,
        ]);
    }

    private function getClosingConfig(): array
    {
        $default = [
            'akun_laba_ditahan'       => '3.2.01.01',
            'akun_laba_rugi_berjalan' => '3.2.02.01',
        ];

        $row = DB::table('settings')->where('key', 'tutup_buku_config')->first();
        if ($row && $row->value) {
            $decoded = json_decode($row->value, true);
            if (is_array($decoded)) {
                return array_merge($default, $decoded);
            }
        }

        return $default;
    }
}
