<template>
  <div class="dashboard-container">
    <div class="grid! items-start! grid-cols-1! sm:grid-cols-2! lg:grid-cols-4! gap-4! mb-8!">
      <statCard
        label="INSTALASI"
        value="100"
        :link="null"
        @detail-click="openDetailModal('instalasi')"
      >
        <font-awesome-icon icon="home" />
      </statCard>

      <statCard
        label="PEMAKAIAN"
        value="100"
        :link="null"
        @detail-click="openDetailModal('pemakaian')"
      >
        <font-awesome-icon icon="tint" />
      </statCard>

      <statCard
        label="TUNGGAKAN"
        value="100"
        :link="null"
        @detail-click="openDetailModal('tunggakan')"
      >
        <font-awesome-icon icon="balance-scale" />
      </statCard>

      <statCard label="TAGIHAN" value="100" :link="null" @detail-click="openDetailModal('tagihan')">
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
              class="flex! items-center! gap-1! px-2! py-1! bg-rose-50! text-rose-600! rounded-md! text-[10px]! font-bold!"
            >
              <font-awesome-icon icon="arrow-down" class="w-2.5! h-2.5!" />
              <span>NEGATIVE</span>
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
              class="flex! items-center! gap-1! px-2! py-1! bg-slate-100! text-slate-500! rounded-md! text-[10px]! font-bold!"
            >
              <font-awesome-icon icon="arrow-left" class="w-2.5! h-2.5!" />
              <span>0% TREND</span>
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
              class="flex! items-center! gap-1! px-2! py-1! bg-rose-50! text-rose-600! rounded-md! text-[10px]! font-bold!"
            >
              <font-awesome-icon icon="arrow-down" class="w-2.5! h-2.5!" />
              <span>NEGATIVE</span>
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
              <h3 class="text-base! font-bold! text-slate-600!">Pendapatan dan Beban</h3>
              <p class="text-[11px]! text-slate-400! mt-0.5!">
                Visualisasi finansial kuartal pertama
              </p>
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
          <div class="w-full! shrink-0!">
            <svg viewBox="0 0 700 320" xmlns="http://www.w3.org/2000/svg" class="w-full! h-auto!">
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

              <line x1="40" y1="20" x2="40" y2="250" stroke="#e2e8f0" stroke-width="1" />
              <line x1="40" y1="250" x2="680" y2="250" stroke="#e2e8f0" stroke-width="1" />

              <line x1="40" y1="200" x2="680" y2="200" stroke="#e2e8f0" stroke-dasharray="4 4" />
              <line x1="40" y1="150" x2="680" y2="150" stroke="#e2e8f0" stroke-dasharray="4 4" />
              <line x1="40" y1="100" x2="680" y2="100" stroke="#e2e8f0" stroke-dasharray="4 4" />

              <text x="50" y="270" fill="#94a3b8" font-size="10">Awal Tahun</text>
              <text x="230" y="270" fill="#94a3b8" font-size="10">Januari</text>
              <text x="410" y="270" fill="#94a3b8" font-size="10">Februari</text>
              <text x="630" y="270" fill="#94a3b8" font-size="10">Maret</text>

              <path
                d="M50,240 C100,230 150,210 230,180 C310,140 390,110 470,90 C540,76 610,70 680,66 L680,250 L50,250 Z"
                fill="url(#gradP)"
              />
              <path
                d="M50,245 C100,240 150,225 230,210 C310,184 390,160 470,150 C540,140 610,136 680,132 L680,250 L50,250 Z"
                fill="url(#gradB)"
              />

              <path
                d="M50,240 C100,230 150,210 230,180 C310,140 390,110 470,90 C540,76 610,70 680,66"
                fill="none"
                stroke="#3b82f6"
                stroke-width="2.5"
                stroke-linecap="round"
              />

              <path
                d="M50,245 C100,240 150,225 230,210 C310,184 390,160 470,150 C540,140 610,136 680,132"
                fill="none"
                stroke="#334155"
                stroke-width="2.5"
                stroke-linecap="round"
              />

              <circle cx="230" cy="180" r="4" fill="white" stroke="#3b82f6" stroke-width="2" />
              <circle cx="470" cy="90" r="4" fill="white" stroke="#3b82f6" stroke-width="2" />
              <circle cx="230" cy="210" r="4" fill="white" stroke="#334155" stroke-width="2" />
              <circle cx="470" cy="150" r="4" fill="white" stroke="#334155" stroke-width="2" />
            </svg>
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
import { ref, computed, onMounted } from 'vue'
import statCard from '../../components/stat-card.vue'
import ContentCard from '../../components/ui/ContentCard.vue'

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

const financialData = ref({
  pendapatan: 2300000,
  beban: 0,
  surplus: 2300000,
})

const formattedPendapatan = ref('')
const formattedBeban = ref('')
const formattedSurplus = ref('')

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 2,
  }).format(amount)
}

const loadData = async () => {
  await new Promise((resolve) => setTimeout(resolve, 1000))
  formattedPendapatan.value = formatCurrency(financialData.value.pendapatan)
  formattedBeban.value = formatCurrency(financialData.value.beban)
  formattedSurplus.value = formatCurrency(financialData.value.surplus)
}

onMounted(() => {
  loadData()
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
