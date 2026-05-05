<template>
  <div class="pelanggan-dashboard">
    <div class="max-w-7xl! mx-auto!">
      <div
        class="mb-10! lg:mb-14! flex! flex-col! lg:flex-row! lg:items-center! justify-between! gap-8!"
      >
        <div class="flex! flex-col! items-center! lg:items-start! w-full! lg:w-auto!">
          <div class="flex! justify-center! lg:justify-start! mb-4!">
            <div
              class="inline-flex! items-center! gap-2! bg-indigo-50! px-4! py-1.5! rounded-full! border! border-indigo-100!"
            >
              <div class="w-1.5! h-1.5! bg-indigo-600! rounded-full! animate-pulse!"></div>
              <span
                class="text-[9px]! lg:text-[10px]! font-black! text-indigo-600! uppercase! tracking-[0.2em]!"
                >Ringkasan Layanan</span
              >
            </div>
          </div>
          <div class="text-center! lg:text-left!">
            <h1
              class="text-2xl! sm:text-3xl! lg:text-5xl! font-black! text-slate-800! tracking-tighter! mb-2!"
            >
              Selamat Datang,<br v-if="isMobile" />
              <span
                class="bg-gradient-to-r! from-indigo-600! to-blue-500! bg-clip-text! text-transparent!"
              >{{ dashboardData.user.name }}</span
              >
              👋
            </h1>
            <p class="text-slate-500! font-medium! text-sm! lg:text-lg! max-w-md! lg:mx-0!">
              Kode Pelanggan: <span class="font-black! text-indigo-600!">{{ dashboardData.user.customer_code }}</span>
            </p>
          </div>
        </div>
      </div>
      
      <div class="grid! grid-cols-1! lg:grid-cols-12! gap-10! mb-12!">
        <div class="lg:col-span-4!">
          <ContentCard
            variant="elevated"
            padding="none"
            class="h-full! border-0! shadow-[0_25px_50px_-12px_rgba(0,0,0,0.15)]! rounded-3xl! overflow-hidden! bg-white! relative!"
          >
            <div
              class="absolute! top-0! left-0! right-0! h-2! bg-gradient-to-r! from-indigo-500! to-blue-400!"
            ></div>

            <div class="p-5! lg:p-8!">
              <div class="flex! items-center! justify-between! mb-6! lg:mb-8!">
                <div
                  class="w-14! h-14! rounded-full! bg-indigo-50! flex! items-center! justify-center! text-indigo-600! shadow-inner!"
                >
                  <font-awesome-icon icon="receipt" size="lg" />
                </div>
                <div class="flex! items-center!">
                  <span
                    v-if="dashboardData.latest_bill?.status === 'unpaid'"
                    class="px-4! py-1.5! bg-red-50! text-red-600! text-[10px]! font-black! rounded-full! border! border-red-100! tracking-widest!"
                    >BELUM LUNAS</span
                  >
                  <span
                    v-else
                    class="px-4! py-1.5! bg-emerald-50! text-emerald-600! text-[10px]! font-black! rounded-full! border! border-emerald-100! tracking-widest!"
                    >LUNAS</span
                  >
                </div>
              </div>

              <div class="mb-8!">
                <h3
                  class="text-slate-400! text-[10px]! font-black! uppercase! tracking-widest! mb-2!"
                >
                  Total Tagihan
                </h3>
                <div class="flex! items-baseline! justify-end! gap-1!">
                  <span class="text-base! lg:text-lg! font-black! text-slate-400!">Rp.</span>
                  <span class="text-3xl! lg:text-4xl! font-black! text-slate-800! tracking-tighter!"
                    >{{ formatNumber(dashboardData.latest_bill?.total_amount || 0) }}</span
                  >
                </div>
              </div>

              <div
                class="space-y-4! mb-10! bg-slate-50! p-5! rounded-3xl! border! border-slate-100!"
              >
                <div class="flex! justify-between! items-center!">
                  <span class="text-sm! font-bold! text-slate-500!">Pemakaian Air</span>
                  <span class="text-sm! font-black! text-slate-800!">
                    {{ dashboardData.latest_bill?.usage_m3 || 0 }} m³
                  </span>
                </div>
                <div class="w-full! h-px! bg-slate-200!"></div>
                <div class="flex! justify-between! items-center!">
                  <span class="text-sm! font-bold! text-slate-500!">Jatuh Tempo</span>
                  <span class="text-sm! font-black! text-slate-800!">
                    {{ formatDate(dashboardData.latest_bill?.due_date) }}
                  </span>
                </div>
              </div>

              <BaseButton
                variant="primary-gradient"
                block
                class="rounded-full! font-black! h-12! text-sm! shadow-xl! shadow-indigo-200! hover:-translate-y-1! transition-all!"
                @click="$router.push('/pelanggan/tagihan-detail')"
                >
                CEK DETAIL
                <font-awesome-icon icon="chevron-right" class="ml-2! text-[10px]!" />
              </BaseButton>
            </div>
          </ContentCard>
        </div>
        <div class="lg:col-span-8!">
          <ContentCard
            variant="elevated"
            padding="none"
            class="h-full! border-0! shadow-[0_25px_50px_-12px_rgba(0,0,0,0.1)]! rounded-3xl! bg-white!"
          >
            <div class="p-5! lg:p-8! flex! items-center! justify-between! mb-2!">
              <div>
                <h2 class="text-lg! lg:text-xl! font-black! text-slate-800! tracking-tight!">
                  Distribusi Penggunaan
                </h2>
                <p class="text-slate-400! text-[10px]! lg:text-xs! font-medium! mt-1!">
                  Perbandingan volume air 5 bulan terakhir
                </p>
              </div>
              <div class="flex! bg-slate-50! p-1.5! rounded-2xl! border! border-slate-100!">
                <button
                  @click="viewType = 'bar'"
                  :class="`text-[10px]! font-black! px-4! py-2! rounded-xl! transition-all! ${viewType === 'bar' ? 'bg-white! shadow-md! text-indigo-600!' : 'text-slate-400! hover:text-slate-600!'}`"
                >
                  Bar
                </button>
                <button
                  @click="viewType = 'pie'"
                  :class="`text-[10px]! font-black! px-4! py-2! rounded-xl! transition-all! ${viewType === 'pie' ? 'bg-white! shadow-md! text-indigo-600!' : 'text-slate-400! hover:text-slate-600!'}`"
                >
                  Pie
                </button>
              </div>
            </div>

            <div
              class="px-5! lg:px-8! pb-8! lg:pb-10! flex! flex-col! lg:flex-row! items-center! justify-center! gap-8! lg:gap-10! min-h-[280px]! lg:min-h-[300px]!"
            >
              <div v-if="usageValues.length === 0" class="flex-1! flex! flex-col! items-center! justify-center! gap-3!">
                <div class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! text-slate-200!">
                  <font-awesome-icon icon="chart-bar" size="2x" />
                </div>
                <p class="text-slate-400! text-xs! font-medium!">Belum ada riwayat pemakaian</p>
              </div>

              <div v-else-if="viewType === 'bar'" class="flex-1! w-full! h-64! lg:h-72! relative! flex! flex-col! pt-10!">
                <svg viewBox="0 0 100 40" class="w-full! h-full! overflow-visible!">
                  <defs>
                    <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #6366f1; stop-opacity: 0.3" />
                      <stop offset="100%" style="stop-color: #6366f1; stop-opacity: 0" />
                    </linearGradient>
                  </defs>

                  <path 
                    :d="generateAreaPath" 
                    fill="url(#areaGradient)"
                  />

                  <path 
                    :d="generateLinePath" 
                    fill="none" 
                    stroke="#6366f1" 
                    stroke-width="1.5" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                    class="drop-shadow-[0_4px_10px_rgba(99,102,241,0.4)]!"
                  />

                  <g v-for="(val, idx) in usageValues" :key="'point-' + idx">
                    <circle 
                      :cx="getPointX(idx)" 
                      :cy="getPointY(val)" 
                      r="2.5" 
                      fill="white" 
                      stroke="#6366f1" 
                      stroke-width="1.5"
                      class="hover:r-4! transition-all! cursor-pointer!"
                    />
                    <text 
                      :x="getPointX(idx)" 
                      :y="getPointY(val) - 5" 
                      text-anchor="middle" 
                      class="text-[4px]! font-black! fill-slate-800!"
                    >
                      {{ val }}
                    </text>
                  </g>
                </svg>

                <div class="flex! justify-between! mt-6! px-2!">
                  <span 
                    v-for="(label, idx) in usageLabels" 
                    :key="'lbl-' + idx"
                    class="text-[10px]! font-black! text-slate-400! uppercase! tracking-tighter!"
                  >
                    {{ label }}
                  </span>
                </div>
              </div>

              <div v-else-if="viewType === 'pie'" class="relative! w-48! lg:w-52! h-48! lg:h-52! flex-shrink-0!">
                <svg viewBox="0 0 100 100" class="w-full! h-full! -rotate-90!">
                  <circle
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    stroke="#f1f5f9"
                    stroke-width="12"
                  />

                  <circle
                    v-for="(val, idx) in usageValues"
                    :key="'pie-' + idx"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="transparent"
                    :stroke="idx === (usageValues.length - 1) ? 'url(#indigoGradient)' : getChartColor(idx)"
                    stroke-width="12"
                    :stroke-dasharray="`${getDashArray(val)} 100`"
                    :stroke-dashoffset="getDashOffset(idx)"
                    :class="idx === (usageValues.length - 1) ? 'transition-all! duration-1000! hover:stroke-width-[14!]' : 'opacity-80!'"
                  />

                  <defs>
                    <linearGradient id="indigoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" style="stop-color: #4f46e5; stop-opacity: 1" />
                      <stop offset="100%" style="stop-color: #06b6d4; stop-opacity: 1" />
                    </linearGradient>
                  </defs>
                </svg>
                <div class="absolute! inset-0! flex! flex-col! items-center! justify-center!">
                  <span 
                    :class="[
                      'font-black! text-slate-800! transition-all!',
                      totalUsage.toString().length > 5 ? 'text-lg!' : 'text-2xl!'
                    ]"
                  >
                    {{ formatNumber(totalUsage) }}
                  </span>
                  <span class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest!"
                    >Total m³</span
                  >
                </div>
              </div>


              <div class="grid! grid-cols-1! gap-4! w-full! max-w-[240px]!">
                <div
                  v-for="(val, idx) in usageValues"
                  :key="idx"
                  class="flex! items-center! justify-between! p-3! rounded-2xl! hover:bg-slate-50! transition-colors! group!"
                >
                  <div class="flex! items-center! gap-3!">
                    <div
                      class="w-3! h-3! rounded-full!"
                      :class="idx === (usageValues.length - 1) ? 'bg-indigo-600!' : 'bg-indigo-200!'"
                    ></div>
                    <span class="text-sm! font-bold! text-slate-600!">{{
                      usageLabels[idx]
                    }}</span>
                  </div>
                  <div class="flex! items-center! gap-2!">
                    <span class="text-sm! font-black! text-slate-800!">{{ val }} m³</span>
                    <span class="text-[10px]! font-bold! text-slate-400! w-8! text-right!">
                      {{ totalUsage > 0 ? Math.round((val / totalUsage) * 100) : 0 }}%
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </ContentCard>
        </div>
      </div>

      <div class="grid! grid-cols-1! md:grid-cols-3! gap-8!">
        <ContentCard
          v-for="(action, idx) in actions"
          :key="idx"
          variant="elevated"
          padding="none"
          clickable
          class="border-0! shadow-[0_15px_30px_-10px_rgba(0,0,0,0.12)]! group! rounded-2xl! lg:rounded-3xl! overflow-hidden! bg-white! hover:-translate-y-2! hover:shadow-[0_25px_50px_-12px_rgba(0,0,0,0.2)]! transition-all!"
          @click="action.path ? $router.push(action.path) : null"
        >
          <div class="p-4! lg:p-5! flex! items-center! gap-4!">
            <div
              :class="`w-12! h-12! rounded-full! ${action.bg}! ${action.color}! flex! items-center! justify-center! text-lg! flex-shrink-0! shadow-inner! group-hover:scale-110! transition-transform!`"
            >
              <font-awesome-icon :icon="action.icon" />
            </div>
            <div>
              <h4 class="text-base! font-black! text-slate-800! leading-tight!">
                {{ action.title }}
              </h4>
              <p class="text-[10px]! text-slate-400! font-bold! mt-1! uppercase! tracking-wider!">
                {{ action.desc }}
              </p>
            </div>
            <div class="ml-auto! text-slate-300! group-hover:text-indigo-500! transition-colors!">
              <font-awesome-icon icon="chevron-right" />
            </div>
          </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import pelangganService from '@/services/pelanggan.service'

const viewType = ref('bar')

const dashboardData = ref({
  user: { name: '', customer_code: '' },
  latest_bill: null,
  usage_history: [],
  balance: 0,
})

const usageLabels = computed(() => {
  return dashboardData.value.usage_history.map(bill => {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    return months[bill.billing_period_month - 1]
  })
})

const usageValues = computed(() => {
  return dashboardData.value.usage_history.map(bill => parseFloat(bill.usage_m3 || 0))
})

const totalUsage = computed(() => {
  return usageValues.value.reduce((acc, val) => acc + val, 0)
})

const maxUsage = computed(() => {
  return Math.max(...usageValues.value, 1)
})

const getPointX = (idx) => {
  if (usageValues.value.length <= 1) return 50
  return (idx / (usageValues.value.length - 1)) * 100
}

const getPointY = (val) => {
  return 35 - (val / maxUsage.value) * 30
}

const generateLinePath = computed(() => {
  if (usageValues.value.length === 0) return ''
  let d = `M ${getPointX(0)} ${getPointY(usageValues.value[0])}`
  
  for (let i = 1; i < usageValues.value.length; i++) {
    const x = getPointX(i)
    const y = getPointY(usageValues.value[i])
    const prevX = getPointX(i - 1)
    const prevY = getPointY(usageValues.value[i - 1])
    
    const cp1x = prevX + (x - prevX) / 2
    d += ` C ${cp1x} ${prevY}, ${cp1x} ${y}, ${x} ${y}`
  }
  return d
})

const generateAreaPath = computed(() => {
  const line = generateLinePath.value
  if (!line) return ''
  return `${line} L ${getPointX(usageValues.value.length - 1)} 40 L ${getPointX(0)} 40 Z`
})

const getDashArray = (val) => {
  if (totalUsage.value === 0) return 0
  return (val / totalUsage.value) * 100
}

const getDashOffset = (idx) => {
  let offset = 0
  for (let i = 0; i < idx; i++) {
    offset += (usageValues.value[i] / totalUsage.value) * 100
  }
  return -offset
}

const getChartColor = (idx) => {
  const colors = ['#c7d2fe', '#a5b4fc', '#818cf8', '#6366f1', '#4f46e5']
  return colors[idx] || '#6366f1'
}

const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

const fetchDashboardData = async () => {
  try {
    const response = await pelangganService.getDashboardData()
    if (response.success) {
      dashboardData.value = response.data
    }
  } catch (error) {
    console.error('Failed to fetch dashboard data:', error)
  }
}

const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  fetchDashboardData()
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

const actions = ref([
  {
    title: 'Lapor Gangguan',
    desc: 'Air mati atau pipa bocor?',
    icon: 'headset',
    color: 'text-red-600',
    bg: 'bg-red-50',
    path: '/pelanggan/lapor-gangguan?from=dashboard',
  },
  {
    title: 'Riwayat Tagihan',
    desc: 'Lihat pembayaran terdahulu',
    icon: 'history',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
    path: '/pelanggan/riwayat-tagihan?from=dashboard',
  },
  {
    title: 'Info Pamsimas',
    desc: 'Berita & pengumuman terbaru',
    icon: 'bullhorn',
    color: 'text-amber-600',
    bg: 'bg-amber-50',
  },
])
</script>

<style scoped>
.pelanggan-dashboard {
  animation: fadeIn 1s cubic-bezier(0.16, 1, 0.3, 1);
}

.ease-out-expo {
  transition-timing-function: cubic-bezier(0.19, 1, 0.22, 1);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
