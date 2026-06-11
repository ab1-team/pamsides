import api from '@/utils/axios'

export const troubleReportService = {
  async submitReport(formData) {
    const response = await api.post('/pelanggan/trouble-report', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  },

  async getReports(params = {}) {
    const response = await api.get('/trouble-reports', { params })
    return response.data
  },

  async getReportDetail(id) {
    const response = await api.get(`/trouble-reports/${id}`)
    return response.data
  },

  async updateStatus(id, payload) {
    const response = await api.patch(`/trouble-reports/${id}/status`, payload)
    return response.data
  },
}

export default troubleReportService
