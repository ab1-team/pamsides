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
      title="Detail Arsip Instalasi"
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
      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md"
          :class="{
            'bg-slate-100 text-slate-600': row.status === 'Draft',
            'bg-blue-50 text-blue-600': row.status === 'Pasang',
            'bg-amber-50 text-amber-600': row.status === 'Prosesing',
            'bg-rose-50 text-rose-600': row.status === 'Unpaid',
          }"
        >
          {{ row.status }}
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

const STATUS_MAP = {
  draft: { label: 'Draft' },
  pending: { label: 'Pasang' },
  surveyed: { label: 'Pasang' },
  unpaid: { label: 'Unpaid' },
  processing: { label: 'Prosesing' },
  completed: { label: 'Aktif' },
  suspended: { label: 'Blokir' },
  terminated: { label: 'Cabut' },
  cancelled: { label: 'Batal' },
  batal: { label: 'Batal' },
}

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'tanggalOrder', title: 'Tanggal Order' },
  { key: 'status', title: 'Status' },
]

const itemsList = ref([])

// Pakai endpoint ringan DashboardController::popupData?type=instalasi
// (sebelumnya ticketService.getTickets({ per_page: 100 }) menarik SEMUA tiket
//  dengan eager-load berat, lalu di-filter client-side)
const fetchInstallations = async () => {
  try {
    loading.value = true
    const response = await dashboardService.getPopupData('instalasi', {
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value,
    })
    if (response?.success) {
      itemsList.value = (response.data || []).map((t) => ({
        id: t.id,
        nomorInduk: t.nomorInduk,
        customer: t.customer,
        alamat: t.alamat,
        tanggalOrder: t.tanggalOrder,
        status: STATUS_MAP[t.status]?.label || t.status || '-',
        rawStatus: t.status,
      }))
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
  fetchInstallations()
})

let searchTimer = null
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchInstallations()
  }, 300)
})

watch([currentPage, perPage], () => {
  fetchInstallations()
})
</script>
