import api from '@/utils/axios'

/**
 * Customer Service - Mengelola data pelanggan terverifikasi
 */
export const customerService = {
  /**
   * Ambil daftar pelanggan
   * @param {Object} params - { search }
   */
  async getCustomers(params = {}) {
    const response = await api.get('/customers', { params })
    return response.data
  },

  /**
   * Ambil detail pelanggan
   */
  async getCustomerDetail(id) {
    const response = await api.get(`/customers/${id}`)
    return response.data
  },
}

export default customerService
