<?php

namespace App\Services;

use App\Models\Inventaris as ModelsInventaris;

class InventoryService
{
public static function hitungBulan($start, $end)
{
    $d1 = new \DateTime($start);
    $d2 = new \DateTime($end);
    $diff = $d1->diff($d2);
    return ($diff->y * 12) + $diff->m;
}

public static function hitungItemSatuan($inv, $tgl_kondisi)
{
    $th_lalu = date('Y', strtotime($tgl_kondisi)) - 1;
    $harga_perolehan = $inv->harsat * $inv->unit;

    if ($inv->kategori == '1') {
            return [
            'penyusutan' => 0,
            'akum_susut' => 0,
            'nilai_buku' => ($inv->status == 'Dijual' || $inv->status == 'Hapus') ? 0 : $harga_perolehan
        ];
    }

    $satuan_susut = $inv->harsat <= 0 ? 0 : round(($inv->harsat / $inv->umur_ekonomis) * $inv->unit, 2);
    $pakai_lalu = self::hitungBulan($inv->tgl_beli, $th_lalu . '-12-31');
    $tgl_ref = ($inv->status != 'Baik' && $tgl_kondisi >= $inv->tgl_validasi) ? $inv->tgl_validasi : $tgl_kondisi;
    $umur = self::hitungBulan($inv->tgl_beli, $tgl_ref);

    if (in_array($inv->status, ['Hilang', 'Dijual', 'Hapus', 'Dihapus']) && $tgl_kondisi >= $inv->tgl_validasi) {
        return ['penyusutan' => 0, 'akum_susut' => $harga_perolehan, 'nilai_buku' => 0];
    }

    if ($inv->status == 'Rusak' && $tgl_kondisi >= $inv->tgl_validasi) {
        return ['penyusutan' => 0, 'akum_susut' => $harga_perolehan - 1, 'nilai_buku' => 1];
    }

    $akum_susut = min($harga_perolehan, $satuan_susut * $umur);
    if ($umur >= $inv->umur_ekonomis) {
        $akum_susut = $harga_perolehan - 1;
        $nilai_buku = 1;
    } else {
        $nilai_buku = max(1, $harga_perolehan - $akum_susut);
    }

    $umur_pakai = max(0, $umur - $pakai_lalu);
    return [
        'penyusutan' => $satuan_susut * $umur_pakai,
        'akum_susut' => $akum_susut,
        'nilai_buku' => $nilai_buku
        ];
    }

    public static function hitungPenyusutan($tgl_kondisi, $kategori)
    {
        $tahun = date('Y', strtotime($tgl_kondisi));
        $th_lalu = $tahun - 1;

        // Inisialisasi Penampung Total
        $data = [
        't_unit' => 0, 't_harga' => 0, 't_penyusutan' => 0,
        't_akum_susut' => 0, 't_nilai_buku' => 0
        ];

        // Query Data Inventaris tanpa filter Lokasi
        $inventaris = ModelsInventaris::where('jenis', '1')
        ->where('kategori', $kategori)
        ->where('status', '!=', '0')
        ->where('tgl_beli', '<=', $tgl_kondisi) ->orderBy('tgl_beli', 'ASC')
            ->get();

        foreach ($inventaris as $inv) {
        $harga_perolehan = $inv->harsat * $inv->unit;

        // Kategori 1 (Tanah/Tetap)
        if ($kategori == '1') {
            $nilai_buku = ($inv->status == 'Dijual' || $inv->status == 'Hapus') ? 0 : $harga_perolehan;
            $data['t_unit'] += $inv->unit;
            $data['t_harga'] += $harga_perolehan;
            $data['t_nilai_buku'] += $nilai_buku;
            } else
        {
            // Logika Susut
            $satuan_susut = $inv->harsat <= 0 ? 0 : round(($inv->harsat / $inv->umur_ekonomis) * $inv->unit, 2);
            $pakai_lalu = self::hitungBulan($inv->tgl_beli, $th_lalu . '-12-31');

            // Tentukan umur sesuai tgl_validasi
            $tgl_ref = ($inv->status != 'Baik' && $tgl_kondisi >= $inv->tgl_validasi) ? $inv->tgl_validasi :
            $tgl_kondisi;
            $umur = self::hitungBulan($inv->tgl_beli, $tgl_ref);

            // Logika Status Rusak/Hilang/Dijual
            if (in_array($inv->status, ['Hilang', 'Dijual', 'Hapus', 'Dihapus']) && $tgl_kondisi >= $inv->tgl_validasi)
            {
                $akum_susut = $harga_perolehan;
                $nilai_buku = 0;
                $penyusutan = 0;
            } elseif ($inv->status == 'Rusak' && $tgl_kondisi >= $inv->tgl_validasi) {
                $akum_susut = $harga_perolehan - 1;
                $nilai_buku = 1;
                $penyusutan = 0;
            } else {
                // Perhitungan Normal
                $akum_susut = min($harga_perolehan, $satuan_susut * $umur);
            if ($umur >= $inv->umur_ekonomis) {
                $akum_susut = $harga_perolehan - 1;
                $nilai_buku = 1;
            } else {
                $nilai_buku = max(1, $harga_perolehan - $akum_susut);
            }
                $umur_pakai = max(0, $umur - $pakai_lalu);
                $penyusutan = $satuan_susut * $umur_pakai;
            }

                // Akumulasi ke Total
                $data['t_unit'] += $inv->unit;
                $data['t_harga'] += $harga_perolehan;
                $data['t_penyusutan'] += $penyusutan;
                $data['t_akum_susut'] += $akum_susut;
                $data['t_nilai_buku'] += $nilai_buku;
            }
        }

        return $data;
    }
}
