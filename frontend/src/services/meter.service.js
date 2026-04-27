import api from '@/utils/axios'

/**
 * Meter Service - Mengelola pencatatan meter bulanan (Teknisi)
 */
export const meterService = {
  /**
   * Ambil daftar pelanggan yang belum dicatat meter bulan ini
   */
  async getPendingReadings() {
    const response = await api.get('/meter-readings/pending')
    return response.data
  },

  /**
   * Input angka meter bulanan + foto (Teknisi)
   */
  async submitReading(formData) {
    // Pastikan menggunakan form-data untuk upload foto
    const response = await api.post('/meter-readings', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
    return response.data
  },
}

export default meterService
