<template>
  <div class="survey-form-view p-4! lg:p-8!">
    <div class="mb-8! flex! items-center! justify-between!">
      <div>
        <BaseButton variant="ghost" icon="arrow-left" @click="$router.back()" class="mb-4!"
          >Kembali</BaseButton
        >
        <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Input Hasil <span class="text-orange-500!">Survey Lapangan</span>
        </h1>
        <p class="text-slate-500! mt-1! font-medium!">
          Isi detail teknis dan koordinat lokasi untuk aktivasi pelanggan.
        </p>
      </div>
    </div>

    <div class="grid! grid-cols-1! lg:grid-cols-3! gap-8!">
      <!-- Form Section -->
      <div class="lg:col-span-2! space-y-6!">
        <ContentCard
          variant="elevated"
          padding="large"
          class="border-0! shadow-xl! shadow-slate-200/40!"
        >
          <h3 class="text-lg! font-bold! text-slate-800! mb-6! flex! items-center! gap-2!">
            <font-awesome-icon icon="info-circle" class="text-orange-500!" />
            Informasi Teknis
          </h3>

          <div class="grid! grid-cols-1! md:grid-cols-2! gap-6!">
            <div class="form-group!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-2!"
                >Jarak dari Pipa Utama (Meter)</label
              >
              <div class="relative!">
                <input
                  v-model="formData.distance"
                  type="number"
                  class="w-full! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-5! py-3.5! focus:border-orange-500! focus:outline-none! font-bold!"
                  placeholder="Contoh: 15"
                />
                <span
                  class="absolute! right-5! top-1/2! -translate-y-1/2! text-slate-400! font-bold!"
                  >m</span
                >
              </div>
            </div>

            <div class="form-group!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-2!"
                >Estimasi Material</label
              >
              <select
                v-model="formData.materialPackage"
                class="w-full! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-5! py-3.5! focus:border-orange-500! focus:outline-none! font-bold!"
              >
                <option value="standard">Paket Standar (&lt; 20m)</option>
                <option value="extra">Paket Tambahan (&gt; 20m)</option>
                <option value="custom">Custom (Sesuai Kebutuhan)</option>
              </select>
            </div>
          </div>

          <div class="mt-6!">
            <label
              class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-2!"
              >Catatan Teknis Lapangan</label
            >
            <textarea
              v-model="formData.notes"
              rows="4"
              class="w-full! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-5! py-3.5! focus:border-orange-500! focus:outline-none! font-medium!"
              placeholder="Sebutkan kendala atau instruksi tambahan untuk teknisi pasang..."
            ></textarea>
          </div>
        </ContentCard>

        <!-- Map Selection -->
        <ContentCard
          variant="elevated"
          padding="none"
          class="border-0! shadow-xl! shadow-slate-200/40! overflow-hidden!"
        >
          <div class="p-6! border-b! border-slate-50! flex! items-center! justify-between!">
            <div class="flex! items-center! gap-3!">
              <div class="w-1.5! h-6! bg-orange-500! rounded-full!"></div>
              <h2 class="text-lg! font-bold! text-slate-800!">Titik Koordinat Pelanggan</h2>
            </div>
            <BaseButton variant="secondary" size="sm" icon="map-marker-alt" @click="detectGPS"
              >Gunakan GPS Saya</BaseButton
            >
          </div>
          <MapContainer v-model="formData.location" height="400px" :zoom="17" />
          <div class="p-4! bg-orange-50! flex! items-center! gap-4!">
            <div class="text-xs! font-bold! text-orange-700!">
              Lat: {{ formData.location.lat.toFixed(6) }}
            </div>
            <div class="text-xs! font-bold! text-orange-700!">
              Lng: {{ formData.location.lng.toFixed(6) }}
            </div>
          </div>
        </ContentCard>
      </div>

      <!-- Photo Section -->
      <div class="lg:col-span-1! space-y-6!">
        <ContentCard
          variant="elevated"
          padding="large"
          class="border-0! shadow-xl! shadow-slate-200/40!"
        >
          <h3 class="text-lg! font-bold! text-slate-800! mb-6! flex! items-center! gap-2!">
            <font-awesome-icon icon="camera" class="text-orange-500!" />
            Foto Lokasi
          </h3>

          <div class="photo-upload-area!">
            <div
              v-if="!photoPreview"
              class="border-2! border-dashed! border-slate-200! rounded-3xl! p-8! text-center! hover:border-orange-500! transition-colors! cursor-pointer!"
              @click="$refs.fileInput.click()"
            >
              <div
                class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-slate-400!"
              >
                <font-awesome-icon icon="cloud-upload-alt" size="2x" />
              </div>
              <p class="text-sm! font-bold! text-slate-600!">Klik untuk Ambil Foto</p>
              <p class="text-[10px]! text-slate-400! mt-1!">Pastikan lokasi terlihat jelas</p>
            </div>
            <div v-else class="relative! group!">
              <img
                :src="photoPreview"
                class="w-full! h-64! object-cover! rounded-3xl! border-4! border-white! shadow-lg!"
              />
              <button
                @click="photoPreview = null"
                class="absolute! top-4! right-4! w-10! h-10! bg-red-500! text-white! rounded-full! flex! items-center! justify-center! shadow-lg! opacity-0! group-hover:opacity-100! transition-opacity!"
              >
                <font-awesome-icon icon="times" />
              </button>
            </div>
            <input
              type="file"
              ref="fileInput"
              class="hidden!"
              accept="image/*"
              capture="environment"
              @change="handlePhotoUpload"
            />
          </div>

          <div class="mt-8! space-y-4!">
            <BaseButton
              variant="primary-gradient"
              block
              size="lg"
              class="rounded-2xl! h-14! font-black!"
              @click="submitSurvey"
              :loading="isSubmitting"
              >SIMPAN HASIL SURVEY</BaseButton
            >
            <p class="text-[10px]! text-center! text-slate-400! font-medium!">
              Data akan dikirim ke Admin untuk verifikasi pembayaran.
            </p>
          </div>
        </ContentCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import MapContainer from '@/presentations/components/ui/MapContainer.vue'
import locationUtils from '@/utils/location'
import cameraUtils from '@/utils/camera'
import ticketService from '@/services/ticket.service'

const router = useRouter()
const uiStore = useUiStore()
const isSubmitting = ref(false)
const photoPreview = ref(null)
const fileInput = ref(null)

const formData = reactive({
  distance: '',
  materialPackage: 'standard',
  notes: '',
  location: { lat: -6.1754, lng: 106.8272 },
  photo: null,
})

const detectGPS = async () => {
  try {
    uiStore.setLoading(true)
    const pos = await locationUtils.getCurrentLocation()
    formData.location = { lat: pos.lat, lng: pos.lng }
    uiStore.success('Lokasi GPS berhasil dikunci!')
  } catch (_err) {
    uiStore.error(_err.message)
  } finally {
    uiStore.setLoading(false)
  }
}

const handlePhotoUpload = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  try {
    uiStore.setLoading(true)
    // Tampilkan preview cepat
    photoPreview.value = URL.createObjectURL(file)

    // Kompres gambar di background
    const compressedBlob = await cameraUtils.compressImage(file)
    formData.photo = compressedBlob

    uiStore.success('Foto berhasil diproses & dikompres.')
  } catch {
    uiStore.error('Gagal memproses gambar.')
  } finally {
    uiStore.setLoading(false)
  }
}

const submitSurvey = async () => {
  if (!formData.distance) return uiStore.warn('Jarak pipa harus diisi!')
  if (!formData.photo) return uiStore.warn('Foto lokasi wajib dilampirkan!')

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    // Kirim ke Service
    await ticketService.submitSurvey('SRV-MOCK', formData)

    uiStore.success('Hasil survey berhasil dikirim ke Admin.')
    router.push('/dashboard')
  } catch {
    uiStore.error('Gagal mengirim data survey.')
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}
</script>

<style scoped>
.survey-form-view {
  animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
