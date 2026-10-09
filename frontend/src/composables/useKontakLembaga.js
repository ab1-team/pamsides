import { ref, computed } from 'vue'
import sopService from '@/services/sop.service'

/**
 * Nomor kontak resmi organisasi (WhatsApp / telepon).
 *
 * Satu-satunya sumber kebenaran adalah `settings.telepon`, yaitu kolom yang
 * diisi admin lewat menu "SOP → Profil Lembaga" (`SopController::updateLembaga`).
 *
 * Kenapa TIDAK ada konstanta hardcode di halaman pelanggan:
 *   Halaman "Lapor Gangguan" dulu menulis `0812-3456-7890` langsung di
 *   template. Begitu admin mengganti nomor di Pengaturan, halaman pelanggan
 *   tetap menampilkan yang lama — jadi pelanggan menghubungi nomor yang
 *   salah. Sekarang semua halaman membaca dari composable ini, jadi cukup
 *   ganti sekali di admin dan seluruh sisi pelanggan ikut berubah.
 */

// Dibagi antar-instance: satu request dipakai bersama di sesi ini, bukan
// satu request per komponen yang mount bersamaan.
const telepon = ref('')
const isLoaded = ref(false)
let inflight = null

/**
 * @param {boolean} force Abaikan cache dan paksa request baru. Dipakai
 *   setelah admin menyimpan Profil Lembaga.
 */
const load = async (force = false) => {
  // Sudah punya data → jangan minta lagi, kecuali dipaksa.
  if (isLoaded.value && !force) return telepon.value

  // Beberapa komponen mount bersamaan (dashboard + halaman detail), atau
  // admin menekan "Simpan" dua kali cepat. Request paralel akan mengembalikan
  // data basi; pakai satu yang sedang jalan.
  if (inflight) return inflight

  inflight = (async () => {
    try {
      const res = await sopService.getPublicIdentity()
      const value = res?.data?.data?.telepon ?? res?.data?.telepon ?? ''
      telepon.value = typeof value === 'string' ? value : ''
    } catch {
      // Sengaja diam. Endpoint ini hanya menampilkan kontak; halaman sudah
      // punya fallback ("Hubungi kantor Pamsimas") sehingga kegagalan tidak
      // boleh menggagalkan seluruh halaman. Kalau `telepon` kosong di
      // database pun, komponen tetap menampilkan fallback-nya.
      telepon.value = ''
    } finally {
      isLoaded.value = true
      inflight = null
    }
    return telepon.value
  })()

  return inflight
}

/**
 * Dipanggil setelah admin menyimpan setting, agar nomor ikut terbarui.
 *
 * Karena `telepon` berupa `ref` modul (shared), semua komponen yang
 * sedang membaca `display` / `waLink` akan ikut berubah otomatis —
 * tidak perlu memuat ulang halaman.
 */
export const refreshKontak = async () => {
  await load(true)
}

/** Buang semua karakter non-digit. */
const onlyDigits = (v) => String(v || '').replace(/\D/g, '')

/**
 * Ubah nomor Indonesia ke format internasional tanpa "+" (dipakai wa.me).
 *   0812-3456-7890  → 6281234567890
 *   8123456789      → 628123456789
 *   +62 812-3456…   → 6281234567890
 * Nomor yang sudah diawali "62" (kode negara) tidak diubah.
 */
export function normalizeToInternational(value) {
  let d = onlyDigits(value)
  if (!d) return ''
  if (d.startsWith('62')) return d
  if (d.startsWith('0')) return `62${d.slice(1)}`
  if (d.startsWith('8')) return `62${d}`
  return d
}

/**
 * Format tampilan enak dibaca.
 *
 * Aturan panjang digit lokal Indonesia:
 *   11 digit → 4-4-3  (nomor seluler)  0812-3456-7890
 *   10 digit → 4-3-3  (telepon BSN)     0274-889-123
 *    9 digit → 3-3-3  (telepon kota)   021-123-456
 *
 * Regex lama `^(\d{3,4})(\d{3,4})(\d{0,5})$` salah memecah nomor 10 digit
 * jadi 4-4-2 ("0274-8891-23"), sehingga nomor telepon kantor tampil
 * dengan digit yang tersusun keliru.
 */
export function formatDisplay(value) {
  const d = onlyDigits(value)
  if (!d) return ''

  // Kembalikan ke format lokal (leading 0) supaya tampil sebagai nomor
  // Indonesia, bukan "+62…".
  const local = d.startsWith('62') ? `0${d.slice(2)}` : d.startsWith('0') ? d : `0${d}`

  const len = local.length
  const pattern =
    len >= 11
      ? /^(\d{4})(\d{4})(\d{3,})$/
      : len === 10
        ? /^(\d{4})(\d{3})(\d{3})$/
        : len === 9
          ? /^(\d{3})(\d{3})(\d{3})$/
          : null

  const m = pattern ? local.match(pattern) : null
  return m ? `${m[1]}-${m[2]}-${m[3]}` : local
}

/**
 * @returns {{
 *   telepon: import('vue').Ref<string>,
 *   digits: import('vue').ComputedRef<string>,
 *   display: import('vue').ComputedRef<string>,
 *   hasNumber: import('vue').ComputedRef<boolean>,
 *   waLink: import('vue').ComputedRef<string>,
 *   telLink: import('vue').ComputedRef<string>,
 *   reload: () => Promise<void>
 * }}
 */
export function useKontakLembaga() {
  if (!isLoaded.value && !inflight) {
    load()
  }

  /** Digit saja untuk wa.me / tel: (mis. 6281234567890). */
  const digits = computed(() => normalizeToInternational(telepon.value))

  /** Nomor siap tampil (mis. 0812-3456-7890). */
  const display = computed(() => formatDisplay(telepon.value))

  const hasNumber = computed(() => digits.value.length > 0)

  const waLink = computed(() => (digits.value ? `https://wa.me/${digits.value}` : ''))

  const telLink = computed(() => (digits.value ? `tel:+${digits.value}` : ''))

  return {
    telepon,
    digits,
    display,
    hasNumber,
    waLink,
    telLink,
    reload: () => load(true),
  }
}

export default useKontakLembaga