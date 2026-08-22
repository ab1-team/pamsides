<template>
  <BaseReportLayout :lembaga="lembaga" :config="payload?.config" :no-kop="true">
    <div class="page-header">
      <h2>DAFTAR TAGIHAN PEMAKAIAN AIR</h2>
      <h2 class="mt-1 mb-0 leading-tight " style="font-size: 19px;">
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
          <span class="meta-label">Cater</span>
          <span class="meta-sep">:</span>
          <span class="meta-value">{{ filter.cater || 'Admin' }}</span>
        </div>
      </div>
      <div class="meta-col meta-col-right">
        <div class="meta-row">
          <span class="meta-label">Tgl Akhir Pembayaran</span>
          <span class="meta-sep">:</span>
          <span class="meta-value">{{ tanggalAkhir }}</span>
        </div>
        <div class="meta-row">
          <span class="meta-label">Dusun</span>
          <span class="meta-sep">:</span>
          <span class="meta-value">{{ dusun }}</span>
        </div>
      </div>
    </div>

    <table class="data-table">
      <thead>
        <tr>
          <th width="4%" class="text-center">No</th>
          <th width="22%" class="text-left">Nama</th>
          <th width="16%" class="text-center">No. Induk</th>
          <th width="7%" class="text-center">Awal</th>
          <th width="7%" class="text-center">Akhir</th>
          <th width="12%" class="text-center">Pemakaian</th>
          <th width="11%" class="text-center">Status</th>
          <th width="15%" class="text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, idx) in items" :key="item.id">
          <td class="text-center">{{ idx + 1 }}</td>
          <td class="text-left">{{ item.nama }}</td>
          <td class="text-center">{{ item.customer_code || item.id }}</td>
          <td class="text-center">{{ Number(item.meterAwal || 0).toLocaleString('id-ID') }}</td>
          <td class="text-center">{{ Number(item.meterAkhir || 0).toLocaleString('id-ID') }}</td>
          <td class="text-center">{{ item.pemakaian }}</td>
          <td class="text-center uppercase">{{ item.status }}</td>
          <td class="text-right">
            {{
              Number(item.tagihan || 0).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
            }}
          </td>
        </tr>
        <tr v-if="!items || items.length === 0">
          <td colspan="8" class="empty">Tidak ada data pelanggan pada dusun ini.</td>
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

const bulans = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const bulanIndex = (val) => {
  if (val === '' || val == null) return null
  if (typeof val === 'number') return Math.max(1, Math.min(12, val))
  const idx = bulans.findIndex((b) => b.toLowerCase() === String(val).toLowerCase())
  return idx >= 0 ? idx + 1 : null
}

const tanggalAkhir = computed(() => {
  const withDue = items.value.find((it) => it.jatuhTempo)
  if (!withDue) {
    const f = filter.value
    const m = bulanIndex(f.bulan)
    const y = parseInt(f.tahun, 10)
    if (!m || !Number.isFinite(y)) return '-'
    const next = m === 12 ? { m: 1, y: y + 1 } : { m: m + 1, y }
    return `26 ${bulans[next.m - 1]} ${next.y}`
  }
  const d = new Date(withDue.jatuhTempo)
  if (Number.isNaN(d.getTime())) return '-'
  d.setDate(d.getDate() - 1)
  return d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
})
</script>

<style scoped>
.meta-grid {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin: 8px 0 12px;
  font-size: 12px;
}

.meta-col {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px; /* Set font size tabel menjadi 12px */
}

.data-table th,
.data-table td {
  border: 1px solid #000;
  font-size: 12px; /* Memastikan isi tabel 12px */
}
.meta-col-right {
  text-align: left;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Mengatur label sebelah kanan agar rata kiri sejajar vertikal dan memiliki lebar tetap */
.meta-col-right .meta-label {
  min-width: 135px; 
  display: inline-block;
}

.meta-label {
  min-width: 95px;
  display: inline-block;
}

.meta-sep {
  font-weight: 700;
}

.meta-value {
  font-weight: 600;
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
.text-right {
  text-align: right !important;
  padding-right: 6px !important;
}

.uppercase {
  text-transform: uppercase;
}

.empty {
  text-align: center;
  font-style: italic;
  padding: 16px !important;
  color: #000000;
}

/* Override padding BaseReportLayout untuk cetak daftar tagihan (lebih ramping) */
</style>

<style>
.report-page.surat-page {
  padding: 60px 90px !important;
}
</style>