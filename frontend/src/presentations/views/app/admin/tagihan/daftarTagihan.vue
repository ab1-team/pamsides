<template>
  <div class="daftar-tagihan-root">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-xl md:text-2xl font-bold text-cyan-600! tracking-tight mb-1!">
          Daftar Tagihan
        </h1>
        <p class="text-xs md:text-sm text-slate-500! leading-relaxed">
          Menampilkan seluruh tagihan air pelanggan. Satu pelanggan dapat muncul di lebih dari satu
          baris bila memiliki banyak tagihan (mis. bulan ini &amp; bulan lalu). Anda dapat mencari
          nama, ID, atau nomor invoice dan mengirim pengingat WhatsApp.
        </p>
      </div>

      <div class="flex flex-wrap gap-2 md:gap-3! w-full lg:w-auto!">
        <BaseButton
          variant="info-gradient"
          size="md"
          @click="fetchBills"
          class="w-full! lg:w-auto! rounded-xl! shadow-md! text-xs md:text-sm"
          icon="redo-alt"
        >
          Muat Ulang Data
        </BaseButton>
      </div>
    </div>

    <ContentCard variant="elevated" padding="none" class="overflow-hidden!">
      <div
        v-if="loadError"
        class="px-4! py-3! bg-rose-50! border-b! border-rose-200! text-rose-700! text-xs! font-semibold! flex! items-center! gap-2!"
      >
        <font-awesome-icon icon="exclamation-triangle" class="text-sm!" />
        Gagal memuat data: {{ loadError }}
      </div>
      <div
        v-else-if="!isLoading && bills.length === 0"
        class="px-4! py-3! bg-amber-50! border-b! border-amber-200! text-amber-700! text-xs! font-semibold! flex! items-center! gap-2!"
      >
        <font-awesome-icon icon="info-circle" class="text-sm!" />
        Tidak ada tagihan di database. Pastikan data sudah di-generate di menu Transaksi → Tagihan
        Bulanan.
      </div>
      <DataTable
        :data="filteredBills"
        :columns="tableColumns"
        v-model:current-page="currentPage"
        v-model:per-page="perPage"
        :total-pages="totalPages"
        :visible-pages="visiblePages"
        :total-entries="filteredBills.length"
        v-model="searchQuery"
        search-placeholder="Cari nama pelanggan, ID, atau no. invoice..."
        empty-title="Tagihan Tidak Ditemukan"
        empty-message="Belum ada tagihan sama sekali atau mohon lakukan pencarian kembali."
        no-card
        row-clickable
        @row-click="handleOpenDetail"
      >
        <template #column-customer="{ row }">
          <div class="flex items-center gap-3!">
            <div
              class="w-9! h-9! rounded-xl! bg-gradient-to-br! from-cyan-500! to-blue-600! flex! items-center! justify-center! text-white! text-xs! font-bold! shrink-0! shadow-sm!"
            >
              {{ getInitials(row) }}
            </div>
            <div>
              <div class="font-bold! text-sm! text-slate-800! mb-0.5!">
                {{ getCustomerName(row) }}
              </div>
              <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-wide!">
                ID: {{ row.customer?.customer_code || 'PAM-' + row.customer_id }}
              </div>
            </div>
          </div>
        </template>

        <template #column-period="{ row }">
          <div>
            <div class="font-bold! text-sm! text-slate-800! mb-0.5!">
              {{ getMonthName(row.billing_period_month) }} {{ row.billing_period_year }}
            </div>
            <div class="text-[10px]! font-mono! text-slate-400! font-bold!">INV-{{ row.id }}</div>
          </div>
        </template>

        <template #column-meter="{ row }">
          <div class="flex items-center gap-2! font-semibold! text-xs!">
            <span class="text-slate-400! font-normal!">
              {{ formatNumber(row.meter_reading_start) }}
            </span>
            <span class="text-slate-300!">→</span>
            <span class="text-cyan-600! font-bold!">
              {{ formatNumber(row.meter_reading_end) }}
            </span>
          </div>
        </template>

        <template #column-usage="{ row }">
          <span class="text-sm! font-bold! text-slate-800!">
            {{ row.usage_m3 || 0 }}
            <span class="text-[10px]! text-slate-400! font-medium!">m³</span>
          </span>
        </template>

        <template #column-total="{ row }">
          <div class="flex flex-col!">
            <span class="text-sm! font-extrabold! text-slate-800!">
              Rp. {{ formatCurrency(row.total_amount) }}
            </span>
            <span
              v-if="Number(row.penalty_amount || 0) > 0"
              class="text-[9px]! text-rose-500! font-bold!"
            >
              Termasuk Denda Rp. {{ formatCurrency(row.penalty_amount) }}
            </span>
          </div>
        </template>

        <template #column-due_date="{ row }">
          <div class="flex flex-col!">
            <span
              :class="[
                'text-xs! font-bold! flex! items-center! gap-1!',
                row.status === 'paid' ? 'text-emerald-600!' : 'text-rose-500!',
              ]"
            >
              <font-awesome-icon icon="calendar-alt" class="text-[10px]!" />
              {{ formatDueDate(row.due_date) }}
            </span>
            <span
              v-if="row.status !== 'paid'"
              class="text-[9px]! font-bold! text-rose-400! uppercase! mt-0.5!"
            >
              {{ getOverdueDays(row.due_date) }}
            </span>
            <span v-else class="text-[9px]! font-bold! text-emerald-500! uppercase! mt-0.5!">
              Lunas
            </span>
          </div>
        </template>
      </DataTable>
    </ContentCard>

    <div class="text-center py-8 text-[10px] text-slate-400 font-semibold tracking-[2px] uppercase">
      PAMSIMAS · LAYANAN AIR BERSIH MASYARAKAT
    </div>

    <detaiDaftarTagihan
      :show="showDetailModal"
      :bill="selectedBill"
      @close="showDetailModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { billingService } from '@/services/billing.service.js'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import detaiDaftarTagihan from './partials/detaiDaftarTagihan.vue'

const bills = ref([])
const isLoading = ref(false)
const loadError = ref(null)
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)

const selectedBill = ref(null)
const showDetailModal = ref(false)

const bulanOptions = [
  'Januari',
  'Februari',
  'Maret',
  'April',
  'Mei',
  'Juni',
  'Juli',
  'Agustus',
  'September',
  'Oktober',
  'November',
  'Desember',
]

const tableColumns = [
  { key: 'customer', title: 'PELANGGAN / ID' },
  { key: 'period', title: 'PERIODE / INVOICE' },
  { key: 'meter', title: 'STAND METER' },
  { key: 'usage', title: 'VOLUME AIR' },
  { key: 'total', title: 'TOTAL TAGIHAN' },
  { key: 'due_date', title: 'JATUH TEMPO' },
]

const fetchBills = async () => {
  isLoading.value = true
  loadError.value = null
  try {
    const res = await billingService.getBills({})
    console.log('[DaftarTagihan] response:', res)
    if (res?.success && res.data) {
      bills.value = Array.isArray(res.data.bills) ? res.data.bills : []
    } else if (res?.data) {
      bills.value = Array.isArray(res.data) ? res.data : []
    } else {
      bills.value = []
    }
  } catch (err) {
    console.error('[DaftarTagihan] Gagal memuat daftar tagihan:', err)
    loadError.value = err.response?.data?.message || err.message || 'Gagal memuat data'
    bills.value = []
  } finally {
    isLoading.value = false
  }
}

const getInitials = (row) => {
  const name = row.customer?.user?.name || row.customer?.ticket?.applicant_name || 'PL'
  return name
    .split(' ')
    .filter(Boolean)
    .map((word) => word[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

const getCustomerName = (row) => {
  return row.customer?.user?.name || row.customer?.ticket?.applicant_name || 'Pelanggan Pamsimas'
}

const getMonthName = (monthNum) => {
  if (!monthNum) return '-'
  return bulanOptions[monthNum - 1] || '-'
}

const formatNumber = (val) => {
  const n = Number(val || 0)
  return n.toLocaleString('id-ID')
}

const formatCurrency = (val) => {
  return Number(val || 0).toLocaleString('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

const formatDueDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    if (Number.isNaN(d.getTime())) return dateStr
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'Mei',
      'Jun',
      'Jul',
      'Agu',
      'Sep',
      'Okt',
      'Nov',
      'Des',
    ]
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
  } catch (err) {
    console.error('Error formatting due date:', err)
    return dateStr
  }
}

const getOverdueDays = (dateStr) => {
  if (!dateStr) return ''
  try {
    const due = new Date(dateStr)
    if (Number.isNaN(due.getTime())) return ''
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    due.setHours(0, 0, 0, 0)

    const diffTime = today - due
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays > 0) return `Terlambat ${diffDays} hari`
    if (diffDays === 0) return 'Jatuh tempo hari ini'
    return `Tersisa ${Math.abs(diffDays)} hari`
  } catch (err) {
    console.error('Error calculating overdue days:', err)
    return ''
  }
}

const filteredBills = computed(() => {
  if (!searchQuery.value) return bills.value
  const query = searchQuery.value.toLowerCase()
  return bills.value.filter((b) => {
    const name = getCustomerName(b).toLowerCase()
    const code = (b.customer?.customer_code || '').toLowerCase()
    const invId = `inv-${b.id}`.toLowerCase()
    const monthName = getMonthName(b.billing_period_month).toLowerCase()
    const year = String(b.billing_period_year)
    return (
      name.includes(query) ||
      code.includes(query) ||
      invId.includes(query) ||
      monthName.includes(query) ||
      year.includes(query)
    )
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredBills.value.length / perPage.value))
})

const visiblePages = computed(() => {
  const pages = []
  for (let i = 1; i <= Math.min(3, totalPages.value); i++) {
    pages.push(i)
  }
  return pages
})

const handleOpenDetail = (row) => {
  selectedBill.value = row
  showDetailModal.value = true
}

onMounted(() => {
  fetchBills()
})
</script>

<style scoped>
.daftar-tagihan-root {
  width: 100%;
}
</style>
