import axios from 'axios'
import { useUiStore } from '@/stores/uiStore'

const baseURL = (() => {
  if (import.meta.env.VITE_API_BASE_URL) return import.meta.env.VITE_API_BASE_URL
  if (import.meta.env.VITE_BACKEND_URL)
    return `${import.meta.env.VITE_BACKEND_URL.replace(/\/$/, '')}/api`
  if (typeof window !== 'undefined') {
    const { protocol, hostname } = window.location
    return `${protocol}//${hostname}/api`
  }
  return '/api'
})()

const axiosInstance = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: false,
  // Timeout generously: proses simpan tagihan involves insert jurnal + trigger
  // `amount`, which historically took 3-8 detik. Default axios (0 = no timeout)
  // meant a hung request left the UI spinning forever with no feedback; 60s is
  // far above normal latency but still bounded so the user eventually gets a
  // retryable error instead of an infinite spinner.
  timeout: 60000,
})

axiosInstance.interceptors.request.use(
  (config) => {
    const uiStore = useUiStore()
    uiStore.setLoading(true)

    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    const uiStore = useUiStore()
    uiStore.setLoading(false)
    return Promise.reject(error)
  },
)

axiosInstance.interceptors.response.use(
  (response) => {
    const uiStore = useUiStore()
    uiStore.setLoading(false)
    return response
  },
  (error) => {
    const uiStore = useUiStore()
    uiStore.setLoading(false)

    if (error.response && error.response.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      localStorage.removeItem('user_role')
      localStorage.removeItem('auth_expires_at')

      if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
        window.location.href = '/login?logout=expired'
      }
    }

    return Promise.reject(error)
  },
)

export default axiosInstance
