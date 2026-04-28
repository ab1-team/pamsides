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
            <div class="text-sm! lg:text-base! text-indigo-600! font-black!">PAM-2025-09821</div>
          </div>
        </div>
      </div>

      <div class="grid! grid-cols-1! lg:grid-cols-3! gap-4! lg:gap-6! mb-10!">
        <ContentCard
          v-for="(stat, idx) in stats"
          :key="idx"
          variant="elevated"
          class="border-0! shadow-xl! shadow-slate-200/40! hover:scale-[1.02]! transition-transform! p-4! lg:p-6!"
        >
          <div class="flex! items-center! justify-between! gap-4!">
            <div class="flex! items-center! gap-3! lg:gap-4!">
              <div
                :class="`w-10! h-10! lg:w-12! lg:h-12! rounded-xl! lg:rounded-2xl! ${stat.bg}! ${stat.color}! flex! items-center! justify-center! flex-shrink-0!`"
              >
                <font-awesome-icon :icon="stat.icon" class="text-base! lg:text-xl! !m-auto!" />
              </div>
              <span
                class="text-[10px]! lg:text-xs! font-black! text-slate-400! uppercase! tracking-wider! leading-tight! max-w-[80px]! lg:max-w-none!"
              >
                {{ stat.label }}
              </span>
            </div>
            <div class="text-right!">
              <div class="text-lg! lg:text-2xl! font-black! text-slate-800! tracking-tighter!">
                {{ stat.value }}
              </div>
              <div
                v-if="idx === 2"
                class="text-[9px]! font-bold! text-green-500! uppercase! tracking-widest!"
              >
                STATUS OK
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
          @row-click="$router.push('/pelanggan/tagihan-detail')"
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
          <div class="divide-y! divide-slate-100!">
            <div
              v-for="bill in filteredBills"
              :key="bill.id"
              class="p-5! active:bg-slate-50! transition-colors! flex! items-center! gap-4! cursor-pointer!"
              @click="$router.push('/pelanggan/tagihan-detail')"
            >
              <div class="flex-1!">
                <div class="flex! items-center! justify-between! mb-2!">
                  <div class="font-black! text-slate-800!">{{ bill.period }}</div>
                  <span :class="getStatusClass(bill.status)">{{ bill.status }}</span>
                </div>
                <div class="flex! items-center! justify-between!">
                  <div class="text-[10px]! font-black! text-slate-300! uppercase! tracking-widest!">
                    #{{ bill.id }}
                  </div>
                  <div class="flex! items-center! gap-5!">
                    <div class="text-right!">
                      <div class="text-[10px]! font-bold! text-slate-400! uppercase! mb-0.5!">
                        Pemakaian
                      </div>
                      <div class="text-sm! font-black! text-slate-700!">
                        {{ bill.usage }}<span class="text-[10px]! opacity-30! ml-1!">m³</span>
                      </div>
                    </div>
                    <div class="text-right!">
                      <div class="text-[10px]! font-bold! text-slate-400! uppercase! mb-0.5!">
                        Total Tagihan
                      </div>
                      <div class="text-sm! font-black! text-indigo-600!">
                        Rp. {{ (bill.amount / 1000).toFixed(0) }}k
                      </div>
                    </div>
                  </div>
                </div>
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

const isMobile = ref(false)
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
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

const stats = ref([
  {
    label: 'Total Pemakaian (6 Bln)',
    value: '142 m³',
    icon: 'droplet',
    bg: 'bg-blue-50',
    color: 'text-blue-600',
  },
  {
    label: 'Rata-rata Tagihan',
    value: 'Rp. 82.500',
    icon: 'chart-line',
    bg: 'bg-indigo-50',
    color: 'text-indigo-600',
  },
  {
    label: 'Status Saat Ini',
    value: 'Lancar',
    icon: 'check-circle',
    bg: 'bg-green-50',
    color: 'text-green-600',
  },
])

const bills = ref([
  { id: '882109', period: 'Mei 2025', usage: 25, amount: 85000, status: 'Belum Bayar' },
  { id: '881092', period: 'April 2025', usage: 22, amount: 78000, status: 'Lunas' },
  { id: '879921', period: 'Maret 2025', usage: 28, amount: 92000, status: 'Lunas' },
  { id: '878810', period: 'Februari 2025', usage: 18, amount: 65000, status: 'Lunas' },
  { id: '877701', period: 'Januari 2025', usage: 20, amount: 72000, status: 'Lunas' },
])

const filteredBills = computed(() => {
  if (!searchQuery.value) return bills.value
  const query = searchQuery.value.toLowerCase()
  return bills.value.filter(
    (bill) => bill.period.toLowerCase().includes(query) || bill.id.toLowerCase().includes(query),
  )
})

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
