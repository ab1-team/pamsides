<?php

namespace App\Support;

/**
 * SUMBER TUNGGAL aturan identitas migrasi legacy (bisnis 5 "Tirto Mulo").
 *
 * Dipisah dari command import agar bisa diuji tanpa DB dan tanpa data legacy,
 * dan supaya `import:tickets` + `import:customers` memakai aturan yang SAMA.
 *
 * Dua aturan di sini adalah inti perbaikan "pelanggan hilang":
 *
 *   1. ticketKey()  -- kunci idempotensi tiket baru. Dulu (package_id,
 *      village_id, applicant_name, nik): 53 pasang instalasi berbeda dianggap
 *      tiket yang sama sehingga tiket kedua tidak dibuat. Dengan
 *      `legacy_installation_id` (bila ada), setiap instalasi legacy dipetakan
 *      ke tepat SATU tiket.
 *
 *   2. customerEmail() -- email akun user pelanggan, wajib unik per pelanggan
 *      legacy. Dulu email = slug(nama) saja, sehingga pelanggan berbeda dengan
 *      nama sama (banyak "Sunarto") berbagi satu akun user dan sebagian hilang.
 */
class LegacyIdentity
{
    /**
     * Apakah tiket sudah punya identitas instalasi legacy.
     *
     * Kunci kuat HANYA dipakai kalau legacy_installation_id tidak null, supaya
     * tiket yang dibuat lewat form aplikasi baru (tanpa pasangan legacy) tidak
     * ikut tercampur.
     */
    public static function hasLegacyKey(mixed $legacyInstallationId): bool
    {
        return $legacyInstallationId !== null && $legacyInstallationId !== '' && (int) $legacyInstallationId > 0;
    }

    /**
     * Kunci idempotensi untuk satu tiket baru.
     *
     * Prioritas: `legacy_installation_id` (pasti, satu instalasi legacy = satu
     * tiket). Kalau tidak ada (tidak mungkin di jalur migrasi yang benar), baru
     * pakai kunci lemah (package|village|name|nik) sebagai jaring terakhir.
     *
     * @param  array{legacy_installation_id?:mixed, package_id?:mixed, village_id?:mixed, applicant_name?:mixed, nik?:mixed}  $row
     * @return array{primary:bool, key:string}
     */
    public static function ticketKey(array $row): array
    {
        $legacy = $row['legacy_installation_id'] ?? null;

        if (self::hasLegacyKey($legacy)) {
            return ['primary' => true, 'key' => 'legacy:'.(int) $legacy];
        }

        return ['primary' => false, 'key' => self::weakTicketKey($row)];
    }

    /**
     * Kunci lemah lama: (package_id, village_id, applicant_name lowercase, nik).
     * Hanya dipakai kalau legacy_installation_id tidak tersedia.
     */
    public static function weakTicketKey(array $row): string
    {
        $name = strtolower(trim((string) ($row['applicant_name'] ?? '')));

        return 'weak:'
            .($row['package_id'] ?? '').'|'
            .($row['village_id'] ?? '').'|'
            .$name.'|'
            .(string) ($row['nik'] ?? '');
    }

    /**
     * Email akun user pelanggan -- WAJIB unik per pelanggan legacy.
     *
     * Pola: `{slug(nama)}{legacy_customer_id}@gmail.com`, mis.
     * nama "Sunarto" + customer legacy id 8811 -> "sunarto8811@gmail.com".
     * Pembeda `legacy_customer_id` inilah yang mencegah dua pelanggan bernama
     * sama berbagi satu akun user (dulu ~191 tabrakan).
     */
    public static function customerEmail(string $name, int $legacyCustomerId): string
    {
        return self::emailLocalPart($name, $legacyCustomerId).'@gmail.com';
    }

    /**
     * Bagian lokal email: slug huruf kecil alfanumerik dari nama + id legacy.
     * Nama yang jadi slug kosong memakai prefiks "user" supaya tetap unik.
     */
    public static function emailLocalPart(string $name, int $legacyCustomerId): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '', $slug) ?? '';
        if ($slug === '') {
            $slug = 'user';
        }

        return $slug.$legacyCustomerId;
    }
}
