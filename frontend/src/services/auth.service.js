import api from '@/utils/axios'

/**
 * Auth Service - Mengelola autentikasi pengguna
 */
export const authService = {
  /**
   * Login pengguna
   * @param {Object} credentials - { username, password }
   */
  async login(credentials) {
    const response = await api.post('/login', credentials)
    return response.data
  },

  /**
   * Logout user
   */
  async logout() {
    const response = await api.post('/logout')
    return response.data
  },

  /**
   * Ambil profile user saat ini
   */
  async getProfile() {
    const response = await api.get('/me')
    return response.data
  },

  /**
   * Ambil data profil pengguna aktif
   */
  async getUser() {
    const response = await api.get('/me')
    return response.data
  },
}

export default authService
