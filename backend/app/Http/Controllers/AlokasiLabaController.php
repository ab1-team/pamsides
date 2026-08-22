<?php

namespace App\Http\Controllers;

use App\Models\Account;
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

        $isClosed = DB::table('amount')
            ->where('tahun', $tahun)
            ->where('bulan', '13')
            ->exists();

        if (! $isClosed) {
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

        $accountsDibagihkan = Account::where('kode_akun', 'like', '2.1.01.%')
            ->whereNull('tgl_nonaktif')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun', 'jenis_mutasi']);

        $labaDibagihkan = [];
        foreach ($accountsDibagihkan as $acc) {
            $labaDibagihkan[] = [
                'kode_akun'   => $acc->kode_akun,
                'nama_akun'   => $acc->nama_akun,
                'jenis_mutasi' => $acc->jenis_mutasi,
                'persen'      => 0,
                'nominal'     => 0,
            ];
        }

        $labaDitahan = [
            [
                'kode_akun'   => null,
                'nama_akun'   => 'Pemupukan Modal',
                'jenis_mutasi' => 'kredit',
                'persen'      => 0,
                'nominal'     => 0,
            ],
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'           => $tahun,
                'total_laba'      => $totalLaba,
                'laba_dibagihkan' => $labaDibagihkan,
                'laba_ditahan'    => $labaDitahan,
                'closed'          => true,
            ],
        ]);
    }

    public function save(Request $request)
    {
        $request->validate([
            'tahun'      => 'required|integer|min:2000',
            'items'      => 'required|array|min:1',
            'items.*.kode_akun'   => 'nullable|string|exists:accounts,kode_akun',
            'items.*.nominal'     => 'required|numeric',
        ]);

        $tahun = (int) $request->tahun;

        $isClosed = DB::table('amount')
            ->where('tahun', $tahun)
            ->where('bulan', '13')
            ->exists();

        if (! $isClosed) {
            return response()->json([
                'success' => false,
                'message' => "Buku tahun {$tahun} belum ditutup.",
            ], 422);
        }

        $totalLaba = $this->getLabaRugi($tahun);

        $totalNominal = 0;
        $kodeList = [];
        foreach ($request->items as $it) {
            $totalNominal += (float) $it['nominal'];
            if (!empty($it['kode_akun'])) {
                $kodeList[$it['kode_akun']] = true;
            }
        }

        if (abs($totalLaba - $totalNominal) >= 0.01) {
            return response()->json([
                'success' => false,
                'message' => "Total nominal alokasi (Rp " . number_format($totalNominal, 0, ',', '.') .
                    ") tidak sama dengan total laba (Rp " . number_format($totalLaba, 0, ',', '.') . ").",
            ], 422);
        }

        $akunMap = Account::whereIn('kode_akun', array_keys($kodeList))
            ->get()
            ->keyBy('kode_akun');

        $akunLabaDitahan = Account::where('kode_akun', '3.2.01.01')->first();
        if (! $akunLabaDitahan) {
            return response()->json([
                'success' => false,
                'message' => 'Akun 3.2.01.01 (Laba Ditahan) tidak ditemukan.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $userId = $request->user()->id ?? 1;
            $now = now();
            $tglAlokasi = $tahun . '-12-31';
            $group = (int) (microtime(true) * 1000);

            $existingTx = DB::table('transactions')
                ->where('reverence_type', 'alokasi_laba')
                ->where('reverence_id', $tahun)
                ->whereNull('deleted_at')
                ->exists();

            if ($existingTx) {
                return response()->json([
                    'success' => false,
                    'message' => "Alokasi laba untuk tahun {$tahun} sudah pernah disimpan.",
                ], 422);
            }

            $trxRows = [];
            $urutan = 1;

            foreach ($request->items as $it) {
                $nominal = round((float) $it['nominal']);
                if (abs($nominal) < 0.01) $nominal = 0;

                if (empty($it['kode_akun'])) {
                    continue;
                }

                if ($it['kode_akun'] === '3.2.01.01') {
                    continue;
                }

                $akun = $akunMap[$it['kode_akun']] ?? null;
                if (! $akun) continue;

                $trxRows[] = [
                    'tgl_transaksi'        => $tglAlokasi,
                    'account_debet'        => '3.2.01.01',
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
            }

            if (!empty($trxRows)) {
                DB::table('transactions')->insert($trxRows);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => [
                    'message'           => "Alokasi laba tahun {$tahun} berhasil disimpan.",
                    'tahun'             => $tahun,
                    'total_dialokasikan' => $totalNominal,
                    'total_laba'        => $totalLaba,
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

    public function accountsForAllocation()
    {
        $accounts = Account::where(function ($q) {
            $q->where('kode_akun', 'like', '1.1.01.%')
              ->orWhere('kode_akun', 'like', '1.1.02.%')
              ->orWhere('kode_akun', 'like', '1.1.03.%')
              ->orWhere('kode_akun', 'like', '1.1.99.%')
              ->orWhere('kode_akun', 'like', '2.1.01.%')
              ->orWhere('kode_akun', 'like', '2.1.02.%')
              ->orWhere('kode_akun', 'like', '2.1.03.%')
              ->orWhere('kode_akun', 'like', '2.1.04.%')
              ->orWhere('kode_akun', 'like', '2.1.99.%')
              ->orWhere('kode_akun', 'like', '2.2.01.%')
              ->orWhere('kode_akun', 'like', '2.2.02.%')
              ->orWhere('kode_akun', 'like', '2.2.03.%')
              ->orWhere('kode_akun', 'like', '2.2.04.%')
              ->orWhere('kode_akun', 'like', '2.2.05.%')
              ->orWhere('kode_akun', 'like', '2.2.99.%')
              ->orWhere('kode_akun', 'like', '3.1.01.%')
              ->orWhere('kode_akun', 'like', '3.1.02.%')
              ->orWhere('kode_akun', 'like', '3.1.03.%')
              ->orWhere('kode_akun', 'like', '3.1.99.%')
              ->orWhere('kode_akun', 'like', '3.2.01.%')
              ->orWhere('kode_akun', 'like', '3.2.99.%');
        })
            ->whereNull('tgl_nonaktif')
            ->orderBy('kode_akun')
            ->get(['id', 'kode_akun', 'nama_akun', 'jenis_mutasi', 'lev1', 'lev2', 'lev3', 'lev4']);

        return response()->json([
            'success' => true,
            'data'    => $accounts,
        ]);
    }

    public function check($year)
    {
        $year = (int) $year;

        $closed = DB::table('amount')
            ->where('tahun', $year)
            ->where('bulan', '13')
            ->exists();

        $akunLabaRugi = $this->getClosingConfig()['akun_laba_rugi_berjalan'];
        $alokasiSaved = DB::table('amount')
            ->where('tahun', $year)
            ->where('bulan', '13')
            ->where('account_id', function ($q) use ($akunLabaRugi) {
                $q->select('id')->from('accounts')->where('kode_akun', $akunLabaRugi);
            })
            ->where('kredit', '>', 0)
            ->exists();

        $totalLaba = $closed ? $this->getLabaRugi($year) : 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'tahun'        => $year,
                'tutup_buku'   => $closed,
                'alokasi_saved' => $alokasiSaved,
                'total_laba'   => $totalLaba,
            ],
        ]);
    }

    private function getLabaRugi(int $tahun): float
    {
        $pendapatan = DB::table('amount as a')
            ->join('accounts as ac', 'a.account_id', '=', 'ac.id')
            ->where('a.tahun', $tahun)
            ->where('a.bulan', '13')
            ->where('ac.kode_akun', 'like', '4.%')
            ->whereNull('ac.tgl_nonaktif')
            ->selectRaw('COALESCE(SUM(a.kredit - a.debit), 0) as total')
            ->value('total');

        $biaya = DB::table('amount as a')
            ->join('accounts as ac', 'a.account_id', '=', 'ac.id')
            ->where('a.tahun', $tahun)
            ->where('a.bulan', '13')
            ->where('ac.kode_akun', 'like', '5.%')
            ->whereNull('ac.tgl_nonaktif')
            ->selectRaw('COALESCE(SUM(a.debit - a.kredit), 0) as total')
            ->value('total');

        return (float) $pendapatan - (float) $biaya;
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
                ['kode_akun' => '2.2.01.01', 'nama_akun' => 'Utang Bank 1',                       'kategori' => 'Utang Bank (45%)',            'persen' => 45],
                ['kode_akun' => '2.2.01.02', 'nama_akun' => 'Utang Bank 2',                       'kategori' => 'Utang Bank (40%)',            'persen' => 40],
                ['kode_akun' => '2.2.02.01', 'nama_akun' => 'Utang Jangka Panjang Lainnya',        'kategori' => 'Utang Jangka Panjang (5%)',   'persen' => 5],
                ['kode_akun' => '2.2.99.01', 'nama_akun' => 'Cadangan Dividen',                    'kategori' => 'Cadangan Dividen (5%)',       'persen' => 5],
            ],
            'ditahan' => [
                ['kode_akun' => '3.1.01.01', 'nama_akun' => 'Modal Pemdes',                         'kategori' => 'Pemupukan Modal (60%)',       'persen' => 60],
                ['kode_akun' => '3.1.02.01', 'nama_akun' => 'Modal Lain-lain',                      'kategori' => 'Cadangan Umum (40%)',         'persen' => 40],
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
