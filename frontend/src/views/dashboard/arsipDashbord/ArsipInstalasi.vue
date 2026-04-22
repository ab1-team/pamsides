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
import { ref, computed } from 'vue'
import DataTable from '../../../components/ui/DataTable.vue'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(5)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
  { key: 'tanggalOrder', title: 'Tanggal Order' },
  { key: 'status', title: 'Status' },
]

const mockData = ref([
  {
    id: 1,
    nomorInduk: 'INS-24001',
    customer: 'Budi Santoso',
    alamat: 'Jl. Merpati No. 10',
    paket: 'Rumah Tangga A',
    tanggalOrder: '2024-03-01',
    status: 'Aktif',
  },
  {
    id: 2,
    nomorInduk: 'INS-24002',
    customer: 'Siti Aminah',
    alamat: 'Jl. Kenari No. 5',
    paket: 'Rumah Tangga B',
    tanggalOrder: '2024-03-05',
    status: 'Proses',
  },
  {
    id: 3,
    nomorInduk: 'INS-24003',
    customer: 'Ahmad Dahlan',
    alamat: 'Jl. Mawar No. 12',
    paket: 'Niaga',
    tanggalOrder: '2024-03-10',
    status: 'Aktif',
  },
  {
    id: 4,
    nomorInduk: 'INS-24004',
    customer: 'Dewi Lestari',
    alamat: 'Jl. Melati No. 8',
    paket: 'Rumah Tangga A',
    tanggalOrder: '2024-03-12',
    status: 'Batal',
  },
  {
    id: 5,
    nomorInduk: 'INS-24005',
    customer: 'Joko Widodo',
    alamat: 'Jl. Anggrek No. 15',
    paket: 'Rumah Tangga C',
    tanggalOrder: '2024-03-15',
    status: 'Aktif',
  },
  {
    id: 6,
    nomorInduk: 'INS-24006',
    customer: 'Indah Pertiwi',
    alamat: 'Jl. Tulip No. 20',
    paket: 'Rumah Tangga A',
    tanggalOrder: '2024-03-18',
    status: 'Proses',
  },
  {
    id: 7,
    nomorInduk: 'INS-24007',
    customer: 'Hendra Gunawan',
    alamat: 'Jl. Flamboyan No. 3',
    paket: 'Sosial',
    tanggalOrder: '2024-03-20',
    status: 'Aktif',
  },
])

const filteredData = computed(() => {
  const query = searchQuery.value.toLowerCase()
  if (!query) return mockData.value

  return mockData.value.filter(
    (item) =>
      item.customer.toLowerCase().includes(query) || item.nomorInduk.toLowerCase().includes(query),
  )
})
</script>
