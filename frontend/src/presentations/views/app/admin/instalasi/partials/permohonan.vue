<template>
  <div class="max-w-6xl! mx-auto grid! grid-cols-1! lg:grid-cols-5! gap-6">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="large" rounded="2xl">
        <div class="flex! items-start! justify-between! gap-4!">
          <div class="flex-1!">
            <p class="text-xs! font-bold! text-indigo-500! uppercase! tracking-widest! mb-2!">
              Customer Profile
            </p>
            <h1 class="text-2xl! md:text-3xl! font-extrabold! text-slate-800! mb-3!">
              {{ customer.name }}
            </h1>
            <div class="flex! items-start! gap-2! text-slate-500!">
              <font-awesome-icon icon="map-marker-alt" class="text-indigo-400! mt-0.5! shrink-0!" />
              <div>
                <p class="text-sm! font-medium!">{{ customer.address }}</p>
                <p class="text-xs! text-slate-400!">{{ customer.region }}</p>
              </div>
            </div>
          </div>
          <div class="shrink-0! flex! flex-col! items-center! gap-2!">
            <div
              class="w-24! h-24! bg-slate-800! rounded-xl! flex! items-center! justify-center! shadow-md!"
            >
              <font-awesome-icon icon="qrcode" class="text-white! text-5xl!" />
            </div>
            <span class="text-[10px]! text-slate-400! font-medium! tracking-wide!"
              >Work Order QR</span
            >
          </div>
        </div>

        <div class="border-t! border-dashed! border-slate-200! mt-4! pt-4!">
          <h3 class="text-sm! font-bold! text-slate-800! mb-3! flex! items-center! gap-2!">
            <font-awesome-icon icon="file-invoice" class="text-indigo-500!" />
            Informasi Permohonan
          </h3>

          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">No. Induk</span>
              <span class="info-value" v-html="formatInduk(customer.noInduk)"></span>
            </div>

            <div class="info-item">
              <span class="info-label">Tgl Order</span>
              <span class="info-value">{{ customer.tglOrder }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Paket Instalasi</span>
              <span class="info-value paket">{{ customer.paket }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Abodemen</span>
              <span class="info-value">{{ customer.abodemen }}</span>
            </div>
          </div>
        </div>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! bg-indigo-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="stream" class="text-indigo-500! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Timeline Status</h3>
        </div>
        <ol class="space-y-3!">
          <li
            v-for="(step, idx) in timelineSteps"
            :key="step.key"
            class="flex! items-start! gap-3!"
          >
            <div class="flex flex-col items-center">
              <div
                :class="[
                  'w-8! h-8! rounded-full! flex! items-center! justify-center! text-xs!',
                  step.state === 'done'
                    ? 'bg-emerald-500! text-white!'
                    : step.state === 'current'
                      ? 'bg-indigo-600! text-white! ring-4! ring-indigo-100!'
                      : step.state === 'terminated'
                        ? 'bg-rose-500! text-white!'
                        : step.state === 'suspended'
                          ? 'bg-amber-500! text-white!'
                          : 'bg-slate-100! text-slate-400!',
                ]"
              >
                <font-awesome-icon :icon="step.icon" />
              </div>
              <span
                v-if="idx < timelineSteps.length - 1"
                class="w-0.5! h-6! bg-slate-200! mt-1!"
                :class="{ 'bg-emerald-300!': step.state === 'done' }"
              ></span>
            </div>
            <div class="pt-1!">
              <p
                :class="[
                  'text-sm! font-semibold!',
                  step.state === 'upcoming' ? 'text-slate-400!' : 'text-slate-800!',
                ]"
              >
                {{ step.label }}
              </p>
              <p class="text-[11px]! text-slate-400! capitalize!">
                {{
                  step.state === 'done'
                    ? 'Selesai'
                    : step.state === 'current'
                      ? 'Sedang berjalan'
                      : step.state === 'terminated'
                        ? 'Permohonan dibatalkan'
                        : step.state === 'suspended'
                          ? 'Sementara diblokir'
                          : 'Menunggu'
                }}
              </p>
            </div>
          </li>
        </ol>
      </ContentCard>

      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="grid! grid-cols-2! gap-3!">
          <button
            @click="handlePrint"
            class="flex! items-center! justify-center! gap-2! bg-indigo-600! hover:bg-indigo-700! text-white! font-semibold! py-2.5! rounded-lg! text-sm! transition-all!"
          >
            <font-awesome-icon icon="print" />
            Cetak
          </button>
          <button
            @click="
              $router.push({ path: '/app/instalasi/status', query: { filter: 'permohonan' } })
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
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! gap-2! mb-4!">
          <div class="w-7! h-7! bg-indigo-100! rounded-lg! flex! items-center! justify-center!">
            <font-awesome-icon icon="clipboard-check" class="text-indigo-500! text-xs!" />
          </div>
          <h3 class="text-sm! font-bold! text-slate-800!">Input Survey</h3>
        </div>

        <div class="space-y-2!">
          <div>
            <label
              class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-1!"
            >
              Jarak ke Pipa Utama
            </label>
            <div class="relative!">
              <input
                v-model="formData.distance_to_pipe_m"
                type="number"
                step="1"
                class="w-full! h-9! px-3! bg-slate-50! border! border-slate-200! rounded-lg! text-xs! text-slate-700! focus:outline-none! focus:border-indigo-500! focus:bg-white! transition-all!"
                placeholder="0"
              />
              <span
                class="absolute! right-3! top-1/2! -translate-y-1/2! text-[10px]! text-slate-400!"
                >Meter</span
              >
            </div>
          </div>

          <div>
            <label
              class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-1!"
            >
              Catatan Material
            </label>
            <textarea
              v-model="formData.material_notes"
              rows="2"
              class="w-full! px-3! py-1.5! bg-slate-50! border! border-slate-200! rounded-lg! text-xs! text-slate-700! focus:outline-none! focus:border-indigo-500! focus:bg-white! transition-all! resize-y! min-h-16!"
              placeholder="Catatan teknis..."
            ></textarea>
          </div>

          <div>
            <label
              class="text-[10px]! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-2!"
            >
              Foto Lokasi
            </label>
            <div class="photo-uploader">
              <div class="grid! grid-cols-2! gap-3!">
                <template v-if="photoPreview && photoSource === 'camera'">
                  <div class="preview-slot">
                    <img :src="photoPreview" class="preview-img" />
                    <button @click="clearPhoto" class="remove-btn">
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
                    class="preview-slot border-indigo-300 flex flex-col items-center justify-center gap-1"
                    @click="triggerCamera"
                  >
                    <font-awesome-icon icon="camera" class="text-indigo-400 text-lg" />
                    <span class="text-[9px] font-bold text-slate-500">Kamera</span>
                  </div>
                </template>

                <template v-if="photoPreview && photoSource === 'gallery'">
                  <div class="preview-slot">
                    <img :src="photoPreview" class="preview-img" />
                    <button @click="clearPhoto" class="remove-btn">
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
                    class="preview-slot border-indigo-300 flex flex-col items-center justify-center gap-1"
                    @click="triggerGallery"
                  >
                    <font-awesome-icon icon="images" class="text-indigo-400 text-lg" />
                    <span class="text-[9px] font-bold text-slate-500">Galeri</span>
                  </div>
                </template>
              </div>

              <div class="hidden!">
                <input
                  ref="galleryInput"
                  type="file"
                  accept="image/*"
                  class="hidden!"
                  @change="handlePhotoUpload"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4!">
          <button
            @click="submitSurvey"
            :disabled="!isFormValid || isSubmitting || !customer.ticketId"
            class="w-full! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-indigo-500! to-blue-600! hover:from-indigo-600! hover:to-blue-700! text-white! font-bold! py-2.5! rounded-xl! shadow-lg! shadow-indigo-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="save" />
            {{ isSubmitting ? 'Menyimpan...' : 'Proses ke Pasang Baru' }}
          </button>
        </div>
      </ContentCard>
    </div>

    <CameraModal
      :show="showCameraModal"
      @close="showCameraModal = false"
      @capture="handleCameraCapture"
    />
  </div>
</template>

<script setup>
defineOptions({ name: 'PermohonanDetail' })
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInstalasiStatus } from '@/composables/useInstalasiStatus'
import { useInstalasiActions } from '@/composables/useInstalasiActions'
import { useUiStore } from '@/stores/uiStore'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import CameraModal from '@/presentations/components/ui/CameraModal.vue'
import cameraUtils from '@/utils/camera'
import ticketService from '@/services/ticket.service'

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

const galleryInput = ref(null)
const photoPreview = ref(null)
const photoSource = ref(null)
const showCameraModal = ref(false)
const isSubmitting = ref(false)

const formData = reactive({
  distance_to_pipe_m: '',
  material_notes: '',
  photo: null,
})

const isFormValid = computed(() => {
  return formData.distance_to_pipe_m && formData.material_notes && formData.photo
})

const formatInduk = (val) => {
  if (!val || val === '-') return '-'
  return `<span class="text-blue-600 font-bold">${val}</span>`
}

const customer = computed(() => {
  const found = dataMap.value.permohonan?.find((r) => r.id === id || String(r.ticketId) === id)
  if (!found)
    return {
      name: 'Tidak Ditemukan',
      address: '-',
      region: '-',
      noInduk: '-',
      nik: '-',
      phone: '-',
      abodemen: '0',
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
  }
})

const timelineSteps = computed(() => {
  const order = ['draft', 'pending', 'surveyed', 'unpaid', 'processing', 'completed']
  const current = customer.value.rawStatus
  const currentIdx = order.indexOf(current)

  const defs = [
    { key: 'pending', label: 'Permohonan', icon: 'file-signature' },
    { key: 'surveyed', label: 'Survey', icon: 'clipboard-check' },
    { key: 'unpaid', label: 'Pembayaran', icon: 'money-bill-wave' },
    { key: 'processing', label: 'Pemasangan', icon: 'tools' },
    { key: 'completed', label: 'Aktif', icon: 'check-circle' },
  ]

  return defs.map((s, i) => ({
    ...s,
    state:
      current === 'terminated'
        ? 'terminated'
        : current === 'suspended'
          ? 'suspended'
          : i < currentIdx
            ? 'done'
            : i === currentIdx
              ? 'current'
              : 'upcoming',
  }))
})

const triggerCamera = () => {
  showCameraModal.value = true
}

const handleCameraCapture = async (file) => {
  try {
    showCameraModal.value = false
    uiStore.setLoading(true)
    const compressed = await cameraUtils.compressImage(file)
    formData.photo = compressed
    photoPreview.value = URL.createObjectURL(compressed)
    photoSource.value = 'camera'
  } catch (err) {
    console.error(err)
    uiStore.error('Gagal memproses foto.')
  } finally {
    uiStore.setLoading(false)
  }
}

const triggerGallery = () => {
  galleryInput.value.click()
}

const handlePhotoUpload = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  try {
    uiStore.setLoading(true)
    photoPreview.value = URL.createObjectURL(file)
    const compressedBlob = await cameraUtils.compressImage(file)
    formData.photo = compressedBlob
    photoSource.value = 'gallery'
    uiStore.success('Foto berhasil diproses.')
  } catch {
    uiStore.error('Gagal memproses gambar.')
  } finally {
    uiStore.setLoading(false)
  }
}

const clearPhoto = () => {
  photoPreview.value = null
  formData.photo = null
  photoSource.value = null
}

const submitSurvey = async () => {
  if (!isFormValid.value || !customer.value.ticketId) {
    uiStore.error('Mohon lengkapi semua data termasuk foto.')
    return
  }

  if (!formData.photo) {
    uiStore.error('Foto lokasi belum diupload.')
    return
  }

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    console.log('Submitting survey:', {
      ticketId: customer.value.ticketId,
      distance: formData.distance_to_pipe_m,
      notes: formData.material_notes,
      photo: formData.photo,
      photoSize: formData.photo?.size,
      photoType: formData.photo?.type,
    })

    const submitData = new FormData()
    submitData.append('distance_to_pipe_m', Math.round(formData.distance_to_pipe_m))
    submitData.append('material_notes', formData.material_notes)
    submitData.append('photo', formData.photo)

    await ticketService.submitSurvey(customer.value.ticketId, submitData)
    uiStore.success('Survey berhasil disimpan.')
    const kodeInstalasi = customer.value.kodeInstalasi || String(customer.value.ticketId)
    await fetchData()
    router.push({
      path: `/app/instalasi/status/pasang-baru/${encodeURIComponent(kodeInstalasi)}`,
    })
  } catch (err) {
    console.error('Survey submit error:', err)
    uiStore.error('Gagal menyimpan survey.')
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}

const handlePrint = () => {
  printDetail(customer.value, 'Permohonan')
}

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
  @apply flex flex-col gap-1 bg-white rounded-xl p-3 border border-slate-100 shadow-sm;
}

.info-label {
  @apply text-[10px] font-bold text-slate-400 uppercase tracking-wider;
}

.info-value {
  @apply text-sm font-bold text-slate-800 truncate;
}

.section-header {
  @apply flex flex-col gap-0.5;
}

.section-title {
  @apply text-sm font-bold text-slate-800;
}

.section-subtitle {
  @apply text-[11px] text-slate-400 font-medium mt-0.5;
}

.upload-placeholder {
  @apply border-2 border-dashed border-slate-200 rounded-2xl;
}

.upload-placeholder-secondary {
  @apply flex items-center gap-4 border border-slate-200 rounded-2xl;
}

.preview-slot {
  @apply relative overflow-hidden rounded-xl border-2 border-dashed border-indigo-300 bg-slate-50 h-40;
}

.preview-img {
  @apply w-full h-full object-cover;
}

.remove-btn {
  @apply absolute top-2 right-2 w-7 h-7 bg-black/40 hover:bg-red-500 text-white rounded-full flex items-center justify-center transition-all text-xs;
}

.source-tag {
  @apply absolute bottom-2 left-2 flex items-center gap-1.5 bg-indigo-600 text-white px-2.5 py-1 rounded-lg text-[10px] font-bold;
}
</style>
