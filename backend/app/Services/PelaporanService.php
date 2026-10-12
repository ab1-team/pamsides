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
                /*
                 * `accountParent` mengembalikan SEMUA baris akun dengan
                 * parent_id ini, termasuk baris lama yang duplikat
                 * (mis. 1.1.01.01 punya id 1 dan 594). Baris lama tidak
                 * pernah disentuh amount sehingga saldonya 0, tapi kalau
                 * ikut diiterasi kode yang sama tampil dua kali dan
                 * CALK jadi kacau.
                 *
                 * Per level3, ambil satu baris per kode_akun: prioritas
                 * yang punya saldo, kalau sama-sama nol ambil id terbesar
                 * (blok seeder terbaru). Struktur anak tetap lengkap --
                 * akun tanpa saldo pun tetap muncul.
                 */
                $akunUnik = [];
                foreach ($l3->accountParent as $acc) {
                    $kode = (string) $acc->kode_akun;
                    $saldo = ($kode === $surplusAccount) ? (float) $surplus : (float) $this->hitungSaldoAccount($acc);

                    if (! isset($akunUnik[$kode])) {
                        $akunUnik[$kode] = ['acc' => $acc, 'saldo' => $saldo];
                        continue;
                    }

                    $lama = $akunUnik[$kode];
                    $lamaNilai = abs($lama['saldo']);
                    $baruNilai = abs($saldo);
                    if ($baruNilai > $lamaNilai || ($baruNilai === $lamaNilai && $acc->id > $lama['acc']->id)) {
                        $akunUnik[$kode] = ['acc' => $acc, 'saldo' => $saldo];
                    }
                }

                $temp_lev4 = [];
                $total_l3 = 0;
                foreach ($akunUnik as $kode => $item) {
                    $total_l3 += $item['saldo'];
                    $temp_lev4[] = [
                        'type' => 'lev4',
                        'kode_akun' => $kode,
                        'nama_akun' => $item['acc']->nama_akun,
                        'saldo' => $item['saldo'],
                    ];
                }

                // Urutkan sesuai kode akun agar tampilan CALK rapi.
                usort($temp_lev4, fn ($a, $b) => strcmp((string) $a['kode_akun'], (string) $b['kode_akun']));

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

    /*
 * Muat pohon akun (level 1-3) beserta mutasinya untuk laporan Neraca & CALK.
 *
 * Kumulatif: saldo per 31 Agustus = mutasi tahun-tahun sebelumnya +
 * mutasi Januari..Agustus tahun berjalan. Versi lama hanya mengambil
 * `tahun = $tahun`, sehingga akun Kas Tunai hanya menampilkan mutasi 2026
 * (972 juta) dan bukan saldo sebenarnya (1.651.904.590).
 *
 * Baris bulan '00' (carry-over) dan '13' (saldo akhir hasil TutupBuku)
 * dikecualikan karena isinya bukan mutasi -- kalau ikut dijumlahkan, saldo
 * terhitung dua kali.
 */
public function loadAkunTree($tahun, $bulan)
{
    /*
     * Struktur pohon AkunLevel1 > AkunLevel2 > AkunLevel3 > accounts(level4)
     * harus LENGAP supaya CALK/Neraca menampilkan urutan anak akun, bukan
     * hanya akun yang kebetulan bersaldo.
     *
     * Tabel `accounts` menyisakan blok lama (id 1-92 & 186-208) di samping
     * blok seeder terbaru (id 594-689) untuk kode_akun yang sama, dan
     * keduanya berbagi parent_id. Kalau difilter "yang punya amount",
     * struktur pohon ikut hilang -- jadi jangan difilter di sini.
     *
     * Duplikasi ditangani di susunFlatRows() lewat dedupe per kode_akun.
     */
    return AkunLevel1::where('lev1', '<=', '3')
        ->with([
            'akunLevel2.akunLevel3' => fn($q) => $q->orderBy('kode_akun', 'ASC'),
            'akunLevel2.akunLevel3.accountParent.amount' => function ($q) use ($tahun, $bulan) {
                $q->where(function ($q) use ($tahun, $bulan) {
                    $q->where(function ($q) use ($tahun) {
                        $q->where('tahun', '<', $tahun)
                            ->where('bulan', '>=', '01')
                            ->where('bulan', '<=', '12');
                    })->orWhere(function ($q) use ($tahun, $bulan) {
                        $q->where('tahun', $tahun)
                            ->where('bulan', '>=', '01')
                            ->where('bulan', '<=', $bulan);
                    });
                });
            },
        ])
        ->orderBy('kode_akun', 'ASC')
        ->get();
}

    public function hitungSurplus($tahun, $bulan)
    {
        $laba_rugi = Account::where('lev1', '>=', '4')
            ->with(['amount' => function ($q) use ($tahun, $bulan) {
                // FIX HIGH-2: Laba/rugi tahun berjalan HANYA untuk tahun berjalan
                // (Januari s/d bulan berjalan). Jangan ikut sertakan tahun-tahun
                // sebelumnya, supaya tidak terjadi pencemaran data lintas tahun
                // pada laporan laba rugi tahun berjalan.
                $q->where('tahun', $tahun)
                    ->where('bulan', '<=', $bulan);
            }])->get();

        // Tanpa dedupe, kode 4.x/5.y yang dobel ikut terhitung dua kali --
        // surplus jadi lebih besar dari laba rugi di laporan.
        $laba_rugi = $this->dedupePerKode($laba_rugi);

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
                    /*
                     * accountParent mengembalikan SEMUA baris akun dengan
                     * parent_id ini, termasuk baris lama yang duplikat
                     * (mis. 3.2.02.01 punya id 47 dan 640). Tanpa dedupe,
                     * level3 terisi dua kali -- dan karena neraca()
                     * menyubstitusi 3.2.02.01 dengan $surplus, labanya
                     * terhitung 2x (966.530.100 -> 1.933.060.200).
                     */
                    $lev3->total_saldo = $this->dedupePerKode($lev3->accountParent)->sum(function ($acc) {
                        $d = $acc->amount ? $acc->amount->sum('debit') : 0;
                        $k = $acc->amount ? $acc->amount->sum('kredit') : 0;

                        return self::isKredit($acc->jenis_mutasi) ? ($k - $d) : ($d - $k);
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
        $accounts = $this->dedupePerKode($accounts);

        return $accounts->map(function ($acc) use ($surplus) {
            $d = $acc->amount ? $acc->amount->sum('debit') : 0;
            $k = $acc->amount ? $acc->amount->sum('kredit') : 0;

            $neracaDebit = 0; $neracaKredit = 0;
            if (in_array($acc->lev1, [1, 2, 3])) {
                if ($acc->jenis_mutasi == 'kredit') {
                    $saldo = $k - $d;
                    $neracaKredit = $saldo > 0 ? $saldo : 0;
                    $neracaDebit  = $saldo < 0 ? abs($saldo) : 0;
                } else {
                    $saldo = $d - $k;
                    $neracaDebit  = $saldo > 0 ? $saldo : 0;
                    $neracaKredit = $saldo < 0 ? abs($saldo) : 0;
                }
            }

            $labaRugiDebit = 0; $labaRugiKredit = 0;
            if ($acc->lev1 == 4) {
                $lr = $k - $d;
                $labaRugiKredit = $lr > 0 ? $lr : 0;
                $labaRugiDebit  = $lr < 0 ? abs($lr) : 0;
            } elseif ($acc->lev1 == 5) {
                $lr = $d - $k;
                $labaRugiDebit  = $lr > 0 ? $lr : 0;
                $labaRugiKredit = $lr < 0 ? abs($lr) : 0;
            }

            if ($acc->kode_akun === '3.2.02.01') {
                if ($surplus > 0) $labaRugiKredit = $surplus;
                else $labaRugiDebit = abs($surplus);
            }

            return [
                'id' => $acc->id,
                'kode_akun' => $acc->kode_akun,
                'nama_akun' => $acc->nama_akun,
                'saldo_debit' => $neracaDebit,
                'saldo_kredit' => $neracaKredit,
                'saldo_laba_rugi_debit' => $labaRugiDebit,
                'saldo_laba_rugi_kredit' => $labaRugiKredit,
                'saldo_neraca_debit' => $neracaDebit,
                'saldo_neraca_kredit' => $neracaKredit,
            ];
        })->values();
    }

/**
     * Buang baris `accounts` yang kodenya dobel, sisakan satu per kode_akun.
     *
     * Tabel `accounts` pernah di-`truncate()` lalu diisi ulang seeder dengan id
     * baru (594-689), tapi blok lama (id 1-92 & 186-208) masih tertinggal --
     * jadi setiap kode punya 2-3 baris. Baris `amount` hanya nunjuk ke blok
     * baru, sehingga baris lama semua nol: tanpa dedupe, laporan neraca saldo
     * dan laba rugi menampilkan kode yang sama berulang (yang dobel cuma baris 0).
     *
     *_prioritas_: baris yang punya saldo menang; kalau sama-sama nol, ambil id
     * terbesar (blok seeder terbaru). Kalau ternyata satu kode punya lebih dari
     * satu baris bersaldo, sisa saldo akan hilang dari laporan -- kondisi itu
     * dicek manual lewat SQL sebelum migrate, bukan diasumsikan aman.
     */
    private function dedupePerKode($accounts)
    {
        $grouped = [];

        foreach ($accounts as $acc) {
            $kode = (string) $acc->kode_akun;
            $nilai = $acc->amount
                ? (float) $acc->amount->sum('debit') + (float) $acc->amount->sum('kredit')
                : 0.0;

            // Prioritas: yang punya saldo. Kalau sama-sama nol, ambil id terbesar
            // (blok seeder terbaru).
            $existing = $grouped[$kode] ?? null;
            if ($existing === null) {
                $grouped[$kode] = ['acc' => $acc, 'nilai' => $nilai];

                continue;
            }

            $existsNilai = $grouped[$kode]['nilai'];
            if ($nilai > $existsNilai || ($nilai === $existsNilai && $acc->id > $grouped[$kode]['acc']->id)) {
                $grouped[$kode] = ['acc' => $acc, 'nilai' => $nilai];
            }
        }

        return collect(array_values(array_column($grouped, 'acc')));
    }

/**
 * Laba bersih tahun yang SUDAH ditutup (baris `amount` bulan '13').
 *
 * Tanggal tutup buku selalu 31 Desember, jadi hanya tahun yang lebih kecil
 * dari `$tahun` laporan yang perlu dijumlahkan. Nilai ini dihitung dari
 * baris penutup, bukan dari jurnal, sehingga tetap benar meski ada koreksi
 * manual saat tutup buku.
 */
public function labaTahunTertutup($tahun): float
{
    $rows = DB::table('amount as a')
        ->join('accounts as ac', 'a.account_id', '=', 'ac.id')
        ->where('a.bulan', '13')
        ->where('a.tahun', '<', (string) $tahun)
        ->get(['ac.kode_akun', 'a.debit', 'a.kredit']);

    $laba = 0.0;
    foreach ($rows as $r) {
        $d = (float) $r->debit;
        $k = (float) $r->kredit;
        if (str_starts_with((string) $r->kode_akun, '4.')) {
            $laba += $k - $d;
        } elseif (str_starts_with((string) $r->kode_akun, '5.')) {
            $laba -= $d - $k;
        }
    }

    return $laba;
}

/**
 * Ambil satu baris akun per kode_akun dalam satu level3.
     *
     * Tabel `accounts` menyisakan blok lama berdampingan dengan blok seeder
     * terbaru untuk kode yang sama, dan keduanya berbagi parent_id --
     * jadi `accountParent` mengembalikan semuanya. Prioritas: baris yang
     * punya saldo; kalau sama-sama nol ambil id terbesar.
     */
    public function akunUnikLevel3($accounts)
    {
        return $this->dedupePerKode($accounts);
    }

    /**
     * Cek apakah jenis mutasi akun bersifat kredit.
     *
     * Kolom `jenis_mutasi` kapitalisasinya tidak seragam di tabel `accounts`
     * ('kredit' dan 'Kredit' sama-sama ada). Perbandingan `== 'kredit'`
     * kebetulan bekerja karena longgar, tapi `===` tidak -- dipakai
     * strtolower supaya aman untuk keduanya.
     */
    public static function isKredit($jenisMutasi): bool
    {
        return strtolower(trim((string) $jenisMutasi)) === 'kredit';
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

    $accs = $this->dedupePerKode($accs);

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
