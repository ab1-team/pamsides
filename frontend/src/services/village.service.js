import api from '@/utils/axios'

/**
 * Village Service - Mengelola data wilayah desa
 */
export const villageService = {

  async getVillages() {
    return await api.get('/villages')
  },


  async getVillageById(id) {
    return await api.get(`/villages/${id}`)
  },


  async createVillage(data) {
    return await api.post('/villages', data)
  },


  async updateVillage(id, data) {
    return await api.put(`/villages/${id}`, data)
  },


  async deleteVillage(id) {
    return await api.delete(`/villages/${id}`)
  }
}

export default villageService
