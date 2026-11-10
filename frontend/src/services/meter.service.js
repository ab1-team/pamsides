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

  /**
   * Reset / ganti meteran (admin saja).
   *
   * Menutup meter lama pada periode ini lalu menandai awal meter baru.
   * Payload: { customer_id, month, year, old_meter_final, new_meter_start, reason }
   */
  async resetMeter(payload = {}) {
    const response = await api.post('/meter-readings/reset', payload)
    return response.data
  },
}

export default meterService
