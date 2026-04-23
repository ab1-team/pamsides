import axios from 'axios'
import { useUiStore } from '@/stores/uiStore'

/**
 * Konfigurasi Axios Instance
 * Digunakan untuk komunikasi API dengan backend Laravel
 */
const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
})

/**
 * Request Interceptor
 */
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

/**
 * Response Interceptor
 */
axiosInstance.interceptors.response.use(
  (response) => {
    const uiStore = useUiStore()
    uiStore.setLoading(false)
    return response
  },
  (error) => {
    const uiStore = useUiStore()
    uiStore.setLoading(false)

    // Handle error unauthorized
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_data')
      uiStore.error('Sesi Anda telah berakhir. Silakan login kembali.')

      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    } else {
      // Handle generic errors
      const message = error.response?.data?.message || 'Terjadi kesalahan pada server'
      uiStore.error(message)
    }

    return Promise.reject(error)
  },
)

export default axiosInstance
