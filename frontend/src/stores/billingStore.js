/**
 * Store Pinia untuk manajemen state tagihan
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { getCurrentDate } from '../composables/useDateFormat.js'
import { formatRupiah } from '../composables/useFormatCurrency.js'

import { customerService } from '@/services/customer.service'
import { billingService } from '@/services/billing.service'

export const useBillingStore = defineStore('billing', () => {
  // State utama tagihan
  const billingPeriods = ref([])
  const searchQuery = ref('')
  const selectedCustomer = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const searchResults = ref([])

  const filteredBillingPeriods = computed(() => {
    // Sembunyikan yang sudah lunas
    const unpaidPeriods = billingPeriods.value.filter(
      (period) => period.type !== 'paid' && period.status !== 'LUNAS'
    )
    
    if (!searchQuery.value) {
      return unpaidPeriods
    }

    const query = searchQuery.value.toLowerCase()
    return unpaidPeriods.filter(
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
      if (!customerId) {
        billingPeriods.value = []
        return
      }

      const res = await billingService.getBills({ customer_id: customerId })
      
      const monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
      ]

      if (res.success && res.data) {
        billingPeriods.value = res.data.bills.map((bill, index) => {
          let type = 'current'
          if (bill.status === 'unpaid') {
            type = new Date(bill.due_date) < new Date() ? 'overdue' : 'processing'
          } else if (bill.status === 'paid') {
            type = 'paid'
          }

          return {
            id: bill.id,
            period: `${monthNames[bill.billing_period_month - 1]} ${bill.billing_period_year}`,
            status: bill.status === 'paid' ? 'LUNAS' : (type === 'overdue' ? 'TERTUNGGAK' : 'BELUM DIBAYAR'),
            statusDate: bill.status === 'paid' ? '' : `JATUH TEMPO ${new Date(bill.due_date).toLocaleDateString('id-ID')}`,
            amount: Number(bill.total_amount),
            abodemen: Number(bill.abodemen),
            denda: Number(bill.penalty_amount),
            usage_charge: Number(bill.usage_charge),
            customerName: bill.customer?.ticket?.applicant_name || bill.customer?.user?.name || 'Pelanggan',
            customerId: bill.customer?.customer_code || '-',
            installationCode: bill.customer?.customer_code || '-',
            isExpanded: false,
            type: type,
            meterAwal: bill.meter_reading_start || 0,
            meterAkhir: bill.meter_reading_end || 0,
            pemakaian: bill.usage_m3 || 0,
            payments: bill.bill_payments ? bill.bill_payments.map((p) => ({
              id: p.id,
              amount: Number(p.amount_paid),
              paidAt: p.paid_at ? new Date(p.paid_at).toLocaleDateString('id-ID') : '',
              confirmedBy: p.confirmed_by || '-'
            })) : []
          }
        })
      }
    } catch (err) {
      error.value = 'Gagal memuat data billing'
      console.error('Error fetching billing periods:', err)
    } finally {
      loading.value = false
    }
  }

  const togglePeriod = (periodId) => {
    billingPeriods.value.forEach((p) => {
      if (p.id === periodId) {
        p.isExpanded = !p.isExpanded
      } else {
        p.isExpanded = false
      }
    })
  }

  const savePayment = async (paymentData) => {
    loading.value = true
    error.value = null

    try {
      const periodId = paymentData.periodId
      if (periodId) {
        const res = await billingService.confirmPayment(periodId)
        
        // Update local state
        const periodIndex = billingPeriods.value.findIndex((p) => p.id === periodId)
        if (periodIndex !== -1) {
          billingPeriods.value[periodIndex] = {
            ...billingPeriods.value[periodIndex],
            status: 'LUNAS',
            statusDate: getCurrentDate(),
            amount: paymentData.pembayaran,
            type: 'paid',
          }
        }

        return {
          success: true,
          message: 'Pembayaran berhasil dikonfirmasi',
          data: paymentData,
        }
      }
    } catch (err) {
      error.value = 'Gagal menyimpan pembayaran'
      console.error('Error saving payment:', err)
      return {
        success: false,
        message: err.response?.data?.message || 'Gagal menyimpan pembayaran',
        error: err,
      }
    } finally {
      loading.value = false
    }
  }

  const searchCustomers = async (query) => {
    searchQuery.value = query

    if (!query.trim()) {
      searchResults.value = []
      return
    }

    try {
      const res = await customerService.searchActive({ search: query })
      if (res.success && res.data) {
        searchResults.value = res.data
      }
    } catch (err) {
      console.error('Failed to search customers', err)
      searchResults.value = []
    }
  }

  const selectCustomer = async (customer) => {
    selectedCustomer.value = customer
    searchResults.value = []
    searchQuery.value = customer.name

    // Ambil periode tagihan untuk pelanggan yang dipilih
    await fetchBillingPeriods(customer.customer_id)
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
