import api from '@/utils/axios'

/**
 * Billing Service - Mengelola tagihan bulanan pelanggan
 */
export const billingService = {
  /**
   * Ambil daftar tagihan
   */
  async getBills(params = {}) {
    const response = await api.get('/monthly-bills', { params })
    return response.data
  },

  /**
   * Ambil rekap tagihan
   */
  async getRecap(params = {}) {
    const response = await api.get('/bills/recap', { params })
    return response.data
  },

  /**
   * Generate tagihan bulanan (Admin)
   */
  async generateMonthlyBills(params = {}) {
    // Backend mendukung POST /monthly-bills/generate (otomatis bulan ini)
    // atau POST /bills/generate (dengan parameter year & month)
    const endpoint = params.year && params.month ? '/bills/generate' : '/monthly-bills/generate'
    const response = await api.post(endpoint, params)
    return response.data
  },

  /**
   * Detail tagihan
   */
  async getBillDetail(id) {
    const response = await api.get(`/bills/${id}`)
    return response.data
  },

  /**
   * Konfirmasi pembayaran tagihan
   */
  async confirmPayment(billId) {
    const response = await api.post(`/monthly-bills/${billId}/pay`)
    return response.data
  },

  /**
   * Laporan tagihan per periode
   */
  async getBillingReport(params = {}) {
    const response = await api.get('/reports/bills', { params })
    return response.data
  },
}

export default billingService
