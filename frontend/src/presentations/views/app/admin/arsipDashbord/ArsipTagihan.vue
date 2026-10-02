<template>
  <div class="h-full bg-white flex flex-col pt-2 pb-4">
    <DataTable
      v-model="searchQuery"
      v-model:selection="selectedRows"
      :data="itemsList"
      :columns="columns"
      title="Detail Arsip Tagihan"
      searchPlaceholder="Cari nama atau nomor pelanggan..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="serverTotal"
      :total-pages="totalPages"
      :show-entries="true"
      :no-card="true"
      server-side
      :loading="loading"
      selectable
    >
      <template #column-periode="{ row }">
        <span class="text-[12px] text-slate-600 font-medium">
          {{ row.periodeLabel }}
        </span>
      </template>
      <template #column-total="{ row }">
        <span class="font-semibold text-[12px] text-slate-700 font-mono whitespace-nowrap">
          {{ formatRupiah(row.total) }}
        </span>
      </template>
      <template #column-denda="{ row }">
        <span :class="['font-semibold text-[12px] font-mono whitespace-nowrap', row.denda > 0 ? 'text-rose-600' : 'text-slate-400']">
          {{ formatRupiah(row.denda) }}
        </span>
      </template>
      <template #column-jatuhTempo="{ row }">
        <span class="text-[12px] text-slate-600 font-medium">
          {{ formatDate(row.jatuhTempo) }}
        </span>
      </template>
      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md"
          :class="
            row.status === 'paid'
              ? 'bg-emerald-50 text-emerald-600'
              : 'bg-rose-50 text-rose-600'
          "
        >
          {{ row.status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
        </span>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, inject } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import dashboardService from '@/services/dashboard.service'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const loading = ref(false)
const serverTotal = ref(0)
const totalPages = ref(1)
const selectedRows = inject('tagihanSelection', ref([]))

const formatRupiah = (value) => {
  const n = Number(value) || 0
  return `Rp ${n.toLocaleString('id-ID')}`
}

const formatDate = (value) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const columns = [
  { key: 'nomorInduk', title: 'No. Pelanggan' },
  { key: 'customer', title: 'Nama' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'periodeLabel', title: 'Periode' },
  { key: 'total', title: 'Total Tagihan' },
  { key: 'denda', title: 'Denda' },
  { key: 'jatuhTempo', title: 'Jatuh Tempo' },
  { key: 'status', title: 'Status' },
]

const itemsList = ref([])

// Pakai endpoint ringan DashboardController::popupData?type=tagihan
// (sebelumnya getAllBills() menarik SEMUA halaman tagihan unpaid)
const fetchUnpaidBills = async () => {
  try {
    loading.value = true
    const response = await dashboardService.getPopupData('tagihan', {
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

let searchTimer = null
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchUnpaidBills()
  }, 300)
})

watch([currentPage, perPage], () => {
  fetchUnpaidBills()
})
</script>
