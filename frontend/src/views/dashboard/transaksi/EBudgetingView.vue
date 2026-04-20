<template>
  <div>
    <ContentCard variant="elevated" padding="small">
      <div class="flex flex-col lg:flex-row gap-2 items-end">
        <div class="flex-1 w-full">
          <SelectSearch
            v-model="selectedTahun"
            :options="tahunOptions"
            placeholder="Pilih Tahun Anggaran"
            icon="calendar"
            no-margin
          />
        </div>
        <div class="flex-1 w-full">
          <SelectSearch
            v-model="selectedBulan"
            :options="bulanOptions"
            placeholder="Pilih Bulan Anggaran"
            icon="calendar-check"
            no-margin
          />
        </div>
        <div class="w-full lg:w-auto">
          <BaseButton
            variant="secondary"
            class="w-full! lg:px-10! h-[44px]! rounded-xl!"
            icon="plus-circle"
            :disabled="isProcessing"
            @click="simpanRencanaAnggaran"
          >
            Tentukan Rencana Anggaran
          </BaseButton>
        </div>
      </div>
    </ContentCard>

    <AppNotification
      v-bind="notificationState"
      @close="notificationState.show = false"
      @confirm="notificationState.show = false"
      @cancel="notificationState.show = false"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useNotification } from '@/composables/useNotification'
import AppNotification from '@/components/ui/AppNotification.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'

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
