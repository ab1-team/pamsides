import { ref, computed, onMounted } from 'vue'
import { showSuccessToast, showErrorToast } from '@/utils/swal'
import { storageUrl } from '@/utils/storage'
import sopService from '@/services/sop.service'
import { useUiStore } from '@/stores/uiStore'

export function useSop() {
  const activeSection = ref('wellcome')
  const isSaving = ref(false)
  const isLoading = ref(false)
  const uiStore = useUiStore()

  const menuList = [
    { key: 'wellcome', label: 'Selamat Datang', icon: 'home' },
    { key: 'lembaga', label: 'Profil Lembaga', icon: 'building' },
    { key: 'pasangBaru', label: 'Pasang Baru', icon: 'user-plus' },
    { key: 'sistemTagihan', label: 'Sistem Tagihan', icon: 'file-invoice-dollar' },
    { key: 'calk', label: 'Pengaturan CALK', icon: 'book-open' },
    { key: 'logo', label: 'Logo & Branding', icon: 'image' },
    { key: 'whatsapp', label: 'Whatsapp API', icon: ['fab', 'whatsapp'] },
    { key: 'signature', label: 'Tanda Tangan Laporan', icon: 'signature' },
  ]

  const activeLabel = computed(() => {
    const m = menuList.find((x) => x.key === activeSection.value)
    return m ? m.label : ''
  })

  const lembagaForm = ref({
    nama: '',
    alamat: '',
    email: '',
    telepon: '',
    domain: '',
    peraturan_desa: '',
    sk_kemenkumham: '',
  })

  const pasangBaruForm = ref({
    statusPembayaran: false,
  })

  const sistemTagihanForm = ref({
    batasTagihan: 10,
    toleransiTunggakan: 0,
  })

  // ---- CALK ----
  // Form konfigurasi CALK (persentase bagian, laba ditahan, dan Point A kustom).
  // Struktur payload mengikuti sidbm (SopController::_calk.blade.php).
  const calkForm = ref({
    peraturan_desa: '',
    bantuan_rumah_tangga: 0,
    pengembangan_kapasitas: 0,
    pelatihan_masyarakat: 0,
    peningkatan_modal: 0,
    penambahan_investasi: 0,
    pendirian_unit_usaha: 0,
    point_a: '',
  })

  const logoForm = ref({
    file: null,
    preview: '',
    previewName: '',
  })

  const whatsappForm = ref({
    templateTagihan: '',
    templatePembayaran: '',
  })

  const wellcomeForm = ref({})

  const loadSettings = async () => {
    try {
      isLoading.value = true
      const res = await sopService.getAll()
      const data = res?.data?.data ?? res?.data ?? res
      if (!data) return

      if (data.lembaga) lembagaForm.value = { ...lembagaForm.value, ...data.lembaga }
      if (data.pasangBaru) pasangBaruForm.value = { ...pasangBaruForm.value, ...data.pasangBaru }
      if (data.sistemTagihan)
        sistemTagihanForm.value = { ...sistemTagihanForm.value, ...data.sistemTagihan }
      if (data.whatsapp) whatsappForm.value = { ...whatsappForm.value, ...data.whatsapp }

      // CALK config (persentase bagian + laba ditahan + point_a)
      if (data.calk) {
        const ck = data.calk
        calkForm.value = {
          peraturan_desa: ck.peraturan_desa ?? '',
          bantuan_rumah_tangga: Number(ck.D?.['1']?.d?.['1'] ?? 0),
          pengembangan_kapasitas: Number(ck.D?.['1']?.d?.['2'] ?? 0),
          pelatihan_masyarakat: Number(ck.D?.['1']?.d?.['3'] ?? 0),
          peningkatan_modal: Number(ck.D?.['2']?.a ?? 0),
          penambahan_investasi: Number(ck.D?.['2']?.b ?? 0),
          pendirian_unit_usaha: Number(ck.D?.['2']?.c ?? 0),
          point_a: ck.point_a ?? '',
        }
      }

      if (data.logo) {
        logoForm.value.previews = {
          mainLogo: data.logo.mainLogo_url || data.logo.mainLogo || '',
          dashboardLogo: data.logo.dashboardLogo_url || data.logo.dashboardLogo || '',
          favicon: data.logo.favicon_url || data.logo.favicon || '',
        }
      }

      if (data.sistemTagihan) {
        sistemTagihanForm.value = {
          batasTagihan: Number(data.sistemTagihan.batasTagihan ?? 10),
          toleransiTunggakan: Number(data.sistemTagihan.toleransiTunggakan ?? 0),
        }
      }

      if (data.whatsapp) {
        whatsappForm.value = {
          templateTagihan: data.whatsapp.templateTagihan ?? '',
          templatePembayaran: data.whatsapp.templatePembayaran ?? '',
        }
      }

      if (data.logo) {
        const fileName = data.logo.logo || ''
        logoForm.value.preview = fileName ? storageUrl(`storage/sop/logo/${fileName}`) : ''
        logoForm.value.previewName = fileName
      }
    } catch (error) {
      showErrorToast(error)
    } finally {
      isLoading.value = false
    }
  }

  const saveLembaga = async () => {
    try {
      isSaving.value = true
      await sopService.saveLembaga({ ...lembagaForm.value })
      uiStore.bumpSettings()
      showSuccessToast('Profil Lembaga berhasil disimpan')
    } catch (error) {
      showErrorToast(error)
    } finally {
      isSaving.value = false
    }
  }

  const savePasangBaru = async () => {
    try {
      isSaving.value = true
      await sopService.savePasangBaru({ ...pasangBaruForm.value })
      showSuccessToast('Pengaturan Pasang Baru berhasil disimpan')
    } catch (error) {
      showErrorToast(error)
    } finally {
      isSaving.value = false
    }
  }

  const saveSistemTagihan = async () => {
    try {
      isSaving.value = true
      await sopService.saveSistemTagihan({ ...sistemTagihanForm.value })
      showSuccessToast('Sistem Tagihan berhasil disimpan')
    } catch (error) {
      showErrorToast(error)
    } finally {
      isSaving.value = false
    }
  }

  const saveCalk = async () => {
    try {
      isSaving.value = true
      // Simpan konfigurasi CALK (persentase + laba ditahan).
      await sopService.saveCalk({
        peraturan_desa: calkForm.value.peraturan_desa,
        bantuan_rumah_tangga: Number(calkForm.value.bantuan_rumah_tangga) || 0,
        pengembangan_kapasitas: Number(calkForm.value.pengembangan_kapasitas) || 0,
        pelatihan_masyarakat: Number(calkForm.value.pelatihan_masyarakat) || 0,
        peningkatan_modal: Number(calkForm.value.peningkatan_modal) || 0,
        penambahan_investasi: Number(calkForm.value.penambahan_investasi) || 0,
        pendirian_unit_usaha: Number(calkForm.value.pendirian_unit_usaha) || 0,
      })
      // Simpan Point A (Gambaran Umum) kustom secara paralel.
      await sopService.saveCustomCalk({ point_a: calkForm.value.point_a ?? '' })
      showSuccessToast('Pengaturan CALK berhasil disimpan')
    } catch (error) {
      showErrorToast(error)
    } finally {
      isSaving.value = false
    }
  }

  const saveLogo = async () => {
    if (!logoForm.value.file) {
      showErrorToast({ message: 'Pilih file logo terlebih dahulu' })
      return
    }
    try {
      isSaving.value = true
      const res = await sopService.saveLogo(logoForm.value.file)
      const data = res?.data?.data ?? res?.data ?? res
      if (data?.logo) {
        logoForm.value.preview = storageUrl(`storage/sop/logo/${data.logo}`)
        logoForm.value.previewName = data.logo
      }
      logoForm.value.file = null
      showSuccessToast('Logo berhasil disimpan')
    } catch (error) {
      showErrorToast(error)
    } finally {
      isSaving.value = false
    }
  }

  const saveWhatsapp = async () => {
    try {
      isSaving.value = true
      await sopService.saveWhatsapp({ ...whatsappForm.value })
      showSuccessToast('Template WhatsApp berhasil disimpan')
    } catch (error) {
      showErrorToast(error)
    } finally {
      isSaving.value = false
    }
  }

  const saveSettings = () => {
    switch (activeSection.value) {
      case 'lembaga':
        return saveLembaga()
      case 'pasangBaru':
        return savePasangBaru()
      case 'sistemTagihan':
        return saveSistemTagihan()
      case 'calk':
        return saveCalk()
      case 'logo':
        return saveLogo()
      case 'whatsapp':
        return saveWhatsapp()
      default:
        return Promise.resolve()
    }
  }

  onMounted(() => {
    loadSettings()
  })

  return {
    activeSection,
    activeLabel,
    menuList,
    isLoading,
    isSaving,
    lembagaForm,
    pasangBaruForm,
    sistemTagihanForm,
    calkForm,
    logoForm,
    whatsappForm,
    wellcomeForm,
    loadSettings,
    saveSettings,
    saveLembaga,
    savePasangBaru,
    saveSistemTagihan,
    saveCalk,
    saveLogo,
    saveWhatsapp,
  }
}
