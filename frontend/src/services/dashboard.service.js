import api from '@/utils/axios'

/**
 * Dashboard Service - Mengelola data ringkasan/statistik dashboard utama
 */
export const dashboardService = {
  /**
   * Ambil data statistik ringkasan
   */
  async getStatistics() {
    const response = await api.get('/dashboard/statistics')
    return response.data
  },
}

export default dashboardService
