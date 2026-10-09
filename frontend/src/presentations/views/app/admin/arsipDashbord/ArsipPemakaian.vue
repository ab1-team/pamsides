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
      title="Detail Arsip Pemakaian"
      searchPlaceholder="Cari nama atau nomor pelanggan..."
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
        <span class="font-semibold text-[12px] text-slate-700 font-mono whitespace-nowrap">
          {{ row.total != null ? formatRupiah(row.total) : '-' }}
        </span>
      </template>
      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md"
          :class="
            row.status === 'paid'
              ? 'bg-emerald-50 text-emerald-600'
              : row.status === 'unpaid'
                ? 'bg-amber-50 text-amber-600'
                : 'bg-slate-100 text-slate-500'
          "
        >
          {{
            row.status === 'paid'
              ? 'Sudah Dicatat'
              : row.status === 'unpaid'
                ? 'Belum Lunas'
                : 'Belum Dicatat'
          }}
        </span>
      </template>
    </DataTable>
  </ContentCard>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import dashboardService from '@/services/dashboard.service'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const loading = ref(false)
const serverTotal = ref(0)
const totalPages = ref(1)

const formatRupiah = (value) => {
  const n = Number(value) || 0
  return `Rp ${n.toLocaleString('id-ID')}`
}

const columns = [
  { key: 'nomorInduk', title: 'No. Pelanggan' },
  { key: 'customer', title: 'Nama' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'periodeLabel', title: 'Periode' },
  { key: 'total', title: 'Tagihan' },
  { key: 'status', title: 'Status' },
]

const itemsList = ref([])

// Pakai endpoint ringan DashboardController::popupData?type=pemakaian
// (sebelumnya: 2 endpoint paralel + getAllBills loop semua halaman)
const fetchUsageData = async () => {
  try {
    loading.value = true
    const now = new Date()
    const response = await dashboardService.getPopupData('pemakaian', {
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value,
      month: now.getMonth() + 1,
      year: now.getFullYear(),
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
  fetchUsageData()
})

let searchTimer = null
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchUsageData()
  }, 300)
})

watch([currentPage, perPage], () => {
  fetchUsageData()
})
</script>
