<template>
  <div class="daftar-tagihan-root p-4 lg:p-6">
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4! mb-6!">
      <div class="flex-1!">
        <h1 class="text-xl md:text-2xl font-bold text-cyan-600! tracking-tight mb-1!">
          Daftar Tagihan Tertunggak (Belum Lunas)
        </h1>
        <p class="text-xs md:text-sm text-slate-500! leading-relaxed">
          Menampilkan seluruh tagihan air pelanggan yang belum dilunasi. Anda dapat mencari
          pelanggan secara instan dan mengirim pengingat WhatsApp atau mencetak invoice.
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
        empty-title="Tagihan Tertunggak Tidak Ditemukan"
        empty-message="Semua tagihan lunas atau mohon lakukan pencarian kembali."
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
              {{ row.meter_reading_start?.toLocaleString('id-ID') || 0 }}
            </span>
            <span class="text-slate-300!">→</span>
            <span class="text-cyan-600! font-bold!">
              {{ row.meter_reading_end?.toLocaleString('id-ID') || 0 }}
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
              Rp. {{ Number(row.total_amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
            <span v-if="Number(row.penalty_amount || 0) > 0" class="text-[9px]! text-rose-500! font-bold!">
              Termasuk Denda Rp. {{ Number(row.penalty_amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
            </span>
          </div>
        </template>

        <template #column-due_date="{ row }">
          <div class="flex flex-col!">
            <span class="text-xs! font-bold! text-rose-500! flex! items-center! gap-1!">
              <font-awesome-icon icon="calendar-alt" class="text-[10px]!" />
              {{ formatDueDate(row.due_date) }}
            </span>
            <span class="text-[9px]! font-bold! text-rose-400! uppercase! mt-0.5!">
              {{ getOverdueDays(row.due_date) }}
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
import { MySwal } from '@/utils/swal.js'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import detaiDaftarTagihan from './partials/detaiDaftarTagihan.vue'

const bills = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const currentPage = ref(1)
const perPage = ref(10)

const selectedBill = ref(null)
const showDetailModal = ref(false)

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
  try {
    const params = {
      status: 'unpaid',
    }
    const res = await billingService.getBills(params)
    if (res.success && res.data) {
      bills.value = res.data.bills || []
    }
  } catch (err) {
    console.error('Gagal memuat daftar tagihan:', err)
  } finally {
    isLoading.value = false
  }
}

const getInitials = (row) => {
  const name = row.customer?.user?.name || row.customer?.ticket?.applicant_name || 'PL'
  return name
    .split(' ')
    .map((word) => word[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

const getCustomerName = (row) => {
  return row.customer?.user?.name || row.customer?.ticket?.applicant_name || 'Pelanggan Pamsimas'
}

const getMonthName = (monthNum) => {
  const months = [
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
  return months[monthNum - 1] || '-'
}

const formatDueDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
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
    return dateStr
  }
}

const getOverdueDays = (dateStr) => {
  if (!dateStr) return ''
  try {
    const due = new Date(dateStr)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    due.setHours(0, 0, 0, 0)

    const diffTime = today - due
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays > 0) {
      return `Terlambat ${diffDays} hari`
    } else if (diffDays === 0) {
      return 'Jatuh tempo hari ini'
    } else {
      return `Tersisa ${Math.abs(diffDays)} hari`
    }
  } catch (err) {
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

const handleSendWAReminder = (bill) => {
  const customerName = getCustomerName(bill)
  const phone = bill.customer?.ticket?.phone || ''
  const total = new Intl.NumberFormat('id-ID').format(bill.total_amount)
  const month = getMonthName(bill.billing_period_month)
  const year = bill.billing_period_year

  const text = `Halo Bapak/Ibu *${customerName}*,\n\nKami dari *PAMSIMAS* ingin menginformasikan bahwa tagihan air Anda untuk periode *${month} ${year}* sebesar *Rp. ${total}* belum dilunasi.\n\nMohon untuk segera melakukan pembayaran demi kelancaran distribusi air bersih.\n\nTerima kasih.`

  const cleanPhone = phone.replace(/[^0-9]/g, '')
  const formattedPhone = cleanPhone.startsWith('0') ? '62' + cleanPhone.slice(1) : cleanPhone

  const url = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(text)}`
  window.open(url, '_blank')
}

const handlePrintInvoice = (bill) => {
  MySwal.fire({
    title: 'Cetak Invoice',
    text: 'Fitur cetak invoice sedang disiapkan.',
    icon: 'info',
    timer: 1500,
    showConfirmButton: false,
  })
}

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
