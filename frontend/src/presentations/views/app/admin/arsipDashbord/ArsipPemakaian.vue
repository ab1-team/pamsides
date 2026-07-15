<template>
  <div class="h-full bg-white flex flex-col pt-2 pb-4">
    <DataTable
      v-model="searchQuery"
      :data="filteredData"
      :columns="columns"
      title="Detail Arsip Pemakaian"
      searchPlaceholder="Cari nama atau nomor pelanggan..."
      v-model:current-page="currentPage"
      v-model:per-page="perPage"
      :total-entries="filteredData.length"
      :show-entries="false"
      :no-card="true"
    >
      <template #column-tagihan="{ row }">
        <span class="font-semibold text-[12px] text-slate-700 font-mono whitespace-nowrap">
          {{ row.tagihan != null ? formatRupiah(row.tagihan) : '-' }}
        </span>
      </template>
      <template #column-status="{ row }">
        <span
          class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-md"
          :class="
            row.status === 'paid'
              ? 'bg-emerald-50 text-emerald-600'
              : row.status === 'unpaid'
                ? 'bg-amber-50 text-amber-600'
                : 'bg-slate-100 text-slate-500'
          "
        >
          {{ row.status === 'paid' ? 'Sudah Dicatat' : row.status === 'unpaid' ? 'Belum Lunas' : 'Belum Dicatat' }}
        </span>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import billingService from '@/services/billing.service'
import api from '@/utils/axios'

const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(8)
const loading = ref(false)

const formatRupiah = (value) => {
  const n = Number(value) || 0
  return `Rp ${n.toLocaleString('id-ID')}`
}

const columns = [
  { key: 'nomorInduk', title: 'No. Pelanggan' },
  { key: 'customer', title: 'Nama' },
  { key: 'alamat', title: 'Alamat' },
  { key: 'paket', title: 'Paket' },
  { key: 'periode', title: 'Periode' },
  { key: 'tagihan', title: 'Tagihan' },
  { key: 'status', title: 'Status' },
]

const monthNames = [
  '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const itemsList = ref([])

const fetchUsageData = async () => {
  try {
    loading.value = true
    const now = new Date()
    const year = now.getFullYear()
    const month = now.getMonth() + 1

    const [ticketsRes, billsRes] = await Promise.all([
      api.get('/customers/search'),
      billingService.getBills({ year, month }),
    ])

    const ticketsRaw = ticketsRes.data?.data
    const tickets = Array.isArray(ticketsRaw)
      ? ticketsRaw
      : Array.isArray(ticketsRaw?.data) ? ticketsRaw.data : []
    const bills = billsRes?.data?.bills || []

    const billByCustomer = new Map()
    for (const bill of bills) {
      const cid = bill.customer_id || bill.customer?.id
      if (cid) billByCustomer.set(cid, bill)
    }

    itemsList.value = tickets
      .filter((t) => t.customer_code)
      .map((t) => {
        const bill = billByCustomer.get(t.customer_id) || null
        const m = bill?.billing_period_month
        const y = bill?.billing_period_year
        return {
          id: t.customer_id,
          nomorInduk: t.customer_code || '-',
          customer: t.name || '-',
          alamat: t.address || '-',
          paket: t.packageName || '-',
          periode: m ? `${monthNames[m]} ${y}` : '-',
          tagihan: bill?.total_amount ?? null,
          status: bill ? bill.status : 'unrecorded',
        }
      })
  } catch (error) {
    console.error('Failed to fetch usage data', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchUsageData()
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