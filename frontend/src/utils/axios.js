import axios from 'axios'
import { useUiStore } from '@/stores/uiStore'

const axiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
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

let isRefreshing = false
let failedQueue = []

const processQueue = (error, token = null) => {
  failedQueue.forEach(({ resolve, reject }) => {
    if (error) {
      reject(error)
    } else {
      resolve(token)
    }
  })
  failedQueue = []
}

const clearAuth = () => {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user_data')
  localStorage.removeItem('user_role')
  localStorage.removeItem('auth_expires_at')
}

const redirectToLogin = (reason) => {
  if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
    window.location.href = `/login?logout=${reason}`
  }
}

axiosInstance.interceptors.response.use(
  (response) => {
    const uiStore = useUiStore()
    uiStore.setLoading(false)
    return response
  },
  async (error) => {
    const uiStore = useUiStore()
    uiStore.setLoading(false)

    const originalRequest = error.config

    if (!error.response) {
      return Promise.reject(error)
    }

    if (error.response.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject })
        })
          .then((token) => {
            originalRequest.headers.Authorization = `Bearer ${token}`
            return axiosInstance(originalRequest)
          })
          .catch((err) => Promise.reject(err))
      }

      originalRequest._retry = true
      isRefreshing = true

      const token = localStorage.getItem('auth_token')
      if (!token) {
        isRefreshing = false
        clearAuth()
        redirectToLogin('expired')
        return Promise.reject(error)
      }

      try {
        const { data } = await axios.post(
          `${axiosInstance.defaults.baseURL}/refresh`,
          {},
          { headers: { Authorization: `Bearer ${token}` } },
        )

        const newToken = data?.data?.token
        const expiresAt = data?.data?.expires_at

        if (!newToken) {
          throw new Error('Refresh response missing token')
        }

        localStorage.setItem('auth_token', newToken)
        if (expiresAt) {
          localStorage.setItem('auth_expires_at', String(expiresAt))
        }

        processQueue(null, newToken)
        originalRequest.headers.Authorization = `Bearer ${newToken}`
        return axiosInstance(originalRequest)
      } catch (refreshError) {
        processQueue(refreshError, null)
        clearAuth()
        redirectToLogin('expired')
        return Promise.reject(refreshError)
      } finally {
        isRefreshing = false
      }
    }

    if (error.response.status === 401) {
      clearAuth()
      redirectToLogin('expired')
    }

    return Promise.reject(error)
  },
)

export default axiosInstance
