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
    { key: 'logo', label: 'Logo & Branding', icon: 'image' },
    { key: 'whatsapp', label: 'Whatsapp API', icon: ['fab', 'whatsapp'] },
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
  })

  const pasangBaruForm = ref({
    statusPembayaran: false,
  })

  const sistemTagihanForm = ref({
    batasTagihan: 10,
    toleransiTunggakan: 0,
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
    logoForm,
    whatsappForm,
    wellcomeForm,
    loadSettings,
    saveSettings,
    saveLembaga,
    savePasangBaru,
    saveSistemTagihan,
    saveLogo,
    saveWhatsapp,
  }
}
