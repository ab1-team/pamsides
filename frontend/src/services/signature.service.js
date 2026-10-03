import api from '@/utils/axios'

/**
 * Signature Service - Manajemen tanda tangan digital laporan.
 *
 * Endpoint mengikuti pola:
 *  GET    /settings/signatures                 -> semua template + URLs
 *  PUT    /settings/signatures                 -> simpan semua template (bulk)
 *  POST   /settings/signatures/image           -> upload image (body: report_key, image data URI)
 *  DELETE /settings/signatures/image           -> hapus image (body: report_key)
 *
 * Backend mengirim `payload.signature` siap-pakai untuk setiap laporan
 * (lihat SignatureService::renderForReport di backend).
 */
export const signatureService = {
  /**
   * Ambil semua template (per report_key) + URL image (null kalau belum ada).
   * Response shape:
   *   {
   *     success: true,
   *     data: {
   *       templates: { default: '<p>...</p>', neraca: '', ... },
   *       images:    { default: null, neraca: 'https://.../storage/signatures/neraca.png', ... },
   *       report_types: { default: 'Default', neraca: 'Neraca', ... },
   *       starter_html: '<p>...</p>'
   *     }
   *   }
   */
  async getAll() {
    const response = await api.get('/settings/signatures')
    return response.data
  },

  /**
   * Simpan template HTML per report_key.
   * Payload: { templates: { neraca: '<p>...</p>', laba_rugi: '', ... } }
   */
  async saveTemplates(payload) {
    const response = await api.put('/settings/signatures', payload)
    return response.data
  },

  /**
   * Upload image tanda tangan untuk report tertentu.
   * Backend memvalidasi data URI inline (PNG/JPG/WebP base64, max 2 MB).
   * @param {string} reportKey
   * @param {string} dataUri data:image/png;base64,...
   */
  async uploadImage(reportKey, dataUri) {
    const response = await api.post('/settings/signatures/image', {
      report_key: reportKey,
      image: dataUri,
    })
    return response.data
  },

  /**
   * Hapus image tanda tangan untuk report tertentu.
   */
  async deleteImage(reportKey) {
    const response = await api.delete('/settings/signatures/image', {
      data: { report_key: reportKey },
    })
    return response.data
  },
}

export default signatureService
