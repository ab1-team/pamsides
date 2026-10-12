<?php

namespace App\Http\Controllers;

use App\Models\Account;
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

        $closing = DB::table('amount')
            ->where('tahun', $year)
            ->where('bulan', '13')
            ->first();

        $isClosed = $closing ? true : false;

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'    => $year,
                'closed'   => $isClosed,
                'closed_at' => $isClosed ? "{$year}-12-31" : null,
                'can_edit' => ! $isClosed,
            ],
        ]);
    }

    public function accountsWithSaldo($year)
    {
        $year = (int) $year;

        // Hanya akun yang punya data jurnal (lihat catatan di close()).
        // Kalau tidak, preview menampilkan 200 baris padahal close()
        // hanya menulis 10 -- user akan lihat angka yang tak akan disimpan.
        $liveIds = DB::table('amount')->distinct()->pluck('account_id')->all();
        if (empty($liveIds)) {
            $liveIds = DB::table('transactions')->whereNull('deleted_at')->distinct()->pluck('account_debet')
                ->merge(DB::table('transactions')->whereNull('deleted_at')->distinct()->pluck('account_kredit'))
                ->filter()->unique()->all();
        }

        $accounts = Account::whereIn('id', $liveIds)
            ->select('id', 'kode_akun', 'nama_akun', 'lev1', 'lev2', 'lev3', 'lev4', 'jenis_mutasi')
            ->orderBy('kode_akun')
            ->get();

        $isClosed = DB::table('amount')
            ->where('tahun', $year)
            ->where('bulan', '13')
            ->exists();

        $saldoMap = [];

        if ($isClosed) {
            $closingRecord = DB::table('amount')
                ->where('tahun', $year)
                ->where('bulan', '13')
                ->where('account_id', function ($q) {
                    $q->select('id')->from('accounts')->where('kode_akun', '3.2.02.01');
                })
                ->first();

            $rows = DB::table('amount')
                ->where('tahun', $year)
                ->whereIn('bulan', ['13'])
                ->join('accounts', 'amount.account_id', '=', 'accounts.id')
                ->select('accounts.kode_akun', 'amount.debit', 'amount.kredit')
                ->get();

            foreach ($rows as $r) {
                $saldoMap[$r->kode_akun] = (float) $r->debit - (float) $r->kredit;
            }
        } else {
            $end   = "{$year}-12-31";
            $kodeList = $accounts->pluck('kode_akun')->all();

            // Sama seperti close(): kumulatif sampai akhir tahun, bukan
            // cuma mutasi 1 tahun, supaya angka di preview ini sama
            // dengan yang benar-benar ditulis.
            $debits = DB::table('transactions')
                ->select('account_debet as kode_akun', DB::raw('SUM(saldo) as total'))
                ->whereIn('account_debet', $kodeList)
                ->whereNull('deleted_at')
                ->where('tgl_transaksi', '<=', $end)
                ->groupBy('account_debet')
                ->pluck('total', 'kode_akun');

            $kredits = DB::table('transactions')
                ->select('account_kredit as kode_akun', DB::raw('SUM(saldo) as total'))
                ->whereIn('account_kredit', $kodeList)
                ->whereNull('deleted_at')
                ->where('tgl_transaksi', '<=', $end)
                ->groupBy('account_kredit')
                ->pluck('total', 'kode_akun');

            foreach ($accounts as $acc) {
                $debit  = (float) ($debits[$acc->kode_akun]  ?? 0);
                $kredit = (float) ($kredits[$acc->kode_akun] ?? 0);
                $saldoMap[$acc->kode_akun] = self::isKredit($acc->jenis_mutasi)
                    ? ($kredit - $debit)
                    : ($debit - $kredit);
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
                'closed'   => $isClosed,
                'accounts' => $result,
            ],
        ]);
    }

    public function close(Request $request)
    {
        $request->validate([
            'tahun'     => 'required|integer|min:2000',
            'overrides' => 'array',
            'overrides.*.kode'   => 'required_with:overrides|string',
            'overrides.*.saldo'  => 'required_with:overrides|numeric',
        ]);

        $tahun = (int) $request->tahun;
        $currentYear = (int) date('Y');

        if ($tahun > $currentYear) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menutup buku untuk tahun mendatang.',
            ], 422);
        }

        $already = DB::table('amount')
            ->where('tahun', $tahun)
            ->where('bulan', '13')
            ->exists();

        if ($already) {
            return response()->json([
                'success' => false,
                'message' => "Buku untuk tahun {$tahun} sudah ditutup.",
            ], 422);
        }

        $overrides = [];
        if ($request->has('overrides')) {
            foreach ($request->overrides as $o) {
                $overrides[$o['kode']] = (float) $o['saldo'];
            }
        }

        /*
 * Hanya akun yang BENAR-benar dipakai jurnal yang perlu ditutup.
 *
 * Tabel `accounts` menyisakan baris lama (id 1-92 & 186-208) di samping
 * blok seeder terbaru (id 594-689) untuk kode yang sama. Baris lama
 * tidak pernah disentuh `transactions` maupun `amount`, jadi menutup
 * semuanya akan menulis carry-over untuk akun yang saldonya nol --
 * dan karena `amount` primary key-nya `account_id.tahun.bulan`, dua
 * baris berkode sama tidak bentrok tapi angkanya dobel di laporan.
 *
 * Jadi: ambil id yang benar-benar punya baris di `amount` atau dipakai
 * transaksi. Kalau `amount` belum ada (install baru), jatuh ke id
 * terbesar per kode agar tetap deterministik.
 */
$accountIds = DB::table('amount')->distinct()->pluck('account_id')->all();

if (empty($accountIds)) {
    $accountIds = DB::table('transactions')->whereNull('deleted_at')->distinct()->pluck('account_debet')
        ->merge(DB::table('transactions')->whereNull('deleted_at')->distinct()->pluck('account_kredit'))
        ->filter()->unique()->all();
}

$accounts = collect($accountIds)
    ->map(fn ($id) => Account::find($id))
    ->filter()
    ->sortBy('kode_akun')
    ->values();

$end   = "{$tahun}-12-31";
        $kodeList = $accounts->pluck('kode_akun')->all();

        /*
         * Saldo AKHIR tahun $tahun = seluruh mutasi dari awal pembukuan
         * sampai 31 Desember $tahun, BUKAN cuma mutasi selama 1 tahun.
         *
         * Versi lama memakai whereBetween(tahun-01-01, tahun-12-31) sehingga
         * opening balance dari tahun-tahun sebelumnya hilang -- saldo Kas Tunai
         * akhir 2025 jadi 807.158.090 (mutasi 2025 saja) sedangkan
         * seharusnya 808.038.090. Mutasi 2024 (/ sebelum periode awal)
         * ikut terlewat.
         *
         * Baris bulan '00' (carry-over) sengaja dikecualikan: isinya
         * saldo yang SUDAH tercermin di mutasi bulan 01..12, jadi
         * menghitungnya akan dobel.
         */
        $debits = DB::table('transactions')
            ->select('account_debet as kode_akun', DB::raw('SUM(saldo) as total'))
            ->whereIn('account_debet', $kodeList)
            ->whereNull('deleted_at')
            ->where('tgl_transaksi', '<=', $end)
            ->groupBy('account_debet')
            ->pluck('total', 'kode_akun');

        $kredits = DB::table('transactions')
            ->select('account_kredit as kode_akun', DB::raw('SUM(saldo) as total'))
            ->whereIn('account_kredit', $kodeList)
            ->whereNull('deleted_at')
            ->where('tgl_transaksi', '<=', $end)
            ->groupBy('account_kredit')
            ->pluck('total', 'kode_akun');

        $saldoAkhir = [];
        foreach ($accounts as $a) {
            $debit  = (float) ($debits[$a->kode_akun]  ?? 0);
            $kredit = (float) ($kredits[$a->kode_akun] ?? 0);
            $saldo = self::isKredit($a->jenis_mutasi) ? ($kredit - $debit) : ($debit - $kredit);

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

        $cfg = $this->getClosingConfig();

        $tahunDepan = $tahun + 1;

        DB::beginTransaction();
        try {
            $now = now();

            $amountRows13 = [];
            foreach ($accounts as $a) {
                $saldo = $saldoAkhir[$a->kode_akun] ?? 0;
                if (abs($saldo) < 0.01) continue;

                $first = substr($a->kode_akun, 0, 1);

                if (! self::isKredit($a->jenis_mutasi)) {
                    $debitVal = $saldo;
                    $kreditVal = 0;
                } else {
                    $debitVal = 0;
                    $kreditVal = $saldo;
                }

                $id = (int) ($a->id . $tahun . '13');

                $amountRows13[] = [
                    'id'         => $id,
                    'account_id' => $a->id,
                    'bulan'      => '13',
                    'tahun'      => (string) $tahun,
                    'debit'      => $debitVal,
                    'kredit'     => $kreditVal,
                ];
            }

            $labaBerjalanAccount = Account::where('kode_akun', $cfg['akun_laba_rugi_berjalan'])->first();
            if ($labaBerjalanAccount && abs($labaRugi) >= 0.01) {
                $idLaba = (int) ($labaBerjalanAccount->id . $tahun . '13');
                if ($labaRugi >= 0) {
                    $amountRows13[] = [
                        'id'         => $idLaba,
                        'account_id' => $labaBerjalanAccount->id,
                        'bulan'      => '13',
                        'tahun'      => (string) $tahun,
                        'debit'      => 0,
                        'kredit'     => abs($labaRugi),
                    ];
                } else {
                    $amountRows13[] = [
                        'id'         => $idLaba,
                        'account_id' => $labaBerjalanAccount->id,
                        'bulan'      => '13',
                        'tahun'      => (string) $tahun,
                        'debit'      => abs($labaRugi),
                        'kredit'     => 0,
                    ];
                }
            }

            $amountRows00 = [];
            foreach ($accounts as $a) {
                /*
                 * Akun pendapatan (4.x) dan beban (5.x) TIDAK boleh dibawa
                 * ke tahun berikutnya sebagai carry-over.they harus ditutup
                 * ke laba ditahan lewat AlokasiLabaController, kalau tidak
                 * neraca 2026 akan ikut memuat pendapatan 2025 dan neraca
                 * saldo tidak akan seimbang (debit aset vs kredit 4.x).
                 *
                 * Yang Carry hanya akun neraca (1.x aset, 2.x kewajiban,
                 * 3.x ekuitas).
                 */
                $first = substr($a->kode_akun, 0, 1);
                if ($first === '4' || $first === '5') {
                    continue;
                }

                $saldo = $saldoAkhir[$a->kode_akun] ?? 0;
                if (abs($saldo) < 0.01) continue;

                $id = (int) ($a->id . $tahunDepan . '00');

                if (! self::isKredit($a->jenis_mutasi)) {
                    $amountRows00[] = [
                        'id'         => $id,
                        'account_id' => $a->id,
                        'bulan'      => '00',
                        'tahun'      => (string) $tahunDepan,
                        'debit'      => $saldo,
                        'kredit'     => 0,
                    ];
                } else {
                    $amountRows00[] = [
                        'id'         => $id,
                        'account_id' => $a->id,
                        'bulan'      => '00',
                        'tahun'      => (string) $tahunDepan,
                        'debit'      => 0,
                        'kredit'     => $saldo,
                    ];
                }
            }

            if (!empty($amountRows13)) {
                DB::table('amount')->insert($amountRows13);
            }

            if (!empty($amountRows00)) {
                DB::table('amount')->insert($amountRows00);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'       => "Buku tahun {$tahun} berhasil ditutup.",
                    'tahun'         => $tahun,
                    'tahun_depan'   => $tahunDepan,
                    'laba_rugi'     => $labaRugi,
                    'amount_rows_13' => count($amountRows13),
                    'amount_rows_00' => count($amountRows00),
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
        $rows = DB::table('amount')
            ->where('bulan', '13')
            ->whereNotNull('tahun')
            ->select('tahun', 'created_at as closed_at')
            ->orderByDesc('tahun')
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
            'akun_laba_ditahan'        => 'required|string|exists:accounts,kode_akun',
            'akun_laba_rugi_berjalan'  => 'required|string|exists:accounts,kode_akun',
        ]);

        $cfg = [
            'akun_laba_ditahan'        => $request->akun_laba_ditahan,
            'akun_laba_rugi_berjalan'  => $request->akun_laba_rugi_berjalan,
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

    /**
     * Cek apakah jenis mutasi akun bersifat kredit.
     *
     * Kolom `jenis_mutasi` tidak konsisten kapitalisasinya di tabel
     * `accounts`: sebagian baris 'kredit', sebagian 'Kredit' (kapital K).
     * Perbandingan `=== 'kredit'` diam-dimmerespons FALSE untuk yang
     * kapital, sehingga akun kewajiban 2.1.02.02 (id 685, 'Kredit')
     * diperlakukan sebagai akun debit -- saldoProcessorоя terbalik
     * saat tutup buku.
     */
    private static function isKredit(?string $jenisMutasi): bool
    {
        return strtolower(trim((string) $jenisMutasi)) === 'kredit';
    }

    private function getClosingConfig(): array
    {
        $default = [
            'akun_laba_ditahan'        => '3.2.01.01',
            'akun_laba_rugi_berjalan'  => '3.2.02.01',
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
