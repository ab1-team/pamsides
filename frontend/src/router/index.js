import {
  createRouter,
  createWebHistory
} from 'vue-router'
import LoginView from '@/presentations/views/auth/LoginView.vue'
import MainView from '@/presentations/layouts/app/MainView.vue'
import DashboardHome from '@/presentations/views/app/DashboardHome.vue'
import SurveyorDashboard from '@/presentations/views/app/surveyor/DashboardMain.vue'
import TeknisiDashboard from '@/presentations/views/app/teknisi/DashboardMain.vue'
import SopIndex from '@/presentations/views/app/admin/sop/SopIndex.vue'
import KelasBiayaView from '@/presentations/views/app/admin/kelas/KelasIndex.vue'
import CreateKelasView from '@/presentations/views/app/admin/kelas/KelasCreate.vue'
import EditKelasView from '@/presentations/views/app/admin/kelas/KelasEdit.vue'
import pelangganView from '@/presentations/views/app/admin/pelanggan/PelangganIndex.vue'
import PelangganCreate from '@/presentations/views/app/admin/pelanggan/PelangganCreate.vue'
import PelangganEdit from '@/presentations/views/app/admin/pelanggan/PelangganEdit.vue'
import datainstalasiView from '@/presentations/views/app/admin/instalasi/dataInstalasi.vue'
import registerInstalasi from '@/presentations/views/app/admin/instalasi/registrasi.vue'
import statusInstalasi from '@/presentations/views/app/admin/instalasi/InstalasiStatus.vue'
import TeknisiPemakaianAir from '@/presentations/views/app/teknisi/PemakaianAir.vue'
import pemakaianair from '@/presentations/views/app/admin/tagihan/pemakaianAir.vue'
import DetailPermohonan from '@/presentations/views/app/admin/instalasi/partials/permohonan.vue'
import DetailPasangBaru from '@/presentations/views/app/admin/instalasi/partials/pasangBaru.vue'
import DetailAktif from '@/presentations/views/app/admin/instalasi/partials/aktif.vue'
import DetailBlokir from '@/presentations/views/app/admin/instalasi/partials/blokir.vue'
import DetailCabut from '@/presentations/views/app/admin/instalasi/partials/cabut.vue'
import jurnalUmum from '@/presentations/views/app/admin/transaksi/jurnalUmum/JurnalUmumIndex.vue'
import tagihanInstalasi from '@/presentations/views/app/admin/transaksi/Tagihan/tagihanInstalasi.vue'
import tagihanBulanan from '@/presentations/views/app/admin/transaksi/Tagihan/tagihanBulanan.vue'
import alokasiLaba from '@/presentations/views/app/admin/transaksi/arsip/alokasiLaba.vue'
import ebudgeting from '@/presentations/views/app/admin/transaksi/EBudgetingView.vue'
import tutupBuku from '@/presentations/views/app/admin/transaksi/tutupBuku.vue'
import komisiSPS from '@/presentations/views/app/admin/transaksi/komisiSPS.vue'
import laporan from '@/presentations/views/app/admin/pelaporan/PelaporanIndex.vue'
import pelaporanPreview from '@/presentations/views/app/admin/pelaporan/PelaporanPreview.vue'
import profil from '@/presentations/views/app/admin/profil/ProfilIndex.vue'
import detailPemakaianAir from '@/presentations/views/app/admin/tagihan/partials/detailPemakaianAir.vue'
import DesaIndex from '@/presentations/views/app/admin/desa/DesaIndex.vue'
import DesaCreate from '@/presentations/views/app/admin/desa/DesaCreate.vue'
import DesaEdit from '@/presentations/views/app/admin/desa/DesaEdit.vue'

const getDashboardRoute = (role) => {
  const routes = {
    surveyor: '/app',
    teknisi: '/app/teknisi',
    pelanggan: '/app',
    admin: '/app',
  }
  return routes[role] || '/app'
}

// NOTE: DashboardHome renders role-specific dashboard via dynamic component,
// jadi surveyor/pelanggan/admin bisa share path /app. Teknisi pakai route khusus.


const router = createRouter({
  history: createWebHistory(
    import.meta.env.BASE_URL),
  routes: [{
      path: '/',
      redirect: '/login',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/profil',
      redirect: '/app/profil',
    },
    /* 1. DI SINI PERUBAHANNYA: 
      Rute preview dipindahkan ke tingkat paling luar (Top-Level) agar tidak dibungkus MainView (Layout Admin)
    */
    {
      path: '/app/pelaporan/preview',
      name: 'Pelaporan Preview',
      component: pelaporanPreview,
    },
    {
      path: '/app',
      name: 'layout-dashboard',
      component: MainView,
      children: [{
          path: '',
          name: 'dashboard',
          component: DashboardHome,
        },
        {
          path: 'surveyor',
          name: 'surveyor-dashboard',
          component: SurveyorDashboard,
        },
        {
          path: 'teknisi',
          name: 'teknisi-dashboard',
          component: TeknisiDashboard,
        },
        {
          path: 'profil',
          name: 'profil',
          component: profil,
        },
        {
          path: 'settings/personalisasi-sop',
          name: 'personalisasi-sop',
          component: SopIndex,
        },
        {
          path: 'settings/coa',
          name: 'coa',
          component: () => import('@/presentations/views/app/admin/sop/CoaIndex.vue'),
        },
        {
          path: 'kelas-biaya',
          name: 'kelas biaya',
          component: KelasBiayaView,
        },
        {
          path: 'kelas-biaya/config',
          name: 'Tambah Kelas',
          component: CreateKelasView,
        },
        {
          path: 'kelas-biaya/config/:id',
          name: 'Edit Kelas',
          component: EditKelasView,
        },
        {
          path: 'data-pelanggan',
          name: 'Data Pelanggan',
          component: pelangganView,
        },
        {
          path: 'data-pelanggan/tambah',
          name: 'Tambah Pelanggan',
          component: PelangganCreate,
        },
        {
          path: 'data-pelanggan/edit/:id',
          name: 'Edit Pelanggan',
          component: PelangganEdit,
        },
        {
          path: 'data-desa',
          name: 'Data Desa',
          component: DesaIndex,
        },
        {
          path: 'data-desa/tambah',
          name: 'Tambah Desa',
          component: DesaCreate,
        },
        {
          path: 'data-desa/edit/:id',
          name: 'Edit Desa',
          component: DesaEdit,
        },
        {
          path: 'dataInstalasi',
          name: 'Data Instalasi',
          component: datainstalasiView,
        },
        {
          path: 'instalasi/register',
          name: 'Register Instalasi',
          component: registerInstalasi,
        },
        {
          path: 'instalasi/status',
          name: 'Status Instalasi',
          component: statusInstalasi,
        },
        {
          path: 'instalasi/status/permohonan/:id',
          name: 'Detail Permohonan',
          component: DetailPermohonan,
        },
        {
          path: 'instalasi/status/pasang-baru/:id',
          name: 'Detail Pasang Baru',
          component: DetailPasangBaru,
        },
        {
          path: 'instalasi/status/aktif/:id',
          name: 'Detail Aktif',
          component: DetailAktif,
        },
        {
          path: 'instalasi/status/blokir/:id',
          name: 'Detail Blokir',
          component: DetailBlokir,
        },
        {
          path: 'instalasi/status/cabut/:id',
          name: 'Detail Cabut',
          component: DetailCabut,
        },
        {
          path: 'instalasi/pemakaian-air',
          name: 'Pemakaian Air',
          component: pemakaianair,
        },
        {
          path: 'instalasi/pemakaian-air/input',
          name: 'Input Pemakaian Air',
          component: detailPemakaianAir,
        },
        {
          path: 'instalasi/daftar-tagihan',
          name: 'Daftar Tagihan',
          component: () => import(
            '@/presentations/views/app/admin/tagihan/daftarTagihan.vue'),
        },
        {
          path: 'survey/create',
          name: 'Create Survey',
          component: () => import('@/presentations/views/app/surveyor/createSurvey.vue'),
        },
        {
          path: 'teknisi/pencatatan-meter',
          name: 'Catat Meter',
          component: () => import('@/presentations/views/app/teknisi/MeterReading.vue'),
        },
        {
          path: 'teknisi/hasil-instalasi/:id',
          name: 'Hasil Instalasi',
          component: () => import(
            '@/presentations/views/app/teknisi/InstallationResult.vue'),
        },
        {
          path: 'pelanggan/tagihan-detail',
          name: 'Detail Tagihan',
          component: () => import('@/presentations/views/app/pelanggan/BillDetail.vue'),
        },
        {
          path: 'pelanggan/riwayat-tagihan',
          name: 'Riwayat Tagihan',
          component: () => import('@/presentations/views/app/pelanggan/riwayatTagihan.vue'),
        },
        {
          path: 'pelanggan/lapor-gangguan',
          name: 'Lapor Gangguan',
          component: () => import('@/presentations/views/app/pelanggan/LaporGangguan.vue'),
        },
        {
          path: 'pelanggan/lapor-gangguan/form',
          name: 'Form Lapor Gangguan',
          component: () => import(
            '@/presentations/views/app/pelanggan/LaporGangguanForm.vue'),
        },
        {
          path: 'instalasi/teknisiPemakaianAir',
          name: 'Input Pemakaian Air Teknisi',
          component: TeknisiPemakaianAir,
        },
        {
          path: 'teknisi/daftar-tagihan',
          name: 'Daftar Tagihan Teknisi',
          component: () => import('@/presentations/views/app/teknisi/DaftarTagihan.vue'),
        },
        {
          path: 'transaksi/jurnal-umum',
          name: 'transaksi jurnal umum',
          component: jurnalUmum,
        },
        {
          path: 'transaksi/tagihan-instalasi',
          name: 'transaksi Intalasi',
          component: tagihanInstalasi,
        },
        {
          path: 'transaksi/tagihan-bulanan',
          name: 'transaksi bulanan',
          component: tagihanBulanan,
        },
        {
          path: 'transaksi/E-budgeting',
          name: 'transaksi E-Budgeting',
          component: ebudgeting,
        },
        {
          path: 'transaksi/tutup-buku',
          name: 'transaksi tutup buku',
          component: tutupBuku,
        },
        {
          path: 'transaksi/alokasi-laba',
          name: 'transaksi alokasi laba',
          component: alokasiLaba,
        },
        {
          path: 'transaksi/komisi-sps',
          name: 'transaksi komisi sps',
          component: komisiSPS,
        },
        {
          path: 'Pelaporan',
          name: 'Pelaporan',
          component: laporan,
        },
        /* 2. DI SINI JUGA DIUBAH:
          Rute pelaporan/preview yang lama di dalam children ini SUDAH DIHAPUS 
          agar tidak bentrok.
        */
      ],
    },
    {
      path: '/usages/cetak_input',
      name: 'Cetak Input',
      component: () =>
        import('@/presentations/views/app/admin/instalasi/partials/view/cetakInput.vue'),
    },
    {
      path: '/usages/cetak_form',
      name: 'Cetak Form',
      component: () =>
        import('@/presentations/views/app/admin/tagihan/cetakForm.vue'),
    },
    {
      path: '/usages/cetak_daftar_tagihan',
      name: 'Cetak Daftar Tagihan',
      component: () =>
        import('@/presentations/views/app/admin/tagihan/cetakDaftarTagihan.vue'),
    },
    {
      path: '/usages/cetak_struk',
      name: 'Cetak Struk',
      component: () =>
        import('@/presentations/views/app/admin/tagihan/cetakStruk.vue'),
    },
    {
      path: '/usages/cetak_bukti_transaksi',
      name: 'Cetak Bukti Transaksi',
      component: () =>
        import('@/presentations/views/app/admin/transaksi/jurnalUmum/cetakBuktiTransaksi.vue'),
    },
  ],
})

router.beforeEach((to) => {
  const token = localStorage.getItem('auth_token')
  const expiresAt = localStorage.getItem('auth_expires_at')
  const userRole = localStorage.getItem('user_role') || 'admin'
  const isAuthPage = to.name === 'login'
  const now = Date.now()

  if (token && expiresAt && now > parseInt(expiresAt)) {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_data')
    localStorage.removeItem('user_role')
    localStorage.removeItem('auth_expires_at')

    return {
      name: 'login'
    }
  }

  const roleSpecificRoutes = {
    surveyor: ['/app/survey/create', '/app/surveyor'],
    teknisi: [
      '/app/teknisi/',
      '/app/instalasi/teknisiPemakaianAir',
      '/app/instalasi/pemakaian-air',
    ],
    admin: [
      '/app/data-pelanggan',
      '/app/data-desa',
      '/app/settings/',
      '/app/coa',
      '/app/kelas-biaya',
      '/app/instalasi/',
      '/app/transaksi/',
      '/app/pelaporan',
    ],
    pelanggan: ['/app/pelanggan/'],
  }

  const isTeknisiPath =
    to.path.startsWith('/app/teknisi/') ||
    to.path === '/app/instalasi/teknisiPemakaianAir' ||
    to.path.startsWith('/app/instalasi/pemakaian-air')

  const matchesRole = (role) => {
    if (role === 'admin' && isTeknisiPath && userRole === 'teknisi') return false
    return roleSpecificRoutes[role].some((r) => to.path.startsWith(r))
  }

  const isTeknisiAllowedPath = isTeknisiPath && userRole === 'teknisi'

  const isSurveyorOnly = matchesRole('surveyor') && !['surveyor', 'admin'].includes(userRole)
  const isTeknisiOnly = isTeknisiPath && !['teknisi', 'admin'].includes(userRole)
  const isAdminOnly = matchesRole('admin') && !['admin'].includes(userRole) && !
    isTeknisiAllowedPath
  const isPelangganOnly = matchesRole('pelanggan') && !['pelanggan', 'admin'].includes(userRole)

  if (isSurveyorOnly || isTeknisiOnly || isAdminOnly || isPelangganOnly) {
    return {
      name: 'login'
    }
  }

  if (to.path.startsWith('/app') || to.path === '/') {
    if (!token) {
      return {
        name: 'login'
      }
    }
    return true
  }

  if (isAuthPage && token) {
    return {
      path: getDashboardRoute(userRole)
    }
  }

  return true
})

export default router
