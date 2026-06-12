<template>
  <div class="trouble-report-form">
    <div class="max-w-4xl! mx-auto!">
      <div class="mb-8!">
        <BaseButton variant="ghost" icon="arrow-left" @click="$router.back()" class="mb-4!"
          >Kembali</BaseButton
        >
        <h1 class="text-2xl! lg:text-3xl! font-black! text-slate-800! tracking-tight!">
          Form Laporan Gangguan
        </h1>
        <p class="text-slate-500! mt-1! font-medium!">
          Laporkan kendala air Anda dengan detail untuk penanganan yang lebih cepat.
        </p>
      </div>

      <ContentCard
        variant="elevated"
        padding="large"
        class="border-0! shadow-xl! shadow-slate-200/40!"
      >
        <div class="space-y-8!">
          <div class="form-group!">
            <label
              class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
              >Jenis Gangguan</label
            >
            <select
              v-model="formData.trouble_type"
              class="w-full! h-12! px-4! bg-slate-50! border-2! border-slate-100! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:border-indigo-500! focus:bg-white! transition-all! font-bold!"
            >
              <option value="">-- Pilih Jenis Gangguan --</option>
              <option value="air_mati">Air Mati Total</option>
              <option value="debit_kecil">Debit Air Kecil</option>
              <option value="pipa_bocor">Pipa Bocor</option>
              <option value="meter_rusak">Meteran Rusak</option>
              <option value="air_keruh">Air Keruh/Berbau</option>
              <option value="lainnya">Lainnya</option>
            </select>
          </div>

          <div class="form-group!">
            <label
              class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
              >Deskripsi Masalah</label
            >
            <textarea
              v-model="formData.description"
              rows="5"
              class="w-full! px-4! py-3! bg-slate-50! border-2! border-slate-100! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:border-indigo-500! focus:bg-white! transition-all! font-medium!"
              placeholder="Jelaskan masalah yang Anda alami secara detail..."
            ></textarea>
          </div>

          <div class="form-group!">
            <label
              class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
              >Foto/Video Gangguan (Opsional)</label
            >
            <div class="space-y-4!">
              <div
                v-if="!photoPreview"
                class="border-4! border-dashed! border-slate-100! rounded-3xl! p-10! text-center! cursor-pointer! hover:border-indigo-300! hover:bg-indigo-50/30! transition-all!"
                @click="$refs.fileInput.click()"
              >
                <div
                  class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! text-slate-300!"
                >
                  <font-awesome-icon icon="camera" size="2x" />
                </div>
                <h4 class="text-sm! font-black! text-slate-700! mb-2!">Upload Foto Gangguan</h4>
                <p class="text-xs! text-slate-400! font-medium!">
                  Foto akan membantu teknisi mempersiapkan peralatan yang tepat
                </p>
              </div>

              <div v-else class="relative! group!">
                <img
                  :src="photoPreview"
                  class="w-full! h-64! object-cover! rounded-3xl! shadow-lg! border-4! border-white!"
                />
                <button
                  @click="removePhoto"
                  class="absolute! top-4! right-4! w-10! h-10! bg-red-500! text-white! rounded-full! flex! items-center! justify-center! shadow-lg! hover:bg-red-600! transition-all!"
                >
                  <font-awesome-icon icon="times" />
                </button>
                <div
                  class="absolute! bottom-4! left-4! bg-emerald-500! text-white! px-3! py-1.5! rounded-xl! text-xs! font-black! flex! items-center! gap-2!"
                >
                  <font-awesome-icon icon="check" />
                  FOTO SIAP
                </div>
              </div>

              <input
                type="file"
                ref="fileInput"
                class="hidden!"
                accept="image/*,video/*"
                capture="environment"
                @change="handleFileUpload"
              />
            </div>
          </div>

          <div class="form-group!">
            <label
              class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
              >Nomor Telepon yang Bisa Dihubungi</label
            >
            <input
              v-model="formData.contact_phone"
              type="tel"
              class="w-full! h-12! px-4! bg-slate-50! border-2! border-slate-100! rounded-xl! text-sm! text-slate-700! focus:outline-none! focus:border-indigo-500! focus:bg-white! transition-all! font-bold!"
              placeholder="Contoh: 081234567890"
            />
          </div>

          <div
            class="bg-amber-50! border! border-amber-200! rounded-2xl! p-5! flex! items-start! gap-4!"
          >
            <div class="text-amber-500! text-xl! mt-1!">
              <font-awesome-icon icon="info-circle" />
            </div>
            <div>
              <h5 class="text-sm! font-black! text-amber-800! mb-2!">Catatan Penting</h5>
              <ul class="text-xs! text-amber-700! space-y-1! font-medium!">
                <li>• Laporan akan ditindaklanjuti maksimal 1x24 jam</li>
                <li>• Untuk gangguan darurat, hubungi hotline: (0274) 889-123</li>
                <li>• Pastikan nomor telepon yang Anda berikan aktif</li>
              </ul>
            </div>
          </div>

          <div class="pt-6! border-t! border-slate-100!">
            <BaseButton
              variant="primary-gradient"
              block
              size="lg"
              class="rounded-3xl! h-14! font-black! text-base! shadow-xl! shadow-indigo-200!"
              @click="submitReport"
              :loading="isSubmitting"
              :disabled="!isFormValid"
            >
              <font-awesome-icon icon="paper-plane" class="mr-2!" />
              KIRIM LAPORAN
            </BaseButton>
            <p class="text-center! text-xs! text-slate-400! mt-4! font-medium!">
              Tim teknisi kami akan segera menindaklanjuti laporan Anda.
            </p>
          </div>
        </div>
      </ContentCard>

      <div class="mt-8! grid! grid-cols-1! md:grid-cols-2! gap-6!">
        <ContentCard
          variant="elevated"
          padding="normal"
          class="border-0! shadow-sm! cursor-pointer! hover:shadow-lg! transition-all!"
          @click="openWhatsApp"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-12! h-12! bg-green-500! text-white! rounded-xl! flex! items-center! justify-center! shadow-lg!"
            >
              <font-awesome-icon :icon="['fab', 'whatsapp']" size="lg" />
            </div>
            <div>
              <h4 class="text-sm! font-bold! text-slate-800!">WhatsApp Center</h4>
              <p class="text-xs! text-slate-500! font-medium!">Respon lebih cepat via chat</p>
            </div>
            <div class="ml-auto! text-slate-300!">
              <font-awesome-icon icon="chevron-right" />
            </div>
          </div>
        </ContentCard>

        <ContentCard
          variant="elevated"
          padding="normal"
          class="border-0! shadow-sm! cursor-pointer! hover:shadow-lg! transition-all!"
          @click="makeCall"
        >
          <div class="flex! items-center! gap-4!">
            <div
              class="w-12! h-12! bg-red-500! text-white! rounded-xl! flex! items-center! justify-center! shadow-lg!"
            >
              <font-awesome-icon icon="phone" size="lg" />
            </div>
            <div>
              <h4 class="text-sm! font-bold! text-slate-800!">Hotline Darurat</h4>
              <p class="text-xs! text-slate-500! font-medium!">Untuk gangguan kritis</p>
            </div>
            <div class="ml-auto! text-slate-300!">
              <font-awesome-icon icon="chevron-right" />
            </div>
          </div>
        </ContentCard>
      </div>
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
import troubleReportService from '@/services/troubleReport.service'
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
})

const router = useRouter()
const uiStore = useUiStore()
const isSubmitting = ref(false)
const photoPreview = ref(null)
const fileInput = ref(null)

const formData = reactive({
  trouble_type: '',
  description: '',
  contact_phone: '',
  photo: null,
})

const isFormValid = computed(() => {
  return formData.trouble_type && formData.description && formData.contact_phone
})

const handleFileUpload = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  if (file.size > 5 * 1024 * 1024) {
    Toast.fire({ icon: 'error', title: 'Ukuran file maksimal 5MB' })
    return
  }

  try {
    uiStore.setLoading(true)
    photoPreview.value = URL.createObjectURL(file)

    if (file.type.startsWith('image/')) {
      const compressedBlob = await cameraUtils.compressImage(file)
      formData.photo = compressedBlob
    } else {
      formData.photo = file
    }

    Toast.fire({ icon: 'success', title: 'File berhasil diproses' })
  } catch {
    Toast.fire({ icon: 'error', title: 'Gagal memproses file' })
  } finally {
    uiStore.setLoading(false)
  }
}

const removePhoto = () => {
  photoPreview.value = null
  formData.photo = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const submitReport = async () => {
  if (!isFormValid.value) {
    return Toast.fire({ icon: 'warning', title: 'Lengkapi semua data wajib!' })
  }

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    const submitData = new FormData()
    submitData.append('trouble_type', formData.trouble_type)
    submitData.append('description', formData.description)
    submitData.append('contact_phone', formData.contact_phone)
    if (formData.photo) {
      submitData.append('photo', formData.photo)
    }

    await troubleReportService.submitReport(submitData)

    await Swal.fire({
      icon: 'success',
      title: 'Laporan Terkirim!',
      text: 'Tim teknisi kami akan segera menindaklanjuti laporan Anda.',
      confirmButtonColor: '#6366f1',
    })

    router.push('/app')
  } catch (error) {
    console.error('Submit error:', error)
    Toast.fire({ icon: 'error', title: 'Gagal mengirim laporan' })
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}

const openWhatsApp = () => {
  window.open('https://wa.me/6281234567890', '_blank')
}

const makeCall = () => {
  window.location.href = 'tel:0274889123'
}
</script>

<style scoped>
.trouble-report-form {
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
