import api from '@/utils/axios'

/**
 * COA Service - Mengelola data Chart of Accounts per level
 */
export const coaService = {
  async getAccounts(params = {}) {
    const response = await api.get('/accounts', { params })
    return response.data
  },

  async getByLevel(level) {
    const response = await api.get(`/accounts/by-level/${level}`)
    return response.data
  },
}

export default coaService
