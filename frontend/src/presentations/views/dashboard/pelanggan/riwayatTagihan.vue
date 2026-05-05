<template>
  <div class="bill-history">
    <div class="max-w-6xl! mx-auto!">
      <div class="flex! flex-col! lg:flex-row! lg:items-center! justify-between! mb-10! gap-6!">
        <div class="flex! items-start! gap-5!">
          <BaseButton
            v-if="$route.query.from === 'dashboard'"
            variant="ghost"
            icon="arrow-left"
            class="w-10! h-10! lg:w-12! lg:h-12! rounded-full! bg-white! shadow-sm! border! border-slate-100! flex! items-center! justify-center! text-slate-600! hover:bg-slate-50! flex-shrink-0! mt-1!"
            @click="$router.back()"
          />
          <div :class="{ 'text-center! w-full!': $route.query.from !== 'dashboard' && isMobile }">
            <h1 class="text-2xl! lg:text-3xl! font-black! text-slate-800! tracking-tight!">
              Riwayat Tagihan
            </h1>
            <p class="text-slate-500! mt-1! font-medium! text-sm! lg:text-base!">
              Pantau penggunaan dan riwayat pembayaran air Anda.
            </p>
          </div>
        </div>
        <div class="hidden! sm:block!">
          <div
            class="bg-indigo-50! px-4! lg:px-6! py-2! lg:py-3! rounded-2xl! border! border-indigo-100!"
          >
            <span
              class="text-[9px]! lg:text-[10px]! font-black! text-indigo-400! uppercase! tracking-widest!"
              >ID PELANGGAN</span
            >
            <div class="text-sm! lg:text-base! text-indigo-600! font-black!"> {{ customerCode }} </div>
          </div>
        </div>
      </div>

      <div class="grid! grid-cols-1! lg:grid-cols-3! gap-4! lg:gap-6! mb-10!">
        <ContentCard
          v-for="(stat, idx) in stats"
          :key="idx"
          variant="elevated"
          class="border-0! shadow-lg! shadow-slate-200/30! hover:scale-[1.01]! transition-transform! p-3! lg:p-4!"
        >
          <div class="flex! flex-col! gap-2!">
            <div class="flex! items-center! justify-between!">
              <span
                class="text-[8px]! lg:text-[9px]! font-black! text-slate-400! uppercase! tracking-widest! whitespace-nowrap!"
              >
                {{ stat.label }}
              </span>
              <div
                :class="`w-6! h-6! lg:w-7! lg:h-7! rounded-lg! ${stat.bg}! ${stat.color}! flex! items-center! justify-center! flex-shrink-0!`"
              >
                <font-awesome-icon :icon="stat.icon" class="text-[10px]! lg:text-xs! !m-auto!" />
              </div>
            </div>
            
            <div class="flex! flex-col! gap-0!">
              <div class="text-base! lg:text-lg! font-black! text-slate-800! tracking-tighter! whitespace-nowrap!">
                {{ stat.value }}
              </div>
              <div
                v-if="idx === 2"
                :class="[
                  'text-[8px]! font-black! uppercase! tracking-[0.15em]! whitespace-nowrap!',
                  stat.value === 'Lancar' ? 'text-emerald-500!' : 'text-amber-500!'
                ]"
              >
                {{ stat.value === 'Lancar' ? 'STATUS OK' : 'PERLU BAYAR' }}
              </div>
            </div>
          </div>
        </ContentCard>
      </div>

      <ContentCard
        variant="elevated"
        padding="none"
        class="border-0! shadow-xl! shadow-slate-200/40! overflow-hidden!"
      >
        <DataTable
          v-if="!isMobile"
          :data="filteredBills"
          :columns="tableColumns"
          v-model="searchQuery"
          :total-entries="bills.length"
          search-placeholder="Cari periode tagihan..."
          empty-title="Tagihan Tidak Ditemukan"
          :empty-message="'Mohon cek kembali periode yang Anda cari.'"
          row-clickable
          @row-click="(row) => $router.push({ path: '/pelanggan/tagihan-detail', query: { id: row.id } })"
          no-card
        >
          <template #column-period="{ row }">
            <div class="font-bold! text-slate-800!">{{ row.period }}</div>
            <div class="text-[10px]! text-slate-400! font-bold! mt-0.5!">INV-{{ row.id }}</div>
          </template>

          <template #column-usage="{ row }">
            <div class="font-bold! text-slate-600!">
              {{ row.usage }} <span class="text-[10px]! opacity-50!">m³</span>
            </div>
          </template>

          <template #column-amount="{ row }">
            <div class="font-black! text-slate-800!">Rp. {{ row.amount.toLocaleString() }}</div>
          </template>

          <template #column-status="{ row }">
            <span :class="getStatusClass(row.status)">
              {{ row.status }}
            </span>
          </template>
        </DataTable>

        <div v-else class="flex! flex-col!">
          <div class="p-5! border-b! border-slate-100!">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari periode..."
              class="w-full! px-5! h-12! bg-slate-50! rounded-2xl! border! border-slate-100! text-sm! focus:bg-white! focus:border-indigo-500! transition-all!"
            />
          </div>
          <div class="divide-y! divide-slate-50!">
            <div
              v-for="bill in filteredBills"
              :key="bill.id"
              class="p-5! lg:p-6! active:bg-slate-50! transition-all! cursor-pointer! group!"
              @click="$router.push({ path: '/pelanggan/tagihan-detail', query: { id: bill.id } })"
            >
              <div class="flex! items-center! justify-between! mb-5!">
                <div class="flex! items-center! gap-3!">
                  <div class="w-10! h-10! bg-indigo-50! rounded-xl! flex! items-center! justify-center! text-indigo-600! group-hover:scale-110! transition-transform!">
                    <font-awesome-icon icon="file-invoice-dollar" />
                  </div>
                  <div>
                    <div class="text-base! font-black! text-slate-800! leading-tight!">
                      {{ bill.period }}
                    </div>
                    <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mt-0.5!">
                      #INV-{{ bill.id }}
                    </div>
                  </div>
                </div>
                <span :class="getStatusClass(bill.status)">{{ bill.status }}</span>
              </div>

              <div class="space-y-3! bg-slate-50/50! p-4! rounded-2xl! border! border-slate-100/50!">
                <div class="flex! justify-between! items-center!">
                  <span class="text-xs! font-bold! text-slate-500!">Pemakaian Air</span>
                  <span class="text-xs! font-black! text-slate-800!">
                    {{ bill.usage }} m³
                  </span>
                </div>
                <div class="w-full! h-px! bg-slate-100!"></div>
                <div class="flex! justify-between! items-center!">
                  <span class="text-xs! font-bold! text-slate-500!">Total Tagihan</span>
                  <span class="text-sm! font-black! text-indigo-600!">
                    Rp. {{ bill.amount.toLocaleString() }}
                  </span>
                </div>
              </div>

              <div class="mt-4! flex! items-center! justify-center! text-[10px]! font-black! text-indigo-500! uppercase! tracking-[0.2em]! opacity-0! group-hover:opacity-100! transition-opacity!">
                Lihat Detail Lengkap
                <font-awesome-icon icon="chevron-right" class="ml-2!" />
              </div>
            </div>
          </div>
          <div v-if="filteredBills.length === 0" class="p-10! text-center!">
            <div
              class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-slate-200!"
            >
              <font-awesome-icon icon="search" size="xl" />
            </div>
            <p class="text-slate-400! font-bold! text-sm!">Tagihan tidak ditemukan</p>
          </div>
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import DataTable from '@/presentations/components/ui/DataTable.vue'
import pelangganService from '@/services/pelanggan.service'

const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
}

const bills = ref([])
const historyStats = ref({
  total_usage_3_months: 0,
  avg_amount: 0,
  current_status: '-'
})
const customerCode = ref('-')
const isLoading = ref(true)

const fetchHistory = async () => {
  isLoading.value = true
  try {
    const response = await pelangganService.getBillHistory()
    if (response.success) {
      bills.value = response.data.bills.map(bill => ({
        id: bill.id.toString(),
        period: getMonthName(bill.billing_period_month) + ' ' + bill.billing_period_year,
        usage: bill.usage_m3,
        amount: bill.total_amount,
        status: bill.status === 'paid' ? 'Lunas' : 'Belum Bayar'
      }))
      historyStats.value = response.data.stats
      customerCode.value = response.data.customer_code
    }
  } catch (error) {
    console.error('Failed to fetch history:', error)
  } finally {
    isLoading.value = false
  }
}

const getMonthName = (month) => {
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  return months[month - 1] || '-'
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchHistory()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

const searchQuery = ref('')

const tableColumns = [
  { key: 'period', title: 'Periode / No. Invoice' },
  { key: 'usage', title: 'Pemakaian' },
  { key: 'amount', title: 'Total Tagihan' },
  { key: 'status', title: 'Status' },
]

const stats = computed(() => [
  {
    label: 'Total Pemakaian (3 Bln)',
    value: `${historyStats.value.total_usage_3_months || 0} m³`,
    icon: 'droplet',
    bg: 'bg-blue-50',
    color: 'text-blue-600',
  },
  {
    label: 'Rata-rata Tagihan',
    value: `Rp. ${new Intl.NumberFormat('id-ID').format(Math.round(historyStats.value.avg_amount))}`,
    icon: 'chart-line',
    bg: 'bg-indigo-50',
    color: 'text-indigo-600',
  },
  {
    label: 'Status Saat Ini',
    value: historyStats.value.current_status,
    icon: historyStats.value.current_status === 'Lancar' ? 'check-circle' : 'exclamation-circle',
    bg: historyStats.value.current_status === 'Lancar' ? 'bg-green-50' : 'bg-amber-50',
    color: historyStats.value.current_status === 'Lancar' ? 'text-green-600' : 'text-amber-600',
  },
])

const filteredBills = computed(() => {
  if (!searchQuery.value) return bills.value
  const query = searchQuery.value.toLowerCase()
  return bills.value.filter(
    (bill) => bill.period.toLowerCase().includes(query) || bill.id.toLowerCase().includes(query),
  )
}
)

const getStatusClass = (status) => {
  if (status === 'Lunas') {
    return 'px-3! py-1! bg-green-50! text-green-600! text-[10px]! font-black! rounded-full! border! border-green-100!'
  }
  return 'px-3! py-1! bg-red-50! text-red-600! text-[10px]! font-black! rounded-full! border! border-red-100!'
}
</script>

<style scoped>
.bill-history {
  animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
