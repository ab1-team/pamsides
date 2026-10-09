<template>
  <ContentCard
    variant="bordered"
    padding="none"
    rounded="2xl"
    class="h-full! flex! flex-col! overflow-hidden! shadow-sm!"
  >
    <DataTable
      v-model="searchQuery"
      :data="itemsList"
      :columns="columns"
      title="Detail Arsip Tunggakan"
      searchPlaceholder="Cari nama atau nomor pelanggan..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="serverTotal"
      :total-pages="totalPages"
      :show-entries="true"
      :no-card="true"
      server-side
      :loading="loading"
      empty-title="Tidak Ada Tunggakan"
      empty-message="Belum ada pelanggan dengan tunggakan denda. Semua tagihan sudah lunas."
      empty-icon="file-invoice-dollar"
    >
      <template #column-nomorInduk="{ row }">
        <span class="text-[12px] text-slate-600 font-medium font-mono whitespace-nowrap">
          {{ row.nomorInduk }}
        </span>
      </template>
      <template #column-periodeLabel="{ row }">
        <span class="text-[12px] text-slate-600 font-medium whitespace-nowrap">
          {{ row.periodeLabel }}
        </span>
      </template>
      <template #column-tagihan="{ row }">
        <span
          :class="[
            'text-[12px] font-semibold font-mono whitespace-nowrap',
            row.tagihan > 0 ? 'text-slate-700' : 'text-slate-400',
          ]"
        >
          {{ formatRupiah(row.tagihan) }}
        </span>
      </template>
      <template #column-denda="{ row }">
        <span
          :class="[
            'text-[12px] font-semibold font-mono whitespace-nowrap',
            row.denda > 0 ? 'text-rose-600' : 'text-slate-400',
          ]"
        >
          {{ formatRupiah(row.denda) }}
        </span>
      </template>
      <template #column-total="{ row }">
        <span class="text-[12px] text-slate-800 font-bold font-mono whitespace-nowrap">
          {{ formatRupiah(row.total) }}
        </span>
      </template>
      <!-- Selalu "Belum Lunas": query backend sudah di-filter status='unpaid'. -->
      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md bg-rose-50 text-rose-600"
        >
          {{ row.status || 'Belum Lunas' }}
        </span>
      </template>
    </DataTable>
  </ContentCard>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import { formatRupiah } from '@/composables/useFormatCurrency'
import dashboardService from '@/services/dashboard.service'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const loading = ref(false)
const serverTotal = ref(0)
const totalPages = ref(1)

// Kolom nominal & tanggal rata kanan; header DataTable default text-left, jadi
// perlu `!` agar override benar-benar menang (sama seperti KelasIndex/detailPemakaianAir).
const columns = [
  { key: 'nomorInduk', title: 'No. Pelanggan' },
  { key: 'customer', title: 'Nama' },
  { key: 'alamat', title: 'Alamat' },
  {
    key: 'periodeLabel',
    title: 'Periode',
    thClass: 'whitespace-nowrap!',
  },
  {
    key: 'tagihan',
    title: 'Tagihan',
    thClass: 'text-right!',
    tdClass: 'text-right!',
  },
  {
    key: 'denda',
    title: 'Denda',
    thClass: 'text-right!',
    tdClass: 'text-right!',
  },
  {
    key: 'total',
    title: 'Total Tunggakan',
    thClass: 'text-right!',
    tdClass: 'text-right!',
  },
  { key: 'status', title: 'Status' },
]

const itemsList = ref([])

// Pakai endpoint ringan DashboardController::popupData?type=tunggakan
// (filter & paginasi di SERVER — sebelumnya getAllBills() menarik semua halaman)
const fetchUnpaidBills = async () => {
  try {
    loading.value = true
    const response = await dashboardService.getPopupData('tunggakan', {
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value,
    })
    if (response?.success) {
      itemsList.value = response.data || []
      serverTotal.value = response.meta?.total ?? itemsList.value.length
      totalPages.value = response.meta?.last_page ?? 1
    }
  } catch (error) {
    // silent
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchUnpaidBills()
})

// Re-fetch saat user ganti halaman, ubah per_page, atau mengetik search
// (searchQuery: debounce di watch manual karena default v-model langsung emit tiap ketukan)
let searchTimer = null
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1 // reset ke page 1 saat search berubah
    fetchUnpaidBills()
  }, 300)
})

watch([currentPage, perPage], () => {
  fetchUnpaidBills()
})
</script>
