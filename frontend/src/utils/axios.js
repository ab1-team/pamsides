import axios from 'axios'
import { useUiStore } from '@/stores/uiStore'

const baseURL = (() => {
  if (import.meta.env.VITE_API_BASE_URL) return import.meta.env.VITE_API_BASE_URL
  if (import.meta.env.VITE_BACKEND_URL) return `${import.meta.env.VITE_BACKEND_URL.replace(/\/$/, '')}/api`
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
