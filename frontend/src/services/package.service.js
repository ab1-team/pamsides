import api from '@/utils/axios'

/**
 * Package Service - Mengelola paket instalasi dan blok tarif
 */
export const packageService = {
  /**
   * Ambil daftar semua paket
   */
  async getPackages() {
    const response = await api.get('/installation-packages')
    return response.data
  },

  /**
   * Tambah paket baru
   */
  async createPackage(packageData) {
    const response = await api.post('/installation-packages', packageData)
    return response.data
  },

  /**
   * Detail paket
   */
  async getPackageDetail(id) {
    const response = await api.get(`/installation-packages/${id}`)
    return response.data
  },

  /**
   * Update paket
   */
  async updatePackage(id, packageData) {
    const response = await api.put(`/installation-packages/${id}`, packageData)
    return response.data
  },

  /**
   * Hapus paket
   */
  async deletePackage(id) {
    const response = await api.delete(`/installation-packages/${id}`)
    return response.data
  },

  /**
   * Ambil daftar blok tarif dalam suatu paket
   */
  async getTariffBlocks(packageId) {
    const response = await api.get(`/installation-packages/${packageId}/water-tariff-blocks`)
    return response.data
  },

  /**
   * Tambah blok tarif baru
   */
  async createTariffBlock(packageId, blockData) {
    const response = await api.post(`/installation-packages/${packageId}/water-tariff-blocks`, blockData)
    return response.data
  },

  /**
   * Update blok tarif
   */
  async updateTariffBlock(packageId, blockId, blockData) {
    const response = await api.put(`/installation-packages/${packageId}/water-tariff-blocks/${blockId}`, blockData)
    return response.data
  },

  /**
   * Hapus blok tarif
   */
  async deleteTariffBlock(packageId, blockId) {
    const response = await api.delete(`/installation-packages/${packageId}/water-tariff-blocks/${blockId}`)
    return response.data
  },
}

export default packageService
