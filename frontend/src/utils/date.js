/**
 * Parse tanggal dari backend jadi objek Date LOCAL (jam 00:00 waktu lokal).
 *
 * Kenapa tidak `new Date(str)`?
 * ------------------------------
 * String `YYYY-MM-DD` (atau `YYYY-MM-DDTHH:mm:ss.sssZ`) yang dikirim Laravel
 * di-parse JavaScript sebagai **UTC**, bukan waktu lokal. Efeknya:
 *
 *   new Date('2026-11-27')            -> 27 Nov 00:00 UTC
 *   new Date('2026-11-27T00:00:00Z')  -> 27 Nov 00:00 UTC
 *
 * Di zona waktu negatif (mis. UTC-5) `getDate()` mengembalikan **26**,
 * sehingga tanggal tampil meleset satu hari. Untuk tanggal yang sudah
 * dikurangi satu hari (mis. batas akhir bayar), errornya jadi dua hari.
 *
 * Di WIB (UTC+7) hasilnya kebetulan benar, jadi bug ini baru muncul kalau
 * server/client dijalankan di zona waktu lain.
 *
 * Solusinya: ambil bagian tanggalnya (YYYY-MM-DD) lalu buat Date sebagai
 * waktu lokal — persis seperti `parseLocalDate()` di `BillingForm.vue`.
 *
 * @param {string|Date|null|undefined} value
 * @returns {Date|null} Date lokal jam 00:00, atau null kalau tidak valid.
 */
export const parseLocalDate = (value) => {
  if (!value) return null

  if (value instanceof Date) {
    if (Number.isNaN(value.getTime())) return null
    return new Date(value.getFullYear(), value.getMonth(), value.getDate())
  }

  if (typeof value === 'string') {
    const m = value.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (m) {
      const year = Number(m[1])
      const month = Number(m[2]) - 1
      const day = Number(m[3])
      return new Date(year, month, day)
    }

    const parsed = new Date(value)
    if (Number.isNaN(parsed.getTime())) return null
    return new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate())
  }

  return null
}
