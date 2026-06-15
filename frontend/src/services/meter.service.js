import api from '@/utils/axios'

/**
 * Meter Service - Mengelola pencatatan meter bulanan (Teknisi)
 */
export const meterService = {
  /**
   * Ambil daftar pelanggan yang belum dicatat meter bulan ini
   */
  async getPendingReadings(params = {}) {
    const response = await api.get('/meter-readings/pending', { params })
    return response.data
  },

  /**
   * Ambil progres pencatatan meter untuk periode tertentu
   * Return: { total_active, total_recorded, total_pending, is_complete }
   */
  async getProgress(params = {}) {
    const response = await api.get('/meter-readings/progress', { params })
    return response.data
  },

  /**
   * Input angka meter bulanan + foto (Teknisi/Admin)
   */
  async submitReading(formData) {
    const response = await api.post('/meter-readings', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  },
}

export default meterService
