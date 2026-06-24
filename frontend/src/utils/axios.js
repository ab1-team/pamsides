import axios from 'axios'
import { useUiStore } from '@/stores/uiStore'

const axiosInstance = axios.create({
  baseURL: '/api',
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
