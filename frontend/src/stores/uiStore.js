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
  const userData = ref(JSON.parse(localStorage.getItem('user_data')) || null)
  const lembagaName = ref('')
  const settingsVersion = ref(0)

  // Modal State
  const activeModalCount = ref(0)

  // Toast State
  const toastMessage = ref(null)

  const openModal = () => activeModalCount.value++
  const closeModal = () => activeModalCount.value = Math.max(0, activeModalCount.value - 1)
  const hasActiveModal = () => activeModalCount.value > 0

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

  const setUserData = (data) => {
    userData.value = data
    localStorage.setItem('user_data', JSON.stringify(data))
  }

  const setLembagaName = (name) => {
    lembagaName.value = name || ''
  }

  const bumpSettings = () => {
    settingsVersion.value++
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
    userData,
    setUserData,
    lembagaName,
    setLembagaName,
    settingsVersion,
    bumpSettings,
    openModal,
    closeModal,
    hasActiveModal,
    activeModalCount,
  }
})
