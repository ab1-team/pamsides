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

  /**
   * Buat pelanggan baru
   * @param {Object} data - Data pelanggan
   */
  async createCustomer(data) {
    const response = await api.post('/customers', data)
    return response.data
  },

  /**
   * Perbarui data pelanggan
   * @param {string|number} id - ID pelanggan
   * @param {Object} data - Data pelanggan
   */
  async updateCustomer(id, data) {
    const response = await api.put(`/customers/${id}`, data)
    return response.data
  },

  /**
   * Hapus data pelanggan
   * @param {string|number} id - ID pelanggan
   */
  async deleteCustomer(id) {
    const response = await api.delete(`/customers/${id}`)
    return response.data
  },
}

export default customerService
