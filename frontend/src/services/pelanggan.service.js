import api from '@/utils/axios'

/**
 * Pelanggan Portal Service - Mengelola data untuk portal pelanggan (customer portal)
 */
export const pelangganService = {
  /**
   * Ambil data dashboard pelanggan
   */
  async getDashboardData() {
    const response = await api.get('/pelanggan/dashboard')
    return response.data
  },

  /**
   * Ambil detail tagihan (opsional by ID)
   */
  async getBillDetail(id = '') {
    const response = await api.get(`/pelanggan/bill-detail/${id}`)
    return response.data
  },

  /**
   * Ambil riwayat tagihan
   */
  async getBillHistory() {
    const response = await api.get('/pelanggan/bill-history')
    return response.data
  },

  /**
   * Ambil data profil pelanggan
   */
  async getProfile() {
    const response = await api.get('/pelanggan/profile')
    return response.data
  }
}

export default pelangganService
