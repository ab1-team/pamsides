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
              Kode Pelanggan:
              <span class="font-black! text-indigo-600!">{{
                dashboardData.user.customer_code
              }}</span>
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
                  <span
                    class="text-3xl! lg:text-4xl! font-black! text-slate-800! tracking-tighter!"
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
                @click="goToBillDetail"
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
            <div class="p-5! lg:p-8! flex! items-start! justify-between! mb-3! gap-4!">
              <div>
                <h2 class="text-lg! lg:text-xl! font-black! text-slate-800! tracking-tight!">
                  Distribusi Penggunaan
                </h2>
                <p class="text-slate-400! text-[10px]! lg:text-xs! font-medium! mt-1!">
                  Pemakaian air 12 bulan terakhir •
                  <span class="font-black! text-indigo-500!">{{ recordedCount }}/12</span>
                  bulan tercatat
                </p>
              </div>
              <div class="flex! bg-slate-50! p-1.5! rounded-2xl! border! border-slate-100!">
                <button
                  @click="viewType = 'bar'"
                  :class="`text-[10px]! font-black! px-4! py-2! rounded-xl! transition-all! ${viewType === 'bar' ? 'bg-white! shadow-md! text-indigo-600!' : 'text-slate-400! hover:text-slate-600!'}`"
                >
                  Batang
                </button>
                <button
                  @click="viewType = 'line'"
                  :class="`text-[10px]! font-black! px-4! py-2! rounded-xl! transition-all! ${viewType === 'line' ? 'bg-white! shadow-md! text-indigo-600!' : 'text-slate-400! hover:text-slate-600!'}`"
                >
                  Garis
                </button>
              </div>
            </div>

            <!-- Summary metrics -->
            <div class="px-5! lg:px-8! grid! grid-cols-2! md:grid-cols-4! gap-3! mb-4!">
              <div class="p-3! rounded-2xl! bg-indigo-50! border! border-indigo-100!">
                <div class="text-[9px]! font-black! text-indigo-400! uppercase! tracking-widest!">
                  Rata-rata
                </div>
                <div class="text-base! font-black! text-indigo-700! mt-1!">
                  {{ formatDecimal(avgUsage) }} <span class="text-[10px]!">m³</span>
                </div>
              </div>
              <div class="p-3! rounded-2xl! bg-emerald-50! border! border-emerald-100!">
                <div class="text-[9px]! font-black! text-emerald-500! uppercase! tracking-widest!">
                  Minimum
                </div>
                <div class="text-base! font-black! text-emerald-700! mt-1!">
                  {{ formatDecimal(summary.min_m3) }} <span class="text-[10px]!">m³</span>
                </div>
              </div>
              <div class="p-3! rounded-2xl! bg-rose-50! border! border-rose-100!">
                <div class="text-[9px]! font-black! text-rose-500! uppercase! tracking-widest!">
                  Maksimum
                </div>
                <div class="text-base! font-black! text-rose-700! mt-1!">
                  {{ formatDecimal(summary.max_m3) }} <span class="text-[10px]!">m³</span>
                </div>
              </div>
              <div
                :class="`p-3! rounded-2xl! border! ${trend.bg}! ${trend.color.replace('text-rose-600', 'border-rose-100').replace('text-emerald-600', 'border-emerald-100').replace('text-slate-500', 'border-slate-200')}!`"
              >
                <div
                  :class="`text-[9px]! font-black! uppercase! tracking-widest! ${trend.color}! opacity-70!`"
                >
                  Tren 3 bln
                </div>
                <div :class="`flex! items-center! gap-1! mt-1! ${trend.color}!`">
                  <font-awesome-icon :icon="trend.icon" class="text-xs!" />
                  <span class="text-base! font-black!">{{ trend.label }}</span>
                </div>
              </div>
            </div>

            <div
              class="px-5! lg:px-8! pb-8! lg:pb-10! flex! flex-col! items-center! justify-center! min-h-[260px]! lg:min-h-[280px]!"
            >
              <div
                v-if="usageValues.length === 0"
                class="flex-1! flex! flex-col! items-center! justify-center! gap-3!"
              >
                <div
                  class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! text-slate-200!"
                >
                  <font-awesome-icon icon="chart-bar" size="2x" />
                </div>
                <p class="text-slate-400! text-xs! font-medium!">Belum ada riwayat pemakaian</p>
              </div>

              <div
                v-else-if="viewType === 'bar'"
                class="w-full! relative!"
              >
                <svg viewBox="0 0 100 40" class="w-full! h-56! lg:h-64! overflow-visible!">
                  <defs>
                    <linearGradient id="barGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #6366f1; stop-opacity: 1" />
                      <stop offset="100%" style="stop-color: #a5b4fc; stop-opacity: 0.7" />
                    </linearGradient>
                    <linearGradient id="barGradCurrent" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #4f46e5; stop-opacity: 1" />
                      <stop offset="100%" style="stop-color: #06b6d4; stop-opacity: 0.8" />
                    </linearGradient>
                  </defs>
                  <g stroke="#f1f5f9" stroke-width="0.2">
                    <line x1="4" :y1="getPointY(maxUsage * 0.75)" x2="96" :y2="getPointY(maxUsage * 0.75)" />
                    <line x1="4" :y1="getPointY(maxUsage * 0.5)" x2="96" :y2="getPointY(maxUsage * 0.5)" />
                    <line x1="4" :y1="getPointY(maxUsage * 0.25)" x2="96" :y2="getPointY(maxUsage * 0.25)" />
                    <line x1="4" :y1="34" x2="96" y2="34" stroke="#cbd5e1" stroke-width="0.3" />
                  </g>
                  <g v-if="avgLineY !== null">
                    <line x1="4" :y1="avgLineY" x2="96" :y2="avgLineY" stroke="#f59e0b" stroke-width="0.3" stroke-dasharray="1,1" opacity="0.7" />
                    <text x="95" :y="avgLineY - 0.8" text-anchor="end" class="text-[2.2px]! font-black! fill-amber-600!">RATA²</text>
                  </g>
                  <g v-for="b in barChartData" :key="'bar-' + b.idx">
                    <rect :x="b.barX" :y="b.barY" :width="b.barWidth" :height="b.barHeight" :fill="b.isCurrent ? 'url(#barGradCurrent)' : 'url(#barGrad)'" :opacity="b.hasData ? 1 : 0.25" rx="0.6" />
                    <text v-if="b.value > 0" :x="b.cx" :y="b.barY - 1.2" text-anchor="middle" class="text-[2.2px]! font-black! fill-slate-700!">{{ formatDecimal(b.value) }}</text>
                  </g>
                </svg>
                <div class="flex! justify-between! mt-3! px-1!">
                  <span
                    v-for="(label, idx) in usageLabelsCompact"
                    :key="'lbl-' + idx"
                    :class="`text-[9px]! font-black! uppercase! ${distributionSeries[idx].isCurrent ? 'text-indigo-600!' : 'text-slate-400!'}`"
                  >
                    {{ label }}
                  </span>
                </div>
              </div>

              <div
                v-else-if="viewType === 'line'"
                class="w-full! relative!"
              >
                <svg viewBox="0 0 100 40" class="w-full! h-56! lg:h-64! overflow-visible!">
                  <defs>
                    <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" style="stop-color: #6366f1; stop-opacity: 0.35" />
                      <stop offset="100%" style="stop-color: #6366f1; stop-opacity: 0" />
                    </linearGradient>
                  </defs>
                  <path :d="generateAreaPath" fill="url(#areaGradient)" />
                  <path
                    :d="generateLinePath"
                    fill="none"
                    stroke="#6366f1"
                    stroke-width="0.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <g v-if="avgLineY !== null">
                    <line x1="4" :y1="avgLineY" x2="96" :y2="avgLineY" stroke="#f59e0b" stroke-width="0.25" stroke-dasharray="1,1" opacity="0.7" />
                  </g>
                  <g v-for="(p, idx) in distributionSeries" :key="'pt-' + idx">
                    <circle
                      :cx="getPointX(idx)"
                      :cy="getPointY(p.usage_m3)"
                      :r="p.is_current ? 0.9 : 0.6"
                      :fill="p.is_current ? '#4f46e5' : 'white'"
                      :stroke="p.is_current ? '#4f46e5' : '#6366f1'"
                      stroke-width="0.3"
                    />
                    <title>{{ p.label }}: {{ formatDecimal(p.usage_m3) }} m³</title>
                  </g>
                </svg>
                <div class="flex! justify-between! mt-3! px-1!">
                  <span
                    v-for="(label, idx) in usageLabelsCompact"
                    :key="'ll-' + idx"
                    :class="`text-[9px]! font-black! uppercase! ${distributionSeries[idx].is_current ? 'text-indigo-600!' : 'text-slate-400!'}`"
                  >
                    {{ label }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Top 3 bulan tertinggi -->
            <div
              v-if="usageValues.length > 0 && recordedCount > 0"
              class="px-5! lg:px-8! pb-8! lg:pb-10! border-t! border-slate-100! pt-5!"
            >
              <div class="flex! items-center! justify-between! mb-3!">
                <h4 class="text-xs! font-black! text-slate-700! uppercase! tracking-widest!">
                  3 Bulan Pemakaian Tertinggi
                </h4>
                <span class="text-[10px]! text-slate-400! font-medium!">
                  Total {{ formatDecimal(totalUsage) }} m³ / {{ recordedCount }} bulan
                </span>
              </div>
              <div class="grid! grid-cols-1! sm:grid-cols-3! gap-3!">
                <div
                  v-for="(top, idx) in topMonths"
                  :key="'top-' + idx"
                  class="flex! items-center! gap-3! p-3! rounded-2xl! bg-slate-50! border! border-slate-100!"
                >
                  <div
                    :class="`w-9! h-9! rounded-xl! flex! items-center! justify-center! text-xs! font-black! ${idx === 0 ? 'bg-rose-100! text-rose-600!' : idx === 1 ? 'bg-amber-100! text-amber-600!' : 'bg-slate-200! text-slate-600!'}`"
                  >
                    #{{ idx + 1 }}
                  </div>
                  <div class="flex-1! min-w-0!">
                    <div class="text-[10px]! font-black! text-slate-400! uppercase! tracking-wide!">
                      {{ top.label }}
                    </div>
                    <div class="text-sm! font-black! text-slate-800!">
                      {{ formatDecimal(top.value) }} m³
                    </div>
                  </div>
                  <span
                    v-if="top.bill_status === 'paid'"
                    class="text-[9px]! font-black! text-emerald-600! bg-emerald-50! px-2! py-1! rounded-full! border! border-emerald-100!"
                    >LUNAS</span
                  >
                  <span
                    v-else-if="top.bill_status === 'unpaid'"
                    class="text-[9px]! font-black! text-rose-600! bg-rose-50! px-2! py-1! rounded-full! border! border-rose-100!"
                    >BELUM</span
                  >
                  <span
                    v-else
                    class="text-[9px]! font-black! text-slate-500! bg-white! px-2! py-1! rounded-full! border! border-slate-100!"
                    >—</span
                  >
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
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import pelangganService from '@/services/pelanggan.service'
import Swal from 'sweetalert2'

const router = useRouter()

const goToBillDetail = () => {
  const billId = dashboardData.value.latest_bill?.id
  if (!billId) {
    Swal.fire({
      icon: 'info',
      title: 'Belum Ada Tagihan',
      text: 'Saat ini belum ada tagihan yang tersedia untuk ditampilkan.',
      confirmButtonColor: '#4f46e5',
    })
    return
  }
  router.push({ path: '/app/pelanggan/tagihan-detail', query: { id: billId } })
}

const MONTH_SHORT = [
  'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
  'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
]

const viewType = ref('bar')

const dashboardData = ref({
  user: { name: '', customer_code: '' },
  latest_bill: null,
  usage_history: [],
  distribution: {
    series: [],
    months_count: 12,
    summary: {
      total_m3: 0,
      avg_m3: 0,
      max_m3: 0,
      min_m3: 0,
      recorded_months: 0,
      trend_direction: 'flat',
      trend_percent: 0,
      recent_avg_m3: 0,
      previous_avg_m3: 0,
    },
  },
  balance: 0,
})

const distributionSeries = computed(() => {
  const series = dashboardData.value.distribution?.series || []
  return series.map((p) => ({
    ...p,
    label: `${MONTH_SHORT[p.month - 1]} ${String(p.year).slice(-2)}`,
    shortLabel: MONTH_SHORT[p.month - 1],
  }))
})

const usageValues = computed(() => distributionSeries.value.map((p) => Number(p.usage_m3 || 0)))

const usageLabels = computed(() => distributionSeries.value.map((p) => p.label))

const usageLabelsCompact = computed(() => distributionSeries.value.map((p) => p.shortLabel))

const recordedCount = computed(() =>
  distributionSeries.value.filter((p) => p.has_reading || p.has_bill).length,
)

const summary = computed(() => dashboardData.value.distribution?.summary || {})

const totalUsage = computed(() => summary.value.total_m3 || 0)
const avgUsage = computed(() => summary.value.avg_m3 || 0)
const maxUsage = computed(() => Math.max(...usageValues.value, 1))

const trend = computed(() => {
  const dir = summary.value.trend_direction || 'flat'
  const pct = Math.abs(Number(summary.value.trend_percent || 0))
  if (dir === 'up') return { icon: 'arrow-up', color: 'text-rose-600', bg: 'bg-rose-50', label: `+${pct}%` }
  if (dir === 'down') return { icon: 'arrow-down', color: 'text-emerald-600', bg: 'bg-emerald-50', label: `-${pct}%` }
  return { icon: 'equals', color: 'text-slate-500', bg: 'bg-slate-100', label: 'Stabil' }
})

// SVG chart geometry (viewBox 100x40)
const CHART_LEFT = 4
const CHART_RIGHT = 96
const CHART_TOP = 4
const CHART_BOTTOM = 34

const getPointX = (idx) => {
  const len = usageValues.value.length
  if (len <= 1) return (CHART_LEFT + CHART_RIGHT) / 2
  return CHART_LEFT + (idx / (len - 1)) * (CHART_RIGHT - CHART_LEFT)
}

const getPointY = (val) => {
  const safeMax = Math.max(maxUsage.value, 1)
  return CHART_BOTTOM - (Number(val) / safeMax) * (CHART_BOTTOM - CHART_TOP)
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
  const last = usageValues.value.length - 1
  return `${line} L ${getPointX(last)} ${CHART_BOTTOM} L ${getPointX(0)} ${CHART_BOTTOM} Z`
})

const avgLineY = computed(() => {
  if (avgUsage.value <= 0) return null
  return getPointY(avgUsage.value)
})

// Bar chart geometry untuk distribusi per bulan (lebih informatif dari pie)
const barChartData = computed(() => {
  const len = distributionSeries.value.length
  const slotWidth = (CHART_RIGHT - CHART_LEFT) / Math.max(len, 1)
  const barWidth = Math.max(slotWidth * 0.55, 1.2)
  return distributionSeries.value.map((p, idx) => {
    const cx = CHART_LEFT + slotWidth * idx + slotWidth / 2
    const baseY = CHART_BOTTOM
    const topY = getPointY(p.usage_m3)
    return {
      idx,
      label: p.shortLabel,
      value: p.usage_m3,
      cx,
      barX: cx - barWidth / 2,
      barY: Math.min(topY, baseY),
      barHeight: Math.max(Math.abs(baseY - topY), 0.5),
      hasData: p.has_reading || p.has_bill,
      isCurrent: p.is_current,
    }
  })
})

const topMonths = computed(() => {
  return [...distributionSeries.value]
    .filter((p) => p.usage_m3 > 0)
    .sort((a, b) => b.usage_m3 - a.usage_m3)
    .slice(0, 3)
})

const totalUsageRupiahEquivalent = computed(() => {
  const bill = dashboardData.value.latest_bill
  if (!bill) return null
  const avg = avgUsage.value
  if (!avg) return Number(bill.total_amount) || 0
  // estimasi kasar: skala tagihan terakhir ke rata-rata pemakaian
  const ratio = avg / Math.max(Number(bill.usage_m3) || 1, 1)
  return Math.round((Number(bill.total_amount) || 0) * ratio)
})

const formatNumber = (num) => new Intl.NumberFormat('id-ID').format(Number(num) || 0)

const formatDecimal = (num) =>
  new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0, minimumFractionDigits: 0 }).format(
    Number(num) || 0,
  )

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
      dashboardData.value = {
        ...response.data,
        distribution: response.data.distribution || dashboardData.value.distribution,
      }
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
    path: '/app/pelanggan/lapor-gangguan?from=dashboard',
  },
  {
    title: 'Riwayat Tagihan',
    desc: 'Lihat pembayaran terdahulu',
    icon: 'history',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
    path: '/app/pelanggan/riwayat-tagihan?from=dashboard',
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
