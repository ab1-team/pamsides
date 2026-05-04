<template>
  <div class="survey-create-view p-4! lg:p-8!">
    <div class="header-section flex! flex-col! md:flex-row! md:items-center! md:justify-between! gap-4! mb-8!">
      <div class="flex! items-center! gap-4!">
        <button 
          v-if="route.query.id"
          @click="router.push('/dashboard')"
          class="w-10! h-10! bg-white! border! border-slate-200! rounded-full! flex! items-center! justify-center! text-slate-500! hover:bg-slate-50! hover:text-orange-500! transition-all! shadow-sm!"
        >
          <font-awesome-icon icon="arrow-left" />
        </button>
        <div class="text-left!">
          <h1 class="text-2xl! md:text-3xl! font-black! text-slate-800! tracking-tight!">
            Buat <span class="text-orange-500!">Survey Baru</span>
          </h1>
          <p class="text-slate-500! text-xs! md:text-sm! font-medium! mt-1!">Input hasil survey lapangan untuk permohonan baru.</p>
        </div>
      </div>

      <div class="bg-white! border! border-slate-200! rounded-2xl! px-4! py-2.5! flex! items-center! gap-3! shadow-sm! self-start! md:self-center!">
        <div class="w-9! h-9! bg-slate-100! rounded-xl! flex! items-center! justify-center! text-slate-500! shrink-0!">
          <font-awesome-icon icon="user" />
        </div>
        <div class="min-w-0!">
          <p class="text-xs! font-bold! text-slate-400! uppercase! tracking-wider! leading-none! mb-1!">Surveyor</p>
          <p class="text-xs! font-black! text-slate-800! leading-none! truncate!">{{ surveyorName }}</p>
        </div>
      </div>
    </div>

    <div class="grid! grid-cols-1! lg:grid-cols-12! gap-6! lg:gap-8!">
      <div class="lg:col-span-8! space-y-6!">
        <ContentCard variant="default" padding="large" class="border-0! shadow-sm!">
          <div class="flex! items-center! gap-3! mb-6!">
            <div class="w-1.5! h-6! bg-indigo-500! rounded-full!"></div>
            <h3 class="text-base! md:text-lg! font-black! text-slate-800!">Informasi Teknis</h3>
          </div>

          <div class="space-y-6! md:space-y-8!">
            <div class="form-group">
              <label class="block! text-xs! font-bold! text-slate-400! uppercase! tracking-wider! mb-3!">
                Pilih Permohonan Instalasi
              </label>
              <SelectSearch
                v-model="formData.ticket_id"
                :options="ticketOptions"
                placeholder="Cari NIK atau Nama Pemohon..."
                :loading="loadingTickets"
                :disabled="!!route.query.id"
              />
              <p v-if="ticketOptions.length === 0 && !loadingTickets" class="mt-2! text-xs! text-orange-500! font-bold!">
                * Tidak ada permohonan yang menunggu survey.
              </p>
            </div>

            <div class="grid! grid-cols-1! gap-6!">
              <div class="form-group">
                <label class="block! text-xs! font-bold! text-slate-400! uppercase! tracking-wider! mb-3!">
                  Jarak ke Pipa Utama
                </label>
                <div class="relative!">
                  <input
                    v-model="formData.distance_to_pipe_m"
                    type="number"
                    step="1"
                    class="w-full! h-12! px-4! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:border-orange-500! focus:bg-white! transition-all! font-bold!"
                    placeholder="0"
                  />
                  <span class="absolute! right-4! top-1/2! -translate-y-1/2! text-xs! font-bold! text-slate-400!">METER</span>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="block! text-xs! font-bold! text-slate-400! uppercase! tracking-wider! mb-3!">
                Catatan Material & Teknis Lapangan
              </label>
              <textarea
                v-model="formData.material_notes"
                rows="4"
                class="w-full! px-4! py-3! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:border-orange-500! focus:bg-white! transition-all! font-medium!"
                placeholder="Contoh: Butuh penambahan pipa 2 meter, lokasi di gang sempit..."
              ></textarea>
            </div>
          </div>
        </ContentCard>
      </div>

      <div class="lg:col-span-4! space-y-6!">
        <ContentCard variant="default" padding="large" class="border-0! shadow-sm!">
          <div class="flex! items-center! gap-3! mb-6! md:mb-8!">
            <div class="w-1.5! h-6! bg-emerald-500! rounded-full!"></div>
            <h3 class="text-base! md:text-lg! font-black! text-slate-800!">Foto Lokasi</h3>
          </div>

          <div class="photo-uploader">
            <div v-if="!photoPreview" class="grid! grid-cols-1! gap-4!">
              <div
                class="upload-placeholder group p-8! md:p-10!"
                @click="triggerCamera"
              >
                <div class="icon-box w-12! h-12! md:w-16! md:h-16! mb-4! md:mb-6!">
                  <font-awesome-icon icon="camera" class="text-xl! md:text-2xl!" />
                </div>
                <h4 class="text-xs! md:text-sm! font-black! text-slate-700! uppercase!">Ambil Foto</h4>
                <p class="text-xs! text-slate-400! font-bold! mt-2!">Kamera Perangkat</p>
              </div>

              <div
                class="upload-placeholder-secondary group p-4!"
                @click="triggerGallery"
              >
                <div class="icon-box-sm shrink-0!">
                  <font-awesome-icon icon="images" />
                </div>
                <div class="text-left! min-w-0!">
                  <h4 class="text-[11px]! font-black! text-slate-700! uppercase! truncate!">Pilih Galeri</h4>
                  <p class="text-xs! text-slate-400! font-bold! truncate!">Upload file yang ada</p>
                </div>
              </div>
            </div>

            <div v-else class="preview-box group h-48! md:h-64!">
              <img :src="photoPreview" class="preview-img" />
              <div class="preview-overlay"></div>
              <button @click="photoPreview = null" class="remove-btn">
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

            <CameraModal 
              :show="showCameraModal" 
              @close="showCameraModal = false"
              @capture="handleCameraCapture"
            />
          </div>

          <div class="mt-8! md:mt-10! space-y-4!">
            <BaseButton
              variant="primary-gradient"
              block
              size="lg"
              class="submit-btn h-12! md:h-14!"
              @click="submitSurvey"
              :loading="isSubmitting"
              :disabled="!isFormValid"
            >
              KIRIM HASIL SURVEY
            </BaseButton>
            <p class="text-center! text-xs! font-bold! text-slate-400! uppercase! tracking-wider!">
              Data akan diverifikasi oleh Admin
            </p>
          </div>
        </ContentCard>

        <div class="bg-indigo-600! rounded-3xl! p-6! text-white! shadow-xl! shadow-indigo-200!">
          <h4 class="text-sm! font-black! uppercase! tracking-widest! mb-4!">Instruksi Survey</h4>
          <ul class="space-y-3!">
            <li class="flex! gap-3! text-xs! font-bold! opacity-90!">
              <div class="w-5! h-5! rounded-full! bg-white/20! flex! items-center! justify-center! shrink-0!">1</div>
              <span>Ukur jarak dari pipa distribusi utama ke titik meter.</span>
            </li>
            <li class="flex! gap-3! text-xs! font-bold! opacity-90!">
              <div class="w-5! h-5! rounded-full! bg-white/20! flex! items-center! justify-center! shrink-0!">2</div>
              <span>Foto harus mencakup area calon lokasi meteran.</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import CameraModal from '@/presentations/components/ui/CameraModal.vue'
import cameraUtils from '@/utils/camera'
import ticketService from '@/services/ticket.service'

const router = useRouter()
const route = useRoute()
const uiStore = useUiStore()

const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
const surveyorName = ref(userData.name || 'Unknown Surveyor')

const isSubmitting = ref(false)
const loadingTickets = ref(false)
const photoPreview = ref(null)
const showCameraModal = ref(false)
const galleryInput = ref(null)
const ticketOptions = ref([])

const formData = reactive({
  ticket_id: '',
  distance_to_pipe_m: '',
  material_notes: '',
  photo: null,
})

const isFormValid = computed(() => {
  return formData.ticket_id && 
         formData.distance_to_pipe_m && 
         formData.photo && 
         formData.material_notes
})

onMounted(async () => {
  await fetchPendingTickets()
  
  if (route.query.id) {
    formData.ticket_id = Number(route.query.id)
  }
})

const fetchPendingTickets = async () => {
  try {
    loadingTickets.value = true
    const response = await ticketService.getTickets({ status: 'pending' })
    const tickets = response.data.data || []
    ticketOptions.value = tickets.map(t => ({
      value: t.id,
      label: `${t.applicant_name} (${t.nik}) - ${t.address}`
    }))
  } catch (err) {
    uiStore.error('Gagal mengambil daftar permohonan.')
  } finally {
    loadingTickets.value = false
  }
}

const triggerCamera = () => {
  showCameraModal.value = true
}

const handleCameraCapture = async (file) => {
  try {
    uiStore.setLoading(true)
    const compressed = await cameraUtils.compressImage(file)
    formData.photo = compressed
    photoPreview.value = URL.createObjectURL(compressed)
  } catch (err) {
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
  if (!isFormValid.value) return

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    const submitData = new FormData()
    submitData.append('distance_to_pipe_m', Math.round(formData.distance_to_pipe_m))
    submitData.append('material_notes', formData.material_notes)
    submitData.append('photo', formData.photo)

    await ticketService.submitSurvey(formData.ticket_id, submitData)

    uiStore.success('Hasil survey berhasil dikirim.')
    
    if (route.query.id) {
      router.push('/dashboard')
    } else {
      Object.assign(formData, {
        ticket_id: '',
        distance_to_pipe_m: '',
        material_notes: '',
        photo: null,
      })
      photoPreview.value = null
      await fetchPendingTickets()
    }
  } catch (err) {
    console.error(err)
    uiStore.error('Gagal mengirim data survey. Coba lagi.')
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}
</script>

<style scoped>
@reference "@/assets/css/main.css";

.survey-create-view {
  animation: fadeIn 0.5s ease-out;
}

.upload-placeholder {
  @apply border-2 border-dashed border-slate-200 rounded-3xl p-10 text-center cursor-pointer transition-all hover:border-orange-500 hover:bg-orange-50/50;
}

.upload-placeholder-secondary {
  @apply flex items-center gap-4 border border-slate-200 rounded-2xl p-4 cursor-pointer transition-all hover:border-blue-500 hover:bg-blue-50/50;
}

.icon-box {
  @apply w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-6 text-slate-300 group-hover:text-orange-500 group-hover:scale-110 transition-all;
}

.icon-box-sm {
  @apply w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-blue-500 group-hover:bg-white transition-all;
}

.preview-box {
  @apply relative overflow-hidden rounded-3xl border-4 border-white shadow-xl h-64;
}

.preview-img {
  @apply w-full h-full object-cover transition-transform duration-700 group-hover:scale-105;
}

.preview-overlay {
  @apply absolute inset-0 bg-gradient-to-t from-black/50 to-transparent;
}

.remove-btn {
  @apply absolute top-4 right-4 w-10 h-10 bg-black/20 hover:bg-red-500 backdrop-blur-md text-white rounded-full flex items-center justify-center transition-all;
}

.preview-info {
  @apply absolute bottom-4 left-4 right-4;
}

.info-tag {
  @apply flex items-center gap-2 bg-emerald-500 text-white px-3 py-1.5 rounded-xl text-[10px] font-black w-fit;
}

.submit-btn {
  @apply rounded-2xl h-14 font-black text-sm shadow-xl shadow-orange-500/20 active:scale-95 transition-all;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
