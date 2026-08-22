import api from '@/utils/axios'

/**
 * Profile Service - Mengelola data profil user yang sedang login
 *
 * Catatan untuk tim Backend:
 * - Endpoint /me untuk ambil data user saat ini sudah tersedia (AuthController::me).
 * - Endpoint update profil & password BELUM ADA, perlu dibuat.
 */
export const profileService = {
  /**
   * Ambil data user yang sedang login.
   * Backend (existing): GET /me -> { success, data: { id, name, email, role, ... } }
   */
  async getMe() {
    const response = await api.get('/me')
    return response.data
  },

  /**
   * Update data diri.
   * Payload: { name, email }
   */
  async updateProfile(payload) {
    const response = await api.put('/me', payload)
    return response.data
  },

  /**
   * Update password.
   * Payload: { current_password, new_password, new_password_confirmation }
   */
  async updatePassword(payload) {
    const response = await api.put('/me/password', payload)
    return response.data
  },

  /**
   * Upload foto profil (avatar).
   * Field: 'avatar' (File image) - multipart/form-data
   * Backend diharapkan mengembalikan: { avatar_url: 'https://...' }
   */
  async uploadAvatar(file) {
    const formData = new FormData()
    formData.append('avatar', file)
    const response = await api.post('/me/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },
}

export default profileService
