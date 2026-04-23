// Konfigurasi dasar API dan layanan untuk aplikasi PAMSIMAS

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000/api'

// Fungsi umum untuk permintaan API
const apiRequest = async (endpoint, options = {}) => {
  const url = `${API_BASE_URL}${endpoint}`

  const config = {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
    ...options,
  }

  try {
    const response = await fetch(url, config)

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
    }

    return await response.json()
  } catch (error) {
    console.error('API Request Error:', error)
    throw error
  }
}

// Layanan API untuk Tutup Buku
export const tutupBukuService = {
  // Cek apakah buku sudah ditutup untuk tahun tertentu
  async checkBookClosed(year) {
    return apiRequest(`/tutup-buku/check/${year}`)
  },

  // Eksekusi proses tutup buku
  async closeBook(year, data = {}) {
    return apiRequest('/tutup-buku/close', {
      method: 'POST',
      body: JSON.stringify({
        tahun: year,
        ...data,
      }),
    })
  },

  // Ambil riwayat tutup buku
  async getClosingHistory() {
    return apiRequest('/tutup-buku/history')
  },

  // Ambil status penutupan untuk tahun tertentu
  async getClosingStatus(year) {
    return apiRequest(`/tutup-buku/status/${year}`)
  },
}

// Layanan API untuk Neraca Saldo
export const neracaSaldoService = {
  // Ambil neraca saldo untuk tahun tertentu
  async getNeracaSaldo(year) {
    return apiRequest(`/neraca-saldo/${year}`)
  },

  // Perbarui saldo akun setelah penutupan
  async updateBalances(year, balances) {
    return apiRequest('/neraca-saldo/update', {
      method: 'POST',
      body: JSON.stringify({
        tahun: year,
        balances,
      }),
    })
  },
}

// Layanan API untuk Alokasi Laba
export const alokasiLabaService = {
  // Hitung alokasi laba
  async calculateAllocation(year, totalSaldo) {
    return apiRequest('/alokasi-laba/calculate', {
      method: 'POST',
      body: JSON.stringify({
        tahun: year,
        totalSaldo,
      }),
    })
  },

  // Simpan data alokasi laba
  async saveAllocation(year, allocationData) {
    return apiRequest('/alokasi-laba/save', {
      method: 'POST',
      body: JSON.stringify({
        tahun: year,
        allocation: allocationData,
      }),
    })
  },
}

// Ekspor konfigurasi API utama
export default {
  API_BASE_URL,
  apiRequest,
  tutupBukuService,
  neracaSaldoService,
  alokasiLabaService,
}
