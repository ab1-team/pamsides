<template>
  <div class="survey-form-view p-4! lg:p-8!">
      <div class="flex! flex-col! lg:flex-row! lg:items-center! justify-between! w-full! gap-6! mb-6! lg:mb-10!">
        <div class="flex! items-center! gap-5!">
          <button 
            @click="$router.back()" 
            class="w-12! h-12! rounded-full! bg-white! border! border-slate-200! text-slate-400! flex! items-center! justify-center! hover:bg-orange-500! hover:border-orange-500! hover:text-white! transition-all! shadow-sm! shrink-0!"
          >
            <font-awesome-icon icon="arrow-left" />
          </button>
          
          <div class="flex! items-center! gap-4!">
            <div>
              <h1 class="text-2xl! lg:text-3xl! font-black! text-slate-800! tracking-tight!">
                Konfirmasi <span class="text-orange-500!">Survey Lapangan</span>
              </h1>
              <p class="text-slate-500! mt-0.5! font-bold! text-[10px]! uppercase! tracking-widest!">Operational Phase • Section A-12</p>
            </div>
          </div>
        </div>

        <div class="flex! items-center! justify-between! lg:justify-start! gap-3! bg-white! p-2! rounded-2xl! border! border-slate-100! shadow-sm!">
          <div class="px-3! lg:px-4! py-1.5! lg:py-2! rounded-xl! bg-emerald-50! text-emerald-600! text-[10px]! lg:text-xs! font-black! whitespace-nowrap!">GPS ACTIVE</div>
          <div class="px-3! lg:px-4! py-1.5! lg:py-2! rounded-xl! bg-slate-50! text-slate-400! text-[10px]! lg:text-xs! font-black! whitespace-nowrap!">ID: #SRV-9921</div>
        </div>
      </div>

    <div class="grid! grid-cols-1! lg:grid-cols-3! gap-8!">
      <div class="lg:col-span-2! space-y-6!">
        <ContentCard
          variant="default"
          padding="large"
          class="border-0! shadow-sm!"
        >
          <div class="flex! items-center! gap-3! mb-8!">
            <div class="w-1.5! h-6! bg-indigo-500! rounded-full!"></div>
            <h3 class="text-lg! font-black! text-slate-800!">Informasi Teknis & Material</h3>
          </div>

          <div class="grid! grid-cols-1! md:grid-cols-2! gap-8!">
            <div class="form-group!">
              <label
                class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Jarak dari Pipa Utama</label
              >
              <div class="relative!">
                <input
                  v-model="formData.distance"
                  type="number"
                  class="w-full! h-11! px-4! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! text-slate-700! transition-all! duration-300! focus:outline-none! focus:border-blue-400! focus:bg-white! focus:ring-4! focus:ring-blue-500/5!"
                  placeholder="0.00"
                />
                <span
                  class="absolute! right-4! top-1/2! -translate-y-1/2! text-xs! font-bold! text-slate-400!"
                  >METER</span
                >
              </div>
            </div>

            <div class="form-group!">
              <label
                class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Estimasi Paket Material</label
              >
              <SelectSearch
                v-model="formData.materialPackage"
                :options="[
                  { value: 'standard', label: 'Paket Standar (< 20m)' },
                  { value: 'extra', label: 'Paket Tambahan (> 20m)' },
                  { value: 'custom', label: 'Custom (Sesuai Kebutuhan)' },
                ]"
                placeholder="Pilih Paket..."
                no-margin
              />
            </div>
          </div>

          <div class="mt-8!">
            <label
              class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
              >Catatan Teknis Lapangan</label
            >
            <textarea
              v-model="formData.notes"
              rows="4"
              class="w-full! px-4! py-3! bg-slate-50! border! border-slate-200! rounded-xl! text-sm! text-slate-700! transition-all! duration-300! focus:outline-none! focus:border-blue-400! focus:bg-white! focus:ring-4! focus:ring-blue-500/5!"
              placeholder="Deskripsikan kondisi lapangan, kendala teknis, atau instruksi khusus untuk tim instalasi..."
            ></textarea>
          </div>
        </ContentCard>

        <ContentCard
          variant="default"
          padding="none"
          class="border-0! overflow-hidden! shadow-sm!"
        >
          <div class="p-6! border-b! border-slate-50! flex! items-center! justify-between! bg-slate-50/30!">
            <div class="flex! items-center! gap-3!">
              <div class="w-1.5! h-6! bg-orange-500! rounded-full!"></div>
              <h2 class="text-lg! font-black! text-slate-800!">Titik Koordinat Pelanggan</h2>
            </div>
            <BaseButton
              variant="primary-gradient"
              size="sm"
              icon="crosshairs"
              @click="detectGPS"
              class="rounded-xl! font-black! text-[10px]! h-9! px-4!"
            >
              DETECT GPS
            </BaseButton>
          </div>
          <div class="relative! p-3! lg:p-4!">
            <MapContainer v-model="formData.location" class="h-[300px]! lg:h-[450px]!" :zoom="17" />
            
            <div class="absolute! bottom-4! lg:bottom-6! left-4! lg:left-6! right-4! lg:right-6! flex! justify-center! pointer-events-none!">
              <div class="bg-slate-900/90! backdrop-blur-xl! border! border-white/20! rounded-2xl! px-4! lg:px-6! py-2.5! lg:py-3! flex! flex-wrap! sm:flex-nowrap! items-center! justify-center! gap-4! lg:gap-6! shadow-2xl! pointer-events-auto!">
                <div class="flex! items-center! gap-2!">
                  <div class="w-1.5! h-1.5! bg-orange-500! rounded-full! animate-ping!"></div>
                  <span class="text-[9px]! lg:text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">Lat</span>
                  <span class="text-xs! font-mono! font-black! text-white!">
                    {{ formData.location.lat.toFixed(4) }}
                  </span>
                </div>
                <div class="hidden! sm:block! w-px! h-4! bg-white/10!"></div>
                <div class="flex! items-center! gap-2!">
                  <span class="text-[9px]! lg:text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">Lng</span>
                  <span class="text-xs! font-mono! font-black! text-white!">
                    {{ formData.location.lng.toFixed(4) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </ContentCard>
      </div>

      <div class="lg:col-span-1! space-y-6!">
        <ContentCard
          variant="default"
          padding="large"
          class="border-0! shadow-sm!"
        >
          <div class="flex! items-center! gap-3! mb-8!">
            <div class="w-1.5! h-6! bg-emerald-500! rounded-full!"></div>
            <h3 class="text-lg! font-black! text-slate-800!">Dokumentasi Lokasi</h3>
          </div>

          <div class="photo-upload-area!">
            <div
              v-if="!photoPreview"
              class="group! relative! border-2! border-dashed! border-slate-200! rounded-2xl! p-10! text-center! hover:border-orange-500! hover:bg-orange-50/30! transition-all! cursor-pointer!"
              @click="$refs.fileInput.click()"
            >
              <div
                class="w-20! h-20! bg-slate-50! group-hover:bg-orange-100! rounded-3xl! flex! items-center! justify-center! mx-auto! mb-6! text-slate-300! group-hover:text-orange-500! transition-all! group-hover:scale-110! group-hover:rotate-6!"
              >
                <font-awesome-icon icon="camera" size="2x" />
              </div>
              <h4 class="text-sm! font-black! text-slate-700! uppercase! tracking-tight!">Ambil Foto Lapangan</h4>
              <p class="text-[10px]! text-slate-400! font-bold! mt-2! leading-relaxed!">
                Ketuk untuk membuka kamera. Pastikan titik instalasi terlihat jelas.
              </p>
            </div>
            <div v-else class="relative! group! overflow-hidden! rounded-2xl! border-4! border-white! shadow-2xl!">
              <img
                :src="photoPreview"
                class="w-full! h-72! object-cover! group-hover:scale-105! transition-transform! duration-700!"
              />
              <div class="absolute! inset-0! bg-gradient-to-t! from-slate-900/60! via-transparent! to-transparent!"></div>
              
              <div class="absolute! top-0! left-0! w-full! h-1! bg-orange-500/50! shadow-[0_0_15px_rgba(249,115,22,0.8)]! animate-[scan_2s_infinite]!"></div>

              <button
                @click="photoPreview = null"
                class="absolute! top-4! right-4! w-10! h-10! bg-white/20! hover:bg-red-500! backdrop-blur-md! text-white! rounded-full! flex! items-center! justify-center! shadow-lg! transition-all! hover:scale-110!"
              >
                <font-awesome-icon icon="times" />
              </button>
              
              <div class="absolute! bottom-4! left-4! right-4!">
                <div class="flex! items-center! gap-2! bg-white/20! backdrop-blur-md! p-2! rounded-xl! border! border-white/20!">
                  <div class="w-8! h-8! rounded-lg! bg-emerald-500! flex! items-center! justify-center! text-white!">
                    <font-awesome-icon icon="check" />
                  </div>
                  <div class="flex-1!">
                    <div class="text-[10px]! font-black! text-white! uppercase!">Image Captured</div>
                    <div class="text-[8px]! font-bold! text-white/70!">READY FOR SUBMISSION</div>
                  </div>
                </div>
              </div>
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

          <div class="mt-10! space-y-4!">
            <BaseButton
              variant="primary-gradient"
              block
              size="lg"
              class="rounded-2xl! h-16! font-black! text-base! shadow-xl! shadow-orange-500/30! transition-all! active:scale-95!"
              @click="submitSurvey"
              :loading="isSubmitting"
            >
              <div class="flex! items-center! justify-center! gap-3!">
                <span>KIRIM HASIL SURVEY</span>
                <font-awesome-icon icon="paper-plane" class="text-sm! opacity-80!" />
              </div>
            </BaseButton>
            <div class="flex! items-center! justify-center! gap-2! text-[10px]! font-bold! text-slate-400! uppercase! tracking-widest!">
               <font-awesome-icon icon="shield-alt" class="text-emerald-500!" />
               Secure Transmission Active
            </div>
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
import SelectSearch from '@/presentations/components/SelectSearch.vue'
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
    photoPreview.value = URL.createObjectURL(file)

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
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scan {
  0% { top: 0%; opacity: 1; }
  50% { top: 100%; opacity: 0.5; }
  100% { top: 0%; opacity: 1; }
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>
