<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed! inset-0! z-[100]! flex! items-end! md:items-center! justify-center! bg-slate-900/80! backdrop-blur-sm! p-0! md:p-4! transition-all!"
      @click="closeModal"
    >
      <div
        class="bg-white! w-full! h-full! md:h-auto! md:max-w-2xl! rounded-none! md:rounded-[2.5rem]! shadow-2xl! relative! flex! flex-col! overflow-hidden! animate-in! slide-in-from-bottom-full! md:fade-in! md:zoom-in-95! duration-300!"
        @click.stop
      >
        <div class="h-2! bg-gradient-to-r! from-cyan-500! to-blue-600! w-full! shrink-0!"></div>

        <div
          class="px-5! md:px-6! py-4! md:py-5! border-b! border-slate-50! flex! items-center! justify-between! shrink-0!"
        >
          <div class="flex! items-center! gap-3!">
            <div
              class="w-10! h-10! rounded-xl! bg-cyan-50! flex! items-center! justify-center! text-cyan-600!"
            >
              <font-awesome-icon icon="water" class="text-xl!" />
            </div>
            <div>
              <h2 class="text-lg! font-black! text-slate-800! leading-none!">Input Pemakaian</h2>
              <p class="text-[10px]! uppercase! tracking-widest! text-slate-400! mt-0.5!">
                Instalasi Air Bersih
              </p>
            </div>
          </div>
          <button
            @click="closeModal"
            class="w-10! h-10! rounded-full! bg-slate-50! text-slate-400! flex! items-center! justify-center! hover:bg-red-50! hover:text-red-500! transition-all!"
          >
            <font-awesome-icon icon="times" />
          </button>
        </div>

        <div class="p-4! md:p-5! overflow-y-auto! flex-1! md:max-h-[75vh]!">
          <div
            class="flex! items-center! gap-2! p-2.5! rounded-2xl! bg-gradient-to-r! from-slate-50! to-blue-50/50! border! border-slate-100! mb-4!"
          >
            <div
              class="w-8! h-8! rounded-lg! bg-white! flex! items-center! justify-center! text-slate-400! shrink-0!"
            >
              <font-awesome-icon icon="id-card" />
            </div>
            <span class="text-xs! font-bold! text-slate-600! font-mono!">{{ customer.noInduk }}</span>
            <span class="text-slate-300!">•</span>
            <span class="text-xs! font-black! text-slate-900! truncate! flex-1!">{{ customer.nama }}</span>
            <span class="hidden! sm:inline! text-slate-300!">•</span>
            <span class="hidden! sm:inline! text-xs! font-bold! text-slate-500!">{{ customer.dusun }}</span>
          </div>

          <div class="grid! grid-cols-1! md:grid-cols-2! gap-4!">
            <div class="space-y-4!">
              <div>
                <div class="flex! items-center! gap-2! mb-1.5!">
                  <div class="w-1! h-3! bg-slate-300! rounded-full!"></div>
                  <label
                    class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest!"
                    >Meter Awal (Bulan Lalu)</label
                  >
                </div>
                <div
                  class="flex! items-center! bg-slate-100/60! border-2! border-transparent! rounded-xl! px-4! py-2.5! opacity-70!"
                >
                  <span class="flex-1! text-sm! font-bold! text-slate-500! font-mono!">{{ customer.meterAwal }}</span>
                  <span class="text-[9px]! font-black! text-slate-400! bg-slate-200/60! px-1.5! py-0.5! rounded!">M³</span>
                </div>
              </div>

              <div>
                <div class="flex! items-center! gap-2! mb-1.5!">
                  <div class="w-1! h-3! bg-cyan-500! rounded-full!"></div>
                  <label class="text-[9px]! font-black! text-slate-700! uppercase! tracking-widest!">Meter Akhir (Bulan Ini)</label>
                </div>
                <div
                  class="flex! items-center! bg-white! border-2! border-slate-200! rounded-xl! px-4! py-2.5! shadow-sm! focus-within:border-cyan-500! focus-within:ring-4! focus-within:ring-cyan-500/10! transition-all!"
                >
                  <input
                    type="text"
                    inputmode="numeric"
                    v-model.number="meterAkhir"
                    ref="meterInput"
                    placeholder="0000"
                    onkeypress="return /[0-9]/.test(event.key)"
                    class="flex-1! bg-transparent! border-none! outline-none! text-base! font-black! text-slate-800! placeholder-slate-200! focus:text-cyan-600! font-mono!"
                  />
                  <span class="text-[9px]! font-black! text-cyan-600! bg-cyan-50! px-1.5! py-0.5! rounded!">M³</span>
                </div>
                <p class="text-[10px] text-slate-400 italic mt-1.5! flex items-center gap-1.5!">
                  <font-awesome-icon v-if="isScanning" icon="spinner" spin class="text-indigo-500" />
                  <font-awesome-icon v-else-if="ocrStatus === 'ok'" icon="check-circle" class="text-emerald-500" />
                  <font-awesome-icon v-else-if="ocrStatus === 'fail'" icon="exclamation-circle" class="text-amber-500" />
                  <span v-if="isScanning">Membaca angka…</span>
                  <span v-else-if="ocrStatus === 'ok'">Terdeteksi otomatis. Periksa sebelum simpan.</span>
                  <span v-else-if="ocrStatus === 'fail'">Gagal membaca, ketik manual.</span>
                  <span v-else>* Masukkan angka meteran</span>
                </p>
              </div>

              <div>
                <div class="flex! items-center! gap-2! mb-1.5!">
                  <div class="w-1! h-3! bg-indigo-500! rounded-full!"></div>
                  <label class="text-[9px]! font-black! text-slate-700! uppercase! tracking-widest!">Scan Meteran (Otomatis)</label>
                </div>
                <div
                  class="relative! flex! items-center! bg-slate-900! border-2! border-slate-800! rounded-xl! overflow-hidden!"
                  style="height: 60px;"
                >
                  <video
                    ref="scanVideoRef"
                    class="absolute! inset-0! w-full! h-full! object-cover! z-0!"
                    autoplay
                    playsinline
                    muted
                  ></video>
                  <div
                    class="absolute! inset-0! flex! items-center! justify-center! pointer-events-none! z-10!"
                  >
                    <div
                      class="border-2! border-emerald-400! rounded-md! shadow-[0_0_0_9999px_rgba(0,0,0,0.55)]!"
                      style="width: 75%; height: 75%;"
                    >
                      <div
                        class="absolute! top-0! left-0! w-full! h-0.5! bg-emerald-400! shadow-[0_0_10px_rgba(16,185,129,0.9)]! animate-[scan_2s_infinite]!"
                      ></div>
                    </div>
                  </div>
                  <div
                    class="absolute! bottom-0.5! inset-x-0! flex! justify-center! z-20!"
                  >
                    <span
                      class="px-2! py-0.5! rounded-full! bg-black/60! text-emerald-300! text-[8px]! font-black! uppercase! tracking-widest! flex! items-center! gap-1!"
                    >
                      <font-awesome-icon :icon="isScanning ? 'spinner' : 'magic'" :spin="isScanning" class="text-[8px]!" />
                      {{ isScanning ? 'Membaca…' : (autoScanAttempts > 0 ? 'Coba ulang…' : 'Arahkan angka ke kotak') }}
                    </span>
                  </div>
                </div>
                <canvas ref="scanCanvasRef" class="hidden!"></canvas>
              </div>
            </div>

            <div>
              <div class="flex! items-center! gap-2! mb-1.5!">
                <div class="w-1! h-3! bg-emerald-500! rounded-full!"></div>
                <label class="text-[9px]! font-black! text-slate-400! uppercase! tracking-widest!">Bukti Foto Meteran</label>
              </div>

              <div>
                <!-- LIVE CAMERA VIEW -->
                <div
                  v-if="isCameraOpen"
                  class="relative! overflow-hidden! rounded-xl! border-2! border-slate-800! bg-black! shadow-md! w-full! aspect-square!"
                >
                  <video
                    ref="videoRef"
                    class="w-full! h-full! object-cover!"
                    autoplay
                    playsinline
                  ></video>

                  <!-- Switch Camera Button -->
                  <button
                    @click="toggleCamera"
                    type="button"
                    class="absolute! top-3! left-3! w-8! h-8! bg-white/20! hover:bg-cyan-500! backdrop-blur-md! text-white! rounded-full! flex! items-center! justify-center! shadow-lg! transition-all! z-20!"
                    title="Tukar Kamera"
                  >
                    <font-awesome-icon icon="sync-alt" class="text-sm!" />
                  </button>

                  <!-- Close Camera Button -->
                  <button
                    @click="stopLiveCamera"
                    type="button"
                    class="absolute! top-3! right-3! w-8! h-8! bg-white/20! hover:bg-red-500! backdrop-blur-md! text-white! rounded-full! flex! items-center! justify-center! shadow-lg! transition-all! z-20!"
                  >
                    <font-awesome-icon icon="times" class="text-sm!" />
                  </button>

                  <!-- Snapshot Button -->
                  <div class="absolute! bottom-4! inset-x-0! flex! justify-center! z-20!">
                    <button
                      @click="takeSnapshot"
                      type="button"
                      class="w-16! h-16! rounded-full! bg-white/30! backdrop-blur-sm! border-4! border-white! flex! items-center! justify-center! hover:scale-105! active:scale-95! transition-all! shadow-lg!"
                    >
                      <div class="w-12! h-12! rounded-full! bg-white!"></div>
                    </button>
                  </div>
                </div>

                <!-- IMAGE PREVIEW VIEW -->
                <div
                  v-else-if="photoPreview"
                  class="relative! group! overflow-hidden! rounded-2xl! border-4! border-white! shadow-2xl!"
                >
                  <img
                    :src="photoPreview"
                    class="w-full! aspect-square! object-cover! group-hover:scale-105! transition-transform! duration-700!"
                  />
                  <div
                    class="absolute! inset-0! bg-gradient-to-t! from-slate-900/60! via-transparent! to-transparent!"
                  ></div>

                  <div
                    class="absolute! top-0! left-0! w-full! h-1! bg-cyan-500/50! shadow-[0_0_15px_rgba(6,182,212,0.8)]! animate-[scan_2s_infinite]!"
                  ></div>

                  <button
                    @click="removePhoto"
                    type="button"
                    class="absolute! top-4! right-4! w-10! h-10! bg-white/20! hover:bg-red-500! backdrop-blur-md! text-white! rounded-full! flex! items-center! justify-center! shadow-lg! transition-all! hover:scale-110!"
                  >
                    <font-awesome-icon icon="times" />
                  </button>

                  <div class="absolute! bottom-4! left-4! right-4!">
                    <div
                      class="flex! items-center! gap-2! bg-white/20! backdrop-blur-md! p-2! rounded-xl! border! border-white/20!"
                    >
                      <div
                        class="w-8! h-8! rounded-lg! bg-emerald-500! flex! items-center! justify-center! text-white!"
                      >
                        <font-awesome-icon icon="check" />
                      </div>
                      <div class="flex-1!">
                        <div class="text-[10px]! font-black! text-white! uppercase!">
                          Foto Terlampir
                        </div>
                        <div class="text-[8px]! font-bold! text-white/70!">SIAP DISIMPAN</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- EMPTY STATE (SELECT METHOD) -->
                <div
                  v-else
                  class="group! relative! border-2! border-dashed! border-slate-200! rounded-xl! p-4! text-center! hover:border-cyan-500! transition-all! aspect-square! flex! flex-col! items-center! justify-center!"
                >
                  <div
                    class="w-12! h-12! bg-slate-50! rounded-xl! flex! items-center! justify-center! mb-3! text-slate-300! group-hover:text-cyan-500! transition-all! group-hover:rotate-6!"
                  >
                    <font-awesome-icon icon="camera" class="text-xl!" />
                  </div>
                  <p
                    class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-3!"
                  >
                    Pilih Metode Input
                  </p>

                  <div class="grid! grid-cols-2! gap-2!">
                    <button
                      type="button"
                      @click="openLiveCamera"
                      class="w-full! flex! items-center! justify-center! gap-2! py-2! px-4! rounded-lg! bg-cyan-600! text-white! font-black! text-[10px]! uppercase! tracking-wider! shadow-md! hover:bg-cyan-700! transition-all! active:scale-95!"
                    >
                      <font-awesome-icon icon="video" />
                      Kamera
                    </button>
                    <button
                      type="button"
                      @click="triggerFile"
                      class="w-full! flex! items-center! justify-center! gap-2! py-2! px-4! rounded-lg! bg-slate-100! text-slate-600! font-black! text-[10px]! uppercase! tracking-wider! shadow-sm! hover:bg-slate-200! transition-all! active:scale-95!"
                    >
                      <font-awesome-icon icon="folder-open" />
                      File
                    </button>
                  </div>
                </div>

                <!-- Hidden Inputs for logic -->
                <canvas ref="canvasRef" class="hidden!"></canvas>
                <input
                  type="file"
                  ref="fileInputRef"
                  class="hidden!"
                  accept="image/*"
                  @change="handleFileChange"
                />
              </div>
            </div>
          </div>
        </div>

        <div
          class="p-3! md:p-4! bg-slate-50! border-t! border-slate-100! flex! items-center! justify-end!"
        >
          <BaseButton
            @click="handleSave"
            class="w-full! md:w-auto! bg-slate-800! hover:bg-slate-900! px-8! py-3! rounded-xl! text-white! font-black! shadow-lg! shadow-slate-200! tracking-widest! text-xs! uppercase! transition-all!"
          >
            Simpan Data Pemakaian
          </BaseButton>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onUnmounted, nextTick } from 'vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import { MySwal } from '@/utils/swal'

const props = defineProps({
  show: Boolean,
  customer: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['close', 'save'])

const meterAkhir = ref(0)
const fileInputRef = ref(null)
const photoPreview = ref(null)
const selectedFile = ref(null)
const meterInput = ref(null)
const isScanning = ref(false)
const ocrStatus = ref('idle')
const isScanCameraOpen = ref(false)
const scanVideoRef = ref(null)
const scanCanvasRef = ref(null)
const scanFacingMode = ref('environment')
let scanStream = null

// Live Camera State
const isCameraOpen = ref(false)
const videoRef = ref(null)
const canvasRef = ref(null)
const facingMode = ref('environment')
let videoStream = null

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      meterAkhir.value = props.customer.meterAwal
      photoPreview.value = null
      selectedFile.value = null
      isCameraOpen.value = false
      facingMode.value = 'environment'
      isScanning.value = false
      ocrStatus.value = 'idle'
      scanFacingMode.value = 'environment'
      stopScanCamera()
      document.body.style.overflow = 'hidden'
      openScanCamera()
    } else {
      document.body.style.overflow = ''
      stopLiveCamera()
      stopScanCamera()
    }
  },
)

const openLiveCamera = async () => {
  try {
    isCameraOpen.value = true
    await nextTick() // wait for video element to render
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: facingMode.value },
    })
    videoStream = stream
    if (videoRef.value) {
      videoRef.value.srcObject = stream
    }
  } catch (err) {
    console.error('Error accessing camera:', err)
    isCameraOpen.value = false
    MySwal.fire({
      icon: 'error',
      title: 'Kamera Gagal',
      text: 'Pastikan browser memiliki izin untuk mengakses kamera (Webcam/HP).',
      confirmButtonColor: '#f59e0b',
    })
  }
}

const toggleCamera = async () => {
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop())
  }
  facingMode.value = facingMode.value === 'environment' ? 'user' : 'environment'

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: facingMode.value },
    })
    videoStream = stream
    if (videoRef.value) {
      videoRef.value.srcObject = stream
    }
  } catch (err) {
    console.error('Error toggling camera:', err)
    MySwal.fire({
      icon: 'warning',
      title: 'Tukar Kamera Gagal',
      text: 'Perangkat ini mungkin hanya memiliki satu kamera aktif.',
      confirmButtonColor: '#f59e0b',
    })
  }
}

const stopLiveCamera = () => {
  if (videoStream) {
    videoStream.getTracks().forEach((track) => track.stop())
    videoStream = null
  }
  isCameraOpen.value = false
}

const takeSnapshot = () => {
  if (videoRef.value && canvasRef.value) {
    const video = videoRef.value
    const canvas = canvasRef.value

    // Set canvas size to match video resolution
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight

    const ctx = canvas.getContext('2d')
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

    // Get image data
    photoPreview.value = canvas.toDataURL('image/jpeg', 0.8)

    // Convert to File object for upload
    canvas.toBlob(
      (blob) => {
        if (blob) {
          selectedFile.value = new File([blob], `meter_${Date.now()}.jpg`, { type: 'image/jpeg' })
        }
      },
      'image/jpeg',
      0.8,
    )

    // Stop camera feed
    stopLiveCamera()
  }
}

const triggerFile = () => {
  fileInputRef.value?.click()
}

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      photoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const removePhoto = () => {
  photoPreview.value = null
  selectedFile.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const handleSave = () => {
  const finalMeter = parseInt(meterAkhir.value, 10)

  if (isNaN(finalMeter) || finalMeter < 0) {
    MySwal.fire({
      icon: 'warning',
      title: 'Validasi Gagal',
      text: 'Angka meteran harus diisi dengan angka bulat yang valid!',
      confirmButtonColor: '#f59e0b',
    })
    return
  }

  if (finalMeter < props.customer.meterAwal) {
    MySwal.fire({
      icon: 'warning',
      title: 'Validasi Gagal',
      text: 'Meter akhir tidak boleh lebih kecil dari meter awal!',
      confirmButtonColor: '#f59e0b',
    })
    return
  }

  if (!selectedFile.value) {
    MySwal.fire({
      icon: 'warning',
      title: 'Validasi Gagal',
      text: 'Bukti foto meteran wajib dilampirkan!',
      confirmButtonColor: '#f59e0b',
    })
    return
  }

  emit('save', {
    meterValue: finalMeter,
    photo: selectedFile.value,
  })
}

const closeModal = () => {
  emit('close')
}

const openScanCamera = async () => {
  try {
    isScanCameraOpen.value = true
    await nextTick()
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: scanFacingMode.value },
    })
    scanStream = stream
    if (scanVideoRef.value) {
      scanVideoRef.value.srcObject = stream
      scanVideoRef.value.onloadedmetadata = () => startAutoScan()
    }
  } catch (err) {
    console.error('scan camera err:', err)
    isScanCameraOpen.value = false
    MySwal.fire({
      icon: 'error',
      title: 'Kamera Gagal',
      text: 'Pastikan browser memiliki izin akses kamera.',
      confirmButtonColor: '#f59e0b',
    })
  }
}

const stopScanCamera = () => {
  if (autoScanTimer) {
    clearTimeout(autoScanTimer)
    autoScanTimer = null
  }
  if (scanStream) {
    scanStream.getTracks().forEach((t) => t.stop())
    scanStream = null
  }
  isScanCameraOpen.value = false
  autoScanAttempts.value = 0
}

const autoScanAttempts = ref(0)
let autoScanTimer = null

const captureAndScan = async () => {
  if (!scanVideoRef.value || !scanCanvasRef.value || isScanning.value || !isScanCameraOpen.value) return
  const video = scanVideoRef.value
  const canvas = scanCanvasRef.value
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, 0, 0, canvas.width, canvas.height)

  const cw = canvas.width
  const ch = canvas.height
  const rw = Math.round(cw * 0.75)
  const rh = Math.round(ch * 0.45)
  const rx = Math.round((cw - rw) / 2)
  const ry = Math.round((ch - rh) / 2)
  const crop = document.createElement('canvas')
  crop.width = rw
  crop.height = rh
  crop.getContext('2d').drawImage(canvas, rx, ry, rw, rh, 0, 0, rw, rh)
  const dataUrl = crop.toDataURL('image/jpeg', 0.9)

  await scanMeterFromPhoto(dataUrl)

  if (ocrStatus.value === 'ok' || !isScanCameraOpen.value) return
  autoScanAttempts.value++
  autoScanTimer = setTimeout(captureAndScan, 500)
}

const startAutoScan = () => {
  autoScanAttempts.value = 0
  if (autoScanTimer) clearTimeout(autoScanTimer)
  captureAndScan()
}

// ponytail: OCR pakai tesseract.js dari CDN (worker + core fetched on demand).
// Upgrade path: pindah ke paket npm + custom traineddata 7-segment saat akurasi rendah.
const loadTesseract = () => {
  if (window.Tesseract) return Promise.resolve(window.Tesseract)
  return new Promise((resolve, reject) => {
    const s = document.createElement('script')
    s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js'
    s.onload = () => resolve(window.Tesseract)
    s.onerror = reject
    document.head.appendChild(s)
  })
}

const extractDigits = (text) => {
  const cleaned = (text || '').replace(/[^\d]/g, '')
  if (!cleaned) return null
  const n = parseInt(cleaned, 10)
  return Number.isFinite(n) ? n : null
}

const scanMeterFromPhoto = async (dataUrl) => {
  if (!dataUrl || isScanning.value) return
  isScanning.value = true
  ocrStatus.value = 'idle'
  try {
    const Tesseract = await loadTesseract()
    const { data } = await Tesseract.recognize(dataUrl, 'eng', {
      tessedit_char_whitelist: '0123456789',
    })
    const digits = extractDigits(data?.text)
    if (digits === null) {
      ocrStatus.value = 'fail'
      return
    }
    meterAkhir.value = digits
    ocrStatus.value = 'ok'
  } catch (err) {
    console.error('OCR error:', err)
    ocrStatus.value = 'fail'
  } finally {
    isScanning.value = false
  }
}

onUnmounted(() => {
  stopLiveCamera()
  stopScanCamera()
})
</script>

<style scoped>
@keyframes scan {
  0% {
    top: 0%;
    opacity: 1;
  }
  50% {
    top: 100%;
    opacity: 0.5;
  }
  100% {
    top: 0%;
    opacity: 1;
  }
}
</style>
