<template>
  <div class="report-page cover-page" ref="rootEl">
    <div class="cover-border">
      <div class="cover-inner">
        <div class="cover-logo-wrap">
          <img
            v-if="lembaga?.logo"
            :src="logoUrl"
            class="cover-logo"
            alt="logo"
            crossorigin="anonymous"
          />
          <div v-else class="cover-logo-fallback">
            {{ initials }}
          </div>
        </div>

        <h1 class="cover-jenis">LAPORAN KEUANGAN</h1>
        <h2 class="cover-periode">{{ periodeLabel }}</h2>

        <div class="cover-divider"></div>

        <h3 class="cover-lembaga">{{ lembaga?.nama || 'PAMSIMAS' }}</h3>
        <p class="cover-alamat">{{ lembaga?.alamat || '-' }}</p>
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
  /* MENGGANTI: Garis bingkai luar menjadi hitam */
  border: 2px solid #000000; 
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24mm 18mm;
}

.cover-inner {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
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
  /* MENGGANTI: Gradasi fallback logo menggunakan hitam-abu gelap agar tetap elegan */
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
  font-size: 32pt;
  font-weight: 800;
  margin: 0;
  letter-spacing: 1px;
  color: #000000;
}

.cover-periode {
  font-size: 16pt;
  font-weight: 500;
  margin: 4px 0 12px;
  color: #1f2937;
}

.cover-divider {
  width: 60%;
  height: 2px;
  /* MENGGANTI: Garis pembatas tengah menjadi hitam */
  background: #000000;
  margin: 14px 0;
}

.cover-lembaga {
  font-size: 14pt;
  font-weight: 700;
  margin: 0;
  /* MENGGANTI: Warna teks nama lembaga menjadi hitam teks */
  color: #0f172a;
}

.cover-alamat {
  font-size: 10pt;
  margin: 0;
  color: #475569;
}
</style> 