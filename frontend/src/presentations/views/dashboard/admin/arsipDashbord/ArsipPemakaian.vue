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
      :no-card="true"
    />
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
