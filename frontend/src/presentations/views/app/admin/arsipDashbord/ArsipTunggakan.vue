<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="h-full bg-white flex flex-col pt-2 pb-4">
    <DataTable
      v-model="searchQuery"
      :data="itemsList"
      :columns="columns"
      title="Detail Arsip Tunggakan"
      searchPlaceholder="Cari nama atau nomor induk..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="serverTotal"
      :total-pages="totalPages"
      :show-entries="true"
      :no-card="true"
      server-side
      :loading="loading"
    >
      <template #column-tagihan="{ row }">
        <span class="font-semibold text-slate-700">
          {{ formatCurrency(row.tagihan) }}
        </span>
      </template>
      <template #column-denda="{ row }">
        <span :class="['font-semibold', row.denda > 0 ? 'text-rose-600' : 'text-slate-400']">
          {{ formatCurrency(row.denda) }}
        </span>
      </template>
      <template #column-total="{ row }">
        <span class="font-bold text-slate-800">
          {{ formatCurrency(row.total) }}
        </span>
      </template>
      <template #column-status>
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md bg-rose-50 text-rose-600"
        >
          Belum Lunas
        </span>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import dashboardService from '@/services/dashboard.service'

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount)
}

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const loading = ref(false)
const serverTotal = ref(0)
const totalPages = ref(1)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'periodeLabel', title: 'Periode' },
  { key: 'tagihan', title: 'Tagihan' },
  { key: 'denda', title: 'Denda' },
  { key: 'total', title: 'Total Tunggakan' },
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
