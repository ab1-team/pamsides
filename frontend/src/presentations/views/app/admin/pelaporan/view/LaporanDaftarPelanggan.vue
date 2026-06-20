<template>
  <div class="laporan-page">
    <div class="laporan-paper">
      <div class="laporan-container">
        <div class="laporan-header">
          <div class="laporan-header__brand">
            <div class="laporan-header__logo">
              <font-awesome-icon icon="water" />
            </div>
            <div class="laporan-header__org">
              <h2>BUMDes BANGUN KENCANA "TIRTO MULO"</h2>
              <p>Kalurahan Mulo, Kapanewon Wonosari, Kabupaten Gunungkidul</p>
            </div>
          </div>
          <div class="laporan-header__divider"></div>
          <h1 class="laporan-header__title">{{ judul }}</h1>
          <div class="laporan-header__meta">
            <span><strong>Periode:</strong> {{ periode }}</span>
            <span v-if="dusun"><strong>Dusun:</strong> {{ dusun }}</span>
            <span><strong>Total Pelanggan:</strong> {{ total }}</span>
          </div>
        </div>

        <table class="laporan-table">
          <thead>
            <tr>
              <th style="width: 32px">No</th>
              <th>No. Induk</th>
              <th>Nama Pelanggan</th>
              <th style="width: 60px">RT</th>
              <th>Dusun</th>
              <th>Alamat</th>
              <th style="width: 70px">Golongan</th>
              <th style="width: 80px">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, idx) in rows" :key="idx">
              <td class="text-center">{{ idx + 1 }}</td>
              <td class="font-mono text-center">{{ row.no_induk || '-' }}</td>
              <td>{{ row.nama || row.nama_pelanggan || '-' }}</td>
              <td class="text-center">{{ row.rt || '-' }}</td>
              <td>{{ row.dusun || '-' }}</td>
              <td>{{ row.alamat || '-' }}</td>
              <td class="text-center">{{ row.golongan || '-' }}</td>
              <td class="text-center">
                <span :class="['laporan-status', `laporan-status--${row.status?.toLowerCase()}`]">
                  {{ row.status || '-' }}
                </span>
              </td>
            </tr>
            <tr v-if="!rows || rows.length === 0">
              <td colspan="8" class="text-center laporan-empty">
                Tidak ada data pelanggan pada periode ini.
              </td>
            </tr>
          </tbody>
          <tfoot v-if="rows && rows.length > 0">
            <tr>
              <td colspan="7" class="text-right"><strong>Total Pelanggan Aktif</strong></td>
              <td class="text-center"><strong>{{ totalAktif }}</strong></td>
            </tr>
          </tfoot>
        </table>

        <div class="laporan-footer">
          <div class="laporan-footer__ttd">
            <p>{{ tempat }}, {{ tanggalCetak }}</p>
            <p>Pengelola Pamsides</p>
            <div class="laporan-footer__space"></div>
            <p class="laporan-footer__name">{{ penandatangan }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  judul: { type: String, default: 'Daftar Pelanggan Pamsides' },
  tahun: { type: [String, Number], required: true },
  bulan: { type: String, default: '' },
  tanggal: { type: String, default: '' },
  dusun: { type: String, default: '' },
  rows: { type: Array, default: () => [] },
  tempat: { type: String, default: 'Mulo' },
  tanggalCetak: { type: String, default: '' },
  penandatangan: { type: String, default: '( ___________________ )' },
})

const bulanLabel = {
  '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
  '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
  '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember',
}

const periode = computed(() => {
  if (props.bulan) return `${bulanLabel[props.bulan] || props.bulan} ${props.tahun}`
  return `Tahun ${props.tahun}`
})

const total = computed(() => props.rows?.length || 0)

const totalAktif = computed(() => {
  if (!props.rows) return 0
  return props.rows.filter((r) => (r.status || '').toLowerCase() === 'aktif').length
})
</script>

<style scoped>
.laporan-page {
  background: #f1f5f9;
  padding: 24px;
  display: flex;
  justify-content: center;
  font-family: 'Inter', Arial, sans-serif;
}

.laporan-paper {
  width: 210mm;
  min-height: 297mm;
  background: #ffffff;
  box-shadow: 0 8px 32px rgba(15, 23, 42, 0.12);
}

.laporan-container {
  padding: 18mm 16mm;
}

.laporan-header {
  text-align: center;
  margin-bottom: 18px;
}

.laporan-header__brand {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-bottom: 6px;
}

.laporan-header__logo {
  width: 46px;
  height: 46px;
  border-radius: 10px;
  background: linear-gradient(135deg, #1e40af 0%, #06b6d4 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.laporan-header__org h2 {
  font-size: 14px;
  font-weight: 800;
  margin: 0;
  letter-spacing: 0.5px;
  color: #0f172a;
}

.laporan-header__org p {
  font-size: 10px;
  color: #475569;
  margin: 2px 0 0;
}

.laporan-header__divider {
  height: 2px;
  background: #1e3a8a;
  margin: 10px 0;
}

.laporan-header__title {
  font-size: 14px;
  font-weight: 800;
  margin: 8px 0;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.laporan-header__meta {
  display: flex;
  justify-content: center;
  gap: 24px;
  font-size: 11px;
  color: #334155;
  margin-top: 6px;
}

.laporan-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 10px;
  margin-top: 8px;
}

.laporan-table th {
  background: #e0e7ff;
  color: #1e3a8a;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  padding: 8px 6px;
  border: 1px solid #1e3a8a;
  text-align: center;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.laporan-table td {
  padding: 6px;
  border: 1px solid #cbd5e1;
  vertical-align: middle;
}

.laporan-table tbody tr:nth-child(even) td {
  background: #f8fafc;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.laporan-table tfoot td {
  background: #f1f5f9;
  border: 1px solid #1e3a8a;
  padding: 8px;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

.laporan-status {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: 700;
  text-transform: uppercase;
}

.laporan-status--aktif {
  background: #dcfce7;
  color: #166534;
}

.laporan-status--nonaktif,
.laporan-status--blokir {
  background: #fee2e2;
  color: #991b1b;
}

.laporan-status--putus,
.laporan-status--cabut {
  background: #fef3c7;
  color: #92400e;
}

.laporan-empty {
  padding: 24px !important;
  font-style: italic;
  color: #94a3b8;
}

.text-center {
  text-align: center;
}
.text-right {
  text-align: right;
}
.font-mono {
  font-family: 'Courier New', monospace;
}

.laporan-footer {
  margin-top: 28px;
  display: flex;
  justify-content: flex-end;
}

.laporan-footer__ttd {
  text-align: center;
  font-size: 11px;
  width: 200px;
}

.laporan-footer__space {
  height: 60px;
}

.laporan-footer__name {
  font-weight: 700;
  text-decoration: underline;
}

@media print {
  .laporan-page {
    background: #fff !important;
    padding: 0 !important;
  }
  .laporan-paper {
    box-shadow: none !important;
    width: 100% !important;
  }
  @page {
    margin: 0;
    size: A4 portrait;
  }
}
</style>