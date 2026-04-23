import { ref, computed } from 'vue'
import { MySwal } from '@/utils/swal'

export function useSop() {
  const activeSection = ref('wellcome')
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
    nama: 'PAMSIDES Digital Solution',
    alamat: 'Jl. Merdeka No. 123, Jakarta Selatan',
    email: 'info@pamsides.id',
    telepon: '021-12345678',
    website: 'https://pamsides.id',
    deskripsi: 'Sistem Pengelolaan Air Minum dan Sanitasi Desa.',
  })

  const logoForm = ref({
    mainLogo: null,
    dashboardLogo: null,
    favicon: null,
    previews: {
      mainLogo: 'https://via.placeholder.com/150?text=Main+Logo',
      dashboardLogo: 'https://via.placeholder.com/150?text=Dashboard+Logo',
      favicon: 'https://via.placeholder.com/32?text=F',
    },
  })

  const whatsappForm = ref({
    templateTagihan: `Yth. {customer} {desa}.

Diinformasikan bahwa anda memiliki tagihan bulanan dengan rincian:
Kode Instalasi : {kode_instalasi}
Jatuh Tempo : {jatuh_tempo}
Jumlah : Rp. {jumlah_tagihan}

Dimohon untuk segera melakukan pembayaran.`,
    templatePembayaran: `Yth. {customer} {desa}.

Terima kasih atas pembayaran tagihan bulanan anda.
Rincian pembayaran:
Kode Instalasi : {kode_instalasi}
Jatuh Tempo : {jatuh_tempo}
Jumlah : Rp. {tagihan}

Pembayaran telah kami terima pada`,
  })

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

  const pasangBaruForm = ref({
    biayaPasang: 750000,
    statusPembayaran: 'wajib',
    enableAir: true,
    enableSampah: false,
  })

  const sistemTagihanForm = ref({
    toleransiTunggakan: 3,
    jatuhTempo: 6,
    biayaAktivasi: 5000,
  })

  const wellcomeForm = ref({})

  const saveSettings = () => {
    if (activeSection.value === 'pasangBaru' && pasangBaruForm.value.enableSampah) {
      MySwal.fire({
        title: 'Konfirmasi Aktivasi Fitur',
        text: 'Aktivasi fitur Retribusi Sampah memerlukan login ulang untuk sinkronisasi data sistem. Apakah Anda ingin melanjutkan?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#0ea5e9',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Login Ulang',
        cancelButtonText: 'Batal',
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = '/login?logout=success'
        }
      })
    } else {
      MySwal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Pengaturan Berhasil Disimpan',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
          popup: 'swal-toast-custom',
          title: 'swal-toast-title',
        },
      })
    }
  }

  return {
    activeSection,
    activeLabel,
    menuList,
    lembagaForm,
    logoForm,
    whatsappForm,
    pasangBaruForm,
    sistemTagihanForm,
    wellcomeForm,
    handleLogoUpload,
    saveSettings,
  }
}
