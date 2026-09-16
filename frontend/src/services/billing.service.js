import api from '@/utils/axios'

/**
 * Billing Service - Mengelola tagihan bulanan pelanggan
 */
export const billingService = {
  /**
   * Ambil daftar tagihan (1 halaman, sesuai response paginator backend)
   */
  async getBills(params = {}) {
    const response = await api.get('/monthly-bills', { params })
    return response.data
  },

  /**
   * Ambil SELURUH tagihan dengan melakukan loop paging otomatis.
   * Gunakan hanya bila konsumen benar-benar butuh daftar lengkap
   * (mis. daftar tagihan admin, arsip, riwayat tagihan pelanggan).
   * Backend default per_page=50 dan di-cap 200, jadi aman secara memori.
   */
  async getAllBills(params = {}, { pageSize = 200, maxPages = 200 } = {}) {
    const safeParams = { ...params }
    delete safeParams.page
    delete safeParams.per_page
    safeParams.per_page = pageSize

    const first = await this.getBills({ ...safeParams, page: 1 })
    if (!first?.success) {
      return first
    }

    const firstBills = first?.data?.bills || []
    const lastPage = first?.meta?.last_page || 1

    if (lastPage <= 1) {
      return first
    }

    const limit = Math.min(lastPage, maxPages)
    const rest = []
    for (let p = 2; p <= limit; p++) {
      const next = await this.getBills({ ...safeParams, page: p })
      const items = next?.data?.bills || []
      rest.push(...items)
    }

    return {
      ...first,
      data: {
        ...first.data,
        bills: [...firstBills, ...rest],
      },
      meta: {
        ...first.meta,
        last_page: lastPage,
        fetched_pages: limit,
        total: first?.meta?.total ?? firstBills.length + rest.length,
      },
    }
  },

  /**
   * Ambil data pemakaian air bulanan (gabungan pelanggan, meter, tagihan)
   */
  async getUsageList(params = {}) {
    const response = await api.get('/monthly-bills/usage', { params })
    return response.data
  },

  /**
   * Ambil rekap tagihan
   */
  async getRecap(params = {}) {
    const response = await api.get('/bills/recap', { params })
    return response.data
  },

  /**
   * Generate tagihan bulanan (Admin)
   */
  async generateMonthlyBills(params = {}) {
    const endpoint = params.year && params.month ? '/bills/generate' : '/monthly-bills/generate'
    const response = await api.post(endpoint, params)
    return response.data
  },

  /**
   * Detail tagihan
   */
  async getBillDetail(id) {
    const response = await api.get(`/bills/${id}`)
    return response.data
  },

  /**
   * Hapus tagihan (soft delete)
   */
  async deleteBill(id) {
    const response = await api.delete(`/monthly-bills/${id}`)
    return response.data
  },

  /**
   * Konfirmasi pembayaran tagihan
   */
  async confirmPayment(billId, payload = {}) {
    const response = await api.post(`/monthly-bills/${billId}/pay`, payload)
    return response.data
  },

  /**
   * Laporan tagihan per periode
   */
  async getBillingReport(params = {}) {
    const response = await api.get('/reports/bills', { params })
    return response.data
  },

  // Daftar pelanggan suspended + tagihan unpaid (teknisi)
  async getSuspendedCustomers() {
    const response = await api.get('/monthly-bills/suspended')
    return response.data
  },

  // Teknisi: konfirmasi restore suspended → completed (setelah admin bayar lunas)
  async restoreCustomer(customerId) {
    const response = await api.post(`/customers/${customerId}/restore`)
    return response.data
  },
}

export default billingService
