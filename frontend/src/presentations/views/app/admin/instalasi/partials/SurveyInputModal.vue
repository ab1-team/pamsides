<template>
  <div
    v-if="show"
    class="fixed! inset-0! bg-black/50! backdrop-blur-sm! z-[60]! flex! items-center! justify-center! p-4!"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white! rounded-2xl! w-full! max-w-2xl! max-h-[90vh]! overflow-y-auto! shadow-2xl! z-[61]!"
    >
      <div
        class="sticky! top-0! bg-white! border-b! border-slate-100! px-6! py-4! flex! items-center! justify-between!"
      >
        <div class="flex! items-center! gap-3!">
          <div class="w-10! h-10! bg-indigo-100! rounded-xl! flex! items-center! justify-center!">
            <font-awesome-icon icon="clipboard-check" class="text-indigo-600!" />
          </div>
          <div>
            <h3 class="text-lg! font-bold! text-slate-800!">Input Survey</h3>
            <p class="text-xs! text-slate-500!">{{ customerName }}</p>
          </div>
        </div>
        <button
          @click="$emit('close')"
          class="w-8! h-8! rounded-full! bg-slate-100! hover:bg-slate-200! flex! items-center! justify-center! text-slate-500! transition-colors!"
        >
          <font-awesome-icon icon="times" />
        </button>
      </div>

      <div class="p-6! space-y-6!">
        <div class="space-y-4!">
          <div>
            <label
              class="text-xs! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-2!"
            >
              Jarak ke Pipa Utama (Meter)
            </label>
            <div class="relative!">
              <input
                v-model="formData.distance_to_pipe_m"
                type="number"
                step="1"
                class="w-full! h-12! px-4! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:border-indigo-500! focus:bg-white! transition-all! font-bold!"
                placeholder="0"
              />
              <span
                class="absolute! right-4! top-1/2! -translate-y-1/2! text-xs! font-bold! text-slate-400!"
                >METER</span
              >
            </div>
          </div>

          <div>
            <label
              class="text-xs! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-2!"
            >
              Catatan Material & Teknis
            </label>
            <textarea
              v-model="formData.material_notes"
              rows="4"
              class="w-full! px-4! py-3! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:border-indigo-500! focus:bg-white! transition-all! font-medium! resize-none!"
              placeholder="Contoh: Butuh penambahan pipa 2 meter, lokasi di gang sempit..."
            ></textarea>
          </div>

          <div>
            <label
              class="text-xs! font-bold! text-slate-500! uppercase! tracking-wide! block! mb-3!"
            >
              Foto Lokasi
            </label>
            <div class="photo-uploader">
              <div v-if="!photoPreview" class="grid! grid-cols-2! gap-4!">
                <div
                  class="upload-placeholder group p-6! text-center! cursor-pointer! transition-all!"
                  @click="triggerCamera"
                >
                  <div
                    class="icon-box w-12! h-12! bg-slate-50! rounded-2xl! flex! items-center! justify-center! mx-auto! mb-3! text-slate-300! group-hover:text-indigo-500! group-hover:scale-110! transition-all!"
                  >
                    <font-awesome-icon icon="camera" class="text-xl!" />
                  </div>
                  <h4 class="text-xs! font-black! text-slate-700! uppercase!">Ambil Foto</h4>
                  <p class="text-[11px]! text-slate-400! font-bold! mt-1!">Kamera</p>
                </div>

                <div
                  class="upload-placeholder-secondary group p-4! cursor-pointer! transition-all!"
                  @click="triggerGallery"
                >
                  <div class="flex! items-center! gap-3!">
                    <div
                      class="icon-box-sm w-10! h-10! bg-slate-50! rounded-xl! flex! items-center! justify-center! text-slate-400! group-hover:text-indigo-500! group-hover:bg-white! transition-all!"
                    >
                      <font-awesome-icon icon="images" />
                    </div>
                    <div class="text-left! min-w-0!">
                      <h4 class="text-[11px]! font-black! text-slate-700! uppercase! truncate!">
                        Pilih Galeri
                      </h4>
                      <p class="text-xs! text-slate-400! font-bold! truncate!">Unggah file</p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else class="preview-box h-48!">
                <img :src="photoPreview" class="preview-img" />
                <div class="preview-overlay"></div>
                <button
                  @click="
                    photoPreview = null
                    formData.photo = null
                  "
                  class="remove-btn"
                >
                  <font-awesome-icon icon="times" />
                </button>
                <div class="preview-info">
                  <div class="info-tag">
                    <font-awesome-icon icon="check" />
                    <span>FOTO SIAP</span>
                  </div>
                </div>
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

        <div class="flex! items-center! gap-3! pt-4! border-t! border-slate-100!">
          <button
            @click="$emit('close')"
            class="flex-1! border! border-slate-200! hover:bg-slate-50! text-slate-600! font-semibold! py-3! rounded-xl! text-sm! transition-all!"
          >
            Batal
          </button>
          <button
            @click="submitSurvey"
            :disabled="!isFormValid || isSubmitting"
            class="flex-1! flex! items-center! justify-center! gap-2! bg-gradient-to-r! from-indigo-500! to-blue-600! hover:from-indigo-600! hover:to-blue-700! text-white! font-bold! py-3! rounded-xl! shadow-lg! shadow-indigo-200/50! transition-all! active:scale-95! disabled:opacity-50! disabled:cursor-not-allowed!"
          >
            <font-awesome-icon icon="save" />
            {{ isSubmitting ? 'Menyimpan...' : 'Simpan Survey' }}
          </button>
        </div>
      </div>

      <CameraModal
        :show="showCameraModal"
        @close="showCameraModal = false"
        @capture="handleCameraCapture"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import CameraModal from '@/presentations/components/ui/CameraModal.vue'
import cameraUtils from '@/utils/camera'
import ticketService from '@/services/ticket.service'

const props = defineProps({
  show: Boolean,
  ticketId: [String, Number],
  customerName: String,
})

const emit = defineEmits(['close', 'success'])

const uiStore = useUiStore()
const galleryInput = ref(null)
const photoPreview = ref(null)
const showCameraModal = ref(false)
const isSubmitting = ref(false)

onMounted(() => {
  if (props.show) uiStore.openModal()
})

onBeforeUnmount(() => {
  uiStore.closeModal()
})

watch(
  () => props.show,
  (newVal) => {
    if (newVal) uiStore.openModal()
    else uiStore.closeModal()
  },
)

const formData = reactive({
  distance_to_pipe_m: '',
  material_notes: '',
  photo: null,
})

const isFormValid = computed(() => {
  return formData.distance_to_pipe_m && formData.material_notes && formData.photo
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
    uiStore.success('Foto berhasil diproses.')
  } catch {
    uiStore.error('Gagal memproses gambar.')
  } finally {
    uiStore.setLoading(false)
  }
}

const submitSurvey = async () => {
  if (!isFormValid.value || !props.ticketId) return

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    const submitData = new FormData()
    submitData.append('distance_to_pipe_m', Math.round(formData.distance_to_pipe_m))
    submitData.append('material_notes', formData.material_notes)
    submitData.append('photo', formData.photo)

    await ticketService.submitSurvey(props.ticketId, submitData)
    uiStore.success('Survey berhasil disimpan.')
    emit('success')
  } catch (err) {
    console.error(err)
    uiStore.error('Gagal menyimpan survey.')
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}
</script>

<style scoped>
@reference "@/assets/css/main.css";

.upload-placeholder {
  @apply border-2 border-dashed border-slate-200 rounded-2xl;
}

.upload-placeholder-secondary {
  @apply flex items-center gap-4 border border-slate-200 rounded-2xl;
}

.preview-box {
  @apply relative overflow-hidden rounded-2xl border-4 border-white shadow-xl;
}

.preview-img {
  @apply w-full h-full object-cover;
}

.preview-overlay {
  @apply absolute inset-0 bg-gradient-to-t from-black/50 to-transparent;
}

.remove-btn {
  @apply absolute top-3 right-3 w-9 h-9 bg-black/20 hover:bg-red-500 backdrop-blur-md text-white rounded-full flex items-center justify-center transition-all;
}

.preview-info {
  @apply absolute bottom-3 left-3 right-3;
}

.info-tag {
  @apply flex items-center gap-2 bg-emerald-500 text-white px-3 py-1.5 rounded-xl text-[10px] font-black w-fit;
}
</style>
