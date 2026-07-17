<template>
  <div class="cover-page" ref="rootEl">
    <div class="cover-border">
      <div class="cover-header">
        <h1 class="cover-jenis">LAPORAN KEUANGAN</h1>
        <h2 class="cover-periode">{{ periodeLabel }}</h2>
      </div>

      <div class="cover-inner">
        <div class="cover-logo-wrap">
          <img v-if="lembaga?.logo" :src="logoUrl" class="cover-logo" alt="logo" crossorigin="anonymous" />
          <div v-else class="cover-logo-fallback">{{ initials }}</div>
        </div>
      </div>

      <div class="cover-footer">
        <div class="cover-footer-name">{{ (lembaga?.nama || 'UNIT PERDAGANGAN').toUpperCase() }}</div>
        <div class="cover-footer-kab"><b>{{ (lembaga?.alamat || '-').toUpperCase() }}</b></div>
        <div class="cover-footer-sub">SK Kemenkumham RI No.-</div>
        <div class="cover-footer-sub" v-if="lembaga?.telepon">
          <i>Telp.{{ lembaga.telepon }}</i>
        </div>
        <div class="cover-footer-sub" v-if="lembaga?.email">{{ lembaga.email }}</div>
        <div class="cover-footer-year"><i>Tahun {{ periode.tahun || '' }}</i></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  payload: { type: Object, required: true },
  meta: { type: Object, default: () => ({}) },
})

const lembaga = computed(() => props.payload?.lembaga || null)
const periode = computed(() => props.payload?.periode || {})

const periodeLabel = computed(() => {
  const bulan = (periode.value.bulan_name || '').toUpperCase()
  return `BULAN ${bulan} ${periode.value.tahun || ''}`
})

const initials = computed(() => {
  const nama = lembaga.value?.nama || 'PAMSIDES'
  return nama
    .split(' ')
    .slice(0, 2)
    .map((s) => s[0])
    .join('')
    .toUpperCase()
})

const logoUrl = computed(() => {
  const logo = lembaga.value?.logo
  if (!logo) return ''
  if (logo.startsWith('http')) return logo
  const base = import.meta.env.VITE_API_STORAGE_URL || 'http://localhost:8000/storage'
  return `${base}/sop/logo/${logo}`
})
</script>

<style scoped>
.cover-page {
  width: 210mm;
  min-height: 297mm;
  padding: 18mm 18mm;
  background: #ffffff;
  color: #0f172a;
  font-family: 'Inter', 'Segoe UI', sans-serif;
  box-sizing: border-box;
}

.cover-border {
  width: 100%;
  height: 261mm;
  border: 2px solid #8d8d8d;
  position: relative;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.cover-header {
  position: absolute;
  top: 24mm;
width: 100%;  text-align: center;
}

.cover-inner {
  width: 100%;
  height: 100%;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
}

.cover-logo-wrap {
  width: 110px;
  height: 110px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}

.cover-logo {
  max-width: 110px;
  max-height: 110px;
  object-fit: contain;
}

.cover-logo-fallback {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0f172a, #5790e0);
  color: white;
  font-size: 38px;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  letter-spacing: 2px;
  box-shadow: 0 4px 18px rgba(15, 23, 42, 0.2);
}

.cover-jenis {
  font-size: 20pt;
  font-weight: 700;
  margin: 0; /* Tetap 0 */
  letter-spacing: 1px;
  color: #000000;
}

.cover-periode {
  font-size: 18pt;
  font-weight: 500;
  /* Ubah margin-top dari 4px menjadi 0 atau angka negatif */
  margin: -5px 0 0; 
  color: #000000;
}

.cover-divider {
  width: 60%;
  height: 2px;
  background: #000000;
  margin: 14px 0;
}



.cover-alamat {
  font-size: 10pt;
  margin: 0;
  color: #475569;
}

.cover-footer {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  text-align: center;
  padding: 10px 12px 14px;
  border-top: 2px solid #8d8d8d;
  box-sizing: border-box;
}

.cover-footer-name {
  font-size: 13px;
  text-transform: uppercase;
  line-height: 1.2;
  color: #000000;
}

.cover-footer-kab {
  font-size: 13px;
  text-transform: uppercase;
  line-height: 1.2;
  color: #000000;
  margin-top: 1px;
}

.cover-footer-sub {
  font-size: 11px;
  color: #000000;
  line-height: 1.2;
  margin-top: 1px;
}

.cover-footer-year {
  font-size: 11px;
  color: #000000;
  margin-top: 10px;
}
</style> 