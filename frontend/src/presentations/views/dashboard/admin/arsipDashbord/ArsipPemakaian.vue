<template>
  <div class="h-full! bg-white! flex! flex-col! px-6! pt-2! pb-4!">
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
      :show-entries="false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import api from '@/utils/axios'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const isLoading = ref(false)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
]

const mockData = ref([])

const loadCustomers = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/customers')
    if (response.data && response.data.success) {
      const customers = response.data.data || []
      mockData.value = customers.map((c) => ({
        id: c.id,
        nomorInduk: c.customer_code || '-',
        customer: c.name,
        alamat: c.address || '-',
        paket: 'Rumah Tangga',
      }))
    }
  } catch (error) {
    console.error('Gagal mengambil data pelanggan:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadCustomers()
})

const filteredData = computed(() => {
  const query = searchQuery.value.toLowerCase()
  if (!query) return mockData.value

  return mockData.value.filter(
    (item) =>
      item.customer.toLowerCase().includes(query) || item.nomorInduk.toLowerCase().includes(query),
  )
})
</script>
