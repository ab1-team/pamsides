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

// Pantau perubahan properti show
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
