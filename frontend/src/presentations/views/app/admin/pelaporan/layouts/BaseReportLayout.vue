<template>
  <div 
    class="report-page surat-page" 
    :class="[
      configPaperSize === 'F4' ? 'size-f4' : 'size-a4',
      configOrientation === 'landscape' ? 'landscape' : 'portrait'
    ]"
  >    
    <div class="surat-kop">
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
              <b>{{ lembaga?.alamat_kab || 'KEDUNG KABUPATEN JEPARA' }}</b>
            </div>
            <div class="kop-info-sub">
              <i>SK Kemenkumham RI No.AHU-00360.AH.01.35 </i>TAHUN 2023
            </div>
            <div class="kop-info-sub">
              <i>{{ lembaga?.alamat || 'Ds SUKOSONO RT 15 RW 4 KEC. KEDUNG KAB. JEPARA' }}<span v-if="lembaga?.telepon">, Telp.{{ lembaga.telepon }}</span></i>
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
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  lembaga: { type: Object, default: () => ({}) },
  config: { type: Object, default: () => ({ paper_size: 'A4', orientation: 'portrait' }) }
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
  const base = import.meta.env.VITE_API_STORAGE_URL || 'http://localhost:8000/storage'
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
    padding: 50px 120px;
    margin: 0 auto;
  }

  /* ================= Ukuran Preview Layar Web (PORTRAIT) ================= */
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

  /* ================= Pengaturan Cetak Browser (PDF) ================= */
  @media print {
    html, body {
      margin: 0 !important;
      padding: 0 !important;
      background: #ffffff;
    }
    
    .report-page.surat-page {
      padding: 0 !important;
      margin: 0 !important;
      width: 100% !important;
      min-height: auto !important;
      box-shadow: none !important;
    }

    .size-a4.portrait { @page { size: A4 portrait; margin: 20mm 15mm; } }
    .size-a4.landscape { @page { size: A4 landscape; margin: 15mm 20mm; } }
    
    .size-f4.portrait { @page { size: 215mm 330mm portrait; margin: 20mm 15mm; } }
    .size-f4.landscape { @page { size: 215mm 330mm landscape; margin: 15mm 20mm; } }
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
  .kop-nama-usaha {
    font-weight: bold;
    font-size: 14px;
    text-transform: uppercase;
    line-height: 1.1;
  }
  .kop-nama-kec {
    font-size: 13px;
    text-transform: uppercase;
    line-height: 1.1;
    margin-top: 1px;
  }
  .kop-info-sub {
    font-size: 8.5px;
    color: #3f3f3f;
    line-height: 1.2;
    margin-top: 1px;
  }
  .kop-single-divider {
    border: 0;
    border-top: 2.5px solid #8a8787;
    margin-top: 4px;
    margin-bottom: 15px;
    width: 100%;
  }
  
  .report-content {
    margin-top: 8px; 
    font-size: 12px;
  }

  /* ================= KUNCI OTOMATIS UNTUK SEMUA LAPORAN MASUK SINI ================= */
  
  /* Aturan Header Judul Laporan */
  :deep(.page-header) {
    text-align: center;
    margin-top: 5px;
    margin-bottom: 12px;
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