<?php

namespace Tests\Unit;

use App\Services\LegacyPaymentSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Matcher tanggal `sync-payments` (Bug A) — regresi yang pernah nyaris
 * menghapus 24.525 baris produksi.
 *
 * Konteks: DB baru menyimpan `bill_payments.paid_at` dalam UTC, sedangkan
 * event legacy dikunci dengan tanggal WIB. Tengah-malam WIB tersimpan sebagai
 * `17:00:00` UTC HARI SEBELUMNYA:
 *
 *   legacy (WIB) : 2025-03-26
 *   DB baru      : paid_at = 2025-03-25 17:00:00  (UTC)
 *
 * Kode lama memakai `substr($paid_at, 0, 10)` → "2025-03-25", legacy →
 * "2025-03-26": selalu beda satu hari, jadi SEMUA pembayaran dianggap tidak
 * punya pasangan (dry-run melaporkan "perlu dibuat 24.525" + "perlu dihapus
 * 24.525" dari 24.529 baris).
 *
 * Helper `legacyDateOfPaidAt()` menormalkan UTC → WIB. Semua tes di bawah
 * GAGAL kalau seseorang mengembalikan `substr($paidAt, 0, 10)`.
 *
 * Ditaruh di Unit/ (extends PHPUnit\Framework\TestCase polos, bukan
 * Tests\TestCase): helper adalah fungsi statis murni tanpa Eloquent/container/
 * DB, jadi tidak perlu bootstrap Laravel — lebih cepat dan tidak menyentuh
 * database sama sekali.
 */
class LegacyPaidAtDateTest extends TestCase
{
    // ---------- 1. pergeseran hari batas tengah-malam WIB ----------

    /**
     * @return array<string,array{0:string,1:string}>
     */
    public static function pergeseranHariProvider(): array
    {
        return [
            // paid_at UTC                    => tanggal legacy WIB yang benar
            'tengah malam WIB 26 Mar -> 25 Mar 17:00 UTC' => ['2025-03-25 17:00:00', '2025-03-26'],
            'tengah malam WIB 1 Mei -> 30 Apr 17:00 UTC (lintas bulan)' => ['2025-04-30 17:00:00', '2025-05-01'],
            'tengah malam WIB 1 Jan -> 31 Des 17:00 UTC (lintas tahun)' => ['2024-12-31 17:00:00', '2025-01-01'],
            'tengah malam WIB 1 Mar -> 28 Feb 17:00 UTC (lintas bulan non-31)' => ['2025-02-28 17:00:00', '2025-03-01'],
        ];
    }

    #[Test]
    #[DataProvider('pergeseranHariProvider')]
    public function legacy_date_of_paid_at_menggeser_ke_tanggal_wib(string $paidAtUtc, string $wibDate): void
    {
        $this->assertSame(
            $wibDate,
            LegacyPaymentSource::legacyDateOfPaidAt($paidAtUtc),
            "paid_at UTC {$paidAtUtc} harus dipetakan ke tanggal WIB {$wibDate}"
        );
    }

    // ---------- 1b. kasus yang TIDAK menggeser ----------

    #[Test]
    public function legacy_date_of_paid_at_tidak_menggeser_saat_jam_wib_masih_hari_yang_sama(): void
    {
        // 08:00 WIB = 01:00 UTC hari yang sama → tanggal tidak bergeser.
        $this->assertSame('2025-03-26', LegacyPaymentSource::legacyDateOfPaidAt('2025-03-26 08:00:00'));
    }

    #[Test]
    public function legacy_date_of_paid_at_memakai_zona_legacy_bukan_zona_server(): void
    {
        // Sanity: helper HARUS memakai Asia/Jakarta sebagai tujuan konversi,
        // bukan timezone app/server (UTC di produksi).
        $this->assertSame('Asia/Jakarta', LegacyPaymentSource::LEGACY_TZ);
    }

    // ---------- 2. input kosong / null ----------

    /**
     * @return array<string,array{0:?string}>
     */
    public static function inputKosongProvider(): array
    {
        return [
            'null' => [null],
            'string kosong' => [''],
            'hanya spasi' => ['   '],
            'zero date MySQL' => ['0000-00-00 00:00:00'],
            'zero date polos' => ['0000-00-00'],
        ];
    }

    #[Test]
    #[DataProvider('inputKosongProvider')]
    public function legacy_date_of_paid_at_mengembalikan_string_kosong_tanpa_exception(?string $paidAt): void
    {
        $this->assertSame('', LegacyPaymentSource::legacyDateOfPaidAt($paidAt));
    }

    // ---------- 3. inti Bug A: kunci sisi legacy == kunci sisi DB baru ----------

    #[Test]
    public function kunci_pencocokan_sisi_legacy_dan_sisi_db_baru_harus_identik(): void
    {
        // Pasangan nyata: event legacy bertanggal 2025-03-26 (WIB) yang
        // setelah import tersimpan sebagai paid_at UTC 2025-03-25 17:00:00.
        $legacyDateWib = '2025-03-26';
        $paidAtUtcDbBaru = '2025-03-25 17:00:00';

        // Sisi legacy: membentuk kunci dari tanggal event apa adanya (WIB).
        $legacyKey = '1234|'.$legacyDateWib;

        // Sisi DB baru: bentuk kunci dari paid_at UTC lewat helper.
        $dbKey = '1234|'.LegacyPaymentSource::legacyDateOfPaidAt($paidAtUtcDbBaru);

        $this->assertSame(
            $legacyKey,
            $dbKey,
            'Kunci legacy dan DB baru harus sama; kalau tidak, matcher menganggap semua pembayaran tak berpasangan (Bug A)'
        );

        // Bukti eksplisit: pendekatan lama (potong 10 karakter mentah) BERBEDA
        // satu hari dari kunci legacy. Tes ini gagal kalau helper dikembalikan
        // ke `substr($paid_at, 0, 10)`.
        $naiveKey = '1234|'.substr($paidAtUtcDbBaru, 0, 10);
        $this->assertSame('1234|2025-03-25', $naiveKey, 'Asumsi kasus: potongan mentah = 25 Mar');
        $this->assertNotSame(
            $legacyKey,
            $naiveKey,
            'Potongan mentah harus TIDAK cocok dengan legacy — inilah bug yang dicegah'
        );
    }

    #[Test]
    public function kunci_pencocokan_identik_untuk_berbagai_event_bergeser(): void
    {
        // Generalisasi: apapun tanggal legacy-nya, pembentukan kunci lewat
        // helper harus balik ke tanggal tersebut.
        $pasangan = [
            '2025-03-26' => '2025-03-25 17:00:00',
            '2025-05-01' => '2025-04-30 17:00:00',
            '2025-01-01' => '2024-12-31 17:00:00',
            '2025-03-26' => '2025-03-26 08:15:00',
        ];

        foreach ($pasangan as $legacyDate => $paidAtUtc) {
            $billId = 99;
            $this->assertSame(
                $billId.'|'.$legacyDate,
                $billId.'|'.LegacyPaymentSource::legacyDateOfPaidAt($paidAtUtc),
                "paid_at UTC {$paidAtUtc} harus cocok dengan event legacy {$legacyDate}"
            );
        }
    }
}
