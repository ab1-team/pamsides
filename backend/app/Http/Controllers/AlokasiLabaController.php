<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AlokasiLabaController extends Controller
{
    public function calculate(Request $request)
    {
        $request->validate([
            'tahun'      => 'required|integer|min:2000',
            'total_saldo' => 'nullable|numeric',
        ]);

        $tahun = (int) $request->tahun;

        $closing = DB::table('transactions')
            ->where('reverence_type', 'tutup_buku')
            ->where('reverence_id', $tahun)
            ->whereNull('deleted_at')
            ->exists();

        if (! $closing) {
            return response()->json([
                'success' => false,
                'message' => "Buku tahun {$tahun} belum ditutup. Tutup buku terlebih dahulu.",
            ], 422);
        }

        if ($request->has('total_saldo') && $request->total_saldo !== null) {
            $totalLaba = (float) $request->total_saldo;
        } else {
            $totalLaba = $this->getLabaRugi($tahun);
        }

        $cfg = $this->getAlokasiConfig();

        $labaDibagikan = [];
        $totalDibagikan = 0;
        foreach ($cfg['dibagikan'] as $item) {
            $nominal = round($totalLaba * ((float) $item['persen'] / 100));
            $labaDibagikan[] = [
                'kode_akun'  => $item['kode_akun'],
                'nama_akun'  => $item['nama_akun'],
                'kategori'   => $item['kategori'],
                'persen'     => (float) $item['persen'],
                'nominal'    => $nominal,
            ];
            $totalDibagikan += $nominal;
        }

        $sisa = round($totalLaba - $totalDibagikan);

        $labaDitahan = [];
        foreach ($cfg['ditahan'] as $item) {
            $nominal = round($sisa * ((float) $item['persen'] / 100));
            $labaDitahan[] = [
                'kode_akun'  => $item['kode_akun'],
                'nama_akun'  => $item['nama_akun'],
                'kategori'   => $item['kategori'],
                'persen'     => (float) $item['persen'],
                'nominal'    => $nominal,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'             => $tahun,
                'total_laba'        => $totalLaba,
                'laba_dibagikan'    => $labaDibagikan,
                'laba_ditahan'      => $labaDitahan,
                'total_dibagikan'   => $totalDibagikan,
                'sisa_ditahan'      => $sisa,
                'config'            => $cfg,
                'closed'            => true,
            ],
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'tahun'      => 'required|integer|min:2000',
            'items'      => 'required|array|min:1',
            'items.*.kode_akun'   => 'required|string|exists:accounts,kode_akun',
            'items.*.nominal'     => 'required|numeric',
        ]);

        $tahun = (int) $request->tahun;

        $closing = DB::table('transactions')
            ->where('reverence_type', 'tutup_buku')
            ->where('reverence_id', $tahun)
            ->whereNull('deleted_at')
            ->exists();

        if (! $closing) {
            return response()->json([
                'success' => false,
                'message' => "Buku tahun {$tahun} belum ditutup.",
            ], 422);
        }

        $existing = DB::table('transactions')
            ->where('reverence_type', 'alokasi_laba')
            ->where('reverence_id', $tahun)
            ->whereNull('deleted_at')
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Alokasi laba untuk tahun {$tahun} sudah pernah disimpan.",
            ], 422);
        }

        $akunLabaDitahan = $this->getClosingConfig()['akun_laba_ditahan'];

        DB::beginTransaction();
        try {
            $userId = $request->user()->id ?? null;
            $tglAlokasi = $tahun . '-12-31';
            $group = (int) (microtime(true) * 1000);
            $now = now();

            $totalLaba = $this->getLabaRugi($tahun);
            $totalNominal = 0;
            foreach ($request->items as $it) {
                $totalNominal += (float) $it['nominal'];
            }

            $kodeList = [];
            foreach ($request->items as $it) {
                if (!empty($it['kode_akun'])) {
                    $kodeList[$it['kode_akun']] = true;
                }
            }
            $akunMap = Account::whereIn('kode_akun', array_keys($kodeList))
                ->get()
                ->keyBy('kode_akun');

            $rows = [];
            $rows[] = [
                'tgl_transaksi'        => $tglAlokasi,
                'account_debet'        => $akunLabaDitahan,
                'account_kredit'       => $akunLabaDitahan,
                'transaction_group'    => $group,
                'reverence_type'       => 'alokasi_laba',
                'reverence_id'         => $tahun,
                'keterangan_transaksi' => "Alokasi Laba Tahun {$tahun} - Pembukaan saldo laba ditahan untuk distribusi",
                'saldo'                => $totalLaba,
                'urutan'               => 0,
                'id_user'              => $userId,
                'created_at'           => $now,
                'updated_at'           => $now,
            ];

            $urutan = 1;
            foreach ($request->items as $it) {
                $nominal = round((float) $it['nominal']);
                if ($nominal <= 0) continue;

                $akun = $akunMap[$it['kode_akun']] ?? null;
                if (! $akun) continue;

                if ($akun->jenis_mutasi === 'kredit') {
                    $rows[] = [
                        'tgl_transaksi'        => $tglAlokasi,
                        'account_debet'        => $akunLabaDitahan,
                        'account_kredit'       => $akun->kode_akun,
                        'transaction_group'    => $group,
                        'reverence_type'       => 'alokasi_laba',
                        'reverence_id'         => $tahun,
                        'keterangan_transaksi' => "Alokasi Laba {$tahun} - {$akun->nama_akun}",
                        'saldo'                => $nominal,
                        'urutan'               => $urutan++,
                        'id_user'              => $userId,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                } else {
                    $rows[] = [
                        'tgl_transaksi'        => $tglAlokasi,
                        'account_debet'        => $akun->kode_akun,
                        'account_kredit'       => $akunLabaDitahan,
                        'transaction_group'    => $group,
                        'reverence_type'       => 'alokasi_laba',
                        'reverence_id'         => $tahun,
                        'keterangan_transaksi' => "Alokasi Laba {$tahun} - {$akun->nama_akun}",
                        'saldo'                => $nominal,
                        'urutan'               => $urutan++,
                        'id_user'              => $userId,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('transactions')->insert($rows);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'         => "Alokasi laba tahun {$tahun} berhasil disimpan.",
                    'tahun'           => $tahun,
                    'total_dialokasikan' => $totalNominal,
                    'group'           => $group,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan alokasi laba: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getConfig()
    {
        return response()->json([
            'success' => true,
            'data'    => $this->getAlokasiConfig(),
        ]);
    }

    public function saveConfig(Request $request)
    {
        $request->validate([
            'dibagikan'         => 'required|array|min:1',
            'dibagikan.*.kode_akun'  => 'required|string|exists:accounts,kode_akun',
            'dibagikan.*.nama_akun'  => 'required|string',
            'dibagikan.*.kategori'   => 'required|string',
            'dibagikan.*.persen'     => 'required|numeric|min:0|max:100',
            'ditahan'           => 'required|array|min:1',
            'ditahan.*.kode_akun'    => 'required|string|exists:accounts,kode_akun',
            'ditahan.*.nama_akun'    => 'required|string',
            'ditahan.*.kategori'     => 'required|string',
            'ditahan.*.persen'       => 'required|numeric|min:0|max:100',
        ]);

        $totalDibagikan = 0;
        foreach ($request->dibagikan as $item) {
            $totalDibagikan += (float) $item['persen'];
        }

        $totalDitahan = 0;
        foreach ($request->ditahan as $item) {
            $totalDitahan += (float) $item['persen'];
        }

        if (round($totalDibagikan + $totalDitahan, 2) != 100) {
            return response()->json([
                'success' => false,
                'message' => "Total persen harus 100% (saat ini: " . ($totalDibagikan + $totalDitahan) . "%).",
            ], 422);
        }

        $cfg = [
            'dibagikan' => $request->dibagikan,
            'ditahan'   => $request->ditahan,
        ];

        DB::table('settings')->updateOrInsert(
            ['key' => 'alokasi_laba_config'],
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

    public function check($year)
    {
        $year = (int) $year;

        $exists = DB::table('transactions')
            ->where('reverence_type', 'alokasi_laba')
            ->where('reverence_id', $year)
            ->whereNull('deleted_at')
            ->exists();

        $closed = DB::table('transactions')
            ->where('reverence_type', 'tutup_buku')
            ->where('reverence_id', $year)
            ->whereNull('deleted_at')
            ->exists();

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'        => $year,
                'tutup_buku'   => $closed,
                'alokasi_saved' => $exists,
            ],
        ]);
    }

    private function getLabaRugi(int $tahun): float
    {
        $akunLR = $this->getClosingConfig()['akun_laba_rugi_berjalan'];
        $start = "{$tahun}-01-01";
        $end   = "{$tahun}-12-31";

        $row = DB::table('transactions')
            ->selectRaw('COALESCE(SUM(CASE WHEN account_debet = ? THEN saldo ELSE 0 END), 0) as debit', [$akunLR])
            ->selectRaw('COALESCE(SUM(CASE WHEN account_kredit = ? THEN saldo ELSE 0 END), 0) as kredit', [$akunLR])
            ->whereNull('deleted_at')
            ->whereBetween('tgl_transaksi', [$start, $end])
            ->where(function ($q) use ($akunLR) {
                $q->where('account_debet', $akunLR)
                    ->orWhere('account_kredit', $akunLR);
            })
            ->first();

        return (float)($row->kredit ?? 0) - (float)($row->debit ?? 0);
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

    private function getAlokasiConfig(): array
    {
        $default = [
            'dibagikan' => [
                ['kode_akun' => '2.1.01.01', 'nama_akun' => 'Utang Dividen Pemdes',                'kategori' => 'Utang Dividen Pemdes (45%)',      'persen' => 45],
                ['kode_akun' => '2.1.01.02', 'nama_akun' => 'Utang Dividen Masy Penyerta Modal',  'kategori' => 'Utang Dividen Masyarakat (40%)', 'persen' => 40],
                ['kode_akun' => '2.1.01.03', 'nama_akun' => 'Bantuan Sosial',                       'kategori' => 'Bantuan Sosial (5%)',            'persen' => 5],
                ['kode_akun' => '2.1.01.04', 'nama_akun' => 'Utang Bonus',                          'kategori' => 'Bonus Karyawan (5%)',            'persen' => 5],
            ],
            'ditahan' => [
                ['kode_akun' => '3.1.01.01', 'nama_akun' => 'Modal Pemdes',                         'kategori' => 'Pemupukan Modal (3%)',           'persen' => 60],
                ['kode_akun' => '3.1.02.01', 'nama_akun' => 'Modal Lain-lain',                      'kategori' => 'Cadangan Umum (2%)',             'persen' => 40],
            ],
        ];

        $row = DB::table('settings')->where('key', 'alokasi_laba_config')->first();
        if ($row && $row->value) {
            $decoded = json_decode($row->value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $default;
    }
}

