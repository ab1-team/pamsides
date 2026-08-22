<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0! z-50 flex items-center justify-center p-4! md:p-8!">
        <div class="absolute inset-0! bg-slate-900/60! backdrop-blur-xs!" @click="close"></div>

        <div
          class="relative w-full! max-w-2xl! bg-white rounded-2xl! shadow-xl! border border-slate-200 flex flex-col overflow-hidden animate-slide-up max-h-[90vh]!"
        >
          <div
            class="flex items-center! justify-between! px-6! py-4! border-b! border-slate-200! bg-white!"
          >
            <div class="flex items-center gap-3!">
              <div
                class="w-10! h-10! rounded-full! bg-orange-600! text-white! flex items-center! justify-center!"
              >
                <font-awesome-icon icon="edit" />
              </div>
              <div>
                <h2 class="text-lg! font-semibold! text-slate-800 leading-tight">
                  Ubah Hasil Survey
                </h2>
                <p class="text-xs! text-slate-500! font-medium!">
                  {{ survey?.ticket?.applicant_name || '-' }}
                </p>
              </div>
            </div>
            <button
              @click="close"
              class="w-9! h-9! hover:bg-slate-100! flex items-center! justify-center! text-slate-400! hover:text-slate-600! transition-all active:scale-95 rounded-md!"
            >
              <font-awesome-icon icon="times" />
            </button>
          </div>

          <div class="flex-1 overflow-auto p-6!">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6!">
              <!-- Kolom Kiri: Form Input -->
              <div class="lg:col-span-2 space-y-4!">
                <div class="bg-slate-50! rounded-xl! p-5!">
                  <h3
                    class="text-xs! font-bold! text-slate-800! uppercase! tracking-wider! mb-4! flex! items-center! gap-2!"
                  >
                    <font-awesome-icon icon="edit" class="text-orange-500!" />
                    Data Survey
                  </h3>

                  <div class="space-y-4!">
                    <div>
                      <label class="text-xs! font-bold! text-slate-700! mb-2! block!">
                        Jarak ke Pipa Utama <span class="text-red-500!">*</span>
                      </label>
                      <div class="relative!">
                        <input
                          v-model="formData.distance_to_pipe_m"
                          type="number"
                          step="0.01"
                          class="w-full! px-4! py-3! bg-white! border-2! border-slate-200! rounded-xl! text-sm! text-slate-700! focus:outline-hidden! focus:border-orange-500! focus:ring-2! focus:ring-orange-200! transition-all! font-bold! pr-16!"
                          placeholder="Masukkan jarak"
                        />
                        <span
                          class="absolute! right-4! top-1/2! -translate-y-1/2! text-xs! font-bold! text-slate-400! bg-slate-100! px-2! py-1! rounded!"
                          >METER</span
                        >
                      </div>
                    </div>

                    <div>
                      <label class="text-xs! font-bold! text-slate-700! mb-2! block!">
                        Catatan Material & Teknis <span class="text-red-500!">*</span>
                      </label>
                      <textarea
                        v-model="formData.material_notes"
                        rows="5"
                        class="w-full! px-4! py-3! bg-white! border-2! border-slate-200! rounded-xl! text-sm! text-slate-700! focus:outline-hidden! focus:border-orange-500! focus:ring-2! focus:ring-orange-200! transition-all! font-medium! resize-none!"
                        placeholder="Contoh: Butuh penambahan pipa 2 meter, lokasi di gang sempit..."
                      ></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Kolom Kanan: Upload Foto & Info Pemohon -->
              <div class="lg:col-span-1 space-y-4!">
                <!-- Upload Foto -->
                <div class="bg-slate-50! rounded-xl! p-5!">
                  <h3
                    class="text-xs! font-bold! text-slate-800! uppercase! tracking-wider! mb-4! flex! items-center! gap-2!"
                  >
                    <font-awesome-icon icon="camera" class="text-orange-500!" />
                    Foto Lokasi
                  </h3>

                  <div
                    @click="triggerFileInput"
                    class="relative! w-full! aspect-square! bg-white! border-2! border-dashed! border-slate-300! hover:border-orange-500! hover:bg-orange-50! rounded-xl! overflow-hidden! cursor-pointer! transition-all! group!"
                  >
                    <img
                      v-if="photoPreview"
                      :src="photoPreview"
                      alt="Foto Survey"
                      class="w-full! h-full! object-cover!"
                    />

                    <div
                      v-if="!photoPreview"
                      class="absolute! inset-0! flex! flex-col! items-center! justify-center! gap-3! text-slate-600! group-hover:text-orange-600!"
                    >
                      <div
                        class="w-12! h-12! bg-slate-100! group-hover:bg-orange-100! rounded-full! flex! items-center! justify-center! transition-all!"
                      >
                        <font-awesome-icon icon="image" class="text-xl!" />
                      </div>
                      <div class="text-center! px-4!">
                        <p class="text-sm! font-bold!">Upload Foto</p>
                        <p class="text-xs! text-slate-400! mt-1!">JPG, PNG (Max 2MB)</p>
                      </div>
                    </div>

                    <button
                      v-if="photoPreview"
                      @click.stop="removePhoto"
                      class="absolute! top-2! right-2! w-8! h-8! bg-red-500! hover:bg-red-600! text-white! rounded-full! flex! items-center! justify-center! transition-all! shadow-lg! z-10!"
                    >
                      <font-awesome-icon icon="times" />
                    </button>

                    <div
                      v-if="photoPreview"
                      class="absolute! inset-0! bg-black/0! hover:bg-black/40! transition-all! flex! items-center! justify-center!"
                    >
                      <div
                        class="opacity-0! group-hover:opacity-100! transition-opacity! text-white! text-sm! font-bold!"
                      >
                        <font-awesome-icon icon="camera" class="mr-2!" />
                        Ganti Foto
                      </div>
                    </div>
                  </div>

                  <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    class="hidden!"
                    @change="handleFileChange"
                  />
                </div>

                <!-- Info Pemohon -->
                <div class="bg-blue-50! border-2! border-blue-100! rounded-xl! p-3!">
                  <p
                    class="text-[10px]! font-bold! text-blue-600! uppercase! tracking-wider! mb-1!"
                  >
                    Informasi Pemohon
                  </p>
                  <p class="text-sm! font-semibold! text-blue-900! leading-tight!">
                    {{ survey?.ticket?.applicant_name || '-' }}
                  </p>
                  <p class="text-xs! text-blue-700! font-medium! leading-tight!">
                    NIK: {{ survey?.ticket?.nik || '-' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div
            class="px-6! py-4! bg-slate-50! border-t! border-slate-200! flex justify-end items-center gap-3!"
          >
            <button
              @click="close"
              class="flex items-center! gap-2! bg-white! border! border-slate-300! hover:bg-slate-100! text-slate-700! px-6! py-2.5! font-semibold! transition-all active:scale-95 rounded-lg! shadow-xs!"
            >
              <font-awesome-icon icon="times" />
              Batal
            </button>
            <button
              @click="handleSave"
              :disabled="!isFormValid"
              class="flex items-center! gap-2! bg-orange-500! hover:bg-orange-600! text-white! px-6! py-2.5! font-semibold! transition-all active:scale-95 rounded-lg! shadow-md! shadow-orange-200! disabled:opacity-50! disabled:cursor-not-allowed!"
            >
              <font-awesome-icon icon="save" />
              Simpan Perubahan
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted, onUnmounted } from 'vue'
import { storageUrl } from '@/utils/storage'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  survey: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'save'])

const fileInput = ref(null)
const photoPreview = ref(null)
const newPhotoFile = ref(null)

const formData = reactive({
  distance_to_pipe_m: 0,
  material_notes: '',
})

const isFormValid = computed(() => {
  return formData.distance_to_pipe_m > 0 && formData.material_notes.trim() !== ''
})

watch(
  () => props.survey,
  (newSurvey) => {
    if (newSurvey) {
      formData.distance_to_pipe_m = newSurvey.distance_to_pipe_m || 0
      formData.material_notes = newSurvey.material_notes || ''
      const url = newSurvey.photo_url
      if (url) {
        photoPreview.value = /^https?:\/\//i.test(url)
          ? url
          : storageUrl(`storage/survey-photos/${url}`)
      } else {
        photoPreview.value = null
      }
      newPhotoFile.value = null
    }
  },
  { immediate: true },
)

const close = () => {
  emit('close')
}

const triggerFileInput = () => {
  fileInput.value.click()
}

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    newPhotoFile.value = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

const removePhoto = () => {
  photoPreview.value = null
  newPhotoFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const handleSave = () => {
  if (!isFormValid.value) return

  const updatedData = {
    id: props.survey.id,
    ticket_id: props.survey.ticket_id,
    distance_to_pipe_m: formData.distance_to_pipe_m,
    material_notes: formData.material_notes,
    photo: newPhotoFile.value,
  }

  emit('save', updatedData)
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  },
)

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.4s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@keyframes slide-up {
  from {
    transform: translateY(30px) scale(0.98);
    opacity: 0;
  }
  to {
    transform: translateY(0) scale(1);
    opacity: 1;
  }
}

.animate-slide-up {
  animation: slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
