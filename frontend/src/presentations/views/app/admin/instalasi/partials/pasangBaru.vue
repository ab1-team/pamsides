<template>
  <div class="max-w-6xl! mx-auto grid! grid-cols-1! lg:grid-cols-5! gap-6!">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-3!">
          <div
            class="w-12! h-12! rounded-full! flex! items-center! justify-center! text-white! text-sm! font-bold! shrink-0! shadow-sm!"
            :style="{ backgroundColor: avatarColor }"
          >
            {{ customerInitials }}
          </div>
          <div class="flex-1! min-w-0!">
            <p class="text-[10px]! font-bold! text-sky-500! uppercase! tracking-widest!">
              Profil Pelanggan
            </p>
            <h1 class="text-lg! font-bold! text-slate-800! truncate!">
              {{ customer.name }}
            </h1>
            <div class="flex! items-center! gap-1.5! text-slate-500!">
              <font-awesome-icon
                icon="map-marker-alt"
                class="text-sky-400! text-[10px]! shrink-0!"
              />
              <p class="text-[11px]! truncate!">{{ customer.address }}</p>
            </div>
          </div>
          <span
            class="inline-flex! items-center! gap-1! px-2! py-0.5! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! shrink-0!"
            :class="statusBadge.class"
          >
            <span class="w-1.5! h-1.5! rounded-full! bg-current! opacity-60!"></span>
            {{ statusBadge.label }}
          </span>
        </div>

        <div class="grid! grid-cols-2! gap-2! mt-4! pt-4! border-t! border-slate-100!">
          <div class="info-item">
            <span class="info-label">No. Induk</span>
            <span class="info-value" v-html="formatInduk(customer.noInduk)"></span>
          </div>
          <div class="info-item">
            <span class="info-label">Tgl Order</span>
            <span class="info-value">{{ customer.tglOrder }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Paket</span>
            <span class="info-value paket">{{ customer.paket }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Abodemen</span>
            <span class="info-value">{{ customer.abodemen }}</span>
          </div>
        </div>

        <div
          v-if="customer.ticketId"
          class="mt-3! pt-3! border-t! border-slate-100! flex! items-center! justify-between!"
        >
          <span class="text-[9px]! font-bold! text-slate-400! uppercase! tracking-widest!"
            >Tiket</span
          >
          <span class="text-[10px]! text-slate-600! font-mono! font-semibold!">
            {{ customer.ticketId }}
          </span>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="small" rounded="2xl">
        <div class="grid! grid-cols-2! gap-2!">
          <button
            @click="handlePrint"
            class="flex! items-center! justify-center! gap-2! bg-sky-600! hover:bg-sky-700! text-white! font-semibold! py-2.5! rounded-lg! text-sm! transition-all!"
          >
            <font-awesome-icon icon="print" />
            Cetak
          </button>
          <button
            @click="
              $router.push({ path: '/app/instalasi/status', query: { filter: 'pasang_baru' } })
            "
            class="flex! items-center! justify-center! gap-2! bg-slate-100! hover:bg-slate-200! text-slate-600! font-semibold! py-2.5! rounded-lg! text-sm! transition-all!"
          >
            <font-awesome-icon icon="arrow-left" />
            Kembali
          </button>
        </div>
      </ContentCard>
    </div>

    <div class="lg:col-span-3! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="small" rounded="2xl">
        <div class="flex! items-center! justify-between! gap-1!">
          <div
            v-for="(step, idx) in steps"
            :key="step.key"
            class="flex-1! flex! flex-col! items-center! relative!"
          >
            <div
              class="w-7! h-7! rounded-full! flex! items-center! justify-center! text-white! text-[10px]! shadow-sm! z-10! transition-all!"
              :class="
                step.state === 'done'
                  ? 'bg-emerald-500!'
                  : step.state === 'current'
                    ? 'bg-sky-500! ring-2! ring-sky-100!'
                    : 'bg-slate-300!'
              "
            >
              <font-awesome-icon :icon="step.state === 'done' ? 'check' : step.icon" />
            </div>
            <p
              class="text-[9px]! font-semibold! mt-1! text-center! leading-tight!"
              :class="
                step.state === 'done'
                  ? 'text-emerald-600!'
                  : step.state === 'current'
                    ? 'text-sky-600!'
                    : 'text-slate-400!'
              "
            >
              {{ step.label }}
            </p>
            <div
              v-if="idx < steps.length - 1"
              class="absolute! top-3.5! left-1/2! w-full! h-0.5! -z-0!"
              :class="step.state === 'done' ? 'bg-emerald-400!' : 'bg-slate-200!'"
            ></div>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div
            class="w-7! h-7! rounded-lg! flex! items-center! justify-center!"
            :class="stagePanel.iconBg"
          >
            <font-awesome-icon
              :icon="stagePanel.icon"
              class="text-xs!"
              :class="stagePanel.iconColor"
            />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">{{ stagePanel.title }}</h3>
        </div>

        <div v-if="customer.rawStatus === 'surveyed'">
          <div v-if="customer.surveyInfo" class="space-y-3!">
            <div
              v-if="customer.paymentInfo"
              class="rounded-xl! border! border-slate-200! bg-slate-50! p-3! space-y-2!"
            >
              <div class="flex! items-center! justify-between! text-xs!">
                <span class="text-slate-600! font-semibold!">Total Biaya Instalasi</span>
                <span class="font-bold! text-slate-800!"
                  >Rp {{ formatRupiah(customer.paymentInfo.total_fee) }}</span
                >
              </div>
              <div>
                <div class="flex! items-center! justify-between! text-xs!">
                  <span class="text-emerald-700! font-semibold!">Sudah Dibayar</span>
                  <span class="font-bold! text-emerald-700!"
                    >Rp
                    {{
                      formatRupiah(customer.paymentInfo.paid + customer.paymentInfo.pending)
                    }}</span
                  >
                </div>
                <div class="flex! items-center! justify-between! text-[10px]! mt-1! pl-2!">
                  <span class="text-slate-500! flex! items-center! gap-1!">
                    <span class="w-1.5! h-1.5! rounded-full! bg-emerald-500!"></span>
                    Confirmed
                  </span>
                  <span class="text-slate-600! font-medium!"
                    >Rp {{ formatRupiah(customer.paymentInfo.paid) }}</span
                  >
                </div>
                <div
                  v-if="customer.paymentInfo.pending > 0"
                  class="flex! items-center! justify-between! text-[10px]! mt-0.5! pl-2!"
                >
                  <span class="text-slate-500! flex! items-center! gap-1!">
                    <span class="w-1.5! h-1.5! rounded-full! bg-amber-500!"></span>
                    Menunggu Konfirmasi
                  </span>
                  <span class="text-slate-600! font-medium!"
                    >Rp {{ formatRupiah(customer.paymentInfo.pending) }}</span
                  >
                </div>
              </div>
              <div
                class="flex! items-center! justify-between! text-xs! pt-2! border-t! border-slate-200!"
              >
                <span
                  class="font-bold! uppercase! tracking-wide!"
                  :class="
                    customer.paymentInfo.remaining > 0 ? 'text-orange-700!' : 'text-emerald-700!'
                  "
                  >Sisa Tagihan</span
                >
                <span
                  class="font-extrabold!"
                  :class="
                    customer.paymentInfo.remaining > 0 ? 'text-orange-700!' : 'text-emerald-700!'
                  "
                >
                  {{
                    customer.paymentInfo.remaining > 0
                      ? `Rp ${formatRupiah(customer.paymentInfo.remaining)}`
                      : 'LUNAS'
                  }}
                </span>
              </div>
            </div>

            <div
              class="flex! items-center! gap-3! p-3! bg-slate-50! border! border-slate-100! rounded-xl!"
            >
              <div
                class="w-10! h-10! rounded-full! bg-amber-500! text-white! flex! items-center! justify-center! shrink-0!"
              >
                <font-awesome-icon icon="clipboard-check" />
              </div>
              <div class="flex-1! min-w-0!">
                <p class="text-sm! font-bold! text-slate-800!">Hasil Survey Tersedia</p>
                <p class="text-[11px]! text-slate-500!">
                  {{ customer.surveyInfo.distance_to_pipe_m }}m dari pipa utama ·
                  {{ customer.surveyInfo.surveyor_name || '-' }}
                </p>
              </div>
              <button
                @click="openSurveyDetail"
                class="px-3! py-1.5! bg-amber-500! hover:bg-amber-600! text-white! text-xs! font-bold! rounded-lg! flex! items-center! gap-1.5! transition-all! active:scale-95! shrink-0!"
              >
                <font-awesome-icon icon="eye" />
                Lihat Detail
              </button>
            </div>

            <button
              @click="handleAdvance"
              :disabled="!customer.ticketId || isAdvancing"
              class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-sky-500! to-blue-600! hover:from-sky-600! hover:to-blue-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-sky-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
            >
              <font-awesome-icon
                :icon="isAdvancing ? 'spinner' : 'arrow-right'"
                :class="isAdvancing ? 'animate-spin!' : ''"
              />
              {{ isAdvancing ? 'Memproses...' : 'Lanjut ke Tahap Berikutnya' }}
            </button>
          </div>
          <div v-else class="text-center! py-6! text-xs! text-slate-400!">
            Belum ada data survey.
          </div>
        </div>

        <div v-else-if="customer.rawStatus === 'unpaid'">
          <div v-if="customer.paymentInfo" class="space-y-3!">
            <div class="rounded-xl! border! border-orange-200! bg-orange-50! p-3! space-y-2!">
              <div class="flex! items-center! justify-between! text-xs!">
                <span class="text-slate-600! font-semibold!">Total Biaya</span>
                <span class="font-bold! text-slate-800!"
                  >Rp {{ formatRupiah(customer.paymentInfo.total_fee) }}</span
                >
              </div>
              <div>
                <div class="flex! items-center! justify-between! text-xs!">
                  <span class="text-emerald-700! font-semibold!">Sudah Dibayar</span>
                  <span class="font-bold! text-emerald-700!"
                    >Rp
                    {{
                      formatRupiah(customer.paymentInfo.paid + customer.paymentInfo.pending)
                    }}</span
                  >
                </div>
                <div
                  v-if="customer.paymentInfo.has_pending"
                  class="flex! items-center! justify-between! text-[10px]! mt-1! pl-2!"
                >
                  <span class="text-slate-500! flex! items-center! gap-1!">
                    <span class="w-1.5! h-1.5! rounded-full! bg-emerald-500!"></span>
                    Confirmed
                  </span>
                  <span class="text-slate-600! font-medium!"
                    >Rp {{ formatRupiah(customer.paymentInfo.paid) }}</span
                  >
                </div>
                <div
                  v-if="customer.paymentInfo.has_pending"
                  class="flex! items-center! justify-between! text-[10px]! mt-0.5! pl-2!"
                >
                  <span class="text-slate-500! flex! items-center! gap-1!">
                    <span class="w-1.5! h-1.5! rounded-full! bg-amber-500!"></span>
                    Menunggu Konfirmasi
                  </span>
                  <span class="text-slate-600! font-medium!"
                    >Rp {{ formatRupiah(customer.paymentInfo.pending) }}</span
                  >
                </div>
              </div>
              <div
                class="flex! items-center! justify-between! text-xs! pt-2! border-t! border-orange-200!"
              >
                <span class="font-bold! uppercase! tracking-wide! text-orange-700!"
                  >Sisa Tagihan</span
                >
                <span
                  class="font-extrabold!"
                  :class="
                    customer.paymentInfo.remaining > 0 ? 'text-orange-700!' : 'text-emerald-700!'
                  "
                >
                  {{
                    customer.paymentInfo.remaining > 0
                      ? `Rp ${formatRupiah(customer.paymentInfo.remaining)}`
                      : 'LUNAS'
                  }}
                </span>
              </div>
            </div>
            <div
              class="bg-rose-50! border! border-rose-200! rounded-xl! p-3! flex! items-start! gap-2!"
            >
              <font-awesome-icon icon="lock" class="text-rose-600! mt-0.5! shrink-0!" />
              <p class="text-[11px]! text-rose-800! leading-relaxed!">
                Tidak dapat lanjut ke tahap Pemasangan sebelum tagihan lunas.
              </p>
            </div>
            <button
              @click="goToTagihanInstalasi"
              class="w-full! py-2.5! rounded-xl! bg-orange-500! hover:bg-orange-600! text-white! text-xs! font-bold! flex! items-center! justify-center! gap-2! transition-all! active:scale-95!"
            >
              <font-awesome-icon icon="money-bill-wave" />
              Bayar / Lunasi di Tagihan Instalasi
            </button>
          </div>
          <div v-else class="text-center! py-6! text-xs! text-slate-400!">
            Belum ada data tagihan.
          </div>
        </div>

        <div v-else-if="customer.rawStatus === 'processing'">
          <div class="space-y-4!">
            <div>
              <label
                class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-1!"
              >
                Angka Meter Awal
              </label>
              <div class="relative!">
                <input
                  v-model="installForm.initial_meter_reading"
                  type="number"
                  step="1"
                  min="0"
                  class="w-full! h-9! px-3! pr-12! bg-slate-50! border! border-slate-200! rounded-lg! text-xs! text-slate-700! focus:outline-none! focus:border-sky-500! focus:bg-white! transition-all!"
                  placeholder="0"
                />
                <span
                  class="absolute! right-3! top-1/2! -translate-y-1/2! text-[10px]! text-slate-400!"
                  >M³</span
                >
              </div>
            </div>

            <div>
              <label
                class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-2!"
              >
                Foto Meteran
              </label>
              <div class="photo-uploader">
                <div class="grid! grid-cols-2! gap-3!">
                  <template v-if="installForm.photoPreview && installForm.photoSource === 'camera'">
                    <div class="preview-slot">
                      <img :src="installForm.photoPreview" class="preview-img" />
                      <button @click="clearInstallPhoto" class="remove-btn">
                        <font-awesome-icon icon="times" />
                      </button>
                      <div class="source-tag">
                        <font-awesome-icon icon="camera" />
                        <span>Kamera</span>
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <div
                      class="preview-slot border-sky-300 flex flex-col items-center justify-center gap-1"
                      @click="triggerInstallCamera"
                    >
                      <font-awesome-icon icon="camera" class="text-sky-400 text-lg" />
                      <span class="text-[9px] font-bold text-slate-500">Kamera</span>
                    </div>
                  </template>

                  <template
                    v-if="installForm.photoPreview && installForm.photoSource === 'gallery'"
                  >
                    <div class="preview-slot">
                      <img :src="installForm.photoPreview" class="preview-img" />
                      <button @click="clearInstallPhoto" class="remove-btn">
                        <font-awesome-icon icon="times" />
                      </button>
                      <div class="source-tag">
                        <font-awesome-icon icon="images" />
                        <span>Galeri</span>
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <div
                      class="preview-slot border-sky-300 flex flex-col items-center justify-center gap-1"
                      @click="triggerInstallGallery"
                    >
                      <font-awesome-icon icon="images" class="text-sky-400 text-lg" />
                      <span class="text-[9px] font-bold text-slate-500">Galeri</span>
                    </div>
                  </template>
                </div>

                <div class="hidden!">
                  <input
                    ref="installGalleryInput"
                    type="file"
                    accept="image/*"
                    class="hidden!"
                    @change="onInstallFileChange"
                  />
                </div>
              </div>
            </div>

            <button
              @click="submitInstallationResult"
              :disabled="
                !installForm.initial_meter_reading || !installForm.photoFile || isSubmittingInstall
              "
              class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-sky-500! to-blue-600! hover:from-sky-600! hover:to-blue-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-sky-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
            >
              <font-awesome-icon
                :icon="isSubmittingInstall ? 'spinner' : 'save'"
                :class="isSubmittingInstall ? 'animate-spin!' : ''"
              />
              {{
                isSubmittingInstall
                  ? 'Menyimpan...'
                  : installationResult
                    ? 'Update Hasil Pemasangan'
                    : 'Aktifkan Pemasangan'
              }}
            </button>
          </div>
        </div>
      </ContentCard>
    </div>

    <DetailSurveyModal :show="showSurveyModal" :survey="currentSurvey" @close="closeSurveyModal" />
    <CameraModal
      :show="showCameraModal"
      @close="showCameraModal = false"
      @capture="handleCameraCapture"
    />
  </div>
</template>

<script setup>
defineOptions({ name: 'PasangBaruDetail' })
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import { useUiStore } from '@/stores/uiStore'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import CameraModal from '@/presentations/components/ui/CameraModal.vue'
import DetailSurveyModal from './DetailSurveyModal.vue'
import ticketService from '@/services/ticket.service'
import cameraUtils from '@/utils/camera'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()
const { dataMap, fetchData } = useInstalasiStatus()
const { printDetail } = useInstalasiActions()

const decodeId = (raw) => {
  let prev = raw
  let curr = raw
  for (let i = 0; i < 3; i++) {
    try {
      curr = decodeURIComponent(curr)
    } catch {
      break
    }
    if (curr === prev) break
    prev = curr
  }
  return curr
}
const id = decodeId(String(route.params.id))

const isAdvancing = ref(false)
const isSubmittingInstall = ref(false)
const showSurveyModal = ref(false)
const showCameraModal = ref(false)
const currentSurvey = ref(null)
const installGalleryInput = ref(null)

const installForm = reactive({
  initial_meter_reading: '',
  photoFile: null,
  photoPreview: null,
  photoSource: null,
})

const installationResult = computed(() => {
  return customer.value?.rawData?.installation_result || null
})

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

const triggerInstallCamera = () => {
  showCameraModal.value = true
}

const triggerInstallGallery = () => {
  installGalleryInput.value?.click()
}

const handleCameraCapture = async (file) => {
  try {
    showCameraModal.value = false
    uiStore.setLoading(true)
    const compressed = await cameraUtils.compressImage(file)
    installForm.photoFile = compressed
    installForm.photoPreview = URL.createObjectURL(compressed)
    installForm.photoSource = 'camera'
    uiStore.success('Foto berhasil ditangkap.')
  } catch (err) {
    console.error(err)
    uiStore.error('Gagal memproses foto.')
  } finally {
    uiStore.setLoading(false)
  }
}

const onInstallFileChange = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  try {
    uiStore.setLoading(true)
    const compressedBlob = await cameraUtils.compressImage(file)
    installForm.photoFile = compressedBlob
    installForm.photoPreview = URL.createObjectURL(compressedBlob)
    installForm.photoSource = 'gallery'
    uiStore.success('Foto berhasil dipilih.')
  } catch (err) {
    console.error(err)
    uiStore.error('Gagal memproses gambar.')
  } finally {
    uiStore.setLoading(false)
  }
}

const clearInstallPhoto = () => {
  installForm.photoFile = null
  installForm.photoPreview = null
  installForm.photoSource = null
  if (installGalleryInput.value) installGalleryInput.value.value = ''
}

const submitInstallationResult = async () => {
  if (!customer.value.ticketId) {
    Swal.fire('Gagal', 'ID Tiket tidak ditemukan.', 'error')
    return
  }
  if (!installForm.initial_meter_reading) {
    Swal.fire('Meter kosong', 'Masukkan angka meter awal.', 'warning')
    return
  }
  if (!installForm.photoFile) {
    Swal.fire('Foto kosong', 'Ambil foto meteran via Kamera atau Galeri.', 'warning')
    return
  }
  isSubmittingInstall.value = true
  try {
    const fd = new FormData()
    fd.append('initial_meter_reading', installForm.initial_meter_reading)
    fd.append('photo', installForm.photoFile)
    const res = await ticketService.submitInstallationResult(customer.value.ticketId, fd)
    if (res?.success) {
      const customerCode = res?.customer_code || res?.data?.customer_code
      await Swal.fire({
        icon: 'success',
        title: 'Pemasangan Aktif!',
        html: `Hasil pemasangan tersimpan & status tiket kini <strong>Selesai</strong>.<br/>Kode Pelanggan: <strong style="color:#0284c7;">${customerCode || '-'}</strong>`,
        confirmButtonColor: '#0284c7',
        confirmButtonText: 'Lihat Detail Aktif',
      })
      if (customerCode) {
        router.push({
          path: `/app/instalasi/status/aktif/${encodeURIComponent(customerCode)}`,
        })
      } else {
        await fetchData()
      }
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal menyimpan hasil pemasangan.'
    Swal.fire('Gagal', msg, 'error')
  } finally {
    isSubmittingInstall.value = false
  }
}

const formatRupiah = (val) => {
  if (val === null || val === undefined || val === '') return '0'
  return Number(val).toLocaleString('id-ID')
}

const formatInduk = (val) => {
  if (!val || val === '-') return '-'
  return `<span class="text-sky-600 font-bold">${val}</span>`
}

const customer = computed(() => {
  const found = dataMap.value.pasang_baru?.find((r) => r.id === id || String(r.ticketId) === id)
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
      paymentInfo: null,
      surveyInfo: null,
      rawData: null,
    }

  const totalFee = Number(found.rawData?.package?.installation_fee || 0)
  const payments = Array.isArray(found.rawData?.payments) ? found.rawData.payments : []
  const paid = payments
    .filter((p) => p.status === 'confirmed')
    .reduce((sum, p) => sum + Number(p.amount || 0), 0)
  const pendingTotal = payments
    .filter((p) => p.status === 'pending')
    .reduce((sum, p) => sum + Number(p.amount || 0), 0)
  const remaining = Math.max(0, totalFee - paid - pendingTotal)

  return {
    name: found.name,
    address: found.address,
    region: found.village || '-',
    noInduk: found.id,
    nik: found.nik,
    phone: found.phone,
    abodemen: found.rawData?.package?.installation_fee || '0',
    installationFee: totalFee,
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
    paymentInfo: {
      total_fee: totalFee,
      paid: paid,
      pending: pendingTotal,
      remaining: remaining,
      has_pending: pendingTotal > 0,
    },
  }
})

const statusBadge = computed(() => {
  switch (customer.value.rawStatus) {
    case 'surveyed':
      return { label: 'Disurvei', class: 'bg-amber-100! text-amber-700!' }
    case 'unpaid':
      return { label: 'Belum Bayar', class: 'bg-orange-100! text-orange-700!' }
    case 'processing':
      return { label: 'Diproses', class: 'bg-blue-100! text-blue-700!' }
    default:
      return { label: 'Pasang Baru', class: 'bg-sky-100! text-sky-700!' }
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
  const palette = [
    '#0ea5e9',
    '#6366f1',
    '#8b5cf6',
    '#ec4899',
    '#f43f5e',
    '#f97316',
    '#10b981',
    '#14b8a6',
  ]
  const name = customer.value?.name || ''
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  return palette[Math.abs(hash) % palette.length]
})

const stagePanel = computed(() => {
  switch (customer.value.rawStatus) {
    case 'surveyed':
      return {
        title: 'Hasil Survey Lapangan',
        icon: 'clipboard-check',
        iconBg: 'bg-amber-100!',
        iconColor: 'text-amber-600!',
      }
    case 'unpaid':
      return {
        title: 'Detail Tagihan',
        icon: 'file-invoice-dollar',
        iconBg: 'bg-orange-100!',
        iconColor: 'text-orange-600!',
      }
    case 'processing':
      return {
        title: 'Pemasangan Aktif',
        icon: 'tools',
        iconBg: 'bg-blue-100!',
        iconColor: 'text-blue-600!',
      }
    default:
      return {
        title: 'Detail',
        icon: 'info-circle',
        iconBg: 'bg-slate-100!',
        iconColor: 'text-slate-500!',
      }
  }
})

const steps = computed(() => {
  const order = ['surveyed', 'unpaid', 'processing']
  const currentIdx = order.indexOf(customer.value.rawStatus)
  const stepDefs = [
    { key: 'surveyed', label: 'Disurvei', icon: 'clipboard-check' },
    { key: 'unpaid', label: 'Pembayaran', icon: 'money-bill-wave' },
    { key: 'processing', label: 'Pemasangan', icon: 'tools' },
  ]
  return stepDefs.map((s, i) => ({
    ...s,
    state: i < currentIdx ? 'done' : i === currentIdx ? 'current' : 'upcoming',
  }))
})

const goToTagihanInstalasi = () => {
  router.push({
    path: '/app/transaksi/tagihan-instalasi',
    query: { ticket: customer.value.ticketId },
  })
}

const handlePrint = () => {
  printDetail(customer.value, 'Pasang Baru')
}

watch(
  () => installationResult.value,
  (val) => {
    if (val && !installForm.initial_meter_reading) {
      installForm.initial_meter_reading = val.initial_meter_reading || ''
    }
  },
  { immediate: true },
)

onMounted(async () => {
  await fetchData()
})
</script>

<style scoped>
@reference "@/assets/css/main.css";

.info-grid {
  @apply grid grid-cols-2 gap-3;
}

.info-item {
  @apply flex flex-col gap-0.5 bg-slate-50 rounded-lg px-3! py-2! border border-slate-100;
}

.info-label {
  @apply text-[9px] font-bold text-slate-400 uppercase tracking-wider;
}

.info-value {
  @apply text-[12px] font-bold text-slate-800 truncate;
}

.preview-slot {
  @apply relative overflow-hidden rounded-xl border-2 border-dashed border-sky-300 bg-slate-50 h-40;
}

.preview-img {
  @apply w-full h-full object-cover;
}

.remove-btn {
  @apply absolute top-2 right-2 w-7 h-7 bg-black/40 hover:bg-red-500 text-white rounded-full flex items-center justify-center transition-all text-xs;
}

.source-tag {
  @apply absolute bottom-2 left-2 flex items-center gap-1.5 bg-sky-600 text-white px-2.5 py-1 rounded-lg text-[10px] font-bold;
}
</style>
