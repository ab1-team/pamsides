import api from '@/utils/axios'

/**
 * SOP Service - Mengelola pengaturan personalisasi SOP
 *
 * Catatan untuk tim Backend:
 * - Setiap section dikirim terpisah agar mudah dibedakan endpoint-nya.
 * - Endpoint default mengikuti pola: /settings/sop/{section}
 * - Untuk upload logo memakai multipart/form-data.
 */
export const sopService = {
  /**
   * Ambil seluruh pengaturan SOP (untuk inisialisasi form)
   */
  async getAll() {
    const response = await api.get('/settings/sop')
    return response.data
  },

  /**
   * Profil Lembaga
   * Payload: { nama, alamat, email, telepon, website, deskripsi }
   */
  async saveLembaga(payload) {
    const response = await api.post('/settings/sop/lembaga', payload)
    return response.data
  },

  /**
   * Aturan Pasang Baru
   * Payload: { biayaPasang, statusPembayaran, enableAir, enableSampah }
   */
  async savePasangBaru(payload) {
    const response = await api.post('/settings/sop/pasang-baru', payload)
    return response.data
  },

  /**
   * Sistem Tagihan
   * Payload: { jatuhTempo, toleransiTunggakan }
   */
  async saveSistemTagihan(payload) {
    const response = await api.post('/settings/sop/sistem-tagihan', payload)
    return response.data
  },

  /**
   * Logo & Branding
   * Mengirim file via multipart/form-data.
   * Field yang dikirim hanya yang berupa File (yang baru diupload).
   * @param {{ mainLogo?: File, dashboardLogo?: File, favicon?: File }} files
   */
  async saveLogo(files) {
    const formData = new FormData()
    if (files.mainLogo instanceof File) formData.append('mainLogo', files.mainLogo)
    if (files.dashboardLogo instanceof File) formData.append('dashboardLogo', files.dashboardLogo)
    if (files.favicon instanceof File) formData.append('favicon', files.favicon)

    const response = await api.post('/settings/sop/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  /**
   * Template WhatsApp
   * Payload: { templateTagihan, templatePembayaran }
   */
  async saveWhatsapp(payload) {
    const response = await api.post('/settings/sop/whatsapp', payload)
    return response.data
  },
}

export default sopService
