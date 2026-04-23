<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="h-full bg-white flex flex-col pt-2 pb-4">
    <DataTable
      v-model="searchQuery"
      :data="filteredData"
      :columns="columns"
      title="Detail Arsip Tunggakan"
      searchPlaceholder="Cari nama atau nomor induk..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="filteredData.length"
      :no-card="true"
    >
      <template #column-jumlahTunggakan="{ row }">
        <span class="font-semibold text-slate-700">
          {{ formatCurrency(row.jumlahTunggakan) }}
        </span>
      </template>

      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md"
          :class="{
            'bg-rose-50 text-rose-600 border border-rose-200': row.status === 'Belum Bayar',
            'bg-amber-50 text-amber-600 border border-amber-200': row.status === 'Sebagian',
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

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount)
}

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(5)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
  { key: 'jumlahTunggakan', title: 'Jumlah Tunggakan' },
  { key: 'status', title: 'Status' },
]

const mockData = ref([
  {
    id: 1,
    nomorInduk: 'INS-24001',
    customer: 'Budi Santoso',
    alamat: 'Jl. Merpati No. 10',
    paket: 'Rumah Tangga A',
    jumlahTunggakan: 150000,
    status: 'Belum Bayar',
  },
  {
    id: 2,
    nomorInduk: 'INS-24002',
    customer: 'Siti Aminah',
    alamat: 'Jl. Kenari No. 5',
    paket: 'Rumah Tangga B',
    jumlahTunggakan: 75000,
    status: 'Sebagian',
  },
  {
    id: 4,
    nomorInduk: 'INS-24004',
    customer: 'Dewi Lestari',
    alamat: 'Jl. Melati No. 8',
    paket: 'Rumah Tangga A',
    jumlahTunggakan: 300000,
    status: 'Belum Bayar',
  },
  {
    id: 6,
    nomorInduk: 'INS-24006',
    customer: 'Indah Pertiwi',
    alamat: 'Jl. Tulip No. 20',
    paket: 'Rumah Tangga A',
    jumlahTunggakan: 150000,
    status: 'Belum Bayar',
  },
  {
    id: 8,
    nomorInduk: 'INS-24008',
    customer: 'Rudi Hermawan',
    alamat: 'Jl. Kamboja No. 9',
    paket: 'Niaga',
    jumlahTunggakan: 500000,
    status: 'Belum Bayar',
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
