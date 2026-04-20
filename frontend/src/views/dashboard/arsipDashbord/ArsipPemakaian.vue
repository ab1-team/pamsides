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
import { ref, computed } from 'vue'
import DataTable from '../../../components/ui/DataTable.vue'

// Search and Pagination states
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(5) // Setting to 5 since it's likely going to be in a popup

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
]

// Mock Data
const mockData = ref([
  {
    id: 1,
    nomorInduk: 'INS-24001',
    customer: 'Budi Santoso',
    alamat: 'Jl. Merpati No. 10',
    paket: 'Rumah Tangga A',
  },
  {
    id: 2,
    nomorInduk: 'INS-24002',
    customer: 'Siti Aminah',
    alamat: 'Jl. Kenari No. 5',
    paket: 'Rumah Tangga B',
  },
  {
    id: 3,
    nomorInduk: 'INS-24003',
    customer: 'Ahmad Dahlan',
    alamat: 'Jl. Mawar No. 12',
    paket: 'Niaga',
  },
  {
    id: 4,
    nomorInduk: 'INS-24004',
    customer: 'Dewi Lestari',
    alamat: 'Jl. Melati No. 8',
    paket: 'Rumah Tangga A',
  },
  {
    id: 5,
    nomorInduk: 'INS-24005',
    customer: 'Joko Widodo',
    alamat: 'Jl. Anggrek No. 15',
    paket: 'Rumah Tangga C',
  },
  {
    id: 6,
    nomorInduk: 'INS-24006',
    customer: 'Indah Pertiwi',
    alamat: 'Jl. Tulip No. 20',
    paket: 'Rumah Tangga A',
  },
  {
    id: 7,
    nomorInduk: 'INS-24007',
    customer: 'Hendra Gunawan',
    alamat: 'Jl. Flamboyan No. 3',
    paket: 'Sosial',
  },
])

// Computed Properties for filtering
const filteredData = computed(() => {
  const query = searchQuery.value.toLowerCase()
  if (!query) return mockData.value

  return mockData.value.filter(
    (item) =>
      item.customer.toLowerCase().includes(query) || item.nomorInduk.toLowerCase().includes(query),
  )
})
</script>
