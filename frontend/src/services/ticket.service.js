import api from '@/utils/axios'

/**
 * Ticket Service - Mengelola tiket instalasi baru
 */
export const ticketService = {
  /**
   * Ambil daftar tiket dengan filter
   * @param {Object} params - { status, search, page }
   */
  async getTickets(params = {}) {
    const response = await api.get('/installation-tickets', { params })
    return response.data
  },

  /**
   * Buat tiket baru
   */
  async createTicket(ticketData) {
    const response = await api.post('/installation-tickets', ticketData)
    return response.data
  },

  /**
   * Ambil detail tiket berdasarkan ID
   */
  async getTicketDetail(id) {
    const response = await api.get(`/installation-tickets/${id}`)
    return response.data
  },

  /**
   * Transisi status tiket
   */
  async transitionStatus(id, status) {
    const response = await api.patch(`/installation-tickets/${id}/transition`, { status })
    return response.data
  },

  /**
   * Input hasil survey (Surveyor)
   */
  async submitSurvey(id, formData) {
    // Pastikan menggunakan form-data untuk upload foto
    const response = await api.post(`/installation-tickets/${id}/survey`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  },

  /**
   * Konfirmasi pembayaran instalasi
   */
  async confirmTicketPayment(id, amount) {
    const response = await api.post(`/installation-tickets/${id}/payment`, { amount })
    return response.data
  },

  /**
   * Input hasil instalasi (Teknisi)
   */
  async submitInstallationResult(id, formData) {
    const response = await api.post(`/installation-tickets/${id}/installation-result`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  },

  /**
   * Aktivasi pelanggan
   */
  async activateCustomer(id) {
    const response = await api.post(`/installation-tickets/${id}/activate`)
    return response.data
  },
}

export default ticketService
