/**
 * Store Pinia untuk manajemen state tagihan
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getCurrentDate } from '../composables/useDateFormat.js'
import { formatRupiah } from '../composables/useFormatCurrency.js'

export const useBillingStore = defineStore('billing', () => {
  // State utama tagihan
  const billingPeriods = ref([])
  const searchQuery = ref('')
  const selectedCustomer = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const searchResults = ref([])

  // Data tiruan pelanggan (sebagai contoh sebelum integrasi API)
  const mockCustomers = [
    {
      name: 'Bambang Susanto',
      id: 'PAM-2025-09821',
      installationCode: 'RT04-RW02',
      village: 'Mojosari',
      hamlet: 'Mojosari Kulon',
      rt: '04',
      rw: '02',
      cater: 'Cater-001',
      status: 'AKTIF',
    },
    {
      name: 'Siti Aminah',
      id: 'PAM-2025-09822',
      installationCode: 'RT03-RW01',
      village: 'Mojosari',
      hamlet: 'Mojosari Tengah',
      rt: '03',
      rw: '01',
      cater: 'Cater-002',
      status: 'AKTIF',
    },
    {
      name: 'Ahmad Wijaya',
      id: 'PAM-2025-09823',
      installationCode: 'RT05-RW02',
      village: 'Mojosari',
      hamlet: 'Mojosari Wetan',
      rt: '05',
      rw: '02',
      cater: 'Cater-003',
      status: 'AKTIF',
    },
  ]

  // Data tiruan riwayat tagihan (sebagai contoh sebelum integrasi API)
  const mockBillingPeriods = [
    {
      id: 1,
      period: 'Mei 2025',
      status: 'LUNAS',
      statusDate: '12 MEI 2025',
      amount: 85000,
      customerName: 'Bambang Susanto',
      customerId: 'PAM-2025-09821',
      installationCode: 'RT04-RW02',
      isExpanded: true,
      type: 'current',
      meterAwal: 1240,
      meterAkhir: 1265,
      pemakaian: 25,
    },
    {
      id: 2,
      period: 'April 2025',
      status: 'TERTUNGGAK',
      statusDate: 'JATUH TEMPO 20 APR 2025',
      amount: 92000,
      customerName: 'Bambang Susanto',
      customerId: 'PAM-2025-09821',
      installationCode: 'RT04-RW02',
      isExpanded: false,
      type: 'overdue',
      meterAwal: 1215,
      meterAkhir: 1240,
      pemakaian: 25,
    },
    {
      id: 3,
      period: 'Maret 2025',
      status: 'MAU PROSES PEMBAYARAN',
      statusDate: '',
      amount: 70000,
      customerName: 'Bambang Susanto',
      customerId: 'PAM-2025-09821',
      installationCode: 'RT04-RW02',
      isExpanded: false,
      type: 'processing',
      meterAwal: 1190,
      meterAkhir: 1215,
      pemakaian: 25,
    },
  ]

  // Data komputasi tagihan yang difilter dan dikelompokkan
  const filteredBillingPeriods = computed(() => {
    if (!searchQuery.value) {
      return billingPeriods.value
    }

    const query = searchQuery.value.toLowerCase()
    return billingPeriods.value.filter(
      (period) =>
        period.customerName.toLowerCase().includes(query) ||
        period.customerId.toLowerCase().includes(query) ||
        period.installationCode.toLowerCase().includes(query) ||
        period.period.toLowerCase().includes(query),
    )
  })

  const currentPeriod = computed(() => {
    return billingPeriods.value.find((period) => period.type === 'current')
  })

  const overduePeriods = computed(() => {
    return billingPeriods.value.filter((period) => period.type === 'overdue')
  })

  const totalOverdueAmount = computed(() => {
    return overduePeriods.value.reduce((total, period) => total + period.amount, 0)
  })

  // Fungsi-fungsi aksi (Actions)
  const fetchBillingPeriods = async (customerId) => {
    loading.value = true
    error.value = null

    try {
      // Simulasi jeda pemanggilan API
      await new Promise((resolve) => setTimeout(resolve, 500))

      // Filter berdasarkan pelanggan jika ID diberikan
      if (customerId) {
        billingPeriods.value = mockBillingPeriods.filter(
          (period) => period.customerId === customerId,
        )
      } else {
        billingPeriods.value = [...mockBillingPeriods]
      }

      // Set pelanggan yang dipilih secara default
      if (billingPeriods.value.length > 0) {
        selectedCustomer.value = {
          name: billingPeriods.value[0].customerName,
          id: billingPeriods.value[0].customerId,
          installationCode: billingPeriods.value[0].installationCode,
          status: 'AKTIF',
        }
      }
    } catch (err) {
      error.value = 'Gagal memuat data billing'
      console.error('Error fetching billing periods:', err)
    } finally {
      loading.value = false
    }
  }

  const togglePeriod = (periodId) => {
    const period = billingPeriods.value.find((p) => p.id === periodId)
    if (period) {
      period.isExpanded = !period.isExpanded
    }
  }

  const updateBillingPeriod = async (periodId, updates) => {
    loading.value = true
    error.value = null

    try {
      // Simulasi jeda pemanggilan API
      await new Promise((resolve) => setTimeout(resolve, 300))

      const periodIndex = billingPeriods.value.findIndex((p) => p.id === periodId)
      if (periodIndex !== -1) {
        billingPeriods.value[periodIndex] = {
          ...billingPeriods.value[periodIndex],
          ...updates,
        }
      }

      return true
    } catch (err) {
      error.value = 'Gagal mengupdate billing period'
      console.error('Error updating billing period:', err)
      return false
    } finally {
      loading.value = false
    }
  }

  const savePayment = async (paymentData) => {
    loading.value = true
    error.value = null

    try {
      // Simulasi jeda pemanggilan API
      await new Promise((resolve) => setTimeout(resolve, 500))

      // Update billing period dengan data pembayaran
      const periodId = paymentData.periodId
      if (periodId) {
        await updateBillingPeriod(periodId, {
          status: 'LUNAS',
          statusDate: getCurrentDate(),
          amount: paymentData.pembayaran,
          type: 'paid',
        })
      }

      return {
        success: true,
        message: 'Pembayaran berhasil disimpan',
        data: paymentData,
      }
    } catch (err) {
      error.value = 'Gagal menyimpan pembayaran'
      console.error('Error saving payment:', err)
      return {
        success: false,
        message: 'Gagal menyimpan pembayaran',
        error: err,
      }
    } finally {
      loading.value = false
    }
  }

  const searchCustomers = (query) => {
    searchQuery.value = query

    if (!query.trim()) {
      searchResults.value = []
      return
    }

    const searchTerm = query.toLowerCase()
    searchResults.value = mockCustomers.filter(
      (customer) =>
        customer.name.toLowerCase().includes(searchTerm) ||
        customer.id.toLowerCase().includes(searchTerm) ||
        customer.installationCode.toLowerCase().includes(searchTerm),
    )
  }

  const selectCustomer = async (customer) => {
    selectedCustomer.value = customer
    searchResults.value = []
    searchQuery.value = customer.name

    // Ambil periode tagihan untuk pelanggan yang dipilih
    await fetchBillingPeriods(customer.id)
  }

  const clearSearch = () => {
    searchQuery.value = ''
  }

  const resetStore = () => {
    billingPeriods.value = []
    searchQuery.value = ''
    selectedCustomer.value = null
    searchResults.value = []
    loading.value = false
    error.value = null
  }

  // Fungsi utilitas bantuan
  const formatAmount = (amount) => {
    return formatRupiah(amount)
  }

  const getPeriodStatusColor = (type) => {
    const colors = {
      overdue: 'text-red-500',
      processing: 'text-amber-600',
      paid: 'text-slate-600',
      current: 'text-green-500',
    }
    return colors[type] || 'text-slate-600'
  }

  const getPeriodStatusBg = (type) => {
    const backgrounds = {
      overdue: 'bg-red-50',
      processing: 'bg-amber-50',
      paid: 'bg-slate-100',
      current: 'bg-green-50',
    }
    return backgrounds[type] || 'bg-slate-100'
  }

  // Inisialisasi store saat pertama dimuat
  const initializeStore = async () => {
    // Data belum di-fetch secara otomatis, menunggu pencarian pengguna
    // await fetchBillingPeriods()
  }

  return {
    // State
    billingPeriods,
    searchQuery,
    selectedCustomer,
    loading,
    error,
    searchResults,

    // Komputasi
    filteredBillingPeriods,
    currentPeriod,
    overduePeriods,
    totalOverdueAmount,

    // Aksi
    fetchBillingPeriods,
    togglePeriod,
    updateBillingPeriod,
    savePayment,
    searchCustomers,
    selectCustomer,
    clearSearch,
    resetStore,
    initializeStore,

    // Utilitas
    formatAmount,
    getPeriodStatusColor,
    getPeriodStatusBg,
  }
})
