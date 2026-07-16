import api from '@/utils/axios'

export const komisiSPSService = {
  async getCashAccounts() {
    const response = await api.get('/komisi-sps/cash-accounts')
    return response.data
  },

  async getPenerimaKomisi(params = {}) {
    const response = await api.get('/komisi-sps/penerima-komisi', { params })
    return response.data
  },

  async getPelangganWithUnpaid(params = {}) {
    const response = await api.get('/komisi-sps/pelanggan-unpaid', { params })
    return response.data
  },

  async getUnpaidByCustomer(customerId) {
    const response = await api.get('/komisi-sps/unpaid-by-customer', {
      params: { customer_id: customerId },
    })
    return response.data
  },

  async store(payload) {
    const response = await api.post('/komisi-sps', payload)
    return response.data
  },
}

export default komisiSPSService
