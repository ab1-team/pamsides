<template>
  <div v-if="show" class="price-detail-modal-overlay" @click="closeModal">
    <div class="price-detail-modal" @click.stop>
      <div class="price-detail-modal__header">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-[#0B7A9E]/10 flex items-center justify-center">
            <font-awesome-icon icon="tag" class="text-[#0B7A9E] text-sm" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-800">Detail Harga Meter</h3>
            <p class="text-xs text-slate-500">Tarif berdasarkan volume pemakaian</p>
          </div>
        </div>
        <button
          @click="closeModal"
          class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors"
        >
          <font-awesome-icon icon="times" class="text-slate-500 text-sm" />
        </button>
      </div>

      <div class="price-detail-modal__content">
        <div class="space-y-3">
          <div class="price-tier">
            <div class="price-tier__header">
              <div class="price-tier__range">0 - 10 M³</div>
              <div class="price-tier__price">Rp 2.500 / M³</div>
            </div>
            <div class="price-tier__details">
              <div class="price-tier__calculation">
                <span class="text-xs text-slate-500">Contoh untuk 5 M³:</span>
                <div class="text-sm font-mono text-slate-700 mt-1">5 × Rp 2.500 = Rp 12.500</div>
              </div>
            </div>
          </div>

          <div class="price-tier">
            <div class="price-tier__header">
              <div class="price-tier__range">10 - 20 M³</div>
              <div class="price-tier__price">Rp 3.000 / M³</div>
            </div>
            <div class="price-tier__details">
              <div class="price-tier__calculation">
                <span class="text-xs text-slate-500">Contoh untuk 15 M³:</span>
                <div class="text-sm font-mono text-slate-700 mt-1">
                  (10 × Rp 2.500) + (5 × Rp 3.000) = Rp 40.000
                </div>
              </div>
            </div>
          </div>

          <div class="price-tier">
            <div class="price-tier__header">
              <div class="price-tier__range">20 - 100 M³</div>
              <div class="price-tier__price">Rp 3.500 / M³</div>
            </div>
            <div class="price-tier__details">
              <div class="price-tier__calculation">
                <span class="text-xs text-slate-500">Contoh untuk 25 M³:</span>
                <div class="text-sm font-mono text-slate-700 mt-1">
                  (10 × Rp 2.500) + (10 × Rp 3.000) + (5 × Rp 3.500) = Rp 67.500
                </div>
              </div>
            </div>
          </div>

          <div class="price-tier">
            <div class="price-tier__header">
              <div class="price-tier__range">&gt; 100 M³</div>
              <div class="price-tier__price">Rp 4.000 / M³</div>
            </div>
            <div class="price-tier__details">
              <div class="price-tier__calculation">
                <span class="text-xs text-slate-500">Contoh untuk 110 M³:</span>
                <div class="text-sm font-mono text-slate-700 mt-1">
                  (10 × Rp 2.500) + (10 × Rp 3.000) + (80 × Rp 3.500) + (10 × Rp 4.000) = Rp 385.000
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="price-detail-modal__summary">
          <div class="summary__header">
            <font-awesome-icon icon="info-circle" class="text-blue-500 text-sm mr-2" />
            <span class="text-sm font-medium text-slate-700">Catatan:</span>
          </div>
          <ul class="summary__list">
            <li>Harga berlaku untuk pelanggan rumah tangga</li>
            <li>Biaya abodemen tetap Rp 10.000/bulan</li>
            <li>Denda keterlambatan 5% dari total tagihan</li>
          </ul>
        </div>
      </div>

      <div class="price-detail-modal__footer">
        <BaseButton variant="secondary" @click="closeModal"> Tutup </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { watch } from 'vue'
import BaseButton from './BaseButton.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const closeModal = () => {
  emit('close')
}

// Close modal on ESC key
const handleEscKey = (event) => {
  if (event.key === 'Escape') {
    closeModal()
  }
}

// Add/remove event listener
watch(
  () => props.show,
  (newValue) => {
    if (newValue) {
      document.addEventListener('keydown', handleEscKey)
      document.body.style.overflow = 'hidden'
    } else {
      document.removeEventListener('keydown', handleEscKey)
      document.body.style.overflow = ''
    }
  },
)
</script>
