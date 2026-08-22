<?php

namespace App\Services;

use App\Models\User;
use App\Models\Account;
use App\Models\AkunLevel1;
use App\Models\JenisLaporan;
use App\Models\SubLaporan;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PelaporanService
{
    public function hitungSaldoAccount($acc)
    {
        $d = $acc->amount ? $acc->amount->sum('debit') : 0;
        $k = $acc->amount ? $acc->amount->sum('kredit') : 0;
        return ($acc->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
    }

    public function susunFlatRows($akun1, $surplus, $surplusAccount = '3.2.02.01')
    {
        $rows = [];
        foreach ($akun1 as $l1) {
            $rows[] = ['type' => 'lev1', 'kode_akun' => $l1->kode_akun, 'nama_akun' => $l1->nama_akun];
            foreach ($l1->akunLevel2 as $l2) {
                $rows[] = ['type' => 'lev2', 'kode_akun' => $l2->kode_akun, 'nama_akun' => $l2->nama_akun];
                foreach ($l2->akunLevel3 as $l3) {
                    $total_l3 = 0;
                    $temp_lev4 = [];
                    foreach ($l3->accountParent as $acc) {
                        $saldo = ($acc->kode_akun === $surplusAccount) ? $surplus : $this->hitungSaldoAccount($acc);
                        $total_l3 += $saldo;
                        $temp_lev4[] = [
                            'type' => 'lev4',
                            'kode_akun' => $acc->kode_akun,
                            'nama_akun' => $acc->nama_akun,
                            'saldo' => $saldo,
                        ];
                    }
                    $rows[] = [
                        'type' => 'lev3',
                        'kode_akun' => $l3->kode_akun,
                        'nama_akun' => $l3->nama_akun,
                        'saldo' => $total_l3,
                    ];
                    $rows = array_merge($rows, $temp_lev4);
                }
            }
        }
        return $rows;
    }

    public function loadAkunTree($tahun, $bulan)
    {
        return AkunLevel1::where('lev1', '<=', '3')
            ->with([
                'akunLevel2.akunLevel3' => fn($q) => $q->orderBy('kode_akun', 'ASC'),
                'akunLevel2.akunLevel3.accountParent.amount' => function ($q) use ($tahun, $bulan) {
                    $q->where('tahun', $tahun)
                        ->where('bulan', '<=', $bulan);
                }
            ])
        ->orderBy('kode_akun', 'ASC')
        ->get();
    }

    public function hitungSurplus($tahun, $bulan)
    {
        $laba_rugi = Account::where('lev1', '>=', '4')
            ->with(['amount' => function ($q) use ($tahun, $bulan) {
                $q->where('tahun', $tahun)
                    ->where('bulan', '<=', $bulan);
            }])->get();

        $hitung = function($acc) {
            $d = $acc->amount ? $acc->amount->sum('debit') : 0;
            $k = $acc->amount ? $acc->amount->sum('kredit') : 0;
            return ($acc->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
        };

        $pendapatan = $laba_rugi->where('lev1', '4')->sum($hitung);
        $beban = $laba_rugi->where('lev1', '5')->sum($hitung);

        return $pendapatan - $beban;
    }

    public function susunAkunDenganTotal($akun1)
    {
        $akun1->each(function ($lev1) {
            $lev1->akunLevel2->each(function ($lev2) {
                $lev2->akunLevel3->each(function ($lev3) {
                    $lev3->total_saldo = $lev3->accountParent->sum(function ($acc) {
                        $d = $acc->amount ? $acc->amount->sum('debit') : 0;
                        $k = $acc->amount ? $acc->amount->sum('kredit') : 0;
                        return ($acc->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
                    });
                });
            });
        });

        $akun1->each(function ($lev1) {
            $lev1->total_saldo_lev1 = $lev1->akunLevel2->sum(function ($lev2) {
                return $lev2->akunLevel3->sum('total_saldo');
            });
        });
    }

    public function getEBudgetingData($tahun, array $list_bulan)
    {
        $b1 = $list_bulan[0];
        $b2 = $list_bulan[1];
        $b3 = $list_bulan[2];

        $accounts = Account::whereBetween('lev1', [4, 5])
            ->with(['amount', 'eb' => fn($q) => $q->where('tahun', $tahun)])
            ->get();

        return $accounts->map(function ($acc) use ($b1, $b2, $b3, $tahun) {
            
            // Fungsi internal untuk menghitung saldo kumulatif per bulan
            $hitung = function($target_bulan) use ($acc, $tahun) {
                // Pastikan menggunakan tahun yang benar (input atau default)
                $target_tahun = $acc->tahun ?? $tahun;
                $amounts = $acc->amount->where('tahun', $target_tahun);
                
                $bulanStr = str_pad($target_bulan, 2, '0', STR_PAD_LEFT);
                $debit    = $amounts->where('bulan', '<=', $bulanStr)->sum('debit');
                $kredit   = $amounts->where('bulan', '<=', $bulanStr)->sum('kredit');
                
                return ($acc->jenis_mutasi == 'kredit') ? ($kredit - $debit) : ($debit - $kredit);
            };

            $s_kom = $hitung($b1 - 1);
            $s_b1  = $hitung($b1);
            $s_b2  = $hitung($b2);
            $s_b3  = $hitung($b3);

            return [
                'nama'       => $acc->kode_akun . '. ' . $acc->nama_akun,
                'komulatif'  => $s_kom,
                'rencana1'   => $acc->eb->where('bulan', $b1)->first()->jumlah ?? 0,
                'realisasi1' => $s_b1,
                'rencana2'   => $acc->eb->where('bulan', $b2)->first()->jumlah ?? 0,
                'realisasi2' => $s_b2 - $s_b1,
                'rencana3'   => $acc->eb->where('bulan', $b3)->first()->jumlah ?? 0,
                'realisasi3' => $s_b3 - $s_b2 - $s_b1,
                'total'      => $s_kom + $s_b3
            ];
        });
    }

    public function getNeracaSaldoData($accounts, $surplus)
    {
        return $accounts->map(function ($acc) use ($surplus) {
            $d = $acc->amount ? $acc->amount->sum('debit') : 0;
            $k = $acc->amount ? $acc->amount->sum('kredit') : 0;

            $neracaDebit = 0; $neracaKredit = 0;
            if (in_array($acc->lev1, [1, 2, 3])) {
                if ($acc->jenis_mutasi == 'kredit') $neracaKredit = $k - $d;
                else $neracaDebit = $d - $k;
            }

            $labaRugiDebit = 0; $labaRugiKredit = 0;
            if ($acc->lev1 == 4) $labaRugiKredit = $k - $d;
            elseif ($acc->lev1 == 5) $labaRugiDebit = $d - $k;

            if ($acc->kode_akun === '3.2.02.01') {
                if ($surplus > 0) $labaRugiKredit = $surplus;
                else $labaRugiDebit = abs($surplus);
            }

            return [
                'kode_akun' => $acc->kode_akun,
                'nama_akun' => $acc->nama_akun,
                'saldo_debit' => $d,
                'saldo_kredit' => $k,
                'saldo_laba_rugi_debit' => $labaRugiDebit,
                'saldo_laba_rugi_kredit' => $labaRugiKredit,
                'saldo_neraca_debit' => $neracaDebit,
                'saldo_neraca_kredit' => $neracaKredit,
            ];
        })->values();
    }

    public function getLabaRugiReport($tahun, $bulan)
    {
        // 1. Ambil data per kategori
        $pendapatanItems = $this->getAccountData('4.1.%', $tahun, $bulan);
        $pendapatanNon   = $this->getAccountData('4.2.%', $tahun, $bulan);
        $bebanOps        = $this->getAccountData('5.1.%', $tahun, $bulan);
        $bebanNon        = $this->getAccountData('5.3.%', $tahun, $bulan);
        $bebanPjk        = $this->getAccountData('5.4.%', $tahun, $bulan);

        // 2. Hitung Total
        $allP = array_merge($pendapatanItems, $pendapatanNon);
        $allB = array_merge($bebanOps, $bebanNon, $bebanPjk);

        $totalPLalu = array_sum(array_column($allP, 'sd_bulan_lalu'));
        $totalPIni  = array_sum(array_column($allP, 'bulan_ini'));
        $totalBLalu = array_sum(array_column($allB, 'sd_bulan_lalu'));
        $totalBIni  = array_sum(array_column($allB, 'bulan_ini'));

        // 3. Susun Grup
        return [
            'groups' => [
                ['type' => 'main', 'label' => '4. PENDAPATAN', 'items' => []],
                ['type' => 'sub', 'label' => '4.1. Pendapatan Operasional', 'items' => $pendapatanItems],
                ['type' => 'sub', 'label' => '4.2. Pendapatan Non Usaha', 'items' => $pendapatanNon],
                ['type' => 'total', 'label' => 'Jumlah Pendapatan', 'items' => [[
                    'nama_akun' => 'Jumlah Pendapatan', 'sd_bulan_lalu' => $totalPLalu, 
                    'bulan_ini' => $totalPIni, 'sd_bulan_ini' => $totalPLalu + $totalPIni, 'isTotal' => true
                ]]],
                ['type' => 'main', 'label' => '5. BEBAN', 'items' => []],
                ['type' => 'sub', 'label' => '5.1. Beban Operasional', 'items' => $bebanOps],
                ['type' => 'sub', 'label' => '5.3. Beban Non Usaha', 'items' => $bebanNon],
                ['type' => 'sub', 'label' => '5.4. Beban Pajak', 'items' => $bebanPjk],
                ['type' => 'total', 'label' => 'Jumlah Beban', 'items' => [[
                    'nama_akun' => 'Jumlah Beban', 'sd_bulan_lalu' => $totalBLalu, 
                    'bulan_ini' => $totalBIni, 'sd_bulan_ini' => $totalBLalu + $totalBIni, 'isTotal' => true
                ]]],
                ['type' => 'grand', 'label' => 'LABA / (RUGI)', 'items' => [[
                    'nama_akun' => ($totalPIni - $totalBIni) >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH',
                    'sd_bulan_lalu' => $totalPLalu - $totalBLalu,
                    'bulan_ini' => $totalPIni - $totalBIni,
                    'sd_bulan_ini' => ($totalPLalu + $totalPIni) - ($totalBLalu + $totalBIni),
                    'isTotal' => true
                ]]]
            ],
            'laba_rugi' => $totalPIni - $totalBIni
        ];
    }
    // Di dalam PelaporanService.php

public function getAccountData($whereAkun, $tahun, $bulan)
{
    $accs = Account::with(['amount' => function ($q) use ($tahun, $bulan) {
        $q->where('tahun', $tahun)->where('bulan', '<=', $bulan);
    }])->where('kode_akun', 'like', $whereAkun)->orderBy('kode_akun', 'ASC')->get();

    return $accs->map(function ($a) use ($bulan) {
        $sdLalu = 0; $bulanIni = 0;
        
        if ($a->amount) {
            foreach ($a->amount as $row) {
                $d = (float) $row->debit;
                $k = (float) $row->kredit;
                
                // Pastikan logika mutasi benar
                $val = ($a->jenis_mutasi == 'kredit') ? ($k - $d) : ($d - $k);
                
                $rb = (int) $row->bulan;
                if ($rb < $bulan) {
                    $sdLalu += $val;
                } elseif ($rb === $bulan) {
                    $bulanIni += $val;
                }
            }
        }
        
        return [
            'nama_akun'     => $a->kode_akun . '. ' . $a->nama_akun,
            'sd_bulan_lalu' => $sdLalu,
            'bulan_ini'     => $bulanIni,
            'sd_bulan_ini'  => $sdLalu + $bulanIni,
        ];
    })->toArray();
}



}
