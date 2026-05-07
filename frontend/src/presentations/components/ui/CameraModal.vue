<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click="closeModal">
      <div class="modal-container max-w-xl!" @click.stop>
        <div class="modal-header">
          <div class="flex! items-center! gap-3!">
            <div class="w-10! h-10! rounded-xl! bg-orange-50! flex! items-center! justify-center! text-orange-600!">
              <font-awesome-icon icon="camera" class="text-xl!" />
            </div>
            <div>
              <h2 class="text-lg! font-black! text-slate-800! leading-none!">Ambil Foto Lokasi</h2>
              <p class="text-[10px]! uppercase! tracking-widest! text-slate-400! mt-0.5!">Live Preview Kamera</p>
            </div>
          </div>
          <button @click="closeModal" class="close-btn">
            <font-awesome-icon icon="times" />
          </button>
        </div>

        <div class="modal-body p-6!">
          <div class="relative! aspect-[4/3]! bg-black! rounded-2xl! overflow-hidden! shadow-inner!">
            <video 
              ref="videoRef" 
              autoplay 
              playsinline 
              class="w-full! h-full! object-cover!"
            ></video>
            
            <!-- Brackets for visual flair -->
            <div class="absolute! inset-8! border-2! border-white/20! pointer-events-none!">
              <div class="absolute! -top-1! -left-1! w-6! h-6! border-t-4! border-l-4! border-orange-500!"></div>
              <div class="absolute! -top-1! -right-1! w-6! h-6! border-t-4! border-r-4! border-orange-500!"></div>
              <div class="absolute! -bottom-1! -left-1! w-6! h-6! border-b-4! border-l-4! border-orange-500!"></div>
              <div class="absolute! -bottom-1! -right-1! w-6! h-6! border-b-4! border-r-4! border-orange-500!"></div>
            </div>

            <div v-if="loading" class="absolute! inset-0! flex! items-center! justify-center! bg-slate-900/80! text-white!">
              <font-awesome-icon icon="spinner" spin class="text-3xl! mb-3!" />
              <p class="text-sm! font-bold!">Menghubungkan Kamera...</p>
            </div>

            <!-- Switch Camera Button -->
            <button 
              v-if="!loading"
              @click="toggleCamera" 
              class="absolute! top-4! right-4! w-12! h-12! rounded-full! bg-white/20! backdrop-blur-md! text-white! flex! items-center! justify-center! hover:bg-white/40! transition-all! z-20!"
            >
              <font-awesome-icon icon="sync-alt" class="text-xl!" />
            </button>
          </div>

          <div class="flex! gap-4! mt-8!">
            <BaseButton
              variant="secondary-gradient"
              class="flex-1! py-4! rounded-xl! font-black! text-xs! uppercase! tracking-widest!"
              @click="closeModal"
            >
              Batal
            </BaseButton>
            <BaseButton
              variant="primary-gradient"
              class="flex-[2]! py-4! rounded-xl! font-black! text-xs! uppercase! tracking-widest! shadow-lg! shadow-orange-200!"
              @click="capturePhoto"
              :disabled="loading"
            >
              <font-awesome-icon icon="camera" class="mr-2!" />
              Tangkap Foto
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'capture'])

const videoRef = ref(null)
const stream = ref(null)
const loading = ref(true)
const facingMode = ref('environment')

watch(() => props.show, (newVal) => {
  if (newVal) {
    startCamera()
  } else {
    stopCamera()
  }
})

const startCamera = async () => {
  loading.value = true
  stopCamera() // Ensure old stream is stopped
  
  try {
    stream.value = await navigator.mediaDevices.getUserMedia({
      video: { 
        facingMode: facingMode.value,
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    })
    if (videoRef.value) {
      videoRef.value.srcObject = stream.value
      loading.value = false
    }
  } catch (err) {
    console.error('Camera Access Error:', err)
    alert('Gagal mengakses kamera. Pastikan izin kamera sudah diberikan.')
    closeModal()
  }
}

const toggleCamera = () => {
  facingMode.value = facingMode.value === 'environment' ? 'user' : 'environment'
  startCamera()
}

const stopCamera = () => {
  if (stream.value) {
    stream.value.getTracks().forEach(track => track.stop())
    stream.value = null
  }
}

const capturePhoto = () => {
  if (!videoRef.value) return

  const canvas = document.createElement('canvas')
  canvas.width = videoRef.value.videoWidth
  canvas.height = videoRef.value.videoHeight
  
  const ctx = canvas.getContext('2d')
  ctx.drawImage(videoRef.value, 0, 0)
  
  canvas.toBlob((blob) => {
    const file = new File([blob], `survey_photo_${Date.now()}.jpg`, { type: 'image/jpeg' })
    emit('capture', file)
    closeModal()
  }, 'image/jpeg', 0.8)
}

const closeModal = () => {
  stopCamera()
  emit('close')
}

onUnmounted(() => {
  stopCamera()
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-container {
  background: white;
  width: 100%;
  border-radius: 2rem;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.close-btn {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f1f5f9;
  color: #1e293b;
}
</style>
