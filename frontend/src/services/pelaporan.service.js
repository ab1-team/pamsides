import api from '@/utils/axios'

/**
 * Pelaporan Service - Menghubungkan modul frontend Pelaporan dengan backend Laravel
 */
export const pelaporanService = {
  /**
   * Ambil master data jenis laporan dan filter tahun awal operasional
   * Endpoint: GET /api/pelaporan
   */
  async getMasterFilter() {
    const response = await api.get('/pelaporan')
    return response.data
  },

  /**
   * Ambil data sub-laporan dinamis berdasarkan jenis laporan yang dipilih
   * Endpoint: GET /api/pelaporan/sub-laporan/{file}
   * @param {string} fileJenisLaporan
   */
  async getSubLaporan(fileJenisLaporan) {
    const response = await api.get(`/pelaporan/sub-laporan/${fileJenisLaporan}`)
    return response.data
  },

  /**
   * Ambil file PDF preview laporan (cover + konten)
   * Endpoint: POST /api/pelaporan/preview
   * @param {Object} data - { tahun, bulan, tanggal, nama_laporan, nama_sub_laporan }
   */
  async getPreview(data = {}) {
    const response = await api.post('/pelaporan/preview', data, {
      responseType: 'json',
    })
    return response.data
  },

  /**
   * Export / Download data laporan ke dalam format Excel (POST)
   * Endpoint: POST /api/pelaporan/excel
   * @param {Object} data - { tahun, bulan, tanggal, nama_laporan, nama_sub_laporan }
   */
  async exportExcel(data = {}) {
    const response = await api.post('/pelaporan/excel', data, {
      responseType: 'blob' // Wajib menggunakan blob untuk handling download file binary dari server
    })
    return response.data
  },

  /**
   * Simpan saldo akhir tahun/bulan berjalan ke database (POST)
   * Endpoint: POST /api/pelaporan/simpan-saldo
   * @param {Object} data - { tahun, bulan, tanggal, nama_laporan, nama_sub_laporan }
   */
  async simpanSaldo(data = {}) {
    const response = await api.post('/pelaporan/simpan-saldo', data)
    return response.data
  },
}

export default pelaporanService
