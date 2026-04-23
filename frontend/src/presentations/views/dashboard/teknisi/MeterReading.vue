<template>
  <div class="meter-reading-view p-4! lg:p-8!">
    <div class="mb-8! flex! items-center! justify-between!">
      <div>
        <BaseButton variant="ghost" icon="arrow-left" @click="$router.back()" class="mb-4!"
          >Kembali</BaseButton
        >
        <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Pencatatan <span class="text-blue-500!">Meter Air</span>
        </h1>
        <p class="text-slate-500! mt-1! font-medium!">
          Input angka meteran terbaru untuk perhitungan tagihan bulan ini.
        </p>
      </div>
    </div>

    <div class="max-w-4xl! mx-auto!">
      <ContentCard
        variant="elevated"
        padding="large"
        class="border-0! shadow-xl! shadow-slate-200/40!"
      >
        <div class="flex! items-center! gap-4! mb-8! p-4! bg-blue-50! rounded-2xl!">
          <div
            class="w-12! h-12! bg-blue-500! text-white! rounded-xl! flex! items-center! justify-center! shadow-lg!"
          >
            <font-awesome-icon icon="user" />
          </div>
          <div>
            <h4 class="text-lg! font-bold! text-slate-800!">Budi Darmawan</h4>
            <p class="text-xs! text-slate-500! font-bold!">ID: #MA-882109 • Southern Spring</p>
          </div>
          <div class="ml-auto! text-right!">
            <p class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">
              Meter Lalu
            </p>
            <p class="text-xl! font-black! text-slate-700!">
              1,240 <span class="text-xs!">m³</span>
            </p>
          </div>
        </div>

        <div class="grid! grid-cols-1! md:grid-cols-2! gap-8!">
          <!-- Input Section -->
          <div class="space-y-6!">
            <div class="form-group!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Angka Meteran Baru</label
              >
              <div class="relative!">
                <input
                  v-model="formData.currentReading"
                  type="number"
                  class="w-full! text-3xl! bg-slate-50! border-4! border-slate-100! rounded-3xl! px-6! py-5! focus:border-blue-500! focus:outline-none! font-black! text-blue-600!"
                  placeholder="0000"
                />
                <span
                  class="absolute! right-6! top-1/2! -translate-y-1/2! text-slate-400! font-black!"
                  >m³</span
                >
              </div>
              <p
                v-if="usage > 0"
                class="mt-3! text-sm! font-bold! text-emerald-600! flex! items-center! gap-2!"
              >
                <font-awesome-icon icon="chart-line" />
                Estimasi Pemakaian: {{ usage }} m³
              </p>
            </div>

            <div class="form-group!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Kondisi Meteran</label
              >
              <div class="flex! gap-3!">
                <button
                  v-for="opt in ['Bagus', 'Rusak', 'Buram']"
                  :key="opt"
                  @click="formData.condition = opt"
                  :class="
                    formData.condition === opt
                      ? 'bg-blue-600! text-white! border-blue-600!'
                      : 'bg-white! text-slate-500! border-slate-200!'
                  "
                  class="flex-1! py-3! rounded-xl! border-2! font-bold! transition-all!"
                >
                  {{ opt }}
                </button>
              </div>
            </div>
          </div>

          <!-- Camera Section -->
          <div class="space-y-6!">
            <label
              class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
              >Bukti Foto Meteran</label
            >
            <div class="relative!">
              <div
                v-if="!photoPreview"
                class="h-64! border-4! border-dashed! border-slate-100! rounded-3xl! flex! flex-col! items-center! justify-center! bg-slate-50! cursor-pointer! hover:bg-slate-100! transition-all!"
                @click="$refs.fileInput.click()"
              >
                <div
                  class="w-16! h-16! bg-white! rounded-full! flex! items-center! justify-center! shadow-sm! mb-3! text-blue-500!"
                >
                  <font-awesome-icon icon="camera" size="2x" />
                </div>
                <span class="text-sm! font-bold! text-slate-500!">Ambil Foto Meteran</span>
              </div>
              <div v-else class="relative! group!">
                <img
                  :src="photoPreview"
                  class="w-full! h-64! object-cover! rounded-3xl! shadow-lg! border-4! border-white!"
                />
                <button
                  @click="photoPreview = null"
                  class="absolute! top-4! right-4! w-10! h-10! bg-red-500! text-white! rounded-full! flex! items-center! justify-center! shadow-lg!"
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
          </div>
        </div>

        <div class="mt-12! pt-8! border-t! border-slate-100!">
          <BaseButton
            variant="primary-gradient"
            block
            size="lg"
            class="rounded-3xl! h-16! font-black! text-lg! shadow-xl! shadow-blue-200!"
            @click="submitReading"
            :loading="isSubmitting"
          >
            SIMPAN PENCATATAN
          </BaseButton>
          <p class="text-center! text-xs! text-slate-400! mt-4! font-medium!">
            Pastikan angka meteran terlihat jelas di foto untuk validasi Admin.
          </p>
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import cameraUtils from '@/utils/camera'
import ticketService from '@/services/ticket.service'

const router = useRouter()
const uiStore = useUiStore()
const isSubmitting = ref(false)
const photoPreview = ref(null)
const fileInput = ref(null)

const lastReading = 1240

const formData = reactive({
  currentReading: '',
  condition: 'Bagus',
  photo: null,
})

const usage = computed(() => {
  if (!formData.currentReading) return 0
  const diff = formData.currentReading - lastReading
  return diff > 0 ? diff : 0
})

const handlePhotoUpload = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  try {
    uiStore.setLoading(true)
    photoPreview.value = URL.createObjectURL(file)
    const compressedBlob = await cameraUtils.compressImage(file)
    formData.photo = compressedBlob
    uiStore.success('Foto meteran berhasil dikompres.')
  } catch {
    uiStore.error('Gagal memproses foto.')
  } finally {
    uiStore.setLoading(false)
  }
}

const submitReading = async () => {
  if (!formData.currentReading) return uiStore.warn('Angka meteran wajib diisi!')
  if (formData.currentReading < lastReading)
    return uiStore.error('Angka meteran tidak boleh lebih kecil dari bulan lalu!')
  if (!formData.photo) return uiStore.warn('Foto meteran wajib dilampirkan!')

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    // Kirim ke Service
    await ticketService.submitInstallation('METER-MOCK', formData)

    uiStore.success('Pencatatan meteran berhasil disimpan.')
    router.push('/dashboard')
  } catch {
    uiStore.error('Gagal menyimpan pencatatan.')
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}
</script>

<style scoped>
.meter-reading-view {
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
