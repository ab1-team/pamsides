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
  async getAccountsWithSaldo(year) {
    const response = await api.get(`/tutup-buku/accounts/${year}`)
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
  async checkAllocation(year) {
    const response = await api.get(`/alokasi-laba/check/${year}`)
    return response.data
  },
  async saveAllocation(year, items) {
    const response = await api.post('/alokasi-laba/save', { tahun: year, items })
    return response.data
  },
  async getAllocationConfig() {
    const response = await api.get('/alokasi-laba/config')
    return response.data
  },
}

export default accountingService
