<template>
  <div class="dashboard-container">
    <div class="grid! items-start! grid-cols-1! sm:grid-cols-2! lg:grid-cols-4! gap-4! mb-8!">
      <statCard
        label="PERMOHONAN"
        :value="statsSummary.instalasi"
        :link="null"
        :progress="statsSummaryProgress.instalasi"
        @detail-click="openDetailModal('instalasi')"
      >
        <font-awesome-icon icon="file-signature" />
      </statCard>

      <statCard
        label="PEMAKAIAN"
        :value="statsSummary.pemakaian"
        :link="null"
        :progress="statsSummaryProgress.pemakaian"
        @detail-click="openDetailModal('pemakaian')"
      >
        <font-awesome-icon icon="faucet" />
      </statCard>

      <statCard
        label="TUNGGAKAN"
        :value="statsSummary.tunggakan"
        :link="null"
        :progress="statsSummaryProgress.tunggakan"
        @detail-click="openDetailModal('tunggakan')"
      >
        <font-awesome-icon icon="clock" />
      </statCard>

      <statCard
        label="TAGIHAN"
        :value="statsSummary.tagihan"
        :link="null"
        :progress="statsSummaryProgress.tagihan"
        @detail-click="openDetailModal('tagihan')"
      >
        <font-awesome-icon icon="paper-plane" />
      </statCard>
    </div>
    <div class="grid! grid-cols-1! lg:grid-cols-12! gap-6! items-stretch!">
      <div class="lg:col-span-4! flex! flex-col! gap-6!">
        <!--
          KOMPOSISI KEUANGAN (Pie Chart Full Circle)
          Menggantikan 3 card Pendapatan / Beban / Surplus.
          - Slice Pendapatan (biru), Beban (slate), Surplus (amber)
          - Pie radius dihitung dari total pendapatan sebagai basis, sehingga
            surplus ditampilkan sebagai porsi "uang yang tersisa" bila P ≥ B,
            atau slice kecil tersembunyi bila P < B (defisit).
          - Slice terkecil = 1.5% minimum supaya tetap terlihat.
          - Ikut selectedYear — reaktif terhadap filter tahun di header.
        -->
        <ContentCard
          variant="bordered"
          padding="normal"
          hoverable
          class="relative! overflow-hidden! h-full! flex! flex-col!"
        >
          <div class="flex! items-center! justify-between! mb-3!">
            <div>
              <h3 class="text-base! font-bold! text-slate-600!">Komposisi Keuangan</h3>
              <p class="text-[10px]! text-slate-400! mt-0.5!">
                Tahun {{ selectedYear }} · {{ pieChartSubtitle }}
              </p>
            </div>
          </div>

          <div class="flex! items-center! justify-center! py-2! relative! flex-1! min-h-[200px]!">
            <svg
              v-if="pieChartGeometry.total > 0"
              :viewBox="`0 0 ${pieChartGeometry.size} ${pieChartGeometry.size}`"
              xmlns="http://www.w3.org/2000/svg"
              class="w-[200px]! h-[200px]!"
              @mouseleave="activeSlice = null"
            >
              <!-- Slice Pendapatan -->
              <path
                v-if="pieChartGeometry.slices.pendapatan.value > 0"
                :d="pieChartGeometry.slices.pendapatan.path"
                :fill="pieChartGeometry.slices.pendapatan.color"
                stroke="white"
                stroke-width="2"
                class="transition-all! duration-300! cursor-pointer!"
                :class="activeSlice === 'pendapatan' ? 'opacity-100!' : (activeSlice && activeSlice !== 'pendapatan') ? 'opacity-50!' : 'opacity-100!'"
                :transform="activeSlice === 'pendapatan' ? 'scale(1.04)' : 'scale(1)'"
                style="transform-origin: 100px 100px; transform-box: fill-box;"
                @mouseenter="activeSlice = 'pendapatan'"
              />
              <!-- Slice Beban -->
              <path
                v-if="pieChartGeometry.slices.beban.value > 0"
                :d="pieChartGeometry.slices.beban.path"
                :fill="pieChartGeometry.slices.beban.color"
                stroke="white"
                stroke-width="2"
                class="transition-all! duration-300! cursor-pointer!"
                :class="activeSlice === 'beban' ? 'opacity-100!' : (activeSlice && activeSlice !== 'beban') ? 'opacity-50!' : 'opacity-100!'"
                :transform="activeSlice === 'beban' ? 'scale(1.04)' : 'scale(1)'"
                style="transform-origin: 100px 100px; transform-box: fill-box;"
                @mouseenter="activeSlice = 'beban'"
              />
              <!-- Slice Surplus (hijau emerald, hanya tampil kalau surplus > 0) -->
              <path
                v-if="pieChartGeometry.slices.surplus.value > 0"
                :d="pieChartGeometry.slices.surplus.path"
                :fill="pieChartGeometry.slices.surplus.color"
                stroke="white"
                stroke-width="2"
                class="transition-all! duration-300! cursor-pointer!"
                :class="activeSlice === 'surplus' ? 'opacity-100!' : (activeSlice && activeSlice !== 'surplus') ? 'opacity-50!' : 'opacity-100!'"
                :transform="activeSlice === 'surplus' ? 'scale(1.04)' : 'scale(1)'"
                style="transform-origin: 100px 100px; transform-box: fill-box;"
                @mouseenter="activeSlice = 'surplus'"
              />

              <!-- Pusat lingkaran: ringkas Pendapatan -->
              <text
                :x="pieChartGeometry.cx"
                :y="pieChartGeometry.cy - 4"
                fill="#94a3b8"
                font-size="9"
                font-weight="700"
                text-anchor="middle"
                style="letter-spacing: 0.05em;"
              >
                PENDAPATAN
              </text>
              <text
                :x="pieChartGeometry.cx"
                :y="pieChartGeometry.cy + 10"
                fill="#1e293b"
                font-size="11"
                font-weight="800"
                text-anchor="middle"
              >
                {{ pieChartGeometry.pendapatanShort }}
              </text>
              <text
                :x="pieChartGeometry.cx"
                :y="pieChartGeometry.cy + 24"
                fill="#10b981"
                font-size="9"
                font-weight="700"
                text-anchor="middle"
              >
                {{ pieChartGeometry.surplusLabel }}
              </text>
            </svg>
            <div
              v-else
              class="w-[200px]! h-[200px]! flex! items-center! justify-center! text-[11px]! text-slate-400! text-center! px-4!"
            >
              Belum ada transaksi keuangan untuk tahun {{ selectedYear }}
            </div>

            <!-- Tooltip custom (muncul saat hover slice) -->
            <div
              v-if="activeSlice && pieChartGeometry.total > 0"
              class="absolute! top-1/2! left-1/2! -translate-x-1/2! -translate-y-1/2! pointer-events-none! bg-slate-900/95! text-white! px-3! py-2! rounded-lg! shadow-xl! backdrop-blur-sm! animate-[fade-in-up_0.2s_ease-out_forwards]!"
              style="z-index: 10;"
            >
              <div class="flex! items-center! gap-1.5! mb-1!">
                <div
                  class="w-2! h-2! rounded-full!"
                  :style="{ background: pieChartGeometry.slices[activeSlice].color }"
                ></div>
                <span class="text-[10px]! font-bold! uppercase! tracking-wider! opacity-80!">
                  {{ pieChartGeometry.slices[activeSlice].label }}
                </span>
              </div>
              <div class="text-[13px]! font-extrabold! font-mono!">
                {{ formatCurrency(pieChartGeometry.slices[activeSlice].value) }}
              </div>
              <div class="text-[10px]! font-bold! opacity-70! mt-0.5!">
                {{ pieChartGeometry.slices[activeSlice].percentLabel }} dari total
              </div>
            </div>
          </div>

          <!-- Legend ringkas 1 baris -->
          <div class="mt-4! flex! items-center! justify-center! gap-3! flex-wrap!">
            <div
              v-for="key in ['pendapatan', 'beban', 'surplus']"
              :key="key"
              class="flex! items-center! gap-1.5! cursor-pointer! transition-opacity! duration-200!"
              :class="activeSlice && activeSlice !== key ? 'opacity-40!' : 'opacity-100!'"
              @mouseenter="activeSlice = key"
              @mouseleave="activeSlice = null"
            >
              <div
                class="w-2.5! h-2.5! rounded-sm!"
                :style="{ background: pieChartGeometry.slices[key].color }"
              ></div>
              <span class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wider!">
                {{ pieChartGeometry.slices[key].label }}
              </span>
            </div>
          </div>

          <!-- Trend baris (badges Pendapatan / Beban / Surplus vs bulan lalu) -->
          <div class="mt-3! pt-3! border-t! border-slate-100! flex! items-center! justify-between! gap-2!">
            <div
              :class="trendBadgeClass(financeTrend.pendapatan)"
              class="flex! items-center! gap-1! px-2! py-1! rounded-md! text-[10px]! font-bold!"
              :title="'Pendapatan: ' + trendLabel(financeTrend.pendapatan) + ' vs bulan lalu'"
            >
              <font-awesome-icon :icon="trendIcon(financeTrend.pendapatan)" class="w-2.5! h-2.5!" />
              <span>P {{ trendLabel(financeTrend.pendapatan) }}</span>
            </div>
            <div
              :class="trendBadgeClass(financeTrend.beban, true)"
              class="flex! items-center! gap-1! px-2! py-1! rounded-md! text-[10px]! font-bold!"
              :title="'Beban: ' + trendLabel(financeTrend.beban) + ' vs bulan lalu'"
            >
              <font-awesome-icon :icon="trendIcon(financeTrend.beban)" class="w-2.5! h-2.5!" />
              <span>B {{ trendLabel(financeTrend.beban) }}</span>
            </div>
            <div
              :class="trendBadgeClass(financeTrend.surplus)"
              class="flex! items-center! gap-1! px-2! py-1! rounded-md! text-[10px]! font-bold!"
              :title="'Surplus: ' + trendLabel(financeTrend.surplus) + ' vs bulan lalu'"
            >
              <font-awesome-icon :icon="trendIcon(financeTrend.surplus)" class="w-2.5! h-2.5!" />
              <span>S {{ trendLabel(financeTrend.surplus) }}</span>
            </div>
          </div>
        </ContentCard>
      </div>

      <div class="lg:col-span-8! h-full! flex!">
        <ContentCard variant="bordered" padding="normal" hoverable class="flex! flex-col! pb-2! h-full! w-full!">
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
          <div class="w-full! flex-1! flex! flex-col! justify-center! min-h-[260px]!">
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
                :x1="chartGeometry.padL"
                y1="20"
                :x2="chartGeometry.padL"
                :y2="chartGeometry.padT + chartGeometry.innerH"
                stroke="#e2e8f0"
                stroke-width="1"
              />
              <line
                :x1="chartGeometry.padL"
                :y1="chartGeometry.padT + chartGeometry.innerH"
                :x2="chartGeometry.width - chartGeometry.padR"
                :y2="chartGeometry.padT + chartGeometry.innerH"
                stroke="#e2e8f0"
                stroke-width="1"
              />

              <g v-for="(t, idx) in chartGeometry.ticks" :key="idx">
                <line
                  :x1="chartGeometry.padL"
                  :x2="chartGeometry.width - chartGeometry.padR"
                  :y1="t.y"
                  :y2="t.y"
                  stroke="#e2e8f0"
                  stroke-dasharray="4 4"
                />
                <text
                  :x="chartGeometry.padL - 8"
                  :y="t.y + 3"
                  fill="#94a3b8"
                  font-size="10"
                  text-anchor="end"
                >
                  {{ t.label }}
                </text>
              </g>

              <g v-for="(lbl, idx) in chartGeometry.xLabels" :key="`xl-${idx}`">
                <text
                  v-if="lbl.label"
                  :x="lbl.x"
                  :y="chartGeometry.height - 10"
                  fill="#94a3b8"
                  font-size="10"
                  text-anchor="middle"
                >
                  {{ lbl.label }}
                </text>
              </g>

              <path :d="chartGeometry.areaP" fill="url(#gradP)" />
              <path :d="chartGeometry.areaB" fill="url(#gradB)" />

              <path
                v-if="chartData.length >= 2"
                :d="chartGeometry.pathP"
                fill="none"
                stroke="#3b82f6"
                stroke-width="2.5"
                stroke-linecap="round"
              />
              <path
                v-if="chartData.length >= 2"
                :d="chartGeometry.pathB"
                fill="none"
                stroke="#334155"
                stroke-width="2.5"
                stroke-linecap="round"
              />
              <path
                v-if="chartData.length >= 2"
                :d="chartGeometry.pathS"
                fill="none"
                stroke="#f59e0b"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-dasharray="6 4"
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

          <div class="px-6! py-4! border-t! border-slate-100! flex! items-center! justify-between! bg-white!">
            <div class="text-xs! text-slate-500! font-medium!">
              <template v-if="currentDetailType === 'tagihan' && tagihanSelection.length > 0">
                {{ tagihanSelection.length }} data tagihan dipilih
              </template>
            </div>
            <div class="flex! items-center! gap-2!">
              <button
                v-if="currentDetailType === 'tagihan'"
                :disabled="tagihanSelection.length === 0"
                @click="handleSendMessage"
                class="px-5! py-2! text-sm! font-semibold! text-white! bg-blue-600! hover:bg-blue-700! disabled:bg-slate-300! disabled:cursor-not-allowed! rounded-lg! flex! items-center! gap-2! transition-colors!"
              >
                <font-awesome-icon icon="paper-plane" />
                Kirim Pesan
              </button>
              <button
                @click="closeDetailModal"
                class="px-6! py-2! text-sm! font-medium! text-slate-700! bg-white! border! border-slate-300! rounded-lg! hover:bg-slate-50! transition-colors!"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, provide } from 'vue'
import statCard from '@/presentations/components/stat-card.vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import dashboardService from '@/services/dashboard.service'

import InstalasiDetail from './arsipDashbord/ArsipInstalasi.vue'
import PemakaianDetail from './arsipDashbord/ArsipPemakaian.vue'
import TunggakanDetail from './arsipDashbord/ArsipTunggakan.vue'
import TagihanDetail from './arsipDashbord/ArsipTagihan.vue'

const activeModal = ref(false)
const currentDetailType = ref('')
const tagihanSelection = ref([])

const openDetailModal = (type) => {
  currentDetailType.value = type
  activeModal.value = true
  tagihanSelection.value = []
}

const closeDetailModal = () => {
  activeModal.value = false
  setTimeout(() => {
    currentDetailType.value = ''
    tagihanSelection.value = []
  }, 300)
}

const handleSendMessage = () => {
  // ponytail: handler placeholder, wire ke WhatsApp/email gateway saat fitur siap
}

provide('tagihanSelection', tagihanSelection)

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
      return 'Permohonan Instalasi'
    case 'pemakaian':
      return 'Pemakaian Air'
    case 'tunggakan':
      return 'Tunggakan'
    case 'tagihan':
      return 'Tagihan'
    default:
      return 'Detail'
  }
})

const modalIcon = computed(() => {
  switch (currentDetailType.value) {
    case 'instalasi':
      return 'file-signature'
    case 'pemakaian':
      return 'faucet'
    case 'tunggakan':
      return 'clock'
    case 'tagihan':
      return 'paper-plane'
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

  const instalasiStatuses = ['draft', 'pending', 'surveyed', 'unpaid']
  const instalasiCount = instalasiStatuses.reduce((sum, key) => sum + Number(tickets[key] || 0), 0)

  return {
    instalasi: instalasiCount,
    pemakaian: data?.pemakaian_count ?? 0,
    tunggakan: data?.tunggakan_total ?? bills.unpaid ?? 0,
    tagihan: bills.unpaid ?? 0,
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

const financeTrend = ref({ pendapatan: 0, beban: 0, surplus: 0 })
const chartData = ref([])
const chartSubtitle = computed(() => {
  const n = chartData.value.length
  if (!n) return `Belum ada data jurnal umum tahun ${selectedYear.value}`
  return `Visualisasi finansial tahun ${selectedYear.value} (${n} bulan memiliki transaksi)`
})

/**
 * PIE CHART KOMPOSISI KEUANGAN
 * ---------------------------
 * - Basis total = MAX(Pendapatan, Beban + |Surplus|) supaya pie chart tetap
 *   proporsional bahkan saat defisit (Beban > Pendapatan).
 * - Slice minimum = 1.5% dari total basis agar tidak hilang.
 * - Saat Surplus > 0 → 3 slice (P, B, S).
 * - Saat Surplus ≤ 0 → 2 slice (P, B); S disembunyikan tapi tetap
 *   ditampilkan di legend dengan nilai minus.
 */
const pieChartSubtitle = computed(() => {
  const p = Number(financialData.value?.pendapatan) || 0
  const b = Number(financialData.value?.beban) || 0
  const s = Number(financialData.value?.surplus) || 0
  if (p === 0 && b === 0) return 'belum ada data'
  if (s >= 0) return `${formatCurrencyShort(s)} surplus`
  return `defisit ${formatCurrencyShort(Math.abs(s))}`
})

const formatCurrencyShort = (amount) => {
  const n = Number(amount) || 0
  const abs = Math.abs(n)
  if (abs >= 1_000_000_000) return `Rp ${(n / 1_000_000_000).toFixed(1).replace(/\.0$/, '')}M`
  if (abs >= 1_000_000) return `Rp ${(n / 1_000_000).toFixed(1).replace(/\.0$/, '')}jt`
  if (abs >= 1_000) return `Rp ${(n / 1_000).toFixed(0)}rb`
  return `Rp ${n}`
}

/**
 * Slice yang sedang di-hover di pie chart.
 * null = tidak ada hover. Digunakan untuk:
 *   - Menampilkan tooltip dengan nominal + persen
 *   - Highlight slice aktif (scale 1.04 + opacity penuh pada slice lain diturunkan)
 *   - Highlight legend chip terkait
 */
const activeSlice = ref(null)

const pieChartGeometry = computed(() => {
  const p = Math.max(0, Number(financialData.value?.pendapatan) || 0)
  const b = Math.max(0, Number(financialData.value?.beban) || 0)
  const s = Number(financialData.value?.surplus) || 0

  // Basis untuk normalisasi slice: pendapatan sebagai denominator utama.
  // Kalau pendapatan 0 (tahun kosong), fallback ke beban.
  const basis = p > 0 ? p : Math.max(b, 1)

  // Hitung slice — surplus hanya dihitung positif (sisa setelah beban)
  const sliceP = p
  const sliceB = b
  const sliceS = Math.max(0, s)

  // Normalisasi ke basis (0–1)
  const sum = sliceP + sliceB + sliceS
  const total = sum > 0 ? sum : 0
  const normP = total > 0 ? sliceP / total : 0
  const normB = total > 0 ? sliceB / total : 0
  const normS = total > 0 ? sliceS / total : 0

  // Minimum slice 1.5% supaya slice kecil tetap kelihatan
  const min = 0.015
  let dP = normP, dB = normB, dS = normS
  if (total > 0) {
    if (dP > 0 && dP < min) dP = min
    if (dB > 0 && dB < min) dB = min
    if (dS > 0 && dS < min) dS = min
    // Renormalize setelah min
    const dSum = dP + dB + dS
    if (dSum > 0) {
      dP /= dSum; dB /= dSum; dS /= dSum
    }
  }

  // Bangun path SVG (arc)
  const size = 200
  const cx = size / 2
  const cy = size / 2
  const r = size / 2 - 4 // margin 4px biar tidak kepotong

  const buildPath = (startAngle, endAngle) => {
    if (endAngle - startAngle <= 0) return ''
    const x1 = cx + r * Math.cos(startAngle)
    const y1 = cy + r * Math.sin(startAngle)
    const x2 = cx + r * Math.cos(endAngle)
    const y2 = cy + r * Math.sin(endAngle)
    const largeArc = endAngle - startAngle > Math.PI ? 1 : 0
    return `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2} Z`
  }

  // Mulai dari -π/2 (atas / 12 o'clock), searah jarum jam
  const start = -Math.PI / 2
  const endP = start + 2 * Math.PI * dP
  const endB = endP + 2 * Math.PI * dB
  const endS = endB + 2 * Math.PI * dS

  const fmtPct = (n) => (n <= 0 ? '0%' : `${(n * 100).toFixed(1).replace(/\.0$/, '')}%`)

  return {
    size,
    cx,
    cy,
    total,
    basis,
    pendapatanShort: formatCurrencyShort(p),
    surplusLabel: s >= 0 ? `▲ ${formatCurrencyShort(s)}` : `▼ ${formatCurrencyShort(Math.abs(s))}`,
    slices: {
      pendapatan: {
        label: 'Pendapatan',
        value: sliceP,
        fraction: dP,
        path: buildPath(start, endP),
        color: '#3b82f6',
        percentLabel: fmtPct(normP),
      },
      beban: {
        label: 'Beban',
        value: sliceB,
        fraction: dB,
        path: buildPath(endP, endB),
        color: '#334155',
        percentLabel: fmtPct(normB),
      },
      surplus: {
        label: 'Surplus',
        value: sliceS,
        fraction: dS,
        path: buildPath(endB, endS),
        color: '#10b981',
        percentLabel: fmtPct(normS),
      },
    },
  }
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(amount)
}

const monthLabel = (m) =>
  [
    '',
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
  ][m] || ''

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

  const maxVal = Math.max(...data.flatMap((d) => [d.pendapatan, d.beban, d.surplus]), 1)
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
    label:
      data.length <= 6
        ? monthLabel(d.month).slice(0, 3)
        : i === 0 || i === data.length - 1 || i % Math.ceil(data.length / 6) === 0
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
    ticks: tickValues.map((v) => ({
      y: yToPx(v),
      label:
        v >= 1e6 ? `${(v / 1e6).toFixed(0)}jt` : v >= 1e3 ? `${(v / 1e3).toFixed(0)}rb` : `${v}`,
    })),
    xLabels,
  }
})

const loadStats = async () => {
  try {
    const response = await dashboardService.getStatistics()
    if (response?.success && response?.data) {
      statsData.value = response.data

      const yrs =
        Array.isArray(response.data.available_years) && response.data.available_years.length
          ? response.data.available_years
          : [new Date().getFullYear()]
      availableYears.value = yrs
      if (!yrs.includes(selectedYear.value)) {
        suppressWatch.value = true
        selectedYear.value = yrs[yrs.length - 1]
        queueMicrotask(() => {
          suppressWatch.value = false
        })
      }
    }
  } catch (error) {
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
          surplus: Number(fin.surplus ?? p - b),
        }
      } else {
        financialData.value = { pendapatan: 0, beban: 0, surplus: 0 }
      }

      const yrs =
        Array.isArray(response.data.available_years) && response.data.available_years.length
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

      if (chartData.value.length) {
        const map = new Map(chartData.value.map((d) => [`${d.year}-${d.month}`, d]))
        const filled = []
        const yr = selectedYear.value
        for (let m = 1; m <= 12; m++) {
          const key = `${yr}-${m}`
          if (map.has(key)) {
            filled.push(map.get(key))
          } else {
            filled.push({ year: yr, month: m, pendapatan: 0, beban: 0, surplus: 0 })
          }
        }
        chartData.value = filled
      }

      // Hanya fetch bulan sebelumnya kalau BUKAN cached stats (cache key sudah include month).
      // Karena stats() sekarang di-cache 5 menit per (year,month), kita skip pemanggilan
      // tambahan untuk prev month kalau cache hit pada response ini.
      // Tapi financeTrend butuh prev month — fetch terpisah (ini hanya 1 query aggregate, cepat).
      const prevMonth = await prevMonthFinance(
        selectedYear.value,
        fin?.month ?? new Date().getMonth() + 1,
      )
      financeTrend.value = {
        pendapatan: pctChange(prevMonth.pendapatan, financialData.value.pendapatan),
        beban: pctChange(prevMonth.beban, financialData.value.beban),
        surplus: pctChange(prevMonth.surplus, financialData.value.surplus),
      }
    }
  } catch (error) {
  } finally {
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
      surplus: Number(f.surplus ?? p - b),
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
  return good ? 'bg-emerald-50! text-emerald-600!' : 'bg-rose-50! text-rose-600!'
}

onMounted(async () => {
  await Promise.all([loadStats(), loadFinance()])
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
