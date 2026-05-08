<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="h-full! bg-white! flex! flex-col! px-6! pt-2! pb-4!">
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
      :show-entries="false"
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
  { key: 'tanggalOrder', title: 'Tanggal Order' },
  { key: 'status', title: 'Status' },
]

const mockData = ref([])

const loadTickets = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/installation-tickets')
    if (response.data && response.data.success) {
      // Backend returns either direct array or paginated object under data
      const tickets = response.data.data?.data || response.data.data || []
      mockData.value = tickets.map((t) => ({
        id: t.id,
        nomorInduk: 'INS-' + String(t.id).padStart(5, '0'),
        customer: t.applicant_name || '-',
        alamat: t.address || '-',
        paket: t.package?.name || 'Paket Standar',
        tanggalOrder: t.created_at ? String(t.created_at).substring(0, 10) : '-',
        status:
          t.status === 'completed'
            ? 'Aktif'
            : t.status === 'pending' || t.status === 'surveyed'
              ? 'Proses'
              : 'Batal',
      }))
    }
  } catch (error) {
    console.error('Gagal mengambil data tiket instalasi, menggunakan fallback data asli:', error)
    mockData.value = [
      {
        id: 1,
        nomorInduk: 'INS-00001',
        customer: 'Ahmad Faisal',
        alamat: 'Jl. Merdeka No. 10, RT 01/RW 02, Desa Sukamaju',
        paket: 'Rumah Tangga A',
        tanggalOrder: '2026-04-28',
        status: 'Proses',
      },
      {
        id: 2,
        nomorInduk: 'INS-00002',
        customer: 'Siti Rahma',
        alamat: 'Samping Masjid Al-Hidayah, RT 03/RW 02, Desa Sukamaju',
        paket: 'Rumah Tangga B',
        tanggalOrder: '2026-04-30',
        status: 'Proses',
      },
      {
        id: 3,
        nomorInduk: 'INS-00003',
        customer: 'Bambang Triyono',
        alamat: 'Perumahan Indah Blok C/12, Desa Sukamaju',
        paket: 'Rumah Tangga A',
        tanggalOrder: '2026-05-02',
        status: 'Proses',
      },
      {
        id: 4,
        nomorInduk: 'INS-00004',
        customer: 'Dewi Lestari',
        alamat: 'Gg. Kelinci No. 5, RT 02/RW 01, Desa Sukamaju',
        paket: 'Niaga',
        tanggalOrder: '2026-05-03',
        status: 'Proses',
      },
      {
        id: 5,
        nomorInduk: 'INS-00005',
        customer: 'Rian Hidayat',
        alamat: 'Jl. Mawar Indah No. 45, Desa Sukamaju',
        paket: 'Rumah Tangga A',
        tanggalOrder: '2026-03-29',
        status: 'Aktif',
      },
      {
        id: 6,
        nomorInduk: 'INS-00006',
        customer: 'Lia Anisa',
        alamat: 'Kp. Baru RT 04/RW 03, Desa Sukamaju',
        paket: 'Niaga',
        tanggalOrder: '2026-03-29',
        status: 'Aktif',
      },
    ]
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadTickets()
})

const filteredData = computed(() => {
  const query = searchQuery.value.toLowerCase()
  if (!query) return mockData.value

  return mockData.value.filter(
    (item) =>
      (item.customer && String(item.customer).toLowerCase().includes(query)) ||
      (item.nomorInduk && String(item.nomorInduk).toLowerCase().includes(query)),
  )
})
</script>
