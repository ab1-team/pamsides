<template>
  <div class="dashboard-container">
    <div class="grid! items-start! grid-cols-1! sm:grid-cols-2! lg:grid-cols-4! gap-4! mb-8!">
      <statCard
        label="INSTALASI"
        :value="statsSummary.instalasi"
        :link="null"
        :progress="statsSummaryProgress.instalasi"
        @detail-click="openDetailModal('instalasi')"
      >
        <font-awesome-icon icon="home" />
      </statCard>

      <statCard
        label="PEMAKAIAN"
        :value="statsSummary.pemakaian"
        :link="null"
        :progress="statsSummaryProgress.pemakaian"
        @detail-click="openDetailModal('pemakaian')"
      >
        <font-awesome-icon icon="tint" />
      </statCard>

      <statCard
        label="TUNGGAKAN"
        :value="statsSummary.tunggakan"
        :link="null"
        :progress="statsSummaryProgress.tunggakan"
        @detail-click="openDetailModal('tunggakan')"
      >
        <font-awesome-icon icon="balance-scale" />
      </statCard>

      <statCard
        label="TAGIHAN"
        :value="statsSummary.tagihan"
        :link="null"
        :progress="statsSummaryProgress.tagihan"
        @detail-click="openDetailModal('tagihan')"
      >
        <font-awesome-icon icon="file-invoice" />
      </statCard>
    </div>
    <div class="grid! grid-cols-1! lg:grid-cols-12! gap-6!">
      <div class="lg:col-span-4! flex! flex-col! gap-6!">
        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          class="relative! overflow-hidden! border-l-4! border-l-blue-500!"
        >
          <div class="flex! items-center! justify-between! mb-4!">
            <span class="text-[10px]! font-bold! text-slate-400! tracking-wider! uppercase!"
              >Pendapatan</span
            >
            <div
              :class="trendBadgeClass(financeTrend.pendapatan)"
              class="flex! items-center! gap-1! px-2! py-1! rounded-md! text-[10px]! font-bold!"
            >
              <font-awesome-icon :icon="trendIcon(financeTrend.pendapatan)" class="w-2.5! h-2.5!" />
              <span>{{ trendLabel(financeTrend.pendapatan) }}</span>
            </div>
          </div>
          <div class="flex! items-baseline! gap-1!">
            <span class="text-sm! font-bold! text-slate-400!">Rp</span>
            <span class="text-2xl! font-extrabold! text-slate-600! tracking-tight!">
              {{ formattedPendapatan.replace('Rp', '').trim() }}
            </span>
          </div>
        </ContentCard>

        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          class="relative! overflow-hidden! border-l-4! border-l-amber-500!"
        >
          <div class="flex! items-center! justify-between! mb-4!">
            <span class="text-[10px]! font-bold! text-slate-400! tracking-wider! uppercase!"
              >Beban</span
            >
            <div
              :class="trendBadgeClass(financeTrend.beban, true)"
              class="flex! items-center! gap-1! px-2! py-1! rounded-md! text-[10px]! font-bold!"
            >
              <font-awesome-icon :icon="trendIcon(financeTrend.beban)" class="w-2.5! h-2.5!" />
              <span>{{ trendLabel(financeTrend.beban) }}</span>
            </div>
          </div>
          <div class="flex! items-baseline! gap-1!">
            <span class="text-sm! font-bold! text-slate-400!">Rp</span>
            <span class="text-2xl! font-extrabold! text-slate-600! tracking-tight!">
              {{ formattedBeban.replace('Rp', '').trim() }}
            </span>
          </div>
        </ContentCard>

        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          class="relative! overflow-hidden! border-l-4! border-l-emerald-500!"
        >
          <div class="flex! items-center! justify-between! mb-4!">
            <span class="text-[10px]! font-bold! text-slate-400! tracking-wider! uppercase!"
              >Surplus</span
            >
            <div
              :class="trendBadgeClass(financeTrend.surplus)"
              class="flex! items-center! gap-1! px-2! py-1! rounded-md! text-[10px]! font-bold!"
            >
              <font-awesome-icon :icon="trendIcon(financeTrend.surplus)" class="w-2.5! h-2.5!" />
              <span>{{ trendLabel(financeTrend.surplus) }}</span>
            </div>
          </div>
          <div class="flex! items-baseline! gap-1!">
            <span class="text-sm! font-bold! text-slate-400!">Rp</span>
            <span class="text-2xl! font-extrabold! text-slate-600! tracking-tight!">
              {{ formattedSurplus.replace('Rp', '').trim() }}
            </span>
          </div>
        </ContentCard>

        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          class="bg-sky-50/50! border-sky-100!"
        >
          <div class="flex! flex-col! gap-2!">
            <h4 class="text-sm! font-bold! text-slate-700!">Aquifer Health</h4>
            <p class="text-[11px]! text-slate-500! leading-relaxed!">
              Current system sustainability index is optimal.
            </p>
            <div class="mt-2! w-full! h-1.5! bg-sky-100! rounded-full! overflow-hidden!">
              <div class="h-full! bg-sky-500! rounded-full!" style="width: 85%"></div>
            </div>
          </div>
        </ContentCard>
      </div>

      <div class="lg:col-span-8!">
        <ContentCard variant="bordered" padding="normal" hoverable class="flex! flex-col! pb-2!">
          <div
            class="flex! flex-col! sm:flex-row! items-start! sm:items-center! justify-between! gap-2! mb-4!"
          >
            <div>
              <h3 class="text-base! font-bold! text-slate-600!">Pendapatan, Beban & Surplus</h3>
              <p class="text-[11px]! text-slate-400! mt-0.5!">
                {{ chartSubtitle }}
              </p>
            </div>
            <div class="flex! items-center! gap-3!">
              <div class="flex! items-center! gap-2!">
                <font-awesome-icon icon="calendar-alt" class="text-slate-400! text-xs!" />
                <select
                  v-model.number="selectedYear"
                  class="text-[11px]! font-bold! text-slate-600! bg-white! border! border-slate-200! rounded-md! px-2! py-1! focus:outline-none! focus:ring-2! focus:ring-blue-200!"
                >
                  <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div class="flex! items-center! gap-4!">
              <div class="flex! items-center! gap-2! text-[10px]! font-bold! text-slate-500!">
                <div class="w-2.5! h-2.5! rounded-full! bg-blue-500!"></div>
                Pendapatan
              </div>
              <div class="flex! items-center! gap-2! text-[10px]! font-bold! text-slate-500!">
                <div class="w-2.5! h-2.5! rounded-full! bg-slate-700!"></div>
                Beban
              </div>
              <div class="flex! items-center! gap-2! text-[10px]! font-bold! text-slate-500!">
                <div class="w-2.5! h-2.5! rounded-full! bg-amber-500!"></div>
                Surplus
              </div>
              </div>
            </div>
          </div>
          <div class="w-full! shrink-0!">
            <svg
              v-if="chartGeometry"
              :viewBox="`0 0 ${chartGeometry.width} ${chartGeometry.height}`"
              xmlns="http://www.w3.org/2000/svg"
              class="w-full! h-auto!"
            >
              <defs>
                <linearGradient id="gradP" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.18" />
                  <stop offset="100%" stop-color="#3b82f6" stop-opacity="0" />
                </linearGradient>
                <linearGradient id="gradB" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#334155" stop-opacity="0.15" />
                  <stop offset="100%" stop-color="#334155" stop-opacity="0" />
                </linearGradient>
              </defs>

              <line
                :x1="chartGeometry.padL" y1="20"
                :x2="chartGeometry.padL" :y2="chartGeometry.padT + chartGeometry.innerH"
                stroke="#e2e8f0" stroke-width="1"
              />
              <line
                :x1="chartGeometry.padL" :y1="chartGeometry.padT + chartGeometry.innerH"
                :x2="chartGeometry.width - chartGeometry.padR"
                :y2="chartGeometry.padT + chartGeometry.innerH"
                stroke="#e2e8f0" stroke-width="1"
              />

              <g v-for="(t, idx) in chartGeometry.ticks" :key="idx">
                <line
                  :x1="chartGeometry.padL" :x2="chartGeometry.width - chartGeometry.padR"
                  :y1="t.y" :y2="t.y"
                  stroke="#e2e8f0" stroke-dasharray="4 4"
                />
                <text :x="chartGeometry.padL - 8" :y="t.y + 3" fill="#94a3b8" font-size="10" text-anchor="end">
                  {{ t.label }}
                </text>
              </g>

              <g v-for="(lbl, idx) in chartGeometry.xLabels" :key="`xl-${idx}`">
                <text
                  v-if="lbl.label"
                  :x="lbl.x" :y="chartGeometry.height - 10"
                  fill="#94a3b8" font-size="10" text-anchor="middle"
                >
                  {{ lbl.label }}
                </text>
              </g>

              <path :d="chartGeometry.areaP" fill="url(#gradP)" />
              <path :d="chartGeometry.areaB" fill="url(#gradB)" />

              <path
                :d="chartGeometry.pathP"
                fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round"
              />
              <path
                :d="chartGeometry.pathB"
                fill="none" stroke="#334155" stroke-width="2.5" stroke-linecap="round"
              />
              <path
                :d="chartGeometry.pathS"
                fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="6 4"
              />

              <g v-for="(p, idx) in chartGeometry.pointsP" :key="`pp-${idx}`">
                <circle :cx="p.x" :cy="p.y" r="4" fill="white" stroke="#3b82f6" stroke-width="2" />
              </g>
              <g v-for="(p, idx) in chartGeometry.pointsB" :key="`pb-${idx}`">
                <circle :cx="p.x" :cy="p.y" r="4" fill="white" stroke="#334155" stroke-width="2" />
              </g>
              <g v-for="(p, idx) in chartGeometry.pointsS" :key="`ps-${idx}`">
                <circle :cx="p.x" :cy="p.y" r="3" fill="white" stroke="#f59e0b" stroke-width="2" />
              </g>
            </svg>
            <div
              v-else
              class="w-full! h-[280px]! flex! items-center! justify-center! text-xs! text-slate-400!"
            >
              Belum ada data keuangan untuk ditampilkan
            </div>
          </div>
        </ContentCard>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="activeModal"
        class="fixed! inset-0! z-[9999]! flex! items-center! justify-center! bg-slate-900/50! backdrop-blur-md! p-4! sm:p-6! md:p-8!"
        @click.self="closeDetailModal"
      >
        <div
          class="bg-white! w-full! h-full! max-w-7xl! rounded-2xl! shadow-2xl! flex! flex-col! overflow-hidden! animate-[fade-in-up_0.3s_ease-out_forwards]!"
        >
          <div class="flex! items-center! justify-between! px-6! py-4! border-b! border-slate-100!">
            <div class="flex! items-center! gap-3!">
              <div
                class="w-8! h-8! rounded-full! bg-slate-700! flex! items-center! justify-center! text-white!"
              >
                <font-awesome-icon :icon="modalIcon" class="text-sm!" />
              </div>
              <h3 class="text-base! font-semibold! text-slate-800!">Detail {{ modalTitle }}</h3>
            </div>
            <button
              @click="closeDetailModal"
              class="text-slate-400! hover:text-slate-600! transition-colors!"
            >
              <font-awesome-icon icon="times" class="text-lg!" />
            </button>
          </div>

          <div class="flex-1! overflow-y-auto! relative! bg-white!">
            <component :is="activeComponent" />
          </div>

          <div class="px-6! py-4! border-t! border-slate-100! flex! justify-end! bg-white!">
            <button
              @click="closeDetailModal"
              class="px-6! py-2! text-sm! font-medium! text-slate-700! bg-white! border! border-slate-300! rounded-lg! hover:bg-slate-50! transition-colors!"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import statCard from '@/presentations/components/stat-card.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import dashboardService from '@/services/dashboard.service'

import InstalasiDetail from './arsipDashbord/ArsipInstalasi.vue'
import PemakaianDetail from './arsipDashbord/ArsipPemakaian.vue'
import TunggakanDetail from './arsipDashbord/ArsipTunggakan.vue'
import TagihanDetail from './arsipDashbord/ArsipTagihan.vue'

const activeModal = ref(false)
const currentDetailType = ref('')

const openDetailModal = (type) => {
  currentDetailType.value = type
  activeModal.value = true
}

const closeDetailModal = () => {
  activeModal.value = false
  setTimeout(() => {
    currentDetailType.value = ''
  }, 300)
}

const activeComponent = computed(() => {
  switch (currentDetailType.value) {
    case 'instalasi':
      return InstalasiDetail
    case 'pemakaian':
      return PemakaianDetail
    case 'tunggakan':
      return TunggakanDetail
    case 'tagihan':
      return TagihanDetail
    default:
      return null
  }
})

const modalTitle = computed(() => {
  switch (currentDetailType.value) {
    case 'instalasi':
      return 'Instalasi'
    case 'pemakaian':
      return 'Pemakaian'
    case 'tunggakan':
      return 'Tunggakan'
    case 'tagihan':
      return 'Tagihan Khusus'
    default:
      return 'Detail'
  }
})

const modalIcon = computed(() => {
  switch (currentDetailType.value) {
    case 'instalasi':
      return 'home'
    case 'pemakaian':
      return 'tint'
    case 'tunggakan':
      return 'balance-scale'
    case 'tagihan':
      return 'file-invoice'
    default:
      return 'info-circle'
  }
})

const statsData = ref(null)

const selectedYear = ref(new Date().getFullYear())
const availableYears = ref([])
const loadingFinance = ref(false)

const statsSummary = computed(() => {
  const data = statsData.value
  const tickets = data?.tickets_by_status || {}
  const bills = data?.bills_this_month || {}

  const totalTickets = Object.values(tickets).reduce((a, b) => a + Number(b), 0)
  const totalBills = (Number(bills.paid) || 0) + (Number(bills.unpaid) || 0)

  return {
    instalasi: totalTickets,
    pemakaian: data?.total_customers || 0,
    tunggakan: bills.unpaid || 0,
    tagihan: totalBills,
  }
})

const statsSummaryProgress = computed(() => {
  const s = statsSummary.value
  const base = Number(s.instalasi) || 0
  if (base <= 0) {
    return { instalasi: 0, pemakaian: 0, tunggakan: 0, tagihan: 0 }
  }
  const ratio = (v) => Math.min(100, Math.max(0, (Number(v) / base) * 100))
  return {
    instalasi: 100,
    pemakaian: ratio(s.pemakaian),
    tunggakan: ratio(s.tunggakan),
    tagihan: ratio(s.tagihan),
  }
})

const financialData = ref({
  pendapatan: 0,
  beban: 0,
  surplus: 0,
})

const formattedPendapatan = ref('')
const formattedBeban = ref('')
const formattedSurplus = ref('')

const financeTrend = ref({ pendapatan: 0, beban: 0, surplus: 0 })
const chartData = ref([])
const chartSubtitle = computed(() => {
  const n = chartData.value.length
  if (!n) return `Belum ada data jurnal umum tahun ${selectedYear.value}`
  return `Visualisasi finansial tahun ${selectedYear.value} (${n} bulan memiliki transaksi)`
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  }).format(amount)
}

const monthLabel = (m) =>
  ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][m] || ''

const chartGeometry = computed(() => {
  const data = chartData.value
  if (!data.length) return null

  const padL = 50
  const padR = 20
  const padT = 20
  const padB = 30
  const width = 700
  const height = 320
  const innerW = width - padL - padR
  const innerH = height - padT - padB

  const maxVal = Math.max(
    ...data.flatMap((d) => [d.pendapatan, d.beban, d.surplus]),
    1
  )
  const niceMax = Math.max(Math.ceil(maxVal / 1e6) * 1e6, 1e6)

  const stepX = data.length > 1 ? innerW / (data.length - 1) : 0
  const yToPx = (v) => padT + innerH - (v / niceMax) * innerH

  const pointAt = (i) => padL + i * stepX
  const buildSmoothPath = (key) => {
    if (!data.length) return ''
    const pts = data.map((d, i) => `${pointAt(i)},${yToPx(d[key])}`)
    if (pts.length === 1) return `M${pts[0]}`
    let path = `M${pts[0]}`
    for (let i = 1; i < pts.length; i++) {
      const [x0, y0] = pts[i - 1].split(',').map(Number)
      const [x1, y1] = pts[i].split(',').map(Number)
      const cx = (x0 + x1) / 2
      path += ` C${cx},${y0} ${cx},${y1} ${x1},${y1}`
    }
    return path
  }

  const pathP = buildSmoothPath('pendapatan')
  const pathB = buildSmoothPath('beban')
  const pathS = buildSmoothPath('surplus')

  const ticks = 4
  const tickValues = Array.from({ length: ticks + 1 }, (_, i) => (niceMax / ticks) * i)

  const xLabels = data.map((d, i) => ({
    x: pointAt(i),
    label: data.length <= 6
      ? monthLabel(d.month).slice(0, 3)
      : (i === 0 || i === data.length - 1 || i % Math.ceil(data.length / 6) === 0)
        ? monthLabel(d.month).slice(0, 3)
        : '',
  }))

  return {
    width,
    height,
    padL,
    padR,
    padT,
    padB,
    innerH,
    niceMax,
    pathP,
    pathB,
    pathS,
    areaP: `${pathP} L${pointAt(data.length - 1)},${padT + innerH} L${pointAt(0)},${padT + innerH} Z`,
    areaB: `${pathB} L${pointAt(data.length - 1)},${padT + innerH} L${pointAt(0)},${padT + innerH} Z`,
    pointsP: data.map((d, i) => ({ x: pointAt(i), y: yToPx(d.pendapatan) })),
    pointsB: data.map((d, i) => ({ x: pointAt(i), y: yToPx(d.beban) })),
    pointsS: data.map((d, i) => ({ x: pointAt(i), y: yToPx(d.surplus) })),
    ticks: tickValues.map((v) => ({ y: yToPx(v), label: v >= 1e6 ? `${(v / 1e6).toFixed(0)}jt` : v >= 1e3 ? `${(v / 1e3).toFixed(0)}rb` : `${v}` })),
    xLabels,
  }
})

const loadStats = async () => {
  try {
    const response = await dashboardService.getStatistics()
    if (response?.success && response?.data) {
      statsData.value = response.data

      const yrs = Array.isArray(response.data.available_years) && response.data.available_years.length
        ? response.data.available_years
        : [new Date().getFullYear()]
      availableYears.value = yrs
      if (!yrs.includes(selectedYear.value)) {
        suppressWatch.value = true
        selectedYear.value = yrs[yrs.length - 1]
        queueMicrotask(() => { suppressWatch.value = false })
      }
    }
  } catch (error) {
    console.error('Failed to load dashboard statistics', error)
  }
}

const suppressWatch = ref(false)

const loadFinance = async () => {
  loadingFinance.value = true
  try {
    const response = await dashboardService.getStatistics({ year: selectedYear.value })
    if (response?.success && response?.data) {
      const fin = response.data.finance
      if (fin) {
        const p = Number(fin.pendapatan) || 0
        const b = Number(fin.beban) || 0
        financialData.value = {
          pendapatan: p,
          beban: b,
          surplus: Number(fin.surplus ?? (p - b)),
        }
      } else {
        financialData.value = { pendapatan: 0, beban: 0, surplus: 0 }
      }

      const yrs = Array.isArray(response.data.available_years) && response.data.available_years.length
        ? response.data.available_years
        : [selectedYear.value]
      availableYears.value = yrs

      chartData.value = Array.isArray(response.data.finance_chart)
        ? response.data.finance_chart.map((r) => ({
            year: Number(r.year),
            month: Number(r.month),
            pendapatan: Number(r.pendapatan) || 0,
            beban: Number(r.beban) || 0,
            surplus: Number(r.surplus) || 0,
          }))
        : []

      const prevMonth = await prevMonthFinance(selectedYear.value, fin?.month ?? new Date().getMonth() + 1)
      financeTrend.value = {
        pendapatan: pctChange(prevMonth.pendapatan, financialData.value.pendapatan),
        beban: pctChange(prevMonth.beban, financialData.value.beban),
        surplus: pctChange(prevMonth.surplus, financialData.value.surplus),
      }
    }
  } catch (error) {
    console.error('Failed to load finance data', error)
  } finally {
    formattedPendapatan.value = formatCurrency(financialData.value.pendapatan)
    formattedBeban.value = formatCurrency(financialData.value.beban)
    formattedSurplus.value = formatCurrency(financialData.value.surplus)
    loadingFinance.value = false
  }
}

async function prevMonthFinance(year, month) {
  let py = year
  let pm = month - 1
  if (pm < 1) {
    pm = 12
    py -= 1
  }
  try {
    const r = await dashboardService.getStatistics({ year: py, month: pm })
    const f = r?.data?.finance
    if (!f) return { pendapatan: 0, beban: 0, surplus: 0 }
    const p = Number(f.pendapatan) || 0
    const b = Number(f.beban) || 0
    return {
      pendapatan: p,
      beban: b,
      surplus: Number(f.surplus ?? (p - b)),
    }
  } catch {
    return { pendapatan: 0, beban: 0, surplus: 0 }
  }
}

watch(selectedYear, () => {
  if (suppressWatch.value) return
  loadFinance()
})

function pctChange(prev, curr) {
  if (!prev) return 0
  return ((curr - prev) / prev) * 100
}

function trendIcon(value) {
  if (value > 0) return 'arrow-up'
  if (value < 0) return 'arrow-down'
  return 'arrow-right'
}

function trendLabel(value) {
  if (!value) return '0%'
  const abs = Math.abs(value).toFixed(1).replace(/\.0$/, '')
  return `${value > 0 ? '+' : '-'}${abs}%`
}

function trendBadgeClass(value, lowerIsBetter = false) {
  if (!value) return 'bg-slate-100! text-slate-500!'
  const positive = value > 0
  const good = lowerIsBetter ? !positive : positive
  return good
    ? 'bg-emerald-50! text-emerald-600!'
    : 'bg-rose-50! text-rose-600!'
}

onMounted(async () => {
  await loadStats()
  await loadFinance()
})
</script>

<style scoped>
@keyframes fade-in-up {
  0% {
    opacity: 0;
    transform: translateY(10px) scale(0.98);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
