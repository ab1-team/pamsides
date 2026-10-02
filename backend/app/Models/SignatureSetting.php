<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SignatureSetting - menyimpan template + image tanda tangan per laporan.
 *
 * Konsep persis seperti SIUPK-Next:
 *   - `template_html` = blok penandatangan editable (misal: 3 kolom Mengetahui/Dibuat/Bendahara)
 *   - `image_path`    = path file gambar tanda tangan (PNG/JPG/WebP, max 2 MB)
 *   - tanda tangan gambar otomatis disisipkan ke template via placeholder
 *     `{ttd_image}` atau replace baris kosong pertama di template.
 */
class SignatureSetting extends Model
{
    protected $table = 'signature_settings';

    protected $fillable = [
        'report_key',
        'label',
        'template_html',
        'image_path',
        'image_mime',
        'image_size',
    ];

    protected $casts = [
        'image_size' => 'integer',
    ];

    /**
     * Daftar jenis laporan PAMSIDES yang didukung tanda-tangan digital.
     * Diselaraskan dengan JenisLaporan + handlerMap PelaporanController.
     *
     * @var array<string, string>
     */
    public const REPORT_TYPES = [
        'default'                  => 'Default',
        'cover'                    => 'Cover',
        'surat_pengantar'          => 'Surat Pengantar',
        'neraca'                   => 'Neraca',
        'laba_rugi'                => 'Laba Rugi',
        'neraca_saldo'             => 'Neraca Saldo',
        'arus_kas'                 => 'Arus Kas',
        'perubahan_modal'          => 'Perubahan Modal',
        'calkk'                    => 'Catatan Atas Laporan Keuangan',
        'buku_besar'               => 'Buku Besar',
        'jurnal_transaksi'         => 'Jurnal Transaksi',
        'daftar_pelanggan'         => 'Daftar Pelanggan',
        'tagihan_pelanggan'        => 'Tagihan Pelanggan',
        'piutang_pelanggan'        => 'Piutang Pelanggan',
        'piutang_komisi'           => 'Utang Komisi SPS',
        'ati'                        => 'Aset Tetap dan Investasi',
        'atb'                        => 'Aset Tak Berwujud',
        'e_budgeting'              => 'E-Budgeting',
        'tutup_buku_neraca'        => 'Tutup Buku Neraca',
        'tutup_buku_laba_rugi'     => 'Tutup Buku Laba Rugi',
        'tutup_buku_alokasi_laba'  => 'Tutup Buku Alokasi Laba',
        'tutup_buku_jurnal'        => 'Tutup Buku Jurnal',
        'tutup_buku_calk'          => 'Tutup Buku Catatan Atas Laporan Keuangan',
    ];

    public static function reportTypes(): array
    {
        return self::REPORT_TYPES;
    }

    public static function starterHtml(): string
    {
        return <<<'HTML'
<table style="width:100%">
  <tbody>
    <tr>
      <td style="width:33%;text-align:center"><p>Mengetahui,</p><p><br><br><br></p><p><strong>( ........................ )</strong></p></td>
      <td style="width:33%;text-align:center"><p>Dibuat oleh,</p><p><br><br><br></p><p><strong>( ........................ )</strong></p></td>
      <td style="width:33%;text-align:center"><p>Bendahara,</p><p><br><br><br></p><p><strong>( ........................ )</strong></p></td>
    </tr>
  </tbody>
</table>
HTML;
    }
}