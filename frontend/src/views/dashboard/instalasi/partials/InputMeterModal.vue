<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click="closeModal">
      <div class="modal-container" @click.stop>
        <div class="modal-top-accent"></div>
        <div class="modal-header">
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
          <button @click="closeModal" class="close-btn">
            <font-awesome-icon icon="times" />
          </button>
        </div>

        <div class="modal-body p-4! md:p-5! overflow-y-auto! flex-1! md:max-h-[65vh]!">
          <div class="identity-card mb-4!">
            <div class="grid! grid-cols-1! md:grid-cols-3! gap-1.5!">
              <div class="info-badge">
                <font-awesome-icon icon="id-card" class="badge-icon" />
                <div class="badge-content">
                  <label>No. Induk</label>
                  <span>{{ customer.noInduk }}</span>
                </div>
              </div>
              <div class="info-badge highlight md:col-span-1!">
                <font-awesome-icon icon="user" class="badge-icon" />
                <div class="badge-content">
                  <label>Nama Pelanggan</label>
                  <span class="text-slate-900! font-extrabold!">{{ customer.nama }}</span>
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

          <div class="grid! grid-cols-1! md:grid-cols-12! gap-4!">
            <div class="md:col-span-5! space-y-4!">
              <div class="input-modern-group">
                <label>Meter Awal (Bulan Lalu)</label>
                <div class="input-wrapper readonly">
                  <input type="text" inputmode="numeric" :value="customer.meterAwal" disabled />
                  <span class="unit">m³</span>
                </div>
              </div>
              <div class="input-modern-group active">
                <label>Meter Akhir (Bulan Ini)</label>
                <div class="input-wrapper ring-cyan-500/10! focus-within:ring-4!">
                  <input
                    type="text"
                    inputmode="numeric"
                    v-model.number="meterAkhir"
                    ref="meterInput"
                    placeholder="0000"
                    onkeypress="return /[0-9]/.test(event.key)"
                    class="placeholder-slate-200!"
                  />
                  <span class="unit">m³</span>
                </div>
              </div>
            </div>

            <div class="md:col-span-7!">
              <div class="scanner-container">
                <div class="scanner-viewfinder-wrapper shadow-2xl!">
                  <video ref="videoRef" autoplay playsinline class="scanner-video"></video>
                  <div class="bracket-left"></div>
                  <div class="bracket-right"></div>
                  <div v-if="scanning" class="tech-scan-line"></div>
                  <div v-if="!cameraActive" class="scanner-placeholder">
                    <div class="pulse-icon">
                      <font-awesome-icon icon="camera" />
                    </div>
                    <p>Siap Memindai Meteran</p>
                  </div>

                  <div
                    v-if="cameraActive"
                    class="absolute bottom-4! left-1/2! -translate-x-1/2! bg-slate-900/60! backdrop-blur! px-4! py-2! rounded-full! text-[10px]! text-white! font-bold! uppercase! tracking-widest! z-20!"
                  >
                    Kamera Aktif
                  </div>
                </div>

                <BaseButton
                  variant="info-gradient"
                  class="w-full! py-3! md:py-4! rounded-xl! shadow-xl! shadow-cyan-200/50! font-black! text-[10px]! md:text-xs! tracking-widest! uppercase! mt-4! transition-transform! active:scale-95!"
                  @click="toggleScanner"
                >
                  <font-awesome-icon :icon="cameraActive ? 'check' : 'qrcode'" class="mr-3!" />
                  {{ cameraActive ? 'Tangkap Angka Meter' : 'Buka Scanner Kamera' }}
                </BaseButton>
              </div>
            </div>
          </div>
        </div>

        <div
          class="modal-footer p-4! md:p-5! bg-slate-50! border-t! border-slate-100! flex! items-center! justify-end!"
        >
          <div class="w-full! md:w-auto!">
            <BaseButton
              variant="secondary-gradient"
              @click="handleSave"
              class="w-full! md:w-auto! px-12! py-4! rounded-xl! text-white! font-black! shadow-2xl! shadow-slate-200! tracking-widest! text-xs! md:text-sm! uppercase!"
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
import { ref, watch, onUnmounted } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
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
const videoRef = ref(null)
const cameraActive = ref(false)
const scanning = ref(false)
const stream = ref(null)
const meterInput = ref(null)

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
      MySwal.fire({
        icon: 'error',
        title: 'Kamera Gagal',
        text: 'Tidak dapat mengakses kamera perangkat Anda.',
        confirmButtonColor: '#0ea5e9',
      })
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
    MySwal.fire({
      icon: 'warning',
      title: 'Validasi Gagal',
      text: 'Meter akhir tidak boleh lebih kecil dari meter awal!',
      confirmButtonColor: '#f59e0b',
    })
    return
  }

  MySwal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: 'Data Tersimpan',
    text: 'cek inputan di tombol hasil input',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: {
      title: 'text-left! text-sm! font-bold! text-slate-800! ml-0!',
      htmlContainer: 'text-left! text-[11px]! text-slate-500! mt-0.5! ml-0!',
    },
  })

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
