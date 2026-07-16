<template>
  <div class="h-full bg-white flex flex-col pt-2 pb-4">
    <DataTable
      v-model="searchQuery"
      v-model:selection="selectedRows"
      :data="filteredData"
      :columns="columns"
      title="Detail Arsip Tagihan"
      searchPlaceholder="Cari nama atau nomor pelanggan..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="filteredData.length"
      :show-entries="false"
      :no-card="true"
      selectable
    >
      <template #column-periode="{ row }">
        <span class="text-[12px] text-slate-600 font-medium">
          {{ row.periodeLabel }}
        </span>
      </template>
      <template #column-total="{ row }">
        <span class="font-semibold text-[12px] text-slate-700 font-mono whitespace-nowrap">
          {{ formatRupiah(row.total) }}
        </span>
      </template>
      <template #column-denda="{ row }">
        <span :class="['font-semibold text-[12px] font-mono whitespace-nowrap', row.denda > 0 ? 'text-rose-600' : 'text-slate-400']">
          {{ formatRupiah(row.denda) }}
        </span>
      </template>
      <template #column-jatuhTempo="{ row }">
        <span class="text-[12px] text-slate-600 font-medium">
          {{ formatDate(row.jatuhTempo) }}
        </span>
      </template>
      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md"
          :class="
            row.status === 'paid'
              ? 'bg-emerald-50 text-emerald-600'
              : 'bg-rose-50 text-rose-600'
          "
        >
          {{ row.status === 'paid' ? 'Lunas' : 'Belum Lunas' }}
        </span>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import billingService from '@/services/billing.service'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(8)
const loading = ref(false)
const selectedRows = inject('tagihanSelection', ref([]))

const formatRupiah = (value) => {
  const n = Number(value) || 0
  return `Rp ${n.toLocaleString('id-ID')}`
}

const monthNames = [
  '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const formatDate = (value) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const columns = [
  { key: 'nomorInduk', title: 'No. Pelanggan' },
  { key: 'customer', title: 'Nama' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'periode', title: 'Periode' },
  { key: 'total', title: 'Total Tagihan' },
  { key: 'denda', title: 'Denda' },
  { key: 'jatuhTempo', title: 'Jatuh Tempo' },
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
          customer: ticket?.applicant_name || bill.customer?.user?.name || '-',
          alamat: ticket?.address || '-',
          periode: bill.billing_period_month,
          tahun: bill.billing_period_year,
          periodeLabel: bill.billing_period_month
            ? `${monthNames[bill.billing_period_month]} ${bill.billing_period_year}`
            : '-',
          total: bill.total_amount ?? 0,
          denda: Number(bill.penalty_amount) || 0,
          jatuhTempo: bill.due_date,
          status: bill.status,
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
      item.customer.toLowerCase().includes(query) ||
      item.nomorInduk.toLowerCase().includes(query),
  )
})
</script>