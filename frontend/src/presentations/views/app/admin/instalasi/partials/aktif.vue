<template>
  <div class="max-w-6xl! mx-auto grid! grid-cols-1! lg:grid-cols-5! gap-6!">
    <div class="lg:col-span-3! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-3!">
          <div
            class="w-12! h-12! rounded-full! flex! items-center! justify-center! text-white! text-sm! font-bold! shrink-0! shadow-sm!"
            :style="{ backgroundColor: avatarColor }"
          >
            {{ customerInitials }}
          </div>
          <div class="flex-1! min-w-0!">
            <p class="text-[10px]! font-bold! text-emerald-500! uppercase! tracking-widest!">
              Customer Aktif
            </p>
            <h1 class="text-lg! font-bold! text-slate-800! truncate!">{{ customer.name }}</h1>
            <div class="flex! items-center! gap-1.5! text-slate-500!">
              <font-awesome-icon icon="map-marker-alt" class="text-emerald-400! text-[10px]! shrink-0!" />
              <p class="text-[11px]! truncate!">{{ customer.address }}</p>
            </div>
          </div>
          <span
            class="inline-flex! items-center! gap-1! px-2! py-0.5! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! shrink-0! bg-emerald-100! text-emerald-700!"
          >
            <span class="w-1.5! h-1.5! rounded-full! bg-current! opacity-60!"></span>
            Aktif
          </span>
        </div>

        <div class="grid! grid-cols-2! gap-2! mt-4! pt-4! border-t! border-slate-100!">
          <div class="info-item">
            <span class="info-label">No. Induk</span>
            <span class="info-value">{{ customer.noInduk }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">NIK</span>
            <span class="info-value">{{ customer.nik }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Paket</span>
            <span class="info-value">{{ customer.paket }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Abodemen / bln</span>
            <span class="info-value">Rp {{ formatRibuan(customer.abodemen) }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Tgl Pasang</span>
            <span class="info-value">{{ customer.tglPasang }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">No. Telepon</span>
            <span class="info-value">{{ customer.phone }}</span>
          </div>
        </div>
      </ContentCard>


      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-3!">
          <div class="w-7! h-7! rounded-lg! bg-orange-100! flex! items-center! justify-center!">
            <font-awesome-icon icon="clipboard-check" class="text-orange-600! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Hasil Survey</h3>
        </div>

        <div v-if="loadingSurvey" class="text-center! py-4! text-xs! text-slate-400!">
          Memuat data survey...
        </div>
        <div v-else-if="surveys.length === 0" class="text-center! py-4! text-xs! text-slate-400!">
          Belum ada data survey untuk pelanggan ini.
        </div>
        <div v-else class="space-y-2!">
          <div
            v-for="(s, idx) in surveys"
            :key="idx"
            class="flex! items-center! justify-between! gap-3! px-3! py-2! rounded-lg! bg-slate-50! border! border-slate-100!"
          >
            <div class="flex! items-center! gap-3! min-w-0! flex-1!">
              <img
                v-if="s.photo_url"
                :src="resolvePhotoUrl(s.photo_url)"
                alt="Foto Survey"
                class="w-12! h-12! rounded-lg! object-cover! border! border-slate-200! shrink-0!"
                @error="(e) => (e.target.style.display = 'none')"
              />
              <div
                v-else
                class="w-12! h-12! rounded-lg! bg-orange-100! flex! items-center! justify-center! shrink-0!"
              >
                <font-awesome-icon icon="camera" class="text-orange-500! text-sm!" />
              </div>
              <div class="min-w-0! flex-1!">
                <div class="flex! items-center! gap-2!">
                  <span class="text-[11px]! font-bold! text-slate-700!">
                    {{ s.distance_to_pipe_m || 0 }} m
                  </span>
                  <span class="text-[10px]! text-slate-400!">•</span>
                  <span class="text-[10px]! text-slate-500!">{{ formatDate(s.surveyed_at) }}</span>
                </div>
                <p class="text-[10px]! text-slate-500! truncate! mt-0.5!">
                  {{ s.surveyor?.name || 'Tanpa surveyor' }}
                </p>
                <p v-if="s.material_notes" class="text-[10px]! text-slate-400! truncate! mt-0.5!">
                  {{ s.material_notes }}
                </p>
              </div>
            </div>
            <button
              @click="openEditSurvey(s)"
              class="w-8! h-8! flex! items-center! justify-center! rounded-lg! border! border-slate-100! hover:border-orange-200! hover:bg-orange-50! text-slate-600! hover:text-orange-600! shrink-0!"
              title="Edit Survey"
            >
              <font-awesome-icon icon="edit" class="text-xs!" />
            </button>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-3!">
          <div class="w-7! h-7! rounded-lg! bg-cyan-100! flex! items-center! justify-center!">
            <font-awesome-icon icon="history" class="text-cyan-600! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Riwayat Pencatatan Meter</h3>
        </div>

        <div v-if="customer.readings.length === 0" class="text-center! py-4! text-xs! text-slate-400!">
          Belum ada pencatatan meter.
        </div>
        <div v-else class="space-y-2!">
          <div
            v-for="(r, idx) in customer.readings"
            :key="idx"
            class="flex! items-center! justify-between! px-3! py-2! rounded-lg! bg-slate-50! border! border-slate-100!"
          >
            <div class="flex! items-center! gap-2!">
              <div
                class="w-7! h-7! rounded-full! bg-white! border! border-slate-200! flex! items-center! justify-center! text-[10px]! font-bold! text-slate-600!"
              >
                {{ r.label }}
              </div>
              <div>
                <div class="text-[11px]! font-bold! text-slate-700!">
                  {{ formatMeter(r.meter_value) }} m³
                </div>
                <div class="text-[10px]! text-slate-400!">{{ r.date }}</div>
              </div>
            </div>
            <span class="text-[10px]! font-bold! text-slate-500!">
              {{ r.usage > 0 ? `+${formatMeter(r.usage)} m³` : '—' }}
            </span>
          </div>
        </div>
      </ContentCard>
    </div>

    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-3!">
          <div class="w-7! h-7! bg-sky-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="file-invoice-dollar" class="text-sky-600! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Tagihan Terakhir</h3>
        </div>

        <div v-if="!customer.latestBill" class="text-center! py-4! text-xs! text-slate-400!">
          Belum ada tagihan.
        </div>
        <div v-else class="space-y-2!">
          <div class="rounded-xl! bg-gradient-to-br! from-sky-50! to-cyan-50! border! border-sky-100! p-3!">
            <div class="flex! items-center! justify-between! mb-1!">
              <span class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wider!">
                Periode
              </span>
              <span class="text-[10px]! font-bold! text-slate-700!">
                {{ customer.latestBill.periodLabel }}
              </span>
            </div>
            <div class="flex! items-center! justify-between! mb-2!">
              <span class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wider!">
                Total Tagihan
              </span>
              <span class="text-base! font-black! text-sky-700!">
                Rp {{ formatRibuan(customer.latestBill.total_amount) }}
              </span>
            </div>
            <div class="grid! grid-cols-3! gap-1! pt-2! border-t! border-sky-100!">
              <div class="text-center!">
                <p class="text-[8px]! font-bold! text-slate-400! uppercase!">Pakai</p>
                <p class="text-[11px]! font-bold! text-slate-700!">
                  {{ formatMeter(customer.latestBill.usage_m3) }} m³
                </p>
              </div>
              <div class="text-center!">
                <p class="text-[8px]! font-bold! text-slate-400! uppercase!">Abodemen</p>
                <p class="text-[11px]! font-bold! text-slate-700!">
                  Rp {{ formatRibuan(customer.latestBill.abodemen) }}
                </p>
              </div>
              <div class="text-center!">
                <p class="text-[8px]! font-bold! text-slate-400! uppercase!">Denda</p>
                <p
                  class="text-[11px]! font-bold!"
                  :class="customer.latestBill.penalty_amount > 0 ? 'text-rose-600!' : 'text-slate-400!'"
                >
                  Rp {{ formatRibuan(customer.latestBill.penalty_amount) }}
                </p>
              </div>
            </div>
            <div class="flex! items-center! justify-between! mt-2! pt-2! border-t! border-sky-100!">
              <span
                class="inline-flex! items-center! gap-1! px-2! py-0.5! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider!"
                :class="
                  customer.latestBill.status === 'paid'
                    ? 'bg-emerald-100! text-emerald-700!'
                    : 'bg-orange-100! text-orange-700!'
                "
              >
                {{ customer.latestBill.status === 'paid' ? 'Lunas' : 'Belum Bayar' }}
              </span>
              <span class="text-[10px]! text-slate-500!">
                Jatuh tempo: {{ customer.latestBill.due_date }}
              </span>
            </div>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-3!">
          <div class="w-7! h-7! bg-violet-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="water" class="text-violet-600! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Tarif Berlangsung</h3>
        </div>

        <div class="space-y-2!">
          <div
            v-for="(block, idx) in customer.tariffBlocks"
            :key="idx"
            class="flex! items-center! justify-between! px-3! py-2! rounded-lg! bg-slate-50! border! border-slate-100!"
          >
            <div class="flex! items-center! gap-2!">
              <div
                class="w-7! h-7! rounded-full! bg-white! border! border-slate-200! flex! items-center! justify-center! text-[10px]! font-bold! text-violet-600!"
              >
                {{ idx + 1 }}
              </div>
              <div>
                <p class="text-[11px]! font-bold! text-slate-700!">{{ block.range }}</p>
                <p class="text-[10px]! text-slate-400!">{{ block.label }}</p>
              </div>
            </div>
            <span class="text-[11px]! font-bold! text-violet-600!">
              Rp {{ formatRibuan(block.price_per_m3) }}/m³
            </span>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-3!">
          <div class="w-7! h-7! bg-emerald-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="cog" class="text-emerald-500! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Kelola Pelanggan</h3>
        </div>
        <div class="space-y-2!">
          <button
            @click="handleBlokir"
            :disabled="!customer.ticketId"
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-orange-500! to-amber-500! hover:from-orange-600! hover:to-amber-600! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-orange-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="ban" />
            Blokir Pelanggan
          </button>
          <button
            @click="handleCabut"
            :disabled="!customer.ticketId"
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-red-500! to-rose-600! hover:from-red-600! hover:to-rose-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-red-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="times-circle" />
            Cabut Instalasi
          </button>
          <div class="grid! grid-cols-2! gap-2! pt-1!">
            <button
              @click="handlePrint"
              class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2! rounded-lg! text-sm! transition-all!"
            >
              <font-awesome-icon icon="print" />
              Cetak
            </button>
            <button
              @click="$router.push({ path: '/app/instalasi/status', query: { filter: 'aktif' } })"
              class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2! rounded-lg! text-sm! transition-all!"
            >
              <font-awesome-icon icon="arrow-left" />
              Kembali
            </button>
          </div>
        </div>
      </ContentCard>
    </div>

    <EditSurveyModal
      :show="showEditSurvey"
      :survey="selectedSurvey"
      @close="showEditSurvey = false"
      @save="handleSaveSurvey"
    />
  </div>
</template>

<script setup>
defineOptions({ name: 'AktifDetail' })
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import { storageUrl } from '@/utils/storage'
import { useUiStore } from '@/stores/uiStore'
import ticketService from '@/services/ticket.service'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import EditSurveyModal from './EditSurveyModal.vue'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()
const { dataMap, fetchData } = useInstalasiStatus()
const { transitionStatus, printDetail } = useInstalasiActions()
const id = decodeURIComponent(route.params.id)

const surveys = ref([])
const loadingSurvey = ref(false)
const showEditSurvey = ref(false)
const selectedSurvey = ref(null)

const resolvePhotoUrl = (url) => {
  if (!url) return null
  if (/^https?:\/\//i.test(url)) return url
  return storageUrl(`storage/survey-photos/${url}`)
}

const loadSurveys = async () => {
  const ticketId = customer.value?.ticketId
  if (!ticketId) {
    surveys.value = []
    return
  }
  loadingSurvey.value = true
  try {
    const res = await ticketService.getSurveyResults({
      ticket_id: ticketId,
      per_page: 100,
    })
    const list = res?.data?.data
    surveys.value = Array.isArray(list) ? list : []
  } catch (err) {
    console.error('Gagal memuat survey:', err)
    surveys.value = []
  } finally {
    loadingSurvey.value = false
  }
}

const openEditSurvey = (s) => {
  selectedSurvey.value = {
    id: s.id,
    ticket_id: s.ticket_id,
    ticket: s.ticket,
    surveyor: s.surveyor,
    distance_to_pipe_m: s.distance_to_pipe_m || 0,
    material_notes: s.material_notes || '',
    photo_url: s.photo_url,
  }
  showEditSurvey.value = true
}

const handleSaveSurvey = async (updatedData) => {
  try {
    uiStore.setLoading(true)
    const formData = new FormData()
    formData.append('distance_to_pipe_m', updatedData.distance_to_pipe_m)
    formData.append('material_notes', updatedData.material_notes)
    formData.append('_method', 'PUT')
    if (updatedData.photo) {
      formData.append('photo', updatedData.photo)
    }
    await ticketService.updateSurvey(updatedData.id, formData)
    uiStore.success('Survey berhasil diupdate')
    showEditSurvey.value = false
    await loadSurveys()
  } catch (err) {
    console.error(err)
    uiStore.error('Gagal update survey')
  } finally {
    uiStore.setLoading(false)
  }
}

const MONTHS_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

const formatMeter = (val) => {
  const n = Number(val || 0)
  return n.toLocaleString('id-ID')
}

const formatRibuan = (val) => {
  if (val === null || val === undefined || val === '') return '0'
  return Number(val).toLocaleString('id-ID')
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return '-'
  return `${d.getDate()} ${MONTHS_ID[d.getMonth()]} ${d.getFullYear()}`
}

const customer = computed(() => {
  const found = dataMap.value.aktif?.find((r) => r.id === id)
  if (!found)
    return {
      name: 'Tidak Ditemukan',
      address: '-',
      region: '-',
      noInduk: '-',
      nik: '-',
      phone: '-',
      abodemen: 0,
      tglPasang: '-',
      paket: '-',
      kodeInstalasi: '-',
      meterAwal: 0,
      meterAkhir: 0,
      totalPemakaian: 0,
      meterPhoto: null,
      readings: [],
      ticketId: null,
      rawStatus: null,
    }

  const customerRecord = Array.isArray(found.rawData?.customer)
    ? found.rawData.customer[0]
    : found.rawData?.customer

  const meterAwal = Number(customerRecord?.initial_meter_reading || 0)

  const readingsRaw = Array.isArray(customerRecord?.meter_readings)
    ? customerRecord.meter_readings
    : []

  const readings = readingsRaw
    .map((r) => {
      const y = Number(r.reading_year)
      const m = Number(r.reading_month)
      const date = new Date(r.recorded_at || r.created_at || `${y}-${String(m).padStart(2, '0')}-25`)
      return {
        label: m ? MONTHS_ID[m - 1].substring(0, 3) + " '" + String(y).slice(-2) : '-',
        year: y,
        month: m,
        meter_value: Number(r.meter_value || 0),
        date: !isNaN(date.getTime()) ? formatDate(date) : `${m}/${y}`,
      }
    })
    .sort((a, b) => (a.year - b.year) * 12 + (a.month - b.month))

  readings.forEach((r, i) => {
    if (i === 0) {
      r.usage = Math.max(0, r.meter_value - meterAwal)
    } else {
      r.usage = Math.max(0, r.meter_value - readings[i - 1].meter_value)
    }
  })

  const lastReading = readings[readings.length - 1]
  const meterAkhir = lastReading ? lastReading.meter_value : meterAwal
  const totalPemakaian = Math.max(0, meterAkhir - meterAwal)

  const photoPath = customerRecord?.meter_photo_url
  const meterPhoto = photoPath
    ? /^https?:\/\//i.test(photoPath)
      ? photoPath
      : storageUrl(`storage/${photoPath}`)
    : null

  // Tagihan terakhir dari monthly_bills
  const billsRaw = Array.isArray(found.rawData?.monthly_bills)
    ? found.rawData.monthly_bills
    : Array.isArray(customerRecord?.monthly_bills)
      ? customerRecord.monthly_bills
      : []
  const latestBill = billsRaw.length
    ? billsRaw
        .slice()
        .sort((a, b) => (Number(b.billing_period_year) - Number(a.billing_period_year)) * 12 + (Number(b.billing_period_month) - Number(a.billing_period_month)))[0]
    : null
  const latestBillData = latestBill
    ? {
        periodLabel: `${MONTHS_ID[Number(latestBill.billing_period_month) - 1]} ${latestBill.billing_period_year}`,
        total_amount: Number(latestBill.total_amount || 0),
        usage_m3: Number(latestBill.usage_m3 || 0),
        abodemen: Number(latestBill.abodemen || 0),
        penalty_amount: Number(latestBill.penalty_amount || 0),
        status: latestBill.status || 'unpaid',
        due_date: formatDate(latestBill.due_date),
      }
    : null

  // Tarif blok dari package.tariffBlocks
  const blocksRaw = Array.isArray(found.rawData?.package?.tariff_blocks)
    ? found.rawData.package.tariff_blocks
    : Array.isArray(found.rawData?.package?.tariffBlocks)
      ? found.rawData.package.tariffBlocks
      : []
  const tariffBlocks = blocksRaw
    .slice()
    .sort((a, b) => Number(a.usage_min_m3) - Number(b.usage_min_m3))
    .map((b) => {
      const min = Number(b.usage_min_m3)
      const max = b.usage_max_m3 !== null && b.usage_max_m3 !== undefined ? Number(b.usage_max_m3) : null
      const range = max === null ? `${min}+ m³` : max - min >= 1 ? `${min}–${max} m³` : `${min} m³`
      return {
        range,
        label: max === null ? `Pemakaian > ${min} m³` : `Pemakaian ${min}–${max} m³`,
        price_per_m3: Number(b.price_per_m3 || 0),
      }
    })

  return {
    name: found.name,
    address: found.address,
    region: found.village || '-',
    noInduk: found.id,
    nik: found.nik,
    phone: found.phone || '-',
    abodemen: found.rawData?.package?.monthly_abodemen || 0,
    tglPasang: formatDate(customerRecord?.activated_at) || formatDate(found.orderDate),
    paket: found.type,
    kodeInstalasi: found.id,
    meterAwal,
    meterAkhir,
    totalPemakaian,
    meterPhoto,
    readings,
    latestBill: latestBillData,
    tariffBlocks,
    ticketId: found.ticketId,
    rawStatus: found.rawStatus,
    rawData: found.rawData,
  }
})

const customerInitials = computed(() => {
  const name = customer.value?.name || '?'
  const parts = String(name).trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const avatarColor = computed(() => {
  const palette = ['#10b981', '#0ea5e9', '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#14b8a6']
  const name = customer.value?.name || ''
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  return palette[Math.abs(hash) % palette.length]
})

const handleBlokir = async () => {
  if (!customer.value.ticketId) return
  const kodeInstalasi = customer.value.kodeInstalasi
  const result = await transitionStatus(
    customer.value.ticketId,
    'suspended',
    `Blokir layanan untuk pelanggan "${customer.value.name}"?`,
  )
  if (result.success) {
    await fetchData()
    router.push({
      path: `/app/instalasi/status/blokir/${encodeURIComponent(kodeInstalasi)}`,
    })
  }
}

const handleCabut = async () => {
  if (!customer.value.ticketId) return
  const kodeInstalasi = customer.value.kodeInstalasi
  const result = await transitionStatus(
    customer.value.ticketId,
    'terminated',
    `Cabut instalasi untuk pelanggan "${customer.value.name}"? Tindakan ini tidak dapat dikembalikan.`,
  )
  if (result.success) {
    await fetchData()
    router.push({
      path: `/app/instalasi/status/cabut/${encodeURIComponent(kodeInstalasi)}`,
    })
  }
}

const handlePrint = () => {
  printDetail({ ...customer.value, tglOrder: customer.value.tglPasang }, 'Aktif')
}

onMounted(async () => {
  await fetchData()
  await loadSurveys()
})

watch(
  () => customer.value?.ticketId,
  () => loadSurveys(),
)
</script>

<style scoped>
@reference "@/assets/css/main.css";

.info-item {
  @apply flex flex-col gap-0.5 bg-slate-50 rounded-lg px-3! py-2! border border-slate-100;
}

.info-label {
  @apply text-[9px] font-bold text-slate-400 uppercase tracking-wider;
}

.info-value {
  @apply text-[12px] font-bold text-slate-800 truncate;
}
</style>

