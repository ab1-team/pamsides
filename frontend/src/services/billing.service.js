import api from '@/utils/axios'

/**
 * Billing Service - Mengelola tagihan bulanan pelanggan
 */
export const billingService = {
  /**
   * Ambil daftar tagihan
   */
  async getBills(_params = {}) {
    // Simulasi delay
    await new Promise((resolve) => setTimeout(resolve, 500))
    return {
      success: true,
      data: [{ id: 'INV-2025-05-001', period: 'Mei 2025', amount: 85000, status: 'unpaid' }],
    }
  },

  /**
   * Generate tagihan bulanan (Admin)
   */
  async generateMonthlyBills(month, year) {
    const response = await api.post('/billing/generate', { month, year })
    return response.data
  },

  /**
   * Konfirmasi pembayaran
   */
  async confirmPayment(billId, paymentData) {
    const response = await api.post(`/billing/${billId}/confirm`, paymentData)
    return response.data
  },
}

export default billingService
