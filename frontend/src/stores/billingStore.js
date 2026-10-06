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
      (period) => period.type !== 'paid' && period.status !== 'LUNAS',
    )

    // Urutkan dari paling lama → paling baru berdasarkan (billing_period_year, billing_period_month).
    // Pembayaran harus dilakukan urut dari tagihan paling lama; kalau list acak,
    // teknisi bisa "kecolongan" membayar bulan baru padahal bulan lama masih nunggak.
    const sorted = [...unpaidPeriods].sort((a, b) => {
      const ya = Number(a.billing_period_year)
      const ma = Number(a.billing_period_month)
      const yb = Number(b.billing_period_year)
      const mb = Number(b.billing_period_month)
      if (Number.isFinite(ya) && Number.isFinite(yb)) {
        if (ya !== yb) return ya - yb
        if (Number.isFinite(ma) && Number.isFinite(mb)) return ma - mb
      }
      return 0
    })

    if (!searchQuery.value) {
      return sorted
    }

    const query = searchQuery.value.toLowerCase()
    return sorted.filter(
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

  /**
   * Cek apakah ada tagihan unpaid lain yang period-nya LEBIH LAMA dari `period`.
   * Kunci pembanding: `billing_period_year` + `billing_period_month` (ASC → kecil = lebih lama).
   * Field `billing_period_year` & `billing_period_month` sudah ada di setiap
   * `period` hasil mapping `fetchBillingPeriods`.
   *
   * Return true kalau ada period lain (selain `period`) yang:
   *   - status unpaid
   *   - year lebih kecil, atau year sama tapi month lebih kecil
   *
   * Dipakai untuk disable bayar dari tagihan yang lebih baru kalau ada tagihan
   * lebih lama yang belum lunas (alur pembayaran harus dari yang terlama).
   */
  const hasOlderUnpaidPeriod = (period) => {
    if (!period) return false
    const targetYear = Number(period.billing_period_year)
    const targetMonth = Number(period.billing_period_month)
    if (!Number.isFinite(targetYear) || !Number.isFinite(targetMonth)) return false
    return billingPeriods.value.some((p) => {
      if (!p || p.id === period.id) return false
      if (p.status === 'paid' || p.status === 'LUNAS') return false
      const y = Number(p.billing_period_year)
      const m = Number(p.billing_period_month)
      if (!Number.isFinite(y) || !Number.isFinite(m)) return false
      // Lebih lama = year lebih kecil, atau year sama tapi month lebih kecil
      if (y < targetYear) return true
      if (y === targetYear && m < targetMonth) return true
      return false
    })
  }

  // Fungsi-fungsi aksi (Actions)
  const fetchBillingPeriods = async (customerId) => {
    loading.value = true
    error.value = null

    try {
      if (!customerId) {
        billingPeriods.value = []
        return
      }

      const res = await billingService.getAllBills({ customer_id: customerId })

      const monthNames = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
      ]

      if (res?.success && res.data) {
        const bills = Array.isArray(res.data.bills) ? res.data.bills : []
        billingPeriods.value = bills.map((bill) => {
          let type = 'current'
          if (bill.status === 'unpaid') {
            type = new Date(bill.due_date) < new Date() ? 'overdue' : 'processing'
          } else if (bill.status === 'paid') {
            type = 'paid'
          }

          return {
            id: bill.id,
            period: `${monthNames[bill.billing_period_month - 1]} ${bill.billing_period_year}`,
            status:
              bill.status === 'paid'
                ? 'LUNAS'
                : type === 'overdue'
                  ? 'TERTUNGGAK'
                  : 'BELUM DIBAYAR',
            statusDate:
              bill.status === 'paid'
                ? ''
                : `JATUH TEMPO ${new Date(bill.due_date).toLocaleDateString('id-ID')}`,
            amount: Number(bill.total_amount),
            abodemen: Number(bill.abodemen),
            denda: Number(bill.penalty_amount),
            usage_charge: Number(bill.usage_charge),
            customerName:
              bill.customer?.ticket?.applicant_name || bill.customer?.user?.name || 'Pelanggan',
            customerId: bill.customer?.customer_code || '-',
            installationCode: bill.customer?.customer_code || '-',
            isExpanded: false,
            type: type,
            meterAwal: bill.meter_reading_start || 0,
            meterAkhir: bill.meter_reading_end || 0,
            pemakaian: bill.usage_m3 || 0,
            dueDate: bill.due_date || null,
            // Field pembanding period (year+month) untuk sort ASC & deteksi
            // "ada tagihan lebih lama yang belum dibayar" → disable tombol bayar
            // kalau bukan bulan paling lama. Tanpa ini, hasOlderUnpaidPeriod
            // selalu return false karena Number(undefined) = NaN.
            billing_period_year: Number(bill.billing_period_year),
            billing_period_month: Number(bill.billing_period_month),
            payments: bill.bill_payments
              ? bill.bill_payments.map((p) => ({
                  id: p.id,
                  amount: Number(p.amount_paid),
                  paidAt: p.paid_at ? new Date(p.paid_at).toLocaleDateString('id-ID') : '',
                  confirmedBy: p.confirmed_by || '-',
                }))
              : [],
          }
        })
      }
    } catch (err) {
      error.value = 'Gagal memuat data billing'
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
      const periodId = paymentData?.periodId
      if (!periodId) {
        return {
          success: false,
          message: 'ID tagihan tidak ditemukan.',
        }
      }

      // Petakan metode pembayaran dari FE → backend.
      // FE pakai kode: 'cash' | 'transfer_bri' (BRI adalah rekening bank tujuan).
      // Backend validator hanya menerima: 'cash' | 'transfer' (lihat MonthlyBillController::pay).
      const feMethod = paymentData?.paymentMethod
      const backendMethod =
        feMethod === 'transfer_bri' ? 'transfer' : feMethod === 'cash' ? 'cash' : null

      // FE harus selalu menyertakan paymentMethod (BillingForm memvalidasi ini
      // sebelum emit). Kalau entah bagaimana tidak ada, gagal cepat — JANGAN
      // kirim request dengan payment_method null ke backend (validator backend
      // 'nullable' akan menerima null, tapi artinya metode jadi tidak tercatat).
      if (!backendMethod) {
        return {
          success: false,
          message: 'Metode pembayaran belum dipilih (Tunai atau Transfer BRI).',
        }
      }

      // Tanggal pembayaran dari date picker. Backend membaca field `paid_at_date`
      // (MonthlyBillController::pay). Sebelumnya field ini TIDAK pernah dikirim,
      // sehingga backend selalu jatuh ke `now()` — tanggal yang dipilih user
      // diabaikan begitu saja, padahal logika ADVANCE di UI memakainya.
      const paidAtDate = paymentData?.tanggal ? String(paymentData.tanggal).slice(0, 10) : null

      const payload = {
        payment_method: backendMethod,
        amount_paid: Number(paymentData.pembayaran || paymentData.amount || 0),
        ...(paidAtDate ? { paid_at_date: paidAtDate } : {}),
      }

      // ── Retry otomatis ──
      // User sering tidak sengaja menekan tombol berulang, atau internet
      // putus sesaat. Karena backend sudah idempoten (status tagihan dicek
      // ulang di dalam transaksi + lockForUpdate), mengirim ulang request
      // yang sama aman: kalau pembayaran pertama sudah sempat berhasil,
      // request kedua dibalas 400 "Tagihan sudah dibayar" yang kita Perlakukan
      // sebagai SUKSES (idempotent), bukan error.
      //
      // Retry hanya untuk error jaringan / server sibuk — bukan untuk 4xx lain.
      const MAX_RETRY = 2
      const RETRY_DELAY_MS = 1200

      const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

      const isRetryable = (err) => {
        const status = err?.response?.status

        // Tidak ada respons = timeout / internet putus / DNS gagal.
        if (!err?.response) return true
        // 503 (server sibuk) & 502/504 (gateway) = layak dicoba lagi.
        return [502, 503, 504].includes(status)
      }

      let res = null
      let lastError = null

      for (let attempt = 0; attempt <= MAX_RETRY; attempt++) {
        try {
          res = await billingService.confirmPayment(periodId, payload)
          lastError = null
          break
        } catch (err) {
          lastError = err

          // "Tagihan sudah dibayar" = pembayaran kita sebenarnya sudah
          // sempat berhasil tapi responsnya hilang. Perlakukan sebagai sukses.
          if (
            err?.response?.status === 400 &&
            /sudah dibayar/i.test(err.response?.data?.message || '')
          ) {
            res = { success: true, message: 'Pembayaran berhasil dikonfirmasi', already_paid: true }
            lastError = null
            break
          }

          if (attempt < MAX_RETRY && isRetryable(err)) {
            await sleep(RETRY_DELAY_MS * (attempt + 1))
            continue
          }

          throw err
        }
      }

      if (lastError) {
        throw lastError
      }

      if (!res?.success) {
        return {
          success: false,
          message: res?.message || 'Gagal mengkonfirmasi pembayaran.',
        }
      }

      const periodIndex = billingPeriods.value.findIndex((p) => p.id === periodId)
      if (periodIndex !== -1) {
        const existing = billingPeriods.value[periodIndex]
        const backendPayment = res?.data?.payment
        const newPayment = backendPayment
          ? [
              {
                id: backendPayment.id,
                amount: Number(backendPayment.amount_paid),
                paidAt: backendPayment.paid_at
                  ? new Date(backendPayment.paid_at).toLocaleDateString('id-ID')
                  : getCurrentDate(),
                confirmedBy: backendPayment.confirmed_by || '-',
              },
            ]
          : existing.payments || []

        billingPeriods.value[periodIndex] = {
          ...existing,
          status: 'LUNAS',
          statusDate: getCurrentDate(),
          amount: payload.amount_paid,
          abodemen: Number(paymentData.abodemen ?? existing.abodemen),
          denda: Number(paymentData.denda ?? existing.denda),
          usage_charge: Number(paymentData.tagihan ?? existing.usage_charge),
          type: 'paid',
          payments: newPayment,
        }
      }

      return {
        success: true,
        message: res.message || 'Pembayaran berhasil dikonfirmasi',
        data: res.data,
      }
    } catch (err) {
      error.value = 'Gagal menyimpan pembayaran'
      return {
        success: false,
        message:
          err?.response?.data?.message ||
          (err?.response
            ? `Gagal menyimpan pembayaran (HTTP ${err.response.status}). Silakan coba lagi.`
            : 'Tidak dapat terhubung ke server. Periksa koneksi internet, lalu coba lagi.'),
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
      if (res?.success && res.data) {
        searchResults.value = res.data
      } else {
        searchResults.value = []
      }
    } catch (err) {
      searchResults.value = []
    }
  }

  const selectCustomer = async (customer) => {
    const customerId = customer?.id ?? customer?.customer_id ?? null
    if (!customerId) {
      return
    }
    selectedCustomer.value = customer
    searchResults.value = []
    searchQuery.value = customer.name

    await fetchBillingPeriods(customerId)
  }

  const clearSearch = () => {
    searchQuery.value = ''
  }

  const deleteBill = async (billId) => {
    loading.value = true
    error.value = null
    try {
      const res = await billingService.deleteBill(billId)
      if (!res?.success) {
        return { success: false, message: res?.message || 'Gagal rollback tagihan.' }
      }
      if (selectedCustomer.value?.id) {
        await fetchBillingPeriods(selectedCustomer.value.id)
      }
      return { success: true, message: res.message || 'Tagihan dikembalikan ke belum dibayar.' }
    } catch (err) {
      error.value = 'Gagal rollback tagihan'
      return { success: false, message: err.response?.data?.message || 'Gagal rollback tagihan.' }
    } finally {
      loading.value = false
    }
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
    hasOlderUnpaidPeriod,

    // Aksi
    fetchBillingPeriods,
    togglePeriod,
    savePayment,
    deleteBill,
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
