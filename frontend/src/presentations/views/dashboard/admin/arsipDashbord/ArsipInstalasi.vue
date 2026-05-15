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
      :no-card="true"
    >
      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md"
          :class="{
            'bg-emerald-50 text-emerald-600': row.status === 'Aktif',
            'bg-amber-50 text-amber-600': row.status === 'Proses',
            'bg-rose-50 text-rose-600': row.status === 'Batal',
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
const perPage = ref(5)
const loading = ref(false)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
  { key: 'tanggalOrder', title: 'Tanggal Order' },
  { key: 'status', title: 'Status' },
]

const itemsList = ref([])

const fetchInstallations = async () => {
  try {
    loading.value = true
    const response = await ticketService.getTickets({ per_page: 100 })
    if (response?.success && response?.data?.data) {
      itemsList.value = response.data.data.map((ticket) => ({
        id: ticket.id,
        nomorInduk: `INS-${ticket.id.toString().padStart(5, '0')}`,
        customer: ticket.applicant_name || '-',
        alamat: ticket.address || '-',
        paket: ticket.package?.name || '-',
        tanggalOrder: ticket.created_at ? new Date(ticket.created_at).toISOString().split('T')[0] : '-',
        status: ticket.status === 'completed' ? 'Aktif' : (['cancelled', 'batal'].includes(ticket.status) ? 'Batal' : 'Proses'),
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
  const query = searchQuery.value.toLowerCase()
  if (!query) return itemsList.value

  return itemsList.value.filter(
    (item) =>
      item.customer.toLowerCase().includes(query) || item.nomorInduk.toLowerCase().includes(query),
  )
})
</script>
