import api from '@/utils/axios'

/**
 * Dashboard Service - Mengelola data ringkasan/statistik dashboard utama
 */
export const dashboardService = {
  /**
   * Ambil data statistik ringkasan
   * @param {Object} params
   * @param {number} [params.year] Tahun fiskal (default tahun saat ini di server)
   * @param {number} [params.month] Bulan (1-12, default bulan saat ini di server)
   */
  async getStatistics(params = {}) {
    const response = await api.get('/dashboard/statistics', { params })
    return response.data
  },
}

export default dashboardService
