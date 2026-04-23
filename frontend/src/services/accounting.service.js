import api from '@/utils/axios'

/**
 * Accounting Service - Mengelola Tutup Buku, Neraca, dan Laba
 */
export const accountingService = {
  // Tutup Buku
  async checkBookClosed(year) {
    const response = await api.get(`/tutup-buku/check/${year}`)
    return response.data
  },
  async closeBook(year, data = {}) {
    const response = await api.post('/tutup-buku/close', { tahun: year, ...data })
    return response.data
  },

  // Neraca Saldo
  async getNeracaSaldo(year) {
    const response = await api.get(`/neraca-saldo/${year}`)
    return response.data
  },

  // Alokasi Laba
  async calculateAllocation(year, totalSaldo) {
    const response = await api.post('/alokasi-laba/calculate', { tahun: year, totalSaldo })
    return response.data
  },
}

export default accountingService
