import api from '@/utils/axios'

/**
 * Ticket Service - Mengelola tiket instalasi baru
 */
export const ticketService = {
  /**
   * Ambil daftar tiket dengan filter
   * @param {Object} params - { status, search, page }
   */
  async getTickets(_params = {}) {
    // Simulasi delay
    await new Promise((resolve) => setTimeout(resolve, 500))

    // Mock data untuk sementara
    return {
      success: true,
      data: [
        { id: 'TIC-001', customerName: 'Budi Santoso', status: 'pending', createdAt: '2025-05-01' },
        { id: 'TIC-002', customerName: 'Siti Aminah', status: 'surveyed', createdAt: '2025-05-02' },
      ],
    }
  },

  /**
   * Ambil detail tiket berdasarkan ID
   */
  async getTicketDetail(id) {
    const response = await api.get(`/tickets/${id}`)
    return response.data
  },

  /**
   * Input hasil survey
   */
  async submitSurvey(id, surveyData) {
    const response = await api.post(`/tickets/${id}/survey`, surveyData)
    return response.data
  },

  /**
   * Input hasil instalasi (Teknisi)
   */
  async submitInstallation(id, installData) {
    const response = await api.post(`/tickets/${id}/install`, installData)
    return response.data
  },
}

export default ticketService
