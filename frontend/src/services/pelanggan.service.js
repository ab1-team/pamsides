import api from '@/utils/axios'

/**
 * Pelanggan Portal Service - Mengelola data untuk portal pelanggan (customer portal)
 */
export const pelangganService = {
  /**
   * Ambil data dashboard pelanggan
   */
  async getDashboardData() {
    const response = await api.get('/pelanggan/dashboard')
    return response.data
  },

  /**
   * Ambil detail tagihan (opsional by ID).
   *
   * PENTING: saat `id` kosong, jangan membangun URL `/pelanggan/bill-detail/`
   * (garis miring menggantung + segmen kosong). Route Laravel punya `{id?}`
   * opsional, dan path seperti itu tidak cocok — hasilnya 404 sehingga
   * cabang "tagihan terbaru" di backend tidak pernah terpakai.
   * Solusinya: panggil endpoint tanpa segmen trailing.
   */
  async getBillDetail(id = null) {
    const path = id ? `/pelanggan/bill-detail/${id}` : '/pelanggan/bill-detail'
    const response = await api.get(path)
    return response.data
  },

  /**
   * Ambil riwayat tagihan
   */
  async getBillHistory() {
    const response = await api.get('/pelanggan/bill-history')
    return response.data
  },

  /**
   * Ambil data profil pelanggan
   */
  async getProfile() {
    const response = await api.get('/pelanggan/profile')
    return response.data
  },
}

export default pelangganService
