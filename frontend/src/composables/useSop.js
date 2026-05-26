import { ref, computed, onMounted } from 'vue'
import { MySwal } from '@/utils/swal'
import sopService from '@/services/sop.service'

export function useSop() {
  const activeSection = ref('wellcome')
  const isSaving = ref(false)
  const isLoading = ref(false)

  const menuList = [
    {
      key: 'wellcome',
      label: 'Selamat Datang',
      icon: 'home',
      variant: 'primary-gradient',
    },
    {
      key: 'lembaga',
      label: 'Profil Lembaga',
      icon: 'building',
      variant: 'primary-gradient',
    },
    {
      key: 'pasangBaru',
      label: 'Pasang Baru',
      icon: 'user-plus',
      variant: 'primary-gradient',
    },
    {
      key: 'sistemTagihan',
      label: 'Sistem Tagihan',
      icon: 'file-invoice-dollar',
      variant: 'primary-gradient',
    },
    {
      key: 'logo',
      label: 'Logo & Branding',
      icon: 'image',
      variant: 'primary-gradient',
    },
    {
      key: 'whatsapp',
      label: 'Whatsapp API',
      icon: ['fab', 'whatsapp'],
      variant: 'primary-gradient',
    },
  ]

  const activeLabel = computed(() => {
    const active = menuList.find((m) => m.key === activeSection.value)
    return active ? active.label : ''
  })

  const lembagaForm = ref({
    nama: '',
    alamat: '',
    email: '',
    telepon: '',
    website: '',
    deskripsi: '',
  })

  const logoForm = ref({
    mainLogo: null,
    dashboardLogo: null,
    favicon: null,
    previews: {
      mainLogo: '',
      dashboardLogo: '',
      favicon: '',
    },
  })

  const whatsappForm = ref({
    templateTagihan: '',
    templatePembayaran: '',
  })

  const pasangBaruForm = ref({
    biayaPasang: 0,
    statusPembayaran: '',
    enableAir: false,
    enableSampah: false,
  })

  const sistemTagihanForm = ref({
    toleransiTunggakan: 0,
    jatuhTempo: 0,
  })

  const wellcomeForm = ref({})

  const handleLogoUpload = (event, type) => {
    const file = event.target.files[0]
    if (file) {
      logoForm.value[type] = file
      const reader = new FileReader()
      reader.onload = (e) => {
        logoForm.value.previews[type] = e.target.result
      }
      reader.readAsDataURL(file)
    }
  }

  const showSuccessToast = (title = 'Pengaturan Berhasil Disimpan') => {
    MySwal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title,
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      customClass: {
        popup: 'swal-toast-custom',
        title: 'swal-toast-title',
      },
    })
  }

  const showErrorToast = (error) => {
    const message = error?.response?.data?.message || error?.message || 'Gagal menyimpan pengaturan'
    MySwal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: message,
      showConfirmButton: false,
      timer: 4000,
      timerProgressBar: true,
      customClass: {
        popup: 'swal-toast-custom',
        title: 'swal-toast-title',
      },
    })
  }

  /**
   * Memuat seluruh pengaturan SOP dari backend.
   * Backend diharapkan mengembalikan struktur:
   * {
   *   lembaga: { nama, alamat, email, telepon, website, deskripsi },
   *   pasangBaru: { biayaPasang, statusPembayaran, enableAir, enableSampah },
   *   sistemTagihan: { toleransiTunggakan, jatuhTempo, biayaAktivasi },
   *   logo: { mainLogo, dashboardLogo, favicon }, // berupa URL string
   *   whatsapp: { templateTagihan, templatePembayaran }
   * }
   */
  const loadSettings = async () => {
    try {
      isLoading.value = true
      const data = await sopService.getAll()
      if (!data) return

      if (data.lembaga) lembagaForm.value = { ...lembagaForm.value, ...data.lembaga }
      if (data.pasangBaru) pasangBaruForm.value = { ...pasangBaruForm.value, ...data.pasangBaru }
      if (data.sistemTagihan)
        sistemTagihanForm.value = { ...sistemTagihanForm.value, ...data.sistemTagihan }
      if (data.whatsapp) whatsappForm.value = { ...whatsappForm.value, ...data.whatsapp }
      if (data.logo) {
        logoForm.value.previews = {
          mainLogo: data.logo.mainLogo || '',
          dashboardLogo: data.logo.dashboardLogo || '',
          favicon: data.logo.favicon || '',
        }
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
      showSuccessToast('Profil Lembaga berhasil disimpan')
    } catch (error) {
      showErrorToast(error)
    } finally {
      isSaving.value = false
    }
  }

  const savePasangBaru = async () => {
    if (pasangBaruForm.value.enableSampah) {
      const result = await MySwal.fire({
        title: 'Konfirmasi Aktivasi Fitur',
        text: 'Aktivasi fitur Retribusi Sampah memerlukan login ulang untuk sinkronisasi data sistem. Apakah Anda ingin melanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0ea5e9',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Login Ulang',
        cancelButtonText: 'Batal',
      })
      if (!result.isConfirmed) return
    }

    try {
      isSaving.value = true
      await sopService.savePasangBaru({ ...pasangBaruForm.value })

      if (pasangBaruForm.value.enableSampah) {
        window.location.href = '/login?logout=success'
        return
      }

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
    try {
      isSaving.value = true
      await sopService.saveLogo({
        mainLogo: logoForm.value.mainLogo,
        dashboardLogo: logoForm.value.dashboardLogo,
        favicon: logoForm.value.favicon,
      })
      showSuccessToast('Logo & Branding berhasil disimpan')
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

  /**
   * Dispatcher untuk dipanggil dari tombol simpan masing-masing form.
   * Memilih handler berdasarkan section yang sedang aktif.
   */
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
    logoForm,
    whatsappForm,
    pasangBaruForm,
    sistemTagihanForm,
    wellcomeForm,
    handleLogoUpload,
    loadSettings,
    saveSettings,
    saveLembaga,
    savePasangBaru,
    saveSistemTagihan,
    saveLogo,
    saveWhatsapp,
  }
}
