<template>
  <BaseReportLayout :lembaga="lembaga" :config="payload?.config" :no-kop="true">
    <template v-if="showMeta">
      <div class="page-header">
        <h2>FORM INPUT PEMAKAIAN AIR</h2>
        <h2 class="mt-1 mb-0 leading-tight" style="font-size: 19px;">
          "TIRTO MULO" BUMDes BANGUN KENCANA
        </h2>
        <p class="page-subtitle" style="font-size: 14px;">KALURAHAN MULO KAPANEWON WONOSARI</p>
        <hr class="kop-single-divider">
      </div>

      <div class="meta-grid">
        <div class="meta-col">
          <div class="meta-row">
            <span class="meta-label">Bulan Pemakaian</span>
            <span class="meta-sep">:</span>
            <span class="meta-value">{{ filter.bulan || '-' }} {{ filter.tahun || '' }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Tanggal Cetak</span>
            <span class="meta-sep">:</span>
            <span class="meta-value">{{ tanggalCetak }}, Pukul {{ jamCetak }}</span>
          </div>
        </div>
        <div class="meta-col meta-col-right">
          <div class="meta-row">
            <span class="met-label">Cater</span>
            <span class="meta-sep">:</span>
            <span class="meta-value">{{ filter.cater || 'Admin' }}</span>
          </div>
          <div class="meta-row">
            <span class="met-label">Dusun</span>
            <span class="meta-sep">:</span>
            <span class="meta-value">{{ dusun }}</span>
          </div>
        </div>
      </div>
    </template>

    <table class="data-table data-table-fixed">
      <colgroup>
        <col style="width: 5%">
        <col style="width: 30%">
        <col style="width: 16%">
        <col style="width: 6%">
        <col style="width: 8%">
        <col style="width: 8%">
        <col style="width: 28%">
      </colgroup>
      <thead>
        <tr>
          <th class="text-center">No</th>
          <th class="text-center">Nama</th>
          <th class="text-center">No. Induk</th>
          <th class="text-center">RT</th>
          <th class="text-center">Awal</th>
          <th class="text-center">Akhir</th>
          <th class="text-center">Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, idx) in items" :key="item.id" class="form-row">
          <td class="text-center">{{ startIndex + idx + 1 }}</td>
          <td class="text-left">{{ item.nama }}</td>
          <td class="text-center">{{ item.customer_code || item.id }}</td>
          <td class="text-center">{{ item.rt || '-' }}</td>
          <td class="text-center">{{ Number(item.meterAwal || 0).toLocaleString('id-ID') }}</td>
          <td class="text-center">
            {{ item.meterAkhir ? Number(item.meterAkhir).toLocaleString('id-ID') : '' }}
          </td>
          <td class="text-left">{{ item.keterangan || '' }}</td>
        </tr>
        <tr v-if="!items || items.length === 0">
          <td colspan="7" class="empty">Tidak ada data pelanggan pada dusun ini.</td>
        </tr>
      </tbody>
    </table>
  </BaseReportLayout>
</template>

<script setup>
import { computed } from 'vue'
import BaseReportLayout from '@/presentations/views/app/admin/pelaporan/layouts/BaseReportLayout.vue'

const props = defineProps({
  payload: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) },
})

const items = computed(() => props.payload?.items || [])
const dusun = computed(() => props.payload?.dusun || '-')
const filter = computed(() => props.payload?.filter || {})
const lembaga = computed(() => props.payload?.lembaga || {})
const startIndex = computed(() => Number(props.payload?.startIndex || 0))
const showMeta = computed(() => props.payload?.showMeta !== false)

const now = new Date()

const tanggalCetak = computed(() =>
  now.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
)

const jamCetak = computed(() =>
  now.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })
)
</script>

<style scoped>
.meta-grid {
  display: flex;
  justify-content: space-between;
  align-items: flex-end; /* Menyelaraskan posisi Dusun ke bawah agar sejajar dengan Cater */
  gap: 12px;
  margin: 4px 0 6px;
  font-size: 12px;
}

.meta-col {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.meta-col-right {
  justify-content: flex-end; /* Memastikan isi kolom kanan turun ke bawah sejajar Cater */
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

.meta-label {
  min-width: 95px;
  display: inline-block;
}
.met-label {
  min-width: 40px;
  display: inline-block;
}
.meta-sep {
  font-weight: 700;
}

.meta-value {
  font-weight: 600;
}

/* Styling Tabel & Font Size 11px (samakan dengan cetak tagihan) */
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.data-table-fixed {
  table-layout: fixed;
}

.data-table-fixed th,
.data-table-fixed td {
  box-sizing: border-box;
  padding: 2px 4px;
  vertical-align: middle;
}

.data-table-fixed th {
  white-space: nowrap;
}

.data-table-fixed td {
  white-space: normal;
  word-break: break-word;
  overflow-wrap: anywhere;
  font-size: 11px;
  line-height: 1.2;
}

.data-table th,
.data-table td {
  border: 1px solid #000;
  font-size: 11px;
  line-height: 1.2;
}

.text-left {
  text-align: left !important;
}

.text-center {
  text-align: center !important;
}

.kop-single-divider {
  border: 0;
  border-top: 2.5px solid #000000;
  margin-top: 4px;
  margin-bottom: 15px;
  width: 100%;
}

.empty {
  text-align: center;
  font-style: italic;
  padding: 16px !important;
  color: #000000;
}

.form-row {
  height: auto;
}

.form-row td {
  padding: 2px 4px !important;
}

/* Header tabel berulang di setiap halaman saat print */
.data-table thead {
  display: table-header-group;
}

.data-table tfoot {
  display: table-footer-group;
}

/* Cegah satu baris form terpotong di antara halaman */
.data-table tbody tr {
  page-break-inside: avoid;
  break-inside: avoid;
}

/* Page-break setelah .report-page */
.report-page,
.report-page.surat-page {
  page-break-after: always;
  break-after: page;
}

.report-page:last-child {
  page-break-after: auto;
  break-after: auto;
}
</style>

<style>
.report-page.surat-page {
  padding: 60px !important;
}

@media print {
  .report-page.surat-page {
    padding: 0 !important;
  }
  .report-page.surat-page.size-a4.portrait {
    width: 210mm !important;
    height: 297mm !important;
    min-height: 297mm !important;
    margin: 0 !important;
  }
}
</style>