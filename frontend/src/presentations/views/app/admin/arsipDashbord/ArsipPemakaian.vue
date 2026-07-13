<template>
  <div class="h-full bg-white flex flex-col pt-2 pb-4">
    <DataTable
      v-model="searchQuery"
      :data="filteredData"
      :columns="columns"
      title="Detail Arsip Pemakaian"
      searchPlaceholder="Cari nama atau nomor induk..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="filteredData.length"
      :show-entries="false"
      :no-card="true"
    >
      <template #column-nominal="{ row }">
        <span class="font-semibold text-[12px] text-slate-700 font-mono whitespace-nowrap">
          {{ row.nominal }}
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

const formatRupiah = (value) => {
  const n = Number(value) || 0
  return `Rp ${n.toLocaleString('id-ID')}`
}

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
  { key: 'nominal', title: 'Nominal' },
]

const itemsList = ref([])

const fetchActiveCustomers = async () => {
  try {
    loading.value = true
    const response = await ticketService.getTickets({ status: 'completed', per_page: 100 })
    if (response?.success && response?.data?.data) {
      itemsList.value = response.data.data.map((ticket) => {
        const custCode = ticket.customer?.[0]?.customer_code
        return {
          id: ticket.id,
          nomorInduk: custCode || `INS-${ticket.id.toString().padStart(5, '0')}`,
          customer: ticket.applicant_name || '-',
          alamat: ticket.address || '-',
          paket: ticket.package?.name || '-',
          nominal: formatRupiah(ticket.package?.installation_fee || 0),
          nominalValue: Number(ticket.package?.installation_fee || 0),
        }
      })
    }
  } catch (error) {
    console.error('Failed to fetch active customers', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchActiveCustomers()
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