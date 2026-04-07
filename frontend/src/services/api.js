// Base API configuration and services for PAMSIMAS application

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000/api'

// Generic API request function
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

// Tutup Buku API Service
export const tutupBukuService = {
  // Check if book is already closed for a specific year
  async checkBookClosed(year) {
    return apiRequest(`/tutup-buku/check/${year}`)
  },

  // Perform book closing process
  async closeBook(year, data = {}) {
    return apiRequest('/tutup-buku/close', {
      method: 'POST',
      body: JSON.stringify({
        tahun: year,
        ...data,
      }),
    })
  },

  // Get book closing history
  async getClosingHistory() {
    return apiRequest('/tutup-buku/history')
  },

  // Get closing status for specific year
  async getClosingStatus(year) {
    return apiRequest(`/tutup-buku/status/${year}`)
  },
}

// Neraca Saldo API Service
export const neracaSaldoService = {
  // Get balance sheet for specific year
  async getNeracaSaldo(year) {
    return apiRequest(`/neraca-saldo/${year}`)
  },

  // Update account balances after closing
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

// Alokasi Laba API Service
export const alokasiLabaService = {
  // Calculate profit allocation
  async calculateAllocation(year, totalSaldo) {
    return apiRequest('/alokasi-laba/calculate', {
      method: 'POST',
      body: JSON.stringify({
        tahun: year,
        totalSaldo,
      }),
    })
  },

  // Save profit allocation
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

// Export default API configuration
export default {
  API_BASE_URL,
  apiRequest,
  tutupBukuService,
  neracaSaldoService,
  alokasiLabaService,
}
