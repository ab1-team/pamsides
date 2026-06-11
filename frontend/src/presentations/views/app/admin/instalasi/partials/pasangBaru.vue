<template>
  <div class="max-w-5xl! mx-auto grid! grid-cols-1! lg:grid-cols-3! gap-6!">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="large" rounded="2xl">
        <div class="flex! items-start! gap-4!">
          <div class="flex-1!">
            <div class="flex! items-center! justify-between! mb-2!">
              <p class="text-xs! font-bold! text-sky-500! uppercase! tracking-widest!">
                Customer Profile
              </p>
              <span class="px-3! py-1! rounded-full! text-xs! font-bold! uppercase! tracking-wider!" :class="statusBadge.class">
                {{ statusBadge.label }}
              </span>
            </div>
            <h1 class="text-3xl! font-extrabold! text-slate-800! mb-2!">{{ customer.name }}</h1>
            <div class="flex! items-start! gap-2! text-slate-500! mb-3!">
              <font-awesome-icon icon="map-marker-alt" class="text-sky-400! mt-0.5! shrink-0!" />
              <div>
                <p class="text-sm! font-medium!">{{ customer.address }}</p>
                <p class="text-xs! text-slate-400!">{{ customer.region }}</p>
              </div>
            </div>

            <div class="grid! grid-cols-2! sm:grid-cols-4! gap-3! mt-4!">
              <div class="bg-sky-50! rounded-lg! px-3! py-2!">
                <p class="text-[10px]! text-sky-400! font-medium!">No. Induk</p>
                <p class="text-sm! font-bold! text-slate-800!">{{ customer.noInduk }}</p>
              </div>
              <div class="bg-violet-50! rounded-lg! px-3! py-2!">
                <p class="text-[10px]! text-violet-400! font-medium!">Abodemen</p>
                <p class="text-sm! font-bold! text-slate-800!">{{ customer.abodemen }}</p>
              </div>
              <div class="bg-emerald-50! rounded-lg! px-3! py-2!">
                <p class="text-[10px]! text-emerald-400! font-medium!">Tgl Order</p>
                <p class="text-sm! font-bold! text-slate-800!">{{ customer.tglOrder }}</p>
              </div>
              <div class="bg-amber-50! rounded-lg! px-3! py-2!">
                <p class="text-[10px]! text-amber-500! font-medium!">Paket</p>
                <p class="text-sm! font-bold! text-slate-800!">{{ customer.paket }}</p>
              </div>
            </div>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <p class="text-xs! font-bold! text-slate-400! uppercase! tracking-widest! mb-4!">Tahapan Proses</p>
        <div class="flex! items-center! justify-between! gap-2!">
          <div v-for="(step, idx) in steps" :key="step.key" class="flex-1! flex! flex-col! items-center! relative!">
            <div
              class="w-10! h-10! rounded-full! flex! items-center! justify-center! text-white! font-bold! shadow-md! z-10! transition-all!"
              :class="step.state === 'done' ? 'bg-emerald-500!' : step.state === 'current' ? 'bg-sky-500! ring-4! ring-sky-100!' : 'bg-slate-300!'"
            >
              <font-awesome-icon :icon="step.state === 'done' ? 'check' : step.icon" />
            </div>
            <p class="text-[11px]! font-bold! mt-2! text-center!" :class="step.state === 'done' ? 'text-emerald-600!' : step.state === 'current' ? 'text-sky-600!' : 'text-slate-400!'">
              {{ step.label }}
            </p>
            <div v-if="idx < steps.length - 1" class="absolute! top-5! left-1/2! w-full! h-0.5! -z-0!" :class="step.state === 'done' ? 'bg-emerald-400!' : 'bg-slate-200!'"></div>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! justify-between!">
          <div class="flex! items-center! gap-3!">
            <div class="w-10! h-10! bg-orange-100! rounded-xl! flex! items-center! justify-center!">
              <font-awesome-icon icon="user" class="text-orange-500!" />
            </div>
            <div>
              <p class="text-xs! text-slate-400! mb-0.5!">Hasil Survey</p>
              <p class="text-sm! font-bold! text-slate-800!">
                {{ customer.surveyInfo ? `${customer.surveyInfo.distance_to_pipe_m}m dari pipa utama` : 'Belum ada survey' }}
              </p>
              <p v-if="customer.surveyInfo" class="text-[11px]! text-slate-400!">
                {{ customer.surveyInfo.surveyor_name || '-' }}
              </p>
            </div>
          </div>
          <button
            v-if="customer.surveyInfo"
            @click="openSurveyDetail"
            class="px-4! py-2! bg-orange-500! hover:bg-orange-600! text-white! text-xs! font-bold! rounded-lg! transition-all! active:scale-95!"
          >
            Detail Surveyor
          </button>
        </div>
      </ContentCard>
    </div>

    <div class="flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! bg-sky-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="edit" class="text-sky-500! text-xs!" />
          </div>
          <h3 class="text-base! font-bold! text-slate-800!">Finalize Installation</h3>
        </div>

        <div class="space-y-4!">
          <div>
            <label class="text-xs! font-semibold! text-slate-500! uppercase! tracking-wide! block! mb-1!">Kode Instalasi</label>
            <input type="text" :value="customer.kodeInstalasi" readonly class="w-full! border! border-slate-200! rounded-xl! px-3! py-2.5! text-sm! text-slate-700! bg-slate-50! focus:outline-none!" />
          </div>
          <div>
            <label class="text-xs! font-semibold! text-slate-500! uppercase! tracking-wide! block! mb-1!">Tanggal Pasang</label>
            <AppDatePicker v-model="tanggalPasang" placeholder="Pilih tanggal pasang" />
          </div>
          <div>
            <label class="text-xs! font-semibold! text-slate-500! uppercase! tracking-wide! block! mb-1!">Jumlah Pembayaran</label>
            <div class="relative!">
              <span class="absolute! left-3! top-1/2! -translate-y-1/2! text-sm! text-slate-400!">Rp</span>
              <input type="text" :value="formatRupiah(customer.installationFee)" readonly class="w-full! border! border-slate-200! rounded-xl! pl-10! pr-3! py-2.5! text-sm! font-bold! text-sky-700! bg-sky-50! focus:outline-none!" />
            </div>
          </div>
        </div>

        <div class="mt-5! space-y-2!">
          <button
            @click="handleFinalize"
            :disabled="!customer.ticketId || isProcessing"
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-sky-500! to-blue-600! hover:from-sky-600! hover:to-blue-700! text-white! font-bold! py-3! rounded-xl! shadow-lg! shadow-sky-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="check-circle" />
            {{ buttonLabel }}
          </button>
          <div class="grid! grid-cols-2! gap-2!">
            <button @click="handlePrint" class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! text-sm! transition-all!">
              <font-awesome-icon icon="print" />
              Cetak
            </button>
            <button @click="$router.push({ path: '/app/instalasi/status', query: { filter: 'pasang_baru' } })" class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! text-sm! transition-all!">
              <font-awesome-icon icon="arrow-left" />
              Kembali
            </button>
          </div>
        </div>
      </ContentCard>
    </div>

    <DetailSurveyModal :show="showSurveyModal" :survey="currentSurvey" @close="closeSurveyModal" />
  </div>
</template>

<script setup>
defineOptions({ name: 'PasangBaruDetail' })
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import DetailSurveyModal from './DetailSurveyModal.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'

const showSurveyModal = ref(false)
const currentSurvey = ref(null)

const openSurveyDetail = () => {
  if (customer.value.surveyInfo) {
    currentSurvey.value = customer.value.surveyInfo
    showSurveyModal.value = true
  }
}

const closeSurveyModal = () => {
  showSurveyModal.value = false
  currentSurvey.value = null
}

const route = useRoute()
const router = useRouter()
const { dataMap, fetchData } = useInstalasiStatus()
const { transitionStatus, confirmPayment, printDetail } = useInstalasiActions()
const id = decodeURIComponent(route.params.id)
const tanggalPasang = ref(new Date().toISOString().split('T')[0])
const isProcessing = ref(false)

const formatRupiah = (val) => {
  if (!val) return '0'
  return Number(val).toLocaleString('id-ID')
}

const customer = computed(() => {
  const found = dataMap.value.pasang_baru?.find((r) => r.id === id)
  if (!found)
    return {
      name: 'Tidak Ditemukan',
      address: '-',
      region: '-',
      noInduk: '-',
      nik: '-',
      phone: '-',
      abodemen: '0',
      installationFee: 0,
      tglOrder: '-',
      paket: '-',
      kodeInstalasi: '-',
      isPaid: false,
      ticketId: null,
      rawStatus: null,
    }
  return {
    name: found.name,
    address: found.address,
    region: found.village || '-',
    noInduk: found.id,
    nik: found.nik,
    phone: found.phone,
    abodemen: found.rawData?.package?.installation_fee || '0',
    installationFee: Number(found.rawData?.package?.installation_fee || 0),
    tglOrder: found.orderDate || found.createdAt,
    paket: found.type,
    kodeInstalasi: found.id,
    isPaid:
      found.rawStatus === 'unpaid' ||
      found.rawStatus === 'processing' ||
      found.rawStatus === 'completed',
    ticketId: found.ticketId,
    rawStatus: found.rawStatus,
    rawData: found.rawData,
    surveyInfo: found.surveyInfo || null,
  }
})

const buttonLabel = computed(() => {
  switch (customer.value.rawStatus) {
    case 'surveyed':
      return 'Konfirmasi Pembayaran'
    case 'unpaid':
      return 'Lanjutkan ke Processing'
    case 'processing':
      return 'Pemasangan Selesai'
    default:
      return 'Pemasangan Selesai'
  }
})

const statusBadge = computed(() => {
  switch (customer.value.rawStatus) {
    case 'surveyed':
      return { label: 'Surveyed', class: 'bg-amber-100! text-amber-700!' }
    case 'unpaid':
      return { label: 'Unpaid', class: 'bg-orange-100! text-orange-700!' }
    case 'processing':
      return { label: 'Processing', class: 'bg-blue-100! text-blue-700!' }
    default:
      return { label: 'Pasang Baru', class: 'bg-sky-100! text-sky-700!' }
  }
})

const steps = computed(() => {
  const order = ['surveyed', 'unpaid', 'processing']
  const currentIdx = order.indexOf(customer.value.rawStatus)
  const stepDefs = [
    { key: 'surveyed', label: 'Surveyed', icon: 'clipboard-check' },
    { key: 'unpaid', label: 'Pembayaran', icon: 'money-bill-wave' },
    { key: 'processing', label: 'Pemasangan', icon: 'tools' },
  ]
  return stepDefs.map((s, i) => ({
    ...s,
    state: i < currentIdx ? 'done' : i === currentIdx ? 'current' : 'upcoming',
  }))
})

const handleFinalize = async () => {
  if (!customer.value.ticketId) return

  const currentStatus = customer.value.rawStatus
  const kodeInstalasi = customer.value.kodeInstalasi

  isProcessing.value = true
  let result

  if (currentStatus === 'surveyed') {
    result = await confirmPayment(customer.value.ticketId, customer.value.installationFee, customer.value.name)
  } else if (currentStatus === 'unpaid') {
    result = await transitionStatus(
      customer.value.ticketId,
      'processing',
      'Lanjutkan ke tahap Processing?',
      tanggalPasang.value
    )
  } else if (currentStatus === 'processing') {
    result = await transitionStatus(customer.value.ticketId, 'completed', 'Tandai instalasi selesai?')
  }

  isProcessing.value = false

  if (result?.success) {
    await fetchData()
    if (currentStatus === 'processing') {
      router.push({ path: `/app/instalasi/status/aktif/${encodeURIComponent(kodeInstalasi)}` })
    }
  }
}

const handlePrint = () => {
  printDetail(customer.value, 'Pasang Baru')
}
</script>