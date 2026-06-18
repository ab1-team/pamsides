<template>
  <div class="max-w-6xl! mx-auto grid! grid-cols-1! lg:grid-cols-5! gap-6">
    <div class="lg:col-span-2! flex! flex-col! gap-6!">
      <ContentCard variant="bordered" padding="normal" rounded="2xl">
        <div class="flex! items-center! justify-between! mb-3!">
          <p class="text-[10px]! font-bold! text-indigo-500! uppercase! tracking-widest!">
            Permohonan Baru
          </p>
          <span
            class="inline-flex! items-center! gap-1! px-2.5! py-1! rounded-full! text-[10px]! font-bold! uppercase! tracking-wider! bg-indigo-100! text-indigo-700!"
          >
            <span class="w-1.5! h-1.5! rounded-full! bg-current! opacity-60!"></span>
            Pending
          </span>
        </div>
        <h1 class="text-lg! font-bold! text-slate-800! truncate! mb-1!">{{ customer.name }}</h1>
        <div class="flex! items-center! gap-1.5! text-slate-500!">
          <font-awesome-icon icon="map-marker-alt" class="text-indigo-400! text-[10px]! shrink-0!" />
          <p class="text-[11px]! truncate!">{{ customer.address }}, {{ customer.region }}</p>
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
            <span class="info-label">Abodemen</span>
            <span class="info-value">Rp {{ customer.abodemen }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">Tgl Order</span>
            <span class="info-value">{{ customer.tglOrder }}</span>
          </div>
          <div class="info-item">
            <span class="info-label">No. Telepon</span>
            <span class="info-value">{{ customer.phone }}</span>
          </div>
        </div>

        <div class="grid! grid-cols-2! gap-2! mt-4!">
          <button
            @click="handlePrint"
            class="flex! items-center! justify-center! gap-2! bg-indigo-600! hover:bg-indigo-700! text-white! font-semibold! py-2.5! rounded-xl! text-sm! transition-all!"
          >
            <font-awesome-icon icon="print" />
            Cetak
          </button>
          <button
            @click="$router.push({ path: '/app/instalasi/status', query: { filter: 'permohonan' } })"
            class="flex! items-center! justify-center! gap-2! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-2.5! rounded-xl! text-sm! transition-all! bg-white!"
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

const customer = computed(() => {
  const empty = {
    name: 'Pelanggan Tidak Ditemukan',
    address: 'Alamat belum tercatat',
    region: 'Wilayah belum tercatat',
    noInduk: 'Tidak tersedia',
    nik: 'Tidak tersedia',
    phone: 'Tidak tersedia',
    abodemen: '0',
    tglOrder: 'Belum tercatat',
    paket: 'Belum ada paket',
    kodeInstalasi: 'Tidak tersedia',
    isPaid: false,
    ticketId: null,
    rawStatus: null,
  }

  const found = dataMap.value.permohonan?.find((r) => r.id === id || String(r.ticketId) === id)
  if (!found) return empty
  return {
    name: found.name || 'Pelanggan Tidak Ditemukan',
    address: found.address || 'Alamat belum tercatat',
    region: found.village || 'Wilayah belum tercatat',
    noInduk: found.id || 'Tidak tersedia',
    nik: found.nik || 'Tidak tersedia',
    phone: found.phone || 'Tidak tersedia',
    abodemen: found.rawData?.package?.installation_fee || '0',
    tglOrder: found.orderDate || found.createdAt || 'Belum tercatat',
    paket: found.type || 'Belum ada paket',
    kodeInstalasi: found.id || 'Tidak tersedia',
    isPaid:
      found.rawStatus === 'unpaid' ||
      found.rawStatus === 'processing' ||
      found.rawStatus === 'completed',
    ticketId: found.ticketId,
    rawStatus: found.rawStatus,
    rawData: found.rawData,
  }
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
