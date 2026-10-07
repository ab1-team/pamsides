import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

import { billingService } from '@/services/billing.service'
import { useUiStore } from '@/stores/uiStore'

/**
 * Store untuk notifikasi tagihan di navbar (icon lonceng).
 *
 * Sengaja terpisah dari billingStore: halaman Tagihan punya store sendiri
 * dengan aturan bayar & pagination yang tidak boleh ikut berubah karena
 * navbar hanya butuh angka + daftar ringkas.
 *
 * Icon tanda tanya (?) tidak punya store — panelnya murni kontak statis,
 * lihat `SupportContactPanel.vue`.
 */
export const useNotificationStore = defineStore('notification', () => {
  const uiStore = useUiStore()

  // Admin & teknisi melihat seluruh masalah tagihan; pelanggan hanya
  // miliknya. Sumber kebenaran adalah role, sama seperti di router guard.
  const isStaffRole = computed(() => ['admin', 'teknisi'].includes(uiStore.userRole || ''))

  const unpaidBills = ref([])
  const billsSummary = ref({
    unpaid_count: 0,
    unpaid_total: 0,
    overdue_count: 0,
    customer_count: 0,
  })
  const billsLoading = ref(false)
  const billsError = ref(null)

  // Badge lonceng: jumlah pelanggan menunggak untuk admin/teknisi,
  // jumlah tagihan sendiri untuk pelanggan. Angka yang dilihat lebih
  // relevan per role.
  const bellCount = computed(() =>
    isStaffRole.value ? billsSummary.value.customer_count : billsSummary.value.unpaid_count,
  )

  const fetchBills = async () => {
    billsLoading.value = true
    billsError.value = null

    try {
      const res = await billingService.getUnpaidSummary()
      if (res?.success) {
        unpaidBills.value = Array.isArray(res.data) ? res.data : []
        if (res.summary) billsSummary.value = res.summary
      }
    } catch (err) {
      billsError.value = err?.response?.data?.message || 'Gagal memuat tagihan.'
    } finally {
      billsLoading.value = false
    }
  }

  const reset = () => {
    unpaidBills.value = []
    billsSummary.value = {
      unpaid_count: 0,
      unpaid_total: 0,
      overdue_count: 0,
      customer_count: 0,
    }
    billsError.value = null
  }

  return {
    unpaidBills,
    billsSummary,
    billsLoading,
    billsError,
    isStaffRole,
    bellCount,
    fetchBills,
    reset,
  }
})
