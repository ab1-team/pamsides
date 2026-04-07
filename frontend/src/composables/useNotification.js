import { ref } from 'vue'

const notificationState = ref({
  show: false,
  type: 'info',
  title: '',
  message: '',
  details: [],
  showIcon: true,
  showActions: false,
  showCancel: true,
  confirmText: 'OK',
  cancelText: 'Batal',
  closeOnOverlay: false,
  autoClose: false,
  autoCloseDelay: 3000,
})

let resolvePromise = null
let rejectPromise = null

export function useNotification() {
  const showNotification = (options) => {
    notificationState.value = {
      ...notificationState.value,
      ...options,
      show: true,
    }
  }

  const hideNotification = () => {
    notificationState.value.show = false
  }

  const confirm = (options) => {
    return new Promise((resolve, reject) => {
      resolvePromise = resolve
      rejectPromise = reject

      showNotification({
        type: 'confirm',
        showActions: true,
        closeOnOverlay: false,
        autoClose: false,
        ...options,
      })
    })
  }

  const success = (title, message = '', options = {}) => {
    showNotification({
      type: 'success',
      title,
      message,
      autoClose: true,
      autoCloseDelay: 3000,
      ...options,
    })
  }

  const error = (title, message = '', options = {}) => {
    showNotification({
      type: 'error',
      title,
      message,
      autoClose: true,
      autoCloseDelay: 5000,
      ...options,
    })
  }

  const warning = (title, message = '', options = {}) => {
    showNotification({
      type: 'warning',
      title,
      message,
      autoClose: true,
      autoCloseDelay: 4000,
      ...options,
    })
  }

  const info = (title, message = '', options = {}) => {
    showNotification({
      type: 'info',
      title,
      message,
      autoClose: true,
      autoCloseDelay: 3000,
      ...options,
    })
  }

  const handleConfirm = () => {
    hideNotification()
    if (resolvePromise) {
      resolvePromise(true)
      resolvePromise = null
      rejectPromise = null
    }
  }

  const handleCancel = () => {
    hideNotification()
    if (rejectPromise) {
      rejectPromise(false)
      resolvePromise = null
      rejectPromise = null
    }
  }

  const handleClose = () => {
    hideNotification()
    resolvePromise = null
    rejectPromise = null
  }

  return {
    notificationState,
    showNotification,
    hideNotification,
    confirm,
    success,
    error,
    warning,
    info,
    handleConfirm,
    handleCancel,
    handleClose,
  }
}
