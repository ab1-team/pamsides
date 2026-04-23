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
    // Simulasi delay untuk UX loading
    await new Promise((resolve) => setTimeout(resolve, 800))

    // Untuk saat ini masih mock, nanti ganti ke:
    // const response = await api.post('/login', credentials)
    // return response.data

    return {
      success: true,
      token: 'mock-token-123',
      user: {
        id: 1,
        username: credentials.username,
        role: credentials.username.toLowerCase().includes('surveyor')
          ? 'surveyor'
          : credentials.username.toLowerCase().includes('teknisi')
            ? 'teknisi'
            : credentials.username.toLowerCase().includes('pelanggan')
              ? 'pelanggan'
              : 'admin',
      },
    }
  },

  /**
   * Logout pengguna
   */
  async logout() {
    const response = await api.post('/logout')
    return response.data
  },

  /**
   * Ambil data profil pengguna aktif
   */
  async getUser() {
    const response = await api.get('/user')
    return response.data
  },
}

export default authService
