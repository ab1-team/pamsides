<template>
  <div
    class="report-page surat-page"
    :class="[
      configPaperSize === 'F4' ? 'size-f4' : 'size-a4',
      configOrientation === 'landscape' ? 'landscape' : 'portrait',
      noMetaHeader ? 'no-meta-header' : ''
    ]"
  >
    <div v-if="!noKop" class="surat-kop">
      <table class="kop-table">
        <tr>
          <td width="70" class="logo-cell">
            <img
              v-if="lembaga?.logo"
              :src="logoUrl"
              alt="logo"
              crossorigin="anonymous"
            />
            <div v-else class="kop-logo-fallback">
              {{ initials }}
            </div>
          </td>

          <td class="text-cell">
            <div class="kop-nama-usaha">
              {{ lembaga?.nama || 'UNIT USAHA ALIRAN AIR MASA DEPAN' }}
            </div>
            <div class="kop-nama-kec">
              <b>{{ lembaga?.alamat_kab || 'MULO WONOSARI' }}</b>
            </div>
           
            <div class="kop-info-sub">
              <i>{{ lembaga?.alamat || '' }}<span v-if="lembaga?.telepon">, Telp.{{ lembaga.telepon }}</span></i>
            </div>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="padding: 0;">
            <hr class="kop-single-divider">
          </td>
        </tr>
      </table>
    </div>

    <main class="report-content">
      <slot></slot>

      <!--
        Blok tanda tangan otomatis.
        signature.html berisi template + image yang sudah diinjeksi
        oleh backend (lihat SignatureService::renderForReport).
        Hanya dirender di halaman terakhir untuk laporan multi-page.
      -->
      <div
        v-if="showSignature"
        class="report-signature-block"
        v-html="effectiveSignature.html"
      ></div>
    </main>
  </div>
</template>

<script setup>
import { computed, inject, useAttrs } from 'vue'

const props = defineProps({
  lembaga: { type: Object, default: () => ({}) },
  config: { type: Object, default: () => ({ paper_size: 'A4', orientation: 'portrait' }) },
  noKop: { type: Boolean, default: false },
  noMetaHeader: { type: Boolean, default: false },
  /**
   * Full payload laporan. Hanya `pageInfo` yang dipakai di layout ini
   * untuk menentukan apakah signature perlu di-render (halaman terakhir saja).
   */
  payload: { type: Object, default: () => ({}) },
  /**
   * Object signature dari backend:
   *   { html, report_key, image_url, has_template }
   * Bisa null/undefined untuk laporan yang tidak butuh tanda tangan.
   * Jika tidak disediakan, coba ambil dari provide('reportSignature').
   */
  signature: { type: Object, default: null },
})

/**
 * Fallback: kalau payload tidak diberikan lewat prop, coba ambil dari $attrs
 * (misalnya kalau Report component meneruskannya sebagai extra attr).
 * Atau dari inject('reportPayload') yang diset oleh PelaporanPreview.
 */
const attrs = useAttrs()
const providedPayload = inject('reportPayload', null)
const effectivePayload = computed(() => {
  return (
    props.payload ||
    attrs.payload ||
    providedPayload?.value ||
    providedPayload ||
    {}
  )
})

/**
 * Ambil signature dari props ATAU dari provide() (di-set oleh PelaporanPreview).
 * Cara provide/inject dipakai supaya kita tidak perlu menambah prop ke 22+
 * file ReportXxx.vue yang membungkus BaseReportLayout.
 */
const providedSignature = inject('reportSignature', null)
const effectiveSignature = computed(() => props.signature || providedSignature?.value || providedSignature || null)

/**
 * Tentukan apakah signature block perlu dirender.
 * - Harus ada html yang tidak kosong (template + image injected)
 *
 * Aturan: blok TTD hanya boleh muncul SATU KALI per file laporan, yaitu di
 * halaman terakhir. `pageInfo` sudah dinormalisasi untuk semua view di
 * PelaporanPreview, jadi di sini cukup membaca current/total.
 * Fallback berjenjang bila pageInfo tidak tersedia: pakai flag isLastPage.
 */
const showSignature = computed(() => {
  const sig = effectiveSignature.value
  if (!sig || typeof sig.html !== 'string') return false
  if (sig.html.trim() === '') return false

  // Dibaca dari payload halaman ini; pageInfo sudah dinormalisasi di atas.
  const payload = effectivePayload.value
  const info = payload?.pageInfo

  // Halaman terakhir = current === total. Dengan normalisasi pageInfo di
  // PelaporanPreview, aturan ini berlaku untuk SEMUA jenis laporan.
  if (info && typeof info === 'object') {
    const current = Number(info.current)
    const total = Number(info.total)
    if (Number.isFinite(total) && total > 0 && Number.isFinite(current)) {
      return current === total
    }
  }

  // Fallback: pakai flag isLastPage bila pageInfo tidak tersedia.
  if (typeof payload?.isLastPage === 'boolean') {
    return payload.isLastPage
  }

  return true
})

const configPaperSize = computed(() => {
  return props.config?.paper_size || 'A4'
})

const configOrientation = computed(() => {
  return props.config?.orientation || 'portrait'
})

const initials = computed(() => {
  const nama = props.lembaga?.nama || 'PAMSIDES'
  return nama.split(' ').slice(0, 2).map((s) => s[0]).join('').toUpperCase()
})

const logoUrl = computed(() => {
  const logo = props.lembaga?.logo
  if (!logo) return ''
  if (logo.startsWith('http')) return logo
  const base =
    import.meta.env.VITE_API_STORAGE_URL ||
    (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/api\/?$/, '') + '/storage'
  return `${base}/sop/logo/${logo}`
})
</script>

<style scoped>
    /* ================= Base Style Master ================= */
    .report-page.surat-page {
    background: #ffffff;
    color: #000000;
    font-family: Arial, Helvetica, sans-serif;
    box-sizing: border-box;
    /* Padding cetak standar agar isi tidak mepet ke tepi kertas */
    padding: 60px 90px;
    margin: 0 auto;
    overflow: hidden;
    word-wrap: break-word;
    overflow-wrap: break-word;
    display: flex;
    flex-direction: column;
  }

    /* ================= Ukuran Preview Layar Web (PORTRAIT) =================
       DEFAULT: min-height A4 — halaman SELALU minimal sepanjang kertas.
       Konten yang melebihi akan meluber (jadi lebih panjang dari A4).
       Untuk laporan CaLK yang dipecah otomatis oleh paginator konten,
       override dengan class .a4-fixed di parent (lihat bawah).
    */
    .report-page.surat-page.size-a4.portrait {
      width: 210mm !important;
      min-height: 297mm;
    }

    .report-page.surat-page.size-f4.portrait {
      width: 215mm !important;
      min-height: 330mm;
    }

    /* ================= Ukuran Preview Layar Web (LANDSCAPE) ================= */
    .report-page.surat-page.size-a4.landscape {
      width: 297mm !important;
      min-height: 210mm;
    }

    .report-page.surat-page.size-f4.landscape {
      width: 330mm !important;
      min-height: 215mm;
    }

    /* Mode fixed-A4: paksa tinggi = tepat A4 (untuk CaLK yang dipaginasi otomatis).
       Konten yang melebihi akan di-handle oleh paginator, jadi tidak ada overflow. */
    .a4-fixed .report-page.surat-page.size-a4.portrait { height: 297mm; }
    .a4-fixed .report-page.surat-page.size-f4.portrait { height: 330mm; }
    .a4-fixed .report-page.surat-page.size-a4.landscape { height: 210mm; }
    .a4-fixed .report-page.surat-page.size-f4.landscape { height: 215mm; }

    /* ================= Pengaturan Cetak Browser (PDF) ================= */
    @media print {
    /* ... kode lainnya ... */

    /* Sesuaikan nilai margin (misal: 15mm atau 20mm agar lebih masuk ke dalam) */
    .size-a4.portrait { @page { size: A4 portrait; margin: 15mm; } }
    .size-a4.landscape { @page { size: A4 landscape; margin: 15mm; } }

    .size-f4.portrait { @page { size: 215mm 330mm portrait; margin: 15mm; } }
    .size-f4.landscape { @page { size: 215mm 330mm landscape; margin: 15mm; } }
  }

    /* ================= Gaya CSS Kop Surat ================= */
    .surat-kop {
      width: 100%;
    }
    .kop-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif;
    }
    .logo-cell {
      vertical-align: top;
      padding-right: 12px;
    }
    .logo-cell img {
      height: 70px;
      object-fit: contain;
    }
    .kop-logo-fallback {
      width: 55px;
      height: 55px;
      border-radius: 50%;
      background: #0c79f5;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      font-weight: 800;
    }
    .text-cell {
      vertical-align: top;
      text-align: left;
    }
   /* Cari bagian ini di file Base Style Anda */

    .kop-nama-usaha {
      font-size: 12px; /* Atur ukuran di sini (misal: 18px) */
      text-transform: uppercase;
      line-height: 1.2;
    }

    .kop-nama-kec {
      font-size: 13px; /* Atur ukuran di sini (misal: 14px) */
      text-transform: uppercase;
      line-height: 1.2;
      margin-top: 1px;
    }

    .kop-info-sub {
      font-size: 11px; 
      color: #000000;
      line-height: 1.2;
      margin-top: 1px;
    }
    .kop-single-divider {
      border: 0;
      border-top: 2.5px solid #888888;
      margin-top: 0.1px;
      margin-bottom: 4px;
      width: 100%;
    }

    .report-content {
      margin-top: 0;
      font-size: 12px;
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
      min-height: 0;
    }

    /* ================= KUNCI OTOMATIS UNTUK SEMUA LAPORAN MASUK SINI ================= */

    /* Aturan Header Judul Laporan */
    :deep(.page-header) {
      text-align: center;
      margin-top: 5px;
      margin-bottom: 12px;
    }

    /* Halaman lanjutan (tanpa page-header): nempelkan tabel tepat di bawah garis kop */
    .no-meta-header .surat-kop {
      margin-bottom: 0 !important;
      padding-bottom: 0 !important;
    }
    .no-meta-header .kop-single-divider {
      margin-bottom: 4px !important;
    }
    .no-meta-header .report-content {
      margin-top: 2px !important;
    }
    :deep(.page-header h2) {
      margin: 0;
      font-size: 14px;
      font-weight: bold;
      color: #000000;
    }
    :deep(.page-subtitle) {
      margin: 2px 0 0;
      font-size: 11px;
      color: #000000;
    }

    /* Aturan Struktur Tabel Data Global */
    :deep(.data-table) {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      table-layout: fixed; /* Memaksa kolom patuh % */
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
    :deep(.data-table th) {
      border: 1px solid #000000;
      color: #000000;
      font-weight: bold;
      text-align: left;
      padding: 2px 4px;
      font-size: 11px; 
      background: #dadde6;
    }
    :deep(.data-table td) {
      padding: 2px 4px;
      border: 1px solid #000000;
      vertical-align: middle;
      font-size: 10px; /* Ukuran mikro muat banyak */
      color: #000000;
      word-wrap: break-word;
      white-space: normal;
    }
    :deep(.text-center) {
      text-align: center;
    }
    :deep(.empty) {
      text-align: center;
      color: #000000;
      padding: 10px;
      font-style: italic;
      font-size: 11px;
    }

    /* Aturan Tanda Tangan / Bagian Bawah */
    :deep(.footer-container) {
      width: 100%;
      margin-top: 20px;
      display: flex;
      justify-content: flex-end;
    }

    /* Blok tanda tangan otomatis (di-inject dari backend via payload.signature). */
    .report-signature-block {
      width: 100%;
      margin-top: 28px;
      page-break-inside: avoid;
      break-inside: avoid;
      font-family: Arial, Helvetica, sans-serif;
      color: #000000;
    }
    .report-signature-block :deep(table) {
      width: 100%;
      border-collapse: collapse;
    }
    .report-signature-block :deep(p),
    .report-signature-block :deep(div) {
      margin: 0 0 4px 0;
      line-height: 1.3;
    }
    .report-signature-block :deep(img) {
      max-height: 60px;
      object-fit: contain;
    }
    :deep(.footer-sign) {
      width: 35%;
      text-align: center;
      font-size: 11px;
      color: #000000;
    }
    :deep(.footer-sign p) {
      margin: 1px 0;
    }
</style>

<style>
/* Halaman lanjutan (no-meta-header): tabel nempel ke garis kop */
.report-page.no-meta-header .data-table,
.report-page.no-meta-header .data-table-tight {
  margin-top: 0 !important;
}
</style>