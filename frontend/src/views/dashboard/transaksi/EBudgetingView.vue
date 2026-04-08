<template>
  <div>
    <ContentCard variant="bordered" padding="normal" hoverable>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
        <div class="w-full">
          <SelectSearch v-model="selectedTahun" :options="tahunOptions" placeholder="Pilih Tahun" />
        </div>
        <div class="w-full">
          <SelectSearch v-model="selectedBulan" :options="bulanOptions" placeholder="Pilih Bulan" />
        </div>
        <button
          @click="simpanRencanaAnggaran"
          :disabled="!selectedTahun || !selectedBulan || isProcessing"
          class="w-full sm:w-auto h-[38px] px-4 flex items-center justify-center bg-blue-600 text-white border border-blue-600 rounded-lg text-sm font-medium transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
        >
          {{ isProcessing ? 'Memproses...' : 'Tentukan Rencana Anggaran' }}
        </button>
      </div>
    </ContentCard>

    <AppNotification
      v-bind="notificationState"
      @close="() => {}"
      @confirm="() => {}"
      @cancel="() => {}"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useNotification } from '@/composables/useNotification'
import AppNotification from '@/components/ui/AppNotification.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import ContentCard from '@/components/ui/ContentCard.vue'

const { notificationState, success, error } = useNotification()

const selectedTahun = ref('')
const selectedBulan = ref('')
const isProcessing = ref(false)

const tahunOptions = computed(() => {
  const current = new Date().getFullYear()
  return Array.from({ length: 10 }, (_, i) => ({
    id: current - i,
    text: (current - i).toString(),
  }))
})

const bulanOptions = [
  { id: 1, text: 'Januari' },
  { id: 2, text: 'Februari' },
  { id: 3, text: 'Maret' },
  { id: 4, text: 'April' },
  { id: 5, text: 'Mei' },
  { id: 6, text: 'Juni' },
  { id: 7, text: 'Juli' },
  { id: 8, text: 'Agustus' },
  { id: 9, text: 'September' },
  { id: 10, text: 'Oktober' },
  { id: 11, text: 'November' },
  { id: 12, text: 'Desember' },
]

const simpanRencanaAnggaran = async () => {
  if (!selectedTahun.value || !selectedBulan.value) {
    error('Tidak Valid', 'Pilih tahun dan bulan terlebih dahulu!')
    return
  }

  try {
    isProcessing.value = true
    await new Promise((resolve) => setTimeout(resolve, 800))
    const bulan = bulanOptions.find((b) => b.id === selectedBulan.value)?.text ?? ''
    success('Berhasil!', `Rencana anggaran ${bulan} ${selectedTahun.value} berhasil disimpan.`)
  } catch (err) {
    console.error('Error menyimpan rencana anggaran:', err)
    error('Kesalahan', 'Gagal menyimpan rencana anggaran.')
  } finally {
    isProcessing.value = false
  }
}
</script>
