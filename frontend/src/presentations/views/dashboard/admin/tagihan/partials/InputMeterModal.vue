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
          <div class="bg-white/50! rounded-3xl! mb-6!">
            <div class="grid! grid-cols-1! md:grid-cols-3! gap-3!">
              <div
                class="flex! items-center! gap-3! p-3! rounded-2xl! bg-slate-50! border! border-slate-100!"
              >
                <div
                  class="w-8! h-8! rounded-lg! bg-slate-100! flex! items-center! justify-center! text-slate-400!"
                >
                  <font-awesome-icon icon="id-card" />
                </div>
                <div>
                  <label
                    class="block! text-[9px]! font-black! text-slate-400! uppercase! tracking-widest! leading-none! mb-1!"
                    >No. Induk</label
                  >
                  <span class="text-xs! font-bold! text-slate-600!">{{ customer.noInduk }}</span>
                </div>
              </div>
              <div
                class="flex! items-center! gap-3! p-3! rounded-2xl! bg-white! border! border-blue-100! shadow-sm! md:col-span-1!"
              >
                <div
                  class="w-8! h-8! rounded-lg! bg-blue-50! flex! items-center! justify-center! text-blue-500!"
                >
                  <font-awesome-icon icon="user" />
                </div>
                <div>
                  <label
                    class="block! text-[9px]! font-black! text-slate-400! uppercase! tracking-widest! leading-none! mb-1!"
                    >Nama Pelanggan</label
                  >
                  <span class="text-xs! font-black! text-slate-900!">{{ customer.nama }}</span>
                </div>
              </div>
              <div
                class="flex! items-center! gap-3! p-3! rounded-2xl! bg-slate-50! border! border-slate-100!"
              >
                <div
                  class="w-8! h-8! rounded-lg! bg-slate-100! flex! items-center! justify-center! text-slate-400!"
                >
                  <font-awesome-icon icon="map-marker-alt" />
                </div>
                <div>
                  <label
                    class="block! text-[9px]! font-black! text-slate-400! uppercase! tracking-widest! leading-none! mb-1!"
                    >Dusun / Lokasi</label
                  >
                  <span class="text-xs! font-bold! text-slate-600!">{{ customer.dusun }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="grid! grid-cols-1! md:grid-cols-12! gap-6! items-start!">
            <div class="md:col-span-6! space-y-5!">
              <div>
                <div class="flex! items-center! gap-2! mb-2!">
                  <div class="w-1.5! h-4! bg-slate-200! rounded-full!"></div>
                  <label
                    class="block! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-0!"
                    >Meter Awal (Bulan Lalu)</label
                  >
                </div>
                <div
                  class="flex! items-center! bg-slate-100/50! border-2! border-transparent! rounded-2xl! px-4! py-3! opacity-60! cursor-not-allowed!"
                >
                  <input
                    type="text"
                    inputmode="numeric"
                    :value="customer.meterAwal"
                    disabled
                    class="w-full! bg-transparent! border-none! outline-none! text-sm! font-bold! text-slate-800! placeholder-slate-200! text-slate-400!"
                  />
                  <div class="flex! items-center! gap-1! bg-slate-200/50! px-2! py-1! rounded-lg!">
                    <span class="text-[9px]! font-black! text-slate-500!">M³</span>
                  </div>
                </div>
              </div>

              <div>
                <div class="flex! items-center! gap-2! mb-2!">
                  <div class="w-1.5! h-4! bg-cyan-500! rounded-full!"></div>
                  <label
                    class="block! text-[10px]! font-black! text-slate-700! uppercase! tracking-widest! mb-0!"
                    >Meter Akhir (Bulan Ini)</label
                  >
                </div>
                <div
                  class="flex! items-center! bg-white! border-2! border-slate-200! rounded-2xl! px-4! py-3! shadow-sm! focus-within:border-cyan-500! focus-within:ring-4! focus-within:ring-cyan-500/10! transition-all! group!"
                >
                  <input
                    type="text"
                    inputmode="numeric"
                    v-model.number="meterAkhir"
                    ref="meterInput"
                    placeholder="0000"
                    onkeypress="return /[0-9]/.test(event.key)"
                    class="w-full! bg-transparent! border-none! outline-none! text-sm! font-bold! text-slate-800! placeholder-slate-200! group-focus-within:text-cyan-600!"
                  />
                  <div class="flex! items-center! gap-1! bg-cyan-50! px-2! py-1! rounded-lg!">
                    <span class="text-[9px]! font-black! text-cyan-600!">M³</span>
                  </div>
                </div>
                <p class="text-[10px]! text-slate-400! mt-2! italic!">
                  * Masukkan angka meteran yang tertera pada alat saat ini
                </p>
              </div>
            </div>

            <div class="md:col-span-6!">
              <div class="flex! items-center! gap-3! mb-4!">
                <div class="w-1.5! h-4! bg-emerald-500! rounded-full!"></div>
                <label
                  class="mb-0! text-[10px]! font-black! text-slate-400! uppercase! tracking-widest!"
                  >Bukti Foto Meteran</label
                >
              </div>

              <div>
                <!-- LIVE CAMERA VIEW -->
                <div
                  v-if="isCameraOpen"
                  class="relative! overflow-hidden! rounded-2xl! border-4! border-slate-800! bg-black! shadow-2xl! h-64! w-full!"
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
                    class="w-full! h-64! object-cover! group-hover:scale-105! transition-transform! duration-700!"
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
                  class="group! relative! border-2! border-dashed! border-slate-200! rounded-2xl! p-6! text-center! hover:border-cyan-500! transition-all!"
                >
                  <div
                    class="w-14! h-14! bg-slate-50! rounded-2xl! flex! items-center! justify-center! mx-auto! mb-4! text-slate-300! group-hover:text-cyan-500! transition-all! group-hover:rotate-6!"
                  >
                    <font-awesome-icon icon="camera" class="text-2xl!" />
                  </div>
                  <p
                    class="text-[10px]! font-black! text-slate-400! uppercase! tracking-widest! mb-4!"
                  >
                    Pilih Metode Input
                  </p>

                  <div class="grid! grid-cols-2! gap-2!">
                    <button
                      type="button"
                      @click="openLiveCamera"
                      class="flex! items-center! justify-center! gap-2! py-3! rounded-xl! bg-cyan-600! text-white! font-black! text-[10px]! uppercase! tracking-wider! shadow-md! hover:bg-cyan-700! transition-all! active:scale-95!"
                    >
                      <font-awesome-icon icon="video" />
                      Kamera
                    </button>
                    <button
                      type="button"
                      @click="triggerFile"
                      class="flex! items-center! justify-center! gap-2! py-3! rounded-xl! bg-slate-100! text-slate-600! font-black! text-[10px]! uppercase! tracking-wider! shadow-sm! hover:bg-slate-200! transition-all! active:scale-95!"
                    >
                      <font-awesome-icon icon="folder-open" />
                      File / Galeri
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
          class="p-4! md:p-5! bg-slate-50! border-t! border-slate-100! flex! items-center! justify-end!"
        >
          <div class="w-full! md:w-auto!">
            <BaseButton
              @click="handleSave"
              class="w-full! md:w-auto! bg-slate-800! hover:bg-slate-900! px-12! py-4! rounded-xl! text-white! font-black! shadow-lg! shadow-slate-200! tracking-widest! text-xs! md:text-sm! uppercase! transition-all!"
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
      meterAkhir.value = props.customer.meterAkhir
      photoPreview.value = null
      selectedFile.value = null
      isCameraOpen.value = false
      facingMode.value = 'environment'
      document.body.style.overflow = 'hidden'
      setTimeout(() => meterInput.value?.focus(), 100)
    } else {
      document.body.style.overflow = ''
      stopLiveCamera()
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

onUnmounted(() => {
  stopLiveCamera()
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
