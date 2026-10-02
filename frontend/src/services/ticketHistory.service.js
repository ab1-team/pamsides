import api from '@/utils/axios'

/**
 * Ticket History Service
 *
 * Endpoint terkait perubahan paket pelanggan:
 *   - GET   /installation-tickets/{id}/history       → detail + history paket
 *   - POST  /installation-tickets/{id}/change-package → update paket + snapshot
 */
export const ticketHistoryService = {
  /**
   * Ambil detail tiket + paket aktif + history snapshot paket lama
   * + ringkasan tagihan customer.
   *
   * @param {number|string} ticketId
   * @returns {Promise<{
   *   ticket, customer, current_package, history, billing_summary
   * }>}
   */
  async getDetail(ticketId) {
    const res = await api.get(`/installation-tickets/${ticketId}/history`)
    return res.data
  },

  /**
   * Update paket tiket + catat history snapshot paket lama.
   *
   * @param {number|string} ticketId
   * @param {{ new_package_id: number|string, reason?: string }} payload
   */
  async changePackage(ticketId, payload) {
    const res = await api.post(
      `/installation-tickets/${ticketId}/change-package`,
      payload,
    )
    return res.data
  },
}

export default ticketHistoryService