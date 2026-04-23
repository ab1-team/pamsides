import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * UI Store - Mengelola state UI global seperti loading dan notifikasi
 */
export const useUiStore = defineStore('ui', () => {
  // State
  const loading = ref(false)
  const activeRequests = ref(0)
  const userRole = ref(localStorage.getItem('user_role') || 'admin')

  // Toast State
  const toastMessage = ref(null)

  // Actions
  const setLoading = (status) => {
    if (status) {
      activeRequests.value++
      loading.value = true
    } else {
      activeRequests.value = Math.max(0, activeRequests.value - 1)
      if (activeRequests.value === 0) {
        loading.value = false
      }
    }
  }

  const showToast = (severity, summary, detail, life = 3000) => {
    toastMessage.value = { severity, summary, detail, life, timestamp: Date.now() }
  }

  const success = (detail, summary = 'Berhasil') => showToast('success', summary, detail)
  const error = (detail, summary = 'Kesalahan') => showToast('error', summary, detail)
  const info = (detail, summary = 'Informasi') => showToast('info', summary, detail)
  const warn = (detail, summary = 'Peringatan') => showToast('warn', summary, detail)

  const setUserRole = (role) => {
    userRole.value = role
    localStorage.setItem('user_role', role)
  }

  return {
    loading,
    toastMessage,
    setLoading,
    showToast,
    success,
    error,
    info,
    warn,
    userRole,
    setUserRole,
  }
})
