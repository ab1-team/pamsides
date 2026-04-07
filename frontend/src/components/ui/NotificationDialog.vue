<template>
  <transition name="notification" appear>
    <div v-if="show" class="notification-overlay" @click="handleOverlayClick">
      <div
        class="notification-dialog"
        :class="[`type-${type}`, { 'with-icon': showIcon }]"
        @click.stop
      >
        <div class="notification-header">
          <div v-if="showIcon" class="notification-icon">
            <span v-if="type === 'success'">✅</span>
            <span v-else-if="type === 'error'">❌</span>
            <span v-else-if="type === 'warning'">⚠️</span>
            <span v-else>ℹ️</span>
          </div>
          <h3 class="notification-title">{{ title }}</h3>
          <button class="notification-close" @click="handleClose" aria-label="Close notification">
            ✕
          </button>
        </div>

        <div class="notification-content">
          <p v-if="message" class="notification-message">{{ message }}</p>
          <div v-if="details" class="notification-details">
            <div v-for="(detail, index) in details" :key="index" class="detail-item">
              <span class="detail-bullet">•</span>
              <span class="detail-text">{{ detail }}</span>
            </div>
          </div>
        </div>

        <div v-if="showActions" class="notification-actions">
          <button v-if="showCancel" class="btn btn-cancel" @click="handleCancel">
            {{ cancelText }}
          </button>
          <button class="btn btn-primary" @click="handleConfirm">
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info', 'confirm'].includes(value),
  },
  title: {
    type: String,
    required: true,
  },
  message: {
    type: String,
    default: '',
  },
  details: {
    type: Array,
    default: () => [],
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
  showActions: {
    type: Boolean,
    default: false,
  },
  showCancel: {
    type: Boolean,
    default: true,
  },
  confirmText: {
    type: String,
    default: 'OK',
  },
  cancelText: {
    type: String,
    default: 'Batal',
  },
  closeOnOverlay: {
    type: Boolean,
    default: false,
  },
  autoClose: {
    type: Boolean,
    default: false,
  },
  autoCloseDelay: {
    type: Number,
    default: 3000,
  },
})

const emit = defineEmits(['close', 'confirm', 'cancel'])

let autoCloseTimer = null

const handleClose = () => {
  emit('close')
}

const handleConfirm = () => {
  emit('confirm')
  handleClose()
}

const handleCancel = () => {
  emit('cancel')
  handleClose()
}

const handleOverlayClick = () => {
  if (props.closeOnOverlay) {
    handleClose()
  }
}

const startAutoClose = () => {
  if (props.autoClose && !props.showActions) {
    autoCloseTimer = setTimeout(() => {
      handleClose()
    }, props.autoCloseDelay)
  }
}

const clearAutoClose = () => {
  if (autoCloseTimer) {
    clearTimeout(autoCloseTimer)
    autoCloseTimer = null
  }
}

onMounted(() => {
  if (props.show) {
    startAutoClose()
  }
})

onUnmounted(() => {
  clearAutoClose()
})

// Watch for show prop changes
const { show } = toRefs(props)
watch(show, (newShow) => {
  if (newShow) {
    startAutoClose()
  } else {
    clearAutoClose()
  }
})
</script>

<script>
import { toRefs, watch } from 'vue'
</script>

<style scoped>
.notification-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.notification-dialog {
  background: white;
  border-radius: 16px;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  border: 2px solid transparent;
}

.notification-dialog.type-success {
  border-color: #10b981;
}

.notification-dialog.type-error {
  border-color: #ef4444;
}

.notification-dialog.type-warning {
  border-color: #f59e0b;
}

.notification-dialog.type-info {
  border-color: #3b82f6;
}

.notification-dialog.type-confirm {
  border-color: #0b7a9e;
}

.notification-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.notification-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.notification-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  flex: 1;
}

.notification-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.15s;
  flex-shrink: 0;
}

.notification-close:hover {
  background: #f1f5f9;
  color: #64748b;
}

.notification-content {
  padding: 1rem 1.5rem;
}

.notification-message {
  font-size: 0.95rem;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 1rem 0;
}

.notification-details {
  margin-top: 0.75rem;
}

.detail-item {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.detail-bullet {
  color: #94a3b8;
  margin-top: 2px;
  flex-shrink: 0;
}

.detail-text {
  font-size: 0.85rem;
  color: #64748b;
  line-height: 1.4;
}

.notification-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  padding: 1rem 1.5rem 1.5rem;
  border-top: 1px solid #f1f5f9;
}

.btn {
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-family: inherit;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  border: none;
}

.btn-cancel {
  background: #f8fafc;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.btn-cancel:hover {
  background: #f1f5f9;
  color: #475569;
}

.btn-primary {
  background: #0b7a9e;
  color: white;
}

.btn-primary:hover {
  background: #0a6a8a;
}

.notification-enter-active,
.notification-leave-active {
  transition: all 0.3s ease;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

.notification-enter-active .notification-dialog,
.notification-leave-active .notification-dialog {
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.notification-enter-from .notification-dialog,
.notification-leave-to .notification-dialog {
  transform: scale(0.7) translateY(-20px);
}

@media (max-width: 640px) {
  .notification-dialog {
    margin: 1rem;
    max-width: calc(100vw - 2rem);
  }

  .notification-header {
    padding: 1.25rem 1.25rem 0.75rem;
  }

  .notification-content {
    padding: 0.75rem 1.25rem;
  }

  .notification-actions {
    padding: 0.75rem 1.25rem 1.25rem;
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>
