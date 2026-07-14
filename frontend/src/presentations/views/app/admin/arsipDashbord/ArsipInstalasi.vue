<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="h-full bg-white flex flex-col pt-2 pb-4">
    <DataTable
      v-model="searchQuery"
      :data="filteredData"
      :columns="columns"
      title="Detail Arsip Instalasi"
      searchPlaceholder="Cari nama atau nomor induk..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="filteredData.length"
      :show-entries="false"
      :no-card="true"
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import ticketService from '@/services/ticket.service'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(8)
const loading = ref(false)

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

const allowedStatuses = ['draft', 'pasang', 'prosesing', 'unpaid']
const allowedRawStatuses = Object.entries(STATUS_MAP)
  .filter(([, v]) => allowedStatuses.includes(v.label.toLowerCase()))
  .map(([k]) => k)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'tanggalOrder', title: 'Tanggal Order' },
  { key: 'status', title: 'Status' },
]

const itemsList = ref([])

const fetchInstallations = async () => {
  try {
    loading.value = true
    const response = await ticketService.getTickets({ per_page: 100 })
    if (response?.success && response?.data?.data) {
      itemsList.value = response.data.data
        .filter((ticket) => allowedRawStatuses.includes(ticket.status))
        .map((ticket) => ({
          id: ticket.id,
          nomorInduk: `INS-${ticket.id.toString().padStart(5, '0')}`,
          customer: ticket.applicant_name || '-',
          alamat: ticket.address || '-',
          tanggalOrder: ticket.created_at
            ? new Date(ticket.created_at).toISOString().split('T')[0]
            : '-',
          status: STATUS_MAP[ticket.status]?.label || '-',
          rawStatus: ticket.status,
        }))
    }
  } catch (error) {
    console.error('Failed to fetch installations', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchInstallations()
})

const filteredData = computed(() => {
  let rows = itemsList.value
  const query = searchQuery.value.toLowerCase()
  if (query) {
    rows = rows.filter(
      (item) =>
        item.customer.toLowerCase().includes(query) ||
        item.nomorInduk.toLowerCase().includes(query),
    )
  }
  return rows
})
</script>