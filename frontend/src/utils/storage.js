function detectApiBase() {
  if (import.meta.env.VITE_API_BASE_URL) return import.meta.env.VITE_API_BASE_URL
  if (import.meta.env.VITE_BACKEND_URL) return `${import.meta.env.VITE_BACKEND_URL.replace(/\/$/, '')}/api`
  if (typeof window !== 'undefined') {
    const { protocol, hostname } = window.location
    return `${protocol}//${hostname}/api`
  }
  return '/api'
}

export const API_BASE = detectApiBase()

export const storageUrl = (path) => {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  const origin = API_BASE.replace(/\/api\/?$/, '')
  const clean = String(path).replace(/^\/+/, '')
  return `${origin}/${clean}`
}

/**
 * URL logo lembaga untuk dipakai di kop surat, laporan, dan cetak bukti.
 *
 * Kenapa tidak lagi andalkan `/storage/sop/logo/{file}`:
 *   - Link symlink `public/storage` tidak ikut ter-deploy ke server produksi
 *     (`backend/.gitignore` mengabaikan `/public/storage` dan workflow
 *     deploy tidak menjalankan `php artisan storage:link`). Request ke
 *     `/storage/...` pun jatuh ke fallback SPA nginx dan mengembalikan
 *     HTML, bukan gambar.
 *   - `config/cors.php` hanya mengizinkan `api/*`, sehingga `<img
 *     crossorigin="anonymous">` pada kop laporan ditolak browser dan
 *     tampil sebagai broken image.
 *
 * Sekarang logo disajikan lewat route API
 * (`GET /api/settings/sop/logo/{file}`) yang mengembalikan gambar asli
 * beserta header CORS.
 *
 * Sumber URL, berurutan prioritas:
 *   1. `lembaga.logo_url` — path/URL dari backend (paling akurat).
 *   2. `lembaga.logo` — nama file; dipertahankan sebagai fallback supaya
 *      data lama yang belum punya `logo_url` tetap bisa ditampilkan.
 *
 * @param {object|string|null} lembaga Payload lembaga, atau nama file logo.
 * @returns {string} URL logo, atau string kosong bila tidak ada logo.
 */
export const logoUrl = (lembaga) => {
  if (!lembaga) return ''

  // Boleh dipanggil dengan nama file langsung.
  if (typeof lembaga === 'string') {
    return storageUrl(`api/settings/sop/logo/${encodeURIComponent(lembaga)}`)
  }

  const url = lembaga.logo_url
  if (url) {
    // Backend mengirim path relatif (mis. `/api/settings/sop/logo/x.png`)
    // supaya tidak bergantung pada APP_URL. Path relatif dirangkai ke
    // origin API yang sedang dipakai.
    if (/^https?:\/\//i.test(url)) return url
    if (url.startsWith('/')) return storageUrl(url)
  }

  const file = lembaga.logo
  if (!file) return ''

  // Sudah berupa URL lengkap (mis. hasil migrasi data lama).
  if (/^https?:\/\//i.test(file) || /^data:image\//i.test(file)) return file

  return storageUrl(`api/settings/sop/logo/${encodeURIComponent(file)}`)
}
