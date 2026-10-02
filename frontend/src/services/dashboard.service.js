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

  /**
   * Ambil data untuk popup 4 kotak di dashboard admin.
   * Endpoint ringkas dengan filter & paginasi server-side — JAUH lebih cepat
   * dari getAllBills() / getTickets({ per_page: 100 }) yang menarik semua
   * halaman lalu filter di client.
   *
   * @param {string} type  Salah satu: 'instalasi' | 'tunggakan' | 'tagihan' | 'pemakaian'
   * @param {Object} params { page, per_page, search, month, year }
   */
  async getPopupData(type, params = {}) {
    const response = await api.get('/dashboard/popup-data', {
      params: { type, ...params },
    })
    return response.data
  },

  async getNotification() {
    const response = await api.get('/dashboard/notification')
    return response.data
  },

  async dismissNotification() {
    const response = await api.post('/dashboard/notification/dismiss')
    return response.data
  },

  /**
   * Auto-generate piutang/abodemen/denda untuk tagihan menunggak
   * ketika hari ini == toleransiTunggakan (dari SOP).
   * Idempotent per (bulan, user). Backend skip kalau sudah pernah
   * dijalankan di bulan ini untuk user ini.
   */
  async autoGenerateOverdue() {
    const response = await api.post('/dashboard/auto-generate-overdue')
    return response.data
  },
}

export default dashboardService
