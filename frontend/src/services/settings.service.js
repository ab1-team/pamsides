import api from '@/utils/axios'

export const settingsService = {
  async getKecamatan() {
    const response = await api.get('/settings/kecamatan')
    return response.data
  },

  async getDesa(params = {}) {
    const response = await api.get('/settings/desa', { params })
    return response.data
  },
}

export default settingsService
