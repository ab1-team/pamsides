<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click="closeModal">
      <div class="modal-container" @click.stop>
        <div class="modal-top-accent"></div>
        <div class="modal-header">
          <div class="flex! items-center! gap-3!">
            <div
              class="w-10! h-10! rounded-xl! bg-cyan-50! flex! items-center! justify-center! text-cyan-500!"
            >
              <font-awesome-icon icon="water" class="text-xl!" />
            </div>
            <div>
              <h2 class="text-lg! font-extrabold! text-slate-800! leading-none!">
                Input Pemakaian
              </h2>
              <p class="text-[10px]! uppercase! tracking-widest! text-slate-400! mt-1!">
                Instalasi Air Bersih
              </p>
            </div>
          </div>
          <button @click="closeModal" class="close-btn">
            <font-awesome-icon icon="times" />
          </button>
        </div>

        <div class="modal-body !p-4 !md:p-5 overflow-y-auto max-h-[60vh]">
          <div class="identity-card mb-2!">
            <div class="grid! grid-cols-1! md:grid-cols-3! gap-2! md:gap-4!">
              <div class="info-badge">
                <font-awesome-icon icon="id-card" class="badge-icon" />
                <div class="badge-content">
                  <label>No. Induk</label>
                  <span>{{ customer.noInduk }}</span>
                </div>
              </div>
              <div class="info-badge highlight">
                <font-awesome-icon icon="user" class="badge-icon" />
                <div class="badge-content">
                  <label>Nama Pelanggan</label>
                  <span class="!text-slate-800! !font-bold">{{ customer.nama }}</span>
                </div>
              </div>
              <div class="info-badge">
                <font-awesome-icon icon="map-marker-alt" class="badge-icon" />
                <div class="badge-content">
                  <label>Dusun / Lokasi</label>
                  <span>{{ customer.dusun }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="grid! grid-cols-1! lg:grid-cols-12! gap-6! md:gap-8!">
            <div class="lg:col-span-5! space-y-4!">
              <div class="input-modern-group">
                <label>Meter Awal</label>
                <div class="input-wrapper readonly">
                  <input type="text" inputmode="numeric" :value="customer.meterAwal" disabled />
                  <span class="unit">m³</span>
                </div>
              </div>

              <div class="input-modern-group active">
                <label>Meter Akhir</label>
                <div class="input-wrapper focus-within:ring-4! focus-within:ring-cyan-500/10!">
                  <input
                    type="text"
                    inputmode="numeric"
                    v-model.number="meterAkhir"
                    ref="meterInput"
                    placeholder="0000"
                    onkeypress="return /[0-9]/.test(event.key)"
                  />
                  <span class="unit">m³</span>
                </div>
              </div>

              <div class="usage-result-card" :class="{ 'has-usage': usageSummary > 0 }">
                <div class="flex! items-center! justify-between!">
                  <div class="flex! items-center! gap-2!">
                    <div
                      class="w-8! h-8! rounded-lg! bg-white/50! flex! items-center! justify-center!"
                    >
                      <font-awesome-icon
                        icon="chart-line"
                        :class="usageSummary > 0 ? 'text-cyan-500' : 'text-slate-400'"
                      />
                    </div>
                    <span class="text-sm! font-semibold! text-slate-600!">Total Pemakaian</span>
                  </div>
                  <div class="text-right!">
                    <span
                      class="text-2xl! font-black! tracking-tight!"
                      :class="usageSummary > 0 ? 'text-cyan-600' : 'text-slate-300'"
                    >
                      {{ usageSummary }}
                    </span>
                    <span class="text-[10px]! font-bold! text-slate-400! ml-1!">m³</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="lg:col-span-7!">
              <div class="scanner-container">
                <div class="scanner-viewfinder-wrapper">
                  <video ref="videoRef" autoplay playsinline class="scanner-video"></video>
                  <div class="tech-overlay top-left"></div>
                  <div class="tech-overlay top-right"></div>
                  <div class="tech-overlay bottom-left"></div>
                  <div class="tech-overlay bottom-right"></div>
                  <div v-if="scanning" class="tech-scan-line"></div>
                  <div v-if="!cameraActive" class="scanner-placeholder">
                    <div class="pulse-icon">
                      <font-awesome-icon icon="camera" />
                    </div>
                    <p>Siap Memindai Meteran</p>
                  </div>
                </div>

                <BaseButton
                  variant="info-gradient"
                  class="!w-full !py-3 !md:py-4 !rounded-2xl !shadow-xl !shadow-cyan-100 !font-black !text-xs !md:text-sm !tracking-widest !uppercase !mt-4 !transition-transform !active:scale-95"
                  @click="toggleScanner"
                >
                  <font-awesome-icon :icon="cameraActive ? 'check' : 'qrcode'" class="mr-2!" />
                  {{ cameraActive ? 'Tangkap Angka' : 'Buka Scanner' }}
                </BaseButton>
              </div>
            </div>
          </div>
        </div>

        <div
          class="modal-footer !p-4 !md:p-6 !bg-slate-50! !border-t-2! border-slate-100! flex! items-center! justify-end! rounded-b-[1.5rem]! md:rounded-b-[2.5rem]!"
        >
          <div class="w-full! md:w-auto!">
            <BaseButton
              variant="secondary"
              @click="handleSave"
              class="!w-full! md:w-auto! !px-12 !py-4 !rounded-full !text-white !font-black !shadow-xl !tracking-wide !text-base"
            >
              Simpan Data Pemakaian
            </BaseButton>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onUnmounted, computed } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'

const props = defineProps({
  show: Boolean,
  customer: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['close', 'save'])

const meterAkhir = ref(0)
const videoRef = ref(null)
const cameraActive = ref(false)
const scanning = ref(false)
const stream = ref(null)
const meterInput = ref(null)

const usageSummary = computed(() => {
  return Math.max(0, (meterAkhir.value || 0) - (props.customer.meterAwal || 0))
})

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      meterAkhir.value = props.customer.meterAkhir
      document.body.style.overflow = 'hidden'
      setTimeout(() => meterInput.value?.focus(), 100)
    } else {
      stopCamera()
      document.body.style.overflow = ''
    }
  },
)

const toggleScanner = async () => {
  if (!cameraActive.value) {
    try {
      stream.value = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
      })
      if (videoRef.value) {
        videoRef.value.srcObject = stream.value
        cameraActive.value = true
        scanning.value = true
      }
    } catch (err) {
      console.error('Error accessing camera:', err)
      alert('Gagal akses kamera.')
    }
  } else {
    scanning.value = false
    const simulatedResult = (props.customer.meterAwal || 0) + Math.floor(Math.random() * 50) + 5
    meterAkhir.value = simulatedResult
    stopCamera()
  }
}

const stopCamera = () => {
  if (stream.value) {
    stream.value.getTracks().forEach((track) => track.stop())
    stream.value = null
  }
  cameraActive.value = false
  scanning.value = false
}

const handleSave = () => {
  if (meterAkhir.value < props.customer.meterAwal) {
    alert('Meter akhir tidak boleh lebih kecil dari meter awal!')
    return
  }
  emit('save', meterAkhir.value)
  closeModal()
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
@reference "@/assets/main.css";

.modal-overlay {
  @apply fixed inset-0 bg-slate-900/40 backdrop-blur-md flex items-center justify-center z-[3000] p-3 md:p-4;
}

.modal-container {
  @apply bg-white rounded-[1.5rem] md:rounded-[2.5rem] shadow-[0_32px_64px_-12px_rgba(0,0,0,0.14)] w-full max-w-2xl lg:max-w-3xl overflow-hidden relative border! border-white;
  animation: modal-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes modal-pop {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-top-accent {
  @apply h-2 w-full bg-gradient-to-r from-cyan-400 via-indigo-500 to-purple-500;
}

.modal-header {
  @apply flex! items-center! justify-between! p-4 md:p-6! pb-1 md:pb-2!;
}

.close-btn {
  @apply w-10! h-10! rounded-full! flex! items-center! justify-center! bg-red-50! text-red-500! hover:bg-red-100! hover:text-red-700! transition-all! shadow-sm!;
}

.identity-card {
  @apply bg-slate-50/80 rounded-2xl md:rounded-3xl p-2! border! border-slate-100;
}

.info-badge {
  @apply flex! items-center! gap-3! p-3 md:p-4! rounded-2xl! transition-all;
}

.info-badge.highlight {
  @apply bg-white! shadow-sm! border! border-white!;
}

.badge-icon {
  @apply text-slate-300! text-base md:text-lg!;
}

.highlight .badge-icon {
  @apply text-cyan-500!;
}

.badge-content label {
  @apply block! text-[8px] md:text-[9px]! uppercase! font-black! tracking-widest! text-slate-400! mb-0.5!;
}

.badge-content span {
  @apply text-xs md:text-sm! font-medium! text-slate-600!;
}

.input-modern-group label {
  @apply block! text-[10px] md:text-xs! font-black! text-slate-500! uppercase! tracking-tighter! mb-1.5 md:mb-2! ml-1!;
}

.input-wrapper {
  @apply relative! bg-slate-100/50! border-2! border-slate-200/50! rounded-xl md:rounded-2xl! px-4! py-3! flex! items-center! transition-all!;
}

.input-wrapper.readonly {
  @apply bg-white! border-dashed! border-slate-200! opacity-80!;
}

.input-wrapper input {
  @apply bg-transparent! border-none! outline-none! w-full! text-xl md:text-2xl! font-black! text-slate-800! tracking-tight!;
}

.input-wrapper .unit {
  @apply text-[10px] md:text-xs! font-bold! text-slate-400! ml-2!;
}

.usage-result-card {
  @apply bg-slate-50! rounded-2xl md:rounded-3xl! p-4 md:p-5! border! border-slate-100! transition-all!;
}

.usage-result-card.has-usage {
  @apply bg-cyan-50/30! border-cyan-100/50!;
}

.scanner-container {
  @apply flex! flex-col! h-full!;
}

.scanner-viewfinder-wrapper {
  @apply relative! rounded-[1.2rem] md:rounded-[2rem]! overflow-hidden! bg-slate-900! aspect-video! max-h-[140px]! md:max-h-[160px]! shadow-2xl!;
}

.scanner-video {
  @apply w-full! h-full! object-cover! opacity-90!;
}

.tech-overlay {
  @apply absolute! w-6 md:w-8! h-6 md:h-8! border-cyan-400!;
}
.top-left {
  @apply top-4 md:top-6! left-4 md:left-6! border-t-4! border-l-4! rounded-tl-lg md:rounded-tl-xl!;
}
.top-right {
  @apply top-4 md:top-6! right-4 md:right-6! border-t-4! border-r-4! rounded-tr-lg md:rounded-tr-xl!;
}
.bottom-left {
  @apply bottom-4 md:bottom-6! left-4 md:left-6! border-b-4! border-l-4! rounded-bl-lg md:rounded-bl-xl!;
}
.bottom-right {
  @apply bottom-4 md:bottom-6! right-4 md:right-6! border-b-4! border-r-4! rounded-br-lg md:rounded-br-xl!;
}

.tech-scan-line {
  @apply absolute! left-6 md:left-8! right-6 md:right-8! h-0.5 md:h-1! bg-cyan-400! z-10! blur-[1px]!;
  box-shadow: 0 0 15px #22d3ee;
  animation: scan-move 2.5s infinite linear;
}

@keyframes scan-move {
  0% {
    top: 15%;
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    top: 85%;
    opacity: 0;
  }
}

.scanner-placeholder {
  @apply absolute! inset-0! flex! flex-col! items-center! justify-center! text-slate-500!;
}

.pulse-icon {
  @apply w-12 md:w-16! h-12 md:h-16! rounded-full! bg-slate-800! flex! items-center! justify-center! text-xl md:text-2xl! mb-3 md:mb-4! border! border-slate-700!;
  animation: pulse-ring 2s infinite;
}

@keyframes pulse-ring {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.2);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 15px rgba(14, 165, 233, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(14, 165, 233, 0);
  }
}

.scanner-placeholder p {
  @apply text-[8px] md:text-[10px]! font-black! uppercase! tracking-[0.2em]! text-slate-600!;
}

input[type='text']::-webkit-inner-spin-button,
input[type='text']::-webkit-outer-spin-button {
  -webkit-appearance: none !important;
  margin: 0 !important;
}

input[type='text'] {
  -moz-appearance: textfield !important;
  appearance: textfield !important;
}
</style>
