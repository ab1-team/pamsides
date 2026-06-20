<template>
  <div class="laporan-page laporan-page--cover">
    <div class="laporan-paper">
      <div class="laporan-cover">
        <div class="laporan-cover__brand">
          <div class="laporan-cover__logo">
            <font-awesome-icon icon="water" />
          </div>
          <div class="laporan-cover__org">
            <h2>BUMDes BANGUN KENCANA</h2>
            <p>"TIRTO MULO"</p>
          </div>
        </div>

        <div class="laporan-cover__divider"></div>

        <div class="laporan-cover__title">
          <p class="laporan-cover__kategori">{{ kategori }}</p>
          <h1>{{ judulLaporan }}</h1>
          <h2 v-if="subJudul">{{ subJudul }}</h2>
          <p class="laporan-cover__periode">Periode {{ periode }}</p>
        </div>

        <div class="laporan-cover__divider laporan-cover__divider--bottom"></div>

        <div class="laporan-cover__meta">
          <div class="laporan-cover__row">
            <span>Desa</span>
            <strong>{{ desa || 'Mulo' }}</strong>
          </div>
          <div class="laporan-cover__row">
            <span>Kecamatan</span>
            <strong>{{ kecamatan || 'Wonosari' }}</strong>
          </div>
          <div class="laporan-cover__row">
            <span>Kabupaten</span>
            <strong>{{ kabupaten || 'Gunungkidul' }}</strong>
          </div>
          <div class="laporan-cover__row">
            <span>Tahun Buku</span>
            <strong>{{ tahun }}</strong>
          </div>
        </div>

        <div class="laporan-cover__footer">
          <p>Dokumen ini dicetak secara otomatis oleh sistem Pamsides</p>
          <p class="laporan-cover__date">{{ tanggalCetak }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  judulLaporan: { type: String, default: 'Laporan Keuangan' },
  subJudul: { type: String, default: '' },
  kategori: { type: String, default: 'Laporan Tahunan' },
  tahun: { type: [String, Number], required: true },
  bulan: { type: String, default: '' },
  tanggal: { type: String, default: '' },
  desa: { type: String, default: 'Mulo' },
  kecamatan: { type: String, default: 'Wonosari' },
  kabupaten: { type: String, default: 'Gunungkidul' },
  tanggalCetak: { type: String, default: '' },
})

const bulanLabel = {
  '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
  '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
  '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember',
}

const periode = computed(() => {
  if (props.tanggal && props.bulan) {
    return `${props.tanggal} ${bulanLabel[props.bulan] || props.bulan} ${props.tahun}`
  }
  if (props.bulan) {
    return `${bulanLabel[props.bulan] || props.bulan} ${props.tahun}`
  }
  return `Tahun ${props.tahun}`
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
  padding: 0;
}

.laporan-cover {
  height: 297mm;
  padding: 30mm 25mm;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border: 6px double #1e3a8a;
  margin: 8mm;
  position: relative;
}

.laporan-cover__brand {
  display: flex;
  align-items: center;
  gap: 16px;
  text-align: left;
}

.laporan-cover__logo {
  width: 64px;
  height: 64px;
  border-radius: 14px;
  background: linear-gradient(135deg, #1e40af 0%, #06b6d4 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  box-shadow: 0 6px 18px rgba(30, 64, 175, 0.3);
}

.laporan-cover__org h2 {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  letter-spacing: 0.5px;
}

.laporan-cover__org p {
  font-size: 13px;
  font-weight: 600;
  color: #1e3a8a;
  margin: 2px 0 0;
  letter-spacing: 2px;
}

.laporan-cover__divider {
  height: 2px;
  background: linear-gradient(90deg, #1e3a8a 0%, #06b6d4 100%);
  margin: 16px 0;
}

.laporan-cover__divider--bottom {
  margin: 0 0 16px;
}

.laporan-cover__title {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  text-align: center;
  padding: 30mm 0;
}

.laporan-cover__kategori {
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 4px;
  color: #64748b;
  text-transform: uppercase;
  margin: 0 0 12px;
}

.laporan-cover__title h1 {
  font-size: 30px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 8px;
  line-height: 1.2;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.laporan-cover__title h2 {
  font-size: 18px;
  font-weight: 600;
  color: #1e3a8a;
  margin: 0 0 20px;
}

.laporan-cover__periode {
  font-size: 14px;
  font-weight: 500;
  color: #475569;
  margin: 0;
  font-style: italic;
}

.laporan-cover__meta {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 32px;
  padding: 0 8mm;
}

.laporan-cover__row {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  border-bottom: 1px dashed #cbd5e1;
  padding: 6px 0;
}

.laporan-cover__row span {
  color: #64748b;
}

.laporan-cover__row strong {
  color: #0f172a;
  font-weight: 700;
}

.laporan-cover__footer {
  text-align: center;
  margin-top: 16px;
}

.laporan-cover__footer p {
  font-size: 11px;
  color: #94a3b8;
  margin: 4px 0;
}

.laporan-cover__date {
  font-weight: 600;
  color: #475569 !important;
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
  .laporan-cover {
    margin: 0 !important;
    border-width: 4px !important;
  }
  @page {
    margin: 0;
    size: A4 portrait;
  }
}
</style>