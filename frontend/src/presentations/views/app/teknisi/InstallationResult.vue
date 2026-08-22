<template>
  <div class="installation-result-view">
    <div class="mb-8! flex! items-center! justify-between!">
      <div>
        <BaseButton variant="ghost" icon="arrow-left" @click="$router.back()" class="mb-4!"
          >Kembali</BaseButton
        >
        <h1 class="text-3xl! font-extrabold! text-slate-800! tracking-tight!">
          Laporan <span class="text-cyan-500!">Hasil Instalasi</span>
        </h1>
        <p class="text-slate-500! mt-1! font-medium!">
          Upload foto dan data hasil pemasangan meter air pelanggan.
        </p>
      </div>
    </div>

    <div class="max-w-5xl! mx-auto!">
      <ContentCard
        variant="elevated"
        padding="large"
        class="border-0! shadow-xl! shadow-slate-200/40!"
      >
        <div class="flex! items-center! gap-4! mb-8! p-4! bg-cyan-50! rounded-2xl!">
          <div
            class="w-12! h-12! bg-cyan-500! text-white! rounded-xl! flex! items-center! justify-center! shadow-lg!"
          >
            <font-awesome-icon icon="user" />
          </div>
          <div>
            <h4 class="text-lg! font-bold! text-slate-800!">{{ ticketData.applicant_name }}</h4>
            <p class="text-xs! text-slate-500! font-bold!">
              Tiket #{{ ticketData.id }} • {{ ticketData.address }}
            </p>
          </div>
          <div class="ml-auto! text-right!">
            <p class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!">
              Paket
            </p>
            <p class="text-base! font-black! text-slate-700!">
              {{ ticketData.package_name }}
            </p>
          </div>
        </div>

        <div class="grid! grid-cols-1! lg:grid-cols-2! gap-8!">
          <div class="space-y-6!">
            <div class="form-group!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Nomor Meteran</label
              >
              <input
                v-model="formData.meter_number"
                type="text"
                class="w-full! text-lg! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-5! py-4! focus:border-cyan-500! focus:outline-hidden! font-bold! text-slate-700!"
                placeholder="Contoh: MTR-2024-001234"
              />
              <p class="mt-2! text-xs! text-slate-400! font-medium!">
                Serial number yang tertera di meteran
              </p>
            </div>

            <div class="form-group!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Angka Meteran Awal</label
              >
              <div class="relative!">
                <input
                  v-model="formData.initial_meter_value"
                  type="number"
                  class="w-full! text-2xl! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-5! py-4! focus:border-cyan-500! focus:outline-hidden! font-black! text-cyan-600!"
                  placeholder="0"
                />
                <span
                  class="absolute! right-5! top-1/2! -translate-y-1/2! text-slate-400! font-black!"
                  >m³</span
                >
              </div>
            </div>

            <div class="form-group!">
              <label
                class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                >Catatan Instalasi (Opsional)</label
              >
              <textarea
                v-model="formData.notes"
                rows="4"
                class="w-full! bg-slate-50! border-2! border-slate-100! rounded-2xl! px-5! py-4! focus:border-cyan-500! focus:outline-hidden! text-sm! text-slate-700! font-medium!"
                placeholder="Contoh: Pipa tambahan 3 meter, dll..."
              ></textarea>
            </div>
          </div>

          <div class="space-y-6!">
            <label
              class="block! text-xs! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
              >Foto Hasil Instalasi</label
            >
            <div class="relative!">
              <div
                v-if="!photoPreview"
                class="h-80! border-4! border-dashed! border-slate-100! rounded-3xl! flex! flex-col! items-center! justify-center! bg-slate-50! cursor-pointer! hover:bg-slate-100! transition-all!"
                @click="$refs.fileInput.click()"
              >
                <div
                  class="w-20! h-20! bg-white! rounded-full! flex! items-center! justify-center! shadow-xs! mb-4! text-cyan-500!"
                >
                  <font-awesome-icon icon="camera" size="2x" />
                </div>
                <span class="text-sm! font-bold! text-slate-500! mb-2!">Ambil Foto Pemasangan</span>
                <span class="text-xs! text-slate-400! font-medium!"
                  >Meteran & pipa harus terlihat jelas</span
                >
              </div>
              <div v-else class="relative! group!">
                <img
                  :src="photoPreview"
                  class="w-full! h-80! object-cover! rounded-3xl! shadow-lg! border-4! border-white!"
                />
                <button
                  @click="removePhoto"
                  class="absolute! top-4! right-4! w-12! h-12! bg-red-500! text-white! rounded-full! flex! items-center! justify-center! shadow-lg! hover:bg-red-600! transition-all!"
                >
                  <font-awesome-icon icon="times" />
                </button>
                <div
                  class="absolute! bottom-4! left-4! bg-emerald-500! text-white! px-4! py-2! rounded-xl! text-xs! font-black! flex! items-center! gap-2!"
                >
                  <font-awesome-icon icon="check" />
                  FOTO SIAP
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

            <div
              class="bg-amber-50! border! border-amber-200! rounded-2xl! p-4! flex! items-start! gap-3!"
            >
              <div class="text-amber-500! mt-0.5!">
                <font-awesome-icon icon="info-circle" />
              </div>
              <div>
                <h5 class="text-xs! font-black! text-amber-800! uppercase! mb-1!">Panduan Foto</h5>
                <ul class="text-xs! text-amber-700! space-y-1! font-medium!">
                  <li>• Nomor meteran terlihat jelas</li>
                  <li>• Sambungan pipa tampak utuh</li>
                  <li>• Pencahayaan cukup terang</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-12! pt-8! border-t! border-slate-100!">
          <BaseButton
            variant="primary-gradient"
            block
            size="lg"
            class="rounded-3xl! h-16! font-black! text-lg! shadow-xl! shadow-cyan-200!"
            @click="submitInstallation"
            :loading="isSubmitting"
            :disabled="!isFormValid"
          >
            <font-awesome-icon icon="check-circle" class="mr-2!" />
            SIMPAN HASIL INSTALASI
          </BaseButton>
          <p class="text-center! text-xs! text-slate-400! mt-4! font-medium!">
            Data akan diverifikasi oleh Admin untuk aktivasi pelanggan.
          </p>
        </div>
      </ContentCard>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import cameraUtils from '@/utils/camera'
import ticketService from '@/services/ticket.service'
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
})

const router = useRouter()
const route = useRoute()
const uiStore = useUiStore()
const isSubmitting = ref(false)
const photoPreview = ref(null)
const fileInput = ref(null)

const ticketData = ref({
  id: route.params.id || '',
  applicant_name: 'Loading...',
  address: '',
  package_name: '',
})

const formData = reactive({
  meter_number: '',
  initial_meter_value: 0,
  notes: '',
  photo: null,
})

const isFormValid = computed(() => {
  return formData.meter_number && formData.initial_meter_value >= 0 && formData.photo
})

onMounted(async () => {
  await fetchTicketData()
})

const fetchTicketData = async () => {
  try {
    const response = await ticketService.getTicket(route.params.id)
    if (response.success) {
      const ticket = response.data
      ticketData.value = {
        id: ticket.id,
        applicant_name: ticket.applicant_name,
        address: ticket.address,
        package_name: ticket.package?.name || 'Paket Standar',
      }
    }
  } catch (error) {
    console.error('Failed to fetch ticket:', error)
    Toast.fire({ icon: 'error', title: 'Gagal memuat data tiket' })
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
    Toast.fire({ icon: 'success', title: 'Foto berhasil diproses' })
  } catch {
    Toast.fire({ icon: 'error', title: 'Gagal memproses foto' })
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

const submitInstallation = async () => {
  if (!isFormValid.value) {
    return Toast.fire({ icon: 'warning', title: 'Lengkapi semua data wajib!' })
  }

  try {
    isSubmitting.value = true
    uiStore.setLoading(true)

    const submitData = new FormData()
    submitData.append('meter_number', formData.meter_number)
    submitData.append('initial_meter_value', formData.initial_meter_value)
    submitData.append('notes', formData.notes || '')
    submitData.append('photo', formData.photo)

    await ticketService.submitInstallation(route.params.id, submitData)

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Hasil instalasi berhasil disimpan.',
      confirmButtonColor: '#06b6d4',
    })

    router.push('/app')
  } catch (error) {
    console.error('Submit error:', error)
    Toast.fire({ icon: 'error', title: 'Gagal menyimpan data instalasi' })
  } finally {
    isSubmitting.value = false
    uiStore.setLoading(false)
  }
}
</script>

<style scoped>
.installation-result-view {
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
