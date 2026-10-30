import { computed, ref, watch } from 'vue'

/**
 * useLogoTrim - memotong ruang kosong (background) di sekeliling logo.
 *
 * Kenapa perlu:
 *   File logo yang diunggah pengguna sering punya padding putih di
 *   sekeliling gambar. Contoh logo PAMSIDES 551x560 px: area gambar
 *   hanya mulai di y=31 dan berakhir di y=529, jadi ada ~30px ruang
 *   kosong di atas dan bawah, ~27px di kiri dan kanan.
 *
 *   Ketika logo itu hanya diberi `height: 70px`, seluruh kotak 70px itu
 *   ikut ter-render, sehingga:
 *
 *     1. Logo terlihat "melayang" — bagian atas logo mulai ~4px di bawah
 *        baris judul ("TIRTO MULO") dan ujungnya jauh melewati baris
 *        alamat, jadi tidak sejajar dengan teks.
 *     2. Ada celah putih lebar (~15px) antara logo dan teks, padahal
 *        di dalam file gambarnya sendiri tidak ada ruang kosong.
 *
 *   Composable ini memotong gambar ke bounding box konten aslinya lalu
 *   mengembalikan data URL hasil potongan. Dengan begitu logo occupy
 *   penuh kotak render-nya, sehingga:
 *
 *     - Kotak logo bisa disejajarkan (vertical-align) dengan baris
 *       paling atas teks.
 *     - Jarak ke teks bisa dibuat tipis supaya logo "nempel" pada blok
 *       nama usaha.
 *
 * Catatan penting:
 *   - Logo dimuat dari origin API (cross-origin). Canvas hanya boleh
 *     dibaca kalau server mengirim header CORS. `LogoService::response()`
 *     sudah mengirim `Access-Control-Allow-Origin: *` dan tag <img> sudah
 *     memakai `crossorigin="anonymous"`, jadi `getImageData` tidak throws.
 *   - Kalau trimming gagal (gambar tidak termuat, CORS ditolak, canvas
 *     tidak didukung), composable ini fallback ke URL asli. Layout tetap
 *     benar, hanya jarak yang tidak ikut dipangkas.
 */

const TRIM_THRESHOLD = 245

// Cache per URL supaya logo yang sama tidak dipotong berulang kali.
// Kop surat diulang di tiap halaman laporan, jadi tanpa cache setiap
// halaman akan melakukan operasi canvas sendiri.
const cache = new Map()

// Cache failure juga disimpan, supaya logo yang gagal dipotong tidak
// dicoba berulang kali di tiap halaman laporan.
const failed = new Set()

/**
 * Hitung bounding box konten (non-background) sebuah URL gambar.
 * Mengembalikan { top, right, bottom, left } dalam satuan piksel asli.
 */
const measureInkBox = (url) =>
  new Promise((resolve) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'

    img.onload = () => {
      try {
        const w = img.naturalWidth
        const h = img.naturalHeight
        if (!w || !h) return resolve(null)

        const canvas = document.createElement('canvas')
        canvas.width = w
        canvas.height = h

        const ctx = canvas.getContext('2d', { willReadFrequently: true })
        if (!ctx) return resolve(null)

        ctx.drawImage(img, 0, 0)

        let data
        try {
          data = ctx.getImageData(0, 0, w, h).data
        } catch {
          // Canvas tainted — CORS ditolak oleh browser.
          return resolve(null)
        }

        let minX = w
        let maxX = -1
        let minY = h
        let maxY = -1

        for (let y = 0; y < h; y++) {
          const rowOffset = y * w * 4
          for (let x = 0; x < w; x++) {
            const i = rowOffset + x * 4
            const r = data[i]
            const g = data[i + 1]
            const b = data[i + 2]
            const a = data[i + 3]

            // Piksel dianggap "background" kalau transparan penuh atau
            // nyaris putih. Logo PNG często punya padding transparan,
            // bukan putih, jadi keduanya harus dianggap background.
            if (a < 16) continue
            if (r > TRIM_THRESHOLD && g > TRIM_THRESHOLD && b > TRIM_THRESHOLD) continue

            if (x < minX) minX = x
            if (x > maxX) maxX = x
            if (y < minY) minY = y
            if (y > maxY) maxY = y
          }
        }

        // Seluruh gambar kosong (atau semua putih) -> jangan dipotong.
        if (maxX < 0 || maxY < 0) return resolve(null)

        resolve({
          top: minY,
          right: w - 1 - maxX,
          bottom: h - 1 - maxY,
          left: minX,
        })
      } catch {
        resolve(null)
      }
    }

    img.onerror = () => resolve(null)
    img.src = url
  })

/**
 * Potong gambar berdasarkan bounding box dan kembalikan data URL baru
 * beserta ukuran hasil potongan (dalam piksel).
 */
const cropImage = (url, box) =>
  new Promise((resolve) => {
    const img = new Image()
    img.crossOrigin = 'anonymous'

    img.onload = () => {
      try {
        const w = img.naturalWidth
        const h = img.naturalHeight

        const sx = box.left
        const sy = box.top
        const sw = Math.max(1, w - box.left - box.right)
        const sh = Math.max(1, h - box.top - box.bottom)

        const canvas = document.createElement('canvas')
        canvas.width = sw
        canvas.height = sh

        const ctx = canvas.getContext('2d')
        if (!ctx) return resolve(null)

        ctx.drawImage(img, sx, sy, sw, sh, 0, 0, sw, sh)

        resolve({ url: canvas.toDataURL('image/png'), width: sw, height: sh })
      } catch {
        resolve(null)
      }
    }

    img.onerror = () => resolve(null)
    img.src = url
  })

export function useLogoTrim(source) {
  const trimmedUrl = ref(null)
  const trimmedSize = ref(null)
  const isTrimming = ref(false)

  const sourceValue = computed(() => {
    const v = typeof source === 'function' ? source() : source
    if (typeof v === 'string') return v
    return v?.value ?? ''
  })

  // Token dipakai supaya hasil trimming yang telat datang tidak menimpa
  // URL logo yang lebih baru (mis. user ganti logo di halaman SOP).
  let currentToken = 0

  const trim = async (url) => {
    const token = ++currentToken

    if (!url) {
      trimmedUrl.value = null
      trimmedSize.value = null
      isTrimming.value = false
      return
    }

    if (cache.has(url)) {
      trimmedUrl.value = cache.get(url).url
      trimmedSize.value = cache.get(url).size
      isTrimming.value = false
      return
    }

    if (failed.has(url)) {
      trimmedUrl.value = url
      trimmedSize.value = null
      isTrimming.value = false
      return
    }

    isTrimming.value = true

    const box = await measureInkBox(url)
    if (token !== currentToken) return

    if (!box) {
      failed.add(url)
      trimmedUrl.value = url
      trimmedSize.value = null
      isTrimming.value = false
      return
    }

    // Tidak ada padding berarti memotong tidak mengubah apa pun.
    const noPadding = box.top === 0 && box.right === 0 && box.bottom === 0 && box.left === 0
    if (noPadding) {
      cache.set(url, { url, size: null })
      trimmedUrl.value = url
      trimmedSize.value = null
      isTrimming.value = false
      return
    }

    const cropped = await cropImage(url, box)
    if (token !== currentToken) return

    if (!cropped) {
      failed.add(url)
      trimmedUrl.value = url
      trimmedSize.value = null
      isTrimming.value = false
      return
    }

    const result = { url: cropped.url, size: { width: cropped.width, height: cropped.height } }
    cache.set(url, result)
    trimmedUrl.value = result.url
    trimmedSize.value = result.size
    isTrimming.value = false
  }

  watch(sourceValue, (url) => trim(url), { immediate: true })

  return { trimmedUrl, trimmedSize, isTrimming }
}

export default useLogoTrim
