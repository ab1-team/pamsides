<template>
  <div class="pelaporan-preview">
    <div v-if="loading" class="pelaporan-preview__state">
      <font-awesome-icon icon="spinner" spin class="text-3xl! text-blue-500!" />
      <p>Menyiapkan pratinjau laporan...</p>
    </div>

    <div v-else-if="errorMsg" class="pelaporan-preview__state pelaporan-preview__state--error">
      <font-awesome-icon icon="circle-exclamation" class="text-3xl! text-rose-500!" />
      <p>{{ errorMsg }}</p>
    </div>

    <iframe
      v-else
      :src="pdfUrl"
      class="pelaporan-preview__frame"
      :title="`Preview Laporan - ${queryParams.nama_laporan || 'Laporan'} - ${queryParams.tahun}`"
    ></iframe>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { useRoute } from 'vue-router'
import pelaporanService from '@/services/pelaporan.service.js'

const route = useRoute()

const loading = ref(true)
const errorMsg = ref('')
const objectUrl = ref('')

const queryParams = computed(() => ({
  tahun: String(route.query.tahun || ''),
  bulan: String(route.query.bulan || ''),
  tanggal: String(route.query.tanggal || ''),
  nama_laporan: String(route.query.nama_laporan || ''),
  nama_sub_laporan: String(route.query.nama_sub_laporan || ''),
}))

const pdfUrl = computed(() => objectUrl.value)

const loadPreview = async () => {
  loading.value = true
  errorMsg.value = ''
  try {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value)
      objectUrl.value = ''
    }
    const blob = await pelaporanService.getPreview(queryParams.value)
    objectUrl.value = URL.createObjectURL(blob)

    const judul = queryParams.value.nama_laporan || 'Laporan'
    document.title = `Preview Laporan - ${judul} - ${queryParams.value.tahun || ''}`
  } catch (err) {
    console.error('Gagal memuat preview laporan:', err)
    errorMsg.value =
      err?.response?.data?.message ||
      'Gagal memuat pratinjau laporan. Periksa parameter filter Anda.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPreview()
})

onBeforeUnmount(() => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = ''
  }
})
</script>

<style>
html,
body,
#app {
  margin: 0;
  padding: 0;
  background: #f1f5f9;
}
</style>

<style scoped>
.pelaporan-preview {
  position: fixed;
  inset: 0;
  display: flex;
  flex-direction: column;
  background: #f1f5f9;
}

.pelaporan-preview__frame {
  flex: 1;
  width: 100%;
  height: 100%;
  border: 0;
  background: #ffffff;
}

.pelaporan-preview__state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #475569;
  font-size: 14px;
}

.pelaporan-preview__state--error {
  color: #b91c1c;
}
</style>
