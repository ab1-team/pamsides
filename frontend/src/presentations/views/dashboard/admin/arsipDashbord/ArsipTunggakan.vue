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
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import billingService from '@/services/billing.service'

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
const loading = ref(false)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
  { key: 'jumlahTunggakan', title: 'Jumlah Tunggakan' },
  { key: 'status', title: 'Status' },
]

const itemsList = ref([])

const fetchUnpaidBills = async () => {
  try {
    loading.value = true
    const response = await billingService.getBills({ status: 'unpaid' })
    if (response?.success && response?.data?.bills) {
      itemsList.value = response.data.bills.map((bill) => {
        const ticket = bill.customer?.ticket
        return {
          id: bill.id,
          nomorInduk: bill.customer?.customer_code || '-',
          customer: ticket?.applicant_name || '-',
          alamat: ticket?.address || '-',
          paket: ticket?.package?.name || '-',
          jumlahTunggakan: Number(bill.total_amount) || 0,
          status: bill.status === 'unpaid' ? 'Belum Bayar' : 'Sebagian',
        }
      })
    }
  } catch (error) {
    console.error('Failed to fetch unpaid bills', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchUnpaidBills()
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
