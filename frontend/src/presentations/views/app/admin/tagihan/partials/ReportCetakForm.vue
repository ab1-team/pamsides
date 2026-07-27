<template>
  <BaseReportLayout :lembaga="lembaga" :config="payload?.config" :no-kop="true">
    <div class="page-header">
      <h2>FORM INPUT PEMAKAIAN AIR</h2>
      <h2 class="mt-1 mb-0 leading-tight" style="font-size: 19px;">
        "TIRTO MULO" BUMDes BANGUN KENCANA
      </h2>
      <p class="page-subtitle" style="font-size: 14px;">KALURAHAN MULO KAPANEWON WONOSARI</p>
      <hr class="kop-single-divider">
    </div>

    <!-- Meta Grid: Kiri (Bulan & Cater), Kanan (Dusun sejajar Cater) -->
    <div class="meta-grid">
      <div class="meta-col">
        <div class="meta-row">
          <span class="meta-label">Bulan Pemakaian</span>
          <span class="meta-sep">:</span>
          <span class="meta-value">{{ filter.bulan || '-' }} {{ filter.tahun || '' }}</span>
        </div>
        <div class="meta-row">
          <span class="meta-label">Cater</span>
          <span class="meta-sep">:</span>
          <span class="meta-value">{{ filter.cater || 'Admin' }}</span>
        </div>
      </div>
      <div class="meta-col meta-col-right">
        <div class="meta-row">
          <span class="met-label">Dusun</span>
          <span class="meta-sep">:</span>
          <span class="meta-value">{{ dusun }}</span>
        </div>
      </div>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th width="4%" class="text-center">No</th>
          <th width="22%" class="text-center">Nama</th>
          <th width="14%" class="text-center">No. Induk</th>
          <th width="6%" class="text-center">RT</th>
          <th width="7%" class="text-center">Awal</th>
          <th width="7%" class="text-center">Akhir</th>
          <th width="20%" class="text-center">Keterangan</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, idx) in items" :key="item.id" class="form-row">
          <td class="text-center">{{ idx + 1 }}</td>
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

/* Styling Tabel & Font Size 12px */
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

.data-table th,
.data-table td {
  border: 1px solid #000;
  font-size: 12px;
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
  height: 22px;
}

.form-row td {
  padding: 2px 4px !important;
}
</style>

<style>
.report-page.surat-page {
  padding: 60px 90px !important;
}
</style>