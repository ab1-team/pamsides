<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="show"
        class="fixed inset-0! z-50 flex items-center justify-center p-2! sm:p-4! md:p-6!"
      >
        <div class="absolute inset-0! bg-slate-900/60! backdrop-blur-xs!" @click="close"></div>

        <div
          class="relative w-full! bg-white rounded-2xl! shadow-xl! border border-slate-200 flex flex-col overflow-hidden animate-slide-up max-h-[95vh]! md:max-h-[90vh]!"
        >
          <!-- Header -->
          <div
            class="flex items-center! justify-between! px-4! sm:px-6! py-3! sm:py-4! border-b! border-slate-200! bg-white!"
          >
            <div class="flex items-center gap-2! sm:gap-3!">
              <div
                class="w-8! h-8! sm:w-10! sm:h-10! rounded-full! bg-orange-600! text-white! flex items-center! justify-center!"
              >
                <font-awesome-icon icon="clipboard-check" class="text-sm!" />
              </div>
              <div>
                <h2 class="text-base! sm:text-lg! font-semibold! text-slate-800 leading-tight">
                  Detail Hasil Survey
                </h2>
                <p class="text-[10px]! sm:text-xs! text-slate-500! font-medium!">
                  {{ survey?.ticket?.applicant_name || '-' }}
                </p>
              </div>
            </div>
            <button
              @click="close"
              class="w-8! h-8! sm:w-9! sm:h-9! hover:bg-slate-100! flex items-center! justify-center! text-slate-400! hover:text-slate-600! transition-all active:scale-95 rounded-md!"
            >
              <font-awesome-icon icon="times" />
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-auto p-3! sm:p-4! md:p-6!">
            <!-- Mobile: Stacked Layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3! sm:gap-4!">
              <!-- Foto Lokasi - First on Mobile -->
              <div class="md:col-span-1 order-first md:order-last">
                <div class="bg-slate-50! rounded-xl! p-3! sm:p-4!">
                  <p
                    class="text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-2!"
                  >
                    Foto Lokasi Survey
                  </p>
                  <div
                    v-if="survey?.photo_url"
                    class="relative! rounded-xl! overflow-hidden! border-4! border-white! shadow-xl!"
                  >
                    <img
                      :src="photoUrl"
                      alt="Foto Survey"
                      class="w-full! h-48! sm:h-64! md:h-auto! object-cover!"
                      @error="handleImageError"
                    />
                  </div>
                  <div
                    v-else
                    class="bg-slate-100! border-2! border-dashed! border-slate-200! rounded-xl! p-6! sm:p-8! text-center!"
                  >
                    <font-awesome-icon
                      icon="image"
                      class="text-2xl! sm:text-3xl! text-slate-300! mb-2!"
                    />
                    <p class="text-xs! sm:text-sm! text-slate-400! font-medium!">Tidak ada foto</p>
                  </div>
                </div>
              </div>

              <!-- Info Section -->
              <div class="md:col-span-2 space-y-3! sm:space-y-4!">
                <!-- Info Pemohon -->
                <div class="bg-slate-50! rounded-xl! p-3! sm:p-4! space-y-2! sm:space-y-3!">
                  <h3
                    class="text-[10px]! sm:text-xs! font-bold! text-slate-800! uppercase! tracking-wider! mb-2!"
                  >
                    Informasi Pemohon
                  </h3>
                  <div class="grid grid-cols-2 gap-2! sm:gap-3!">
                    <div>
                      <p
                        class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-0.5! sm:mb-1!"
                      >
                        Nama Lengkap
                      </p>
                      <p class="text-xs! sm:text-sm! font-semibold! text-slate-800!">
                        {{ survey?.ticket?.applicant_name || '-' }}
                      </p>
                    </div>
                    <div>
                      <p
                        class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-0.5! sm:mb-1!"
                      >
                        NIK
                      </p>
                      <p class="text-xs! sm:text-sm! font-semibold! text-slate-800!">
                        {{ survey?.ticket?.nik || '-' }}
                      </p>
                    </div>
                    <div class="col-span-2">
                      <p
                        class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-0.5! sm:mb-1!"
                      >
                        Alamat
                      </p>
                      <p class="text-xs! sm:text-sm! font-semibold! text-slate-800!">
                        {{ survey?.ticket?.address || '-' }}
                      </p>
                    </div>
                    <div>
                      <p
                        class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-0.5! sm:mb-1!"
                      >
                        No. Telepon
                      </p>
                      <p class="text-xs! sm:text-sm! font-semibold! text-slate-800!">
                        {{ survey?.ticket?.phone || '-' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Hasil Survey -->
                <div class="bg-slate-50! rounded-xl! p-3! sm:p-4! space-y-2! sm:space-y-3!">
                  <h3
                    class="text-[10px]! sm:text-xs! font-bold! text-slate-800! uppercase! tracking-wider! mb-2!"
                  >
                    Hasil Survey Lapangan
                  </h3>
                  <div class="grid grid-cols-2 gap-2! sm:gap-3!">
                    <div>
                      <p
                        class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-0.5! sm:mb-1!"
                      >
                        Jarak ke Pipa Utama
                      </p>
                      <p class="text-xl! sm:text-2xl! font-black! text-cyan-600!">
                        {{ survey?.distance_to_pipe_m || 0 }}
                        <span class="text-xs! sm:text-sm! text-slate-400! font-medium!">meter</span>
                      </p>
                    </div>
                    <div>
                      <p
                        class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-0.5! sm:mb-1!"
                      >
                        Surveyor
                      </p>
                      <p class="text-xs! sm:text-sm! font-semibold! text-slate-800!">
                        {{ survey?.surveyor?.name || '-' }}
                      </p>
                    </div>
                    <div class="col-span-2">
                      <p
                        class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-0.5! sm:mb-1!"
                      >
                        Tanggal Survey
                      </p>
                      <p class="text-xs! sm:text-sm! font-semibold! text-slate-800!">
                        {{ formatDate(survey?.surveyed_at) }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Catatan Material -->
                <div class="bg-slate-50! rounded-xl! p-3! sm:p-4!">
                  <p
                    class="text-[9px]! sm:text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider! mb-1! sm:mb-2!"
                  >
                    Catatan Material & Teknis
                  </p>
                  <p class="text-xs! sm:text-sm! text-slate-700! font-medium! leading-relaxed!">
                    {{ survey?.material_notes || 'Tidak ada catatan' }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue'
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

const emit = defineEmits(['close', 'approve', 'reject'])

const photoUrl = computed(() => {
  const v = props.survey?.photo_url
  if (!v) return ''
  if (/^https?:\/\//i.test(v)) return v
  return storageUrl(`storage/survey-photos/${v}`)
})

const close = () => {
  emit('close')
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch (err) {
    console.error('Error formatting date:', err)
    return dateStr
  }
}

const handleImageError = (e) => {
  console.error('Failed to load image:', e.target.src)
  e.target.style.display = 'none'
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
