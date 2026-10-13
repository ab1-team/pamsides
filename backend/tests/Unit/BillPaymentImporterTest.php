<?php

namespace Tests\Unit;

use App\Support\BillPaymentImporter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Aturan identitas & status pembayaran tagihan.
 *
 * Perilaku ini TIDAK boleh berubah diam-diam: dulu ditulis inline di beberapa
 * tempat pada ImportLegacyBillPaymentsCommand, dan sekarang dipakai bersama.
 * Kalau aturan ini bergeser, akibatnya pembayaran dobel ATAU tagihan salah
 * status -- keduanya sulit terdeteksi karena command tetap keluar sukses.
 */
class BillPaymentImporterTest extends TestCase
{
    // ---------- dedupKey ----------

    #[Test]
    public function dedup_key_menormalkan_jam_tanpa_mengubah_identitas_pembayaran(): void
    {
        // Jam pada data legacy tidak bermakna untuk identitas pembayaran.
        // Tanpa normalisasi, event yang sama terisi dua kali.
        $a = BillPaymentImporter::dedupKey(10, '2025-03-25 17:00:00', 15000);
        $b = BillPaymentImporter::dedupKey(10, '2025-03-25 00:00:00', 15000);
        $c = BillPaymentImporter::dedupKey(10, '2025-03-25', 15000.0);

        $this->assertSame($a, $b, 'Jam 17:00 dan 00:00 harus dianggap event yang sama');
        $this->assertSame($b, $c, 'Format tanggal tanpa jam harus dianggap sama');
        $this->assertSame('10|2025-03-25|15000.00', $a);
    }

    #[Test]
    public function dedup_key_membedakan_bill_tanggal_dan_nominal(): void
    {
        $base = BillPaymentImporter::dedupKey(10, '2025-03-25 09:00:00', 15000);

        $this->assertNotSame($base, BillPaymentImporter::dedupKey(11, '2025-03-25 09:00:00', 15000));
        $this->assertNotSame($base, BillPaymentImporter::dedupKey(10, '2025-03-26 09:00:00', 15000));
        $this->assertNotSame($base, BillPaymentImporter::dedupKey(10, '2025-03-25 09:00:00', 25000));
    }

    #[Test]
    public function dedup_key_membulatkan_nominal_ke_sen(): void
    {
        // Selisih pembulatan sen harus dianggap pembayaran yang sama,
        // kalau tidak import akan menambah pembayaran kembar.
        $this->assertSame(
            BillPaymentImporter::dedupKey(1, '2025-01-01', 1000.001),
            BillPaymentImporter::dedupKey(1, '2025-01-01', 1000.002)
        );
    }

    #[Test]
    public function dedup_key_menghasilkan_dimensi_yang_bisa_dibalik(): void
    {
        // Kalau pembatas tidak konsisten, key bisa bentrok diam-diam.
        $key = BillPaymentImporter::dedupKey(123, '2025-06-07 08:09:10', 1234.56);

        $this->assertSame(3, count(explode('|', $key)));
        $this->assertSame('123', explode('|', $key)[0]);
        $this->assertSame('2025-06-07', explode('|', $key)[1]);
        $this->assertSame('1234.56', explode('|', $key)[2]);
    }

    // ---------- statusFor ----------

    #[Test]
    #[DataProvider('statusCases')]
    public function status_mengikuti_aturan_lunas(float $paid, float $amount, string $expected): void
    {
        $this->assertSame($expected, BillPaymentImporter::statusFor($paid, $amount));
    }

    public static function statusCases(): array
    {
        return [
            'lunas penuh' => [100000.0, 100000.0, 'paid'],
            'lunas kurang 1 sen' => [99999.99, 100000.0, 'paid'],
            'kurang 2 sen' => [99998.99, 100000.0, 'unpaid'],
            'belum bayar' => [0.0, 100000.0, 'unpaid'],
            'bayar lebih' => [120000.0, 100000.0, 'paid'],
            'nol rupiah' => [0.0, 0.0, 'paid'],
        ];
    }

    #[Test]
    public function toleransi_hanya_satu_sen(): void
    {
        // Batas toleransi harus presisi -- melebar berarti tagihan dianggap
        // lunas padahal masih kurang.
        $this->assertSame('paid', BillPaymentImporter::statusFor(99999.99, 100000.0));
        $this->assertSame('unpaid', BillPaymentImporter::statusFor(99999.98, 100000.0));
        $this->assertSame(0.01, BillPaymentImporter::PAID_TOLERANCE);
    }

    // ---------- billIndexKey ----------

    #[Test]
    public function bill_index_key_unik_untuk_setiap_periode(): void
    {
        $keys = [
            BillPaymentImporter::billIndexKey(1, 2025, 1),
            BillPaymentImporter::billIndexKey(1, 2025, 2),
            BillPaymentImporter::billIndexKey(1, 2026, 1),
            BillPaymentImporter::billIndexKey(2, 2025, 1),
        ];

        $this->assertCount(4, array_unique($keys), 'Key tagihan harus berbeda per pelanggan+periode');
    }

    #[Test]
    public function bill_index_key_tidak_bocor_antar_komponen(): void
    {
        // Pembatas harus mencegah (1, 12) dan (1, 2) jadi key sama.
        $this->assertNotSame(
            BillPaymentImporter::billIndexKey(1, 2025, 12),
            BillPaymentImporter::billIndexKey(1, 2025, 2)
        );
    }

    #[Test]
    public function bill_index_key_konsisten_dengan_tipe_data(): void
    {
        // Nilai dari DB bisa string; pemanggilan dengan cast int harus sama.
        $this->assertSame(
            BillPaymentImporter::billIndexKey(7, 2025, 3),
            BillPaymentImporter::billIndexKey('7', '2025', '3')
        );
    }
}
