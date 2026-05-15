<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="h-full! bg-white! flex! flex-col! px-6! pt-2! pb-4!">
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
      :show-entries="false"
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
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import api from '@/utils/axios'

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount)
}

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)
const isLoading = ref(false)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
  { key: 'jumlahTunggakan', title: 'Jumlah Tunggakan' },
  { key: 'status', title: 'Status' },
]

const mockData = ref([])

const loadOverdueBills = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/monthly-bills', { params: { status: 'unpaid' } })
    if (response.data && response.data.success) {
      const bills = response.data.data?.bills || response.data.data || []
      mockData.value = bills.map((b) => ({
        id: b.id,
        nomorInduk: b.customer?.customer_code || 'PAM-' + String(b.customer_id).padStart(5, '0'),
        customer: b.customer?.user?.name || 'Customer #' + b.customer_id,
        alamat: b.customer?.ticket?.address || '-',
        paket: b.customer?.ticket?.package?.name || 'Rumah Tangga',
        jumlahTunggakan: b.total_amount,
        status: 'Belum Bayar',
      }))
    }
  } catch (error) {
    console.error('Gagal mengambil data tunggakan:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadOverdueBills()
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
