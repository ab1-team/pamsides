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
      :show-entries="false"
      :no-card="true"
    >
      <template #column-tagihan="{ row }">
        <span class="font-semibold text-slate-700">
          {{ formatCurrency(row.tagihan) }}
        </span>
      </template>
      <template #column-denda="{ row }">
        <span
          :class="[
            'font-semibold',
            row.denda > 0 ? 'text-rose-600' : 'text-slate-400',
          ]"
        >
          {{ formatCurrency(row.denda) }}
        </span>
      </template>
      <template #column-total="{ row }">
        <span class="font-bold text-slate-800">
          {{ formatCurrency(row.total) }}
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
const perPage = ref(8)
const loading = ref(false)

const columns = [
  { key: 'nomorInduk', title: 'Nomor Induk' },
  { key: 'customer', title: 'Customer' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'tagihan', title: 'Tagihan' },
  { key: 'denda', title: 'Denda' },
  { key: 'total', title: 'Total Tunggakan' },
]

const itemsList = ref([])

const fetchUnpaidBills = async () => {
  try {
    loading.value = true
    const response = await billingService.getBills({ status: 'unpaid' })
    if (response?.success && response?.data?.bills) {
      itemsList.value = response.data.bills.map((bill) => {
        const ticket = bill.customer?.ticket
        const total = Number(bill.total_amount) || 0
        const denda = Number(bill.penalty_amount) || 0
        const tagihan = Math.max(0, total - denda)
        return {
          id: bill.id,
          nomorInduk: bill.customer?.customer_code || '-',
          customer: ticket?.applicant_name || '-',
          alamat: ticket?.address || '-',
          tagihan,
          denda,
          total,
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
