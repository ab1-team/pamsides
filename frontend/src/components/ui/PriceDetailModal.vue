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

<style scoped>
/* =============================================
   PRICE DETAIL MODAL - COMPONENT
   ============================================= */

.price-detail-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.price-detail-modal {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.price-detail-modal__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  background: linear-gradient(to right, #f8fafc, #f1f5f9);
}

.price-detail-modal__content {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.price-tier {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
  padding: 1rem;
  transition: all 0.2s ease;
}

.price-tier:hover {
  border-color: #0b7a9e;
  box-shadow: 0 4px 6px -1px rgba(11, 122, 158, 0.1);
}

.price-tier__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.75rem;
}

.price-tier__range {
  font-weight: 600;
  color: #334155;
  font-size: 0.875rem;
}

.price-tier__price {
  font-weight: 700;
  color: #0b7a9e;
  font-size: 0.875rem;
  background: rgba(11, 122, 158, 0.1);
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
}

.price-tier__details {
  border-top: 1px solid #e2e8f0;
  padding-top: 0.75rem;
}

.price-tier__calculation {
  font-family: 'Courier New', monospace;
}

.price-detail-modal__summary {
  margin-top: 1.5rem;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border-radius: 0.75rem;
  padding: 1rem;
}

.summary__header {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
}

.summary__list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.summary__list li {
  font-size: 0.75rem;
  margin-bottom: 0.25rem;
  opacity: 0.9;
}

.summary__list li::before {
  content: '•';
  margin-right: 0.5rem;
  opacity: 0.7;
}

.price-detail-modal__footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  justify-content: flex-end;
}

@media (max-width: 640px) {
  .price-detail-modal {
    margin: 0;
    border-radius: 1rem 1rem 0 0;
    max-height: 85vh;
  }

  .price-detail-modal-overlay {
    align-items: flex-end;
    padding: 0;
  }
}
</style>
