import { defineStore } from 'pinia'
import { ref } from 'vue'
import sopService from '@/services/sop.service.js'

/**
 * Settings Store - Mengelola pengaturan SOP/setting global yang
 * dibutuhkan lintas komponen (mis. BillingForm), sehingga tidak
 * setiap komponen harus memanggil API sendiri.
 *
 * Saat ini menyimpan data Sistem Tagihan:
 * - batasTagihan: batas hari jatuh tempo tagihan (default 10)
 * - toleransiTunggakan: jumlah bulan toleransi tunggakan (default 0)
 */
export const useSettingsStore = defineStore('settings', () => {
  // State - Sistem Tagihan
  const batasTagihan = ref(10)
  const toleransiTunggakan = ref(0)

  // State - lembaga (siapa tahu nanti butuh di komponen lain)
  const lembagaName = ref('')

  // Loading flag
  const isLoading = ref(false)
  const isLoaded = ref(false)

  /**
   * Ambil semua setting dari backend (/settings/sop) dan simpan ke store.
   * Aman dipanggil berulang - hanya fetch kalau belum pernah di-load,
   * kecuali `force = true`.
   */
  const loadSettings = async (force = false) => {
    if (isLoaded.value && !force) return
    try {
      isLoading.value = true
      const res = await sopService.getAll()
      const data = res?.data?.data ?? res?.data ?? res
      if (!data) return

      if (data.sistemTagihan) {
        batasTagihan.value = Number(data.sistemTagihan.batasTagihan ?? 10)
        toleransiTunggakan.value = Number(data.sistemTagihan.toleransiTunggakan ?? 0)
      }
      if (data.lembaga?.nama) {
        lembagaName.value = data.lembaga.nama
      }

      isLoaded.value = true
    } catch (err) {
      // Jangan lempar error agar komponen lain tidak crash.
      // Biarkan nilai default (batasTagihan=10, toleransiTunggakan=0).
      console.error('[settingsStore] loadSettings error:', err)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Update nilai toleransiTunggakan secara lokal (mis. setelah save dari
   * halaman SOP) tanpa harus refetch semua data.
   */
  const setToleransiTunggakan = (val) => {
    toleransiTunggakan.value = Number(val ?? 0)
  }

  const setBatasTagihan = (val) => {
    batasTagihan.value = Number(val ?? 10)
  }

  const reset = () => {
    batasTagihan.value = 10
    toleransiTunggakan.value = 0
    lembagaName.value = ''
    isLoaded.value = false
  }

  return {
    // state
    batasTagihan,
    toleransiTunggakan,
    lembagaName,
    isLoading,
    isLoaded,
    // actions
    loadSettings,
    setToleransiTunggakan,
    setBatasTagihan,
    reset,
  }
})
