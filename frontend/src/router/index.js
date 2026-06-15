import { createRouter, createWebHistory } from 'vue-router'
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
import profil from '@/presentations/views/app/admin/profil/ProfilIndex.vue'
import detailPemakaianAir from '@/presentations/views/app/admin/tagihan/partials/detailPemakaianAir.vue'
import DesaIndex from '@/presentations/views/app/admin/desa/DesaIndex.vue'
import DesaCreate from '@/presentations/views/app/admin/desa/DesaCreate.vue'
import DesaEdit from '@/presentations/views/app/admin/desa/DesaEdit.vue'

const getDashboardRoute = (role) => {
  const routes = {
    surveyor: '/app/surveyor',
    teknisi: '/app/teknisi',
    pelanggan: '/app/pelanggan/dashboard',
    admin: '/app',
  }
  return routes[role] || '/app'
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/login',
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true },
    },
    {
      path: '/app',
      name: 'layout-dashboard',
      component: MainView,
      meta: { auth: true, roles: ['admin', 'surveyor', 'teknisi', 'pelanggan'] },
      children: [
        {
          path: '',
          name: 'dashboard',
          component: DashboardHome,
          meta: { roles: ['admin', 'surveyor', 'teknisi', 'pelanggan'] },
        },
        {
          path: 'surveyor',
          name: 'surveyor-dashboard',
          component: SurveyorDashboard,
          meta: { roles: ['admin', 'surveyor'] },
        },
        {
          path: 'teknisi',
          name: 'teknisi-dashboard',
          component: TeknisiDashboard,
          meta: { roles: ['admin', 'teknisi'] },
        },
        {
          path: 'profil',
          name: 'profil',
          component: profil,
          meta: { roles: ['admin', 'surveyor', 'teknisi', 'pelanggan'] },
        },
        {
          path: 'settings/personalisasi-sop',
          name: 'personalisasi-sop',
          component: SopIndex,
          meta: { roles: ['admin'] },
        },
        {
          path: 'settings/coa',
          name: 'coa',
          component: () => import('@/presentations/views/app/admin/sop/CoaIndex.vue'),
          meta: { roles: ['admin'] },
        },
        {
          path: 'kelas-biaya',
          name: 'kelas biaya',
          component: KelasBiayaView,
          meta: { roles: ['admin'] },
        },
        {
          path: 'kelas-biaya/config',
          name: 'Tambah Kelas',
          component: CreateKelasView,
          meta: { roles: ['admin'] },
        },
        {
          path: 'kelas-biaya/config/:id',
          name: 'Edit Kelas',
          component: EditKelasView,
          meta: { roles: ['admin'] },
        },
        {
          path: 'data-pelanggan',
          name: 'Data Pelanggan',
          component: pelangganView,
          meta: { roles: ['admin'] },
        },
        {
          path: 'data-pelanggan/tambah',
          name: 'Tambah Pelanggan',
          component: PelangganCreate,
          meta: { roles: ['admin'] },
        },
        {
          path: 'data-pelanggan/edit/:id',
          name: 'Edit Pelanggan',
          component: PelangganEdit,
          meta: { roles: ['admin'] },
        },
        {
          path: 'data-desa',
          name: 'Data Desa',
          component: DesaIndex,
          meta: { roles: ['admin'] },
        },
        {
          path: 'data-desa/tambah',
          name: 'Tambah Desa',
          component: DesaCreate,
          meta: { roles: ['admin'] },
        },
        {
          path: 'data-desa/edit/:id',
          name: 'Edit Desa',
          component: DesaEdit,
          meta: { roles: ['admin'] },
        },

        {
          path: 'dataInstalasi',
          name: 'Data Instalasi',
          component: datainstalasiView,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/register',
          name: 'Register Instalasi',
          component: registerInstalasi,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/status',
          name: 'Status Instalasi',
          component: statusInstalasi,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/hasil-survey',
          name: 'Hasil Survey',
          component: () => import('@/presentations/views/app/admin/instalasi/hasilSurvey.vue'),
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/status/permohonan/:id',
          name: 'Detail Permohonan',
          component: DetailPermohonan,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/status/pasang-baru/:id',
          name: 'Detail Pasang Baru',
          component: DetailPasangBaru,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/status/aktif/:id',
          name: 'Detail Aktif',
          component: DetailAktif,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/status/blokir/:id',
          name: 'Detail Blokir',
          component: DetailBlokir,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/status/cabut/:id',
          name: 'Detail Cabut',
          component: DetailCabut,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/pemakaian-air',
          name: 'Pemakaian Air',
          component: pemakaianair,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/pemakaian-air/input',
          name: 'Input Pemakaian Air',
          component: detailPemakaianAir,
          meta: { roles: ['admin'] },
        },
        {
          path: 'instalasi/daftar-tagihan',
          name: 'Daftar Tagihan',
          component: () => import('@/presentations/views/app/admin/tagihan/daftarTagihan.vue'),
          meta: { roles: ['admin'] },
        },
        {
          path: 'survey/create',
          name: 'Create Survey',
          component: () => import('@/presentations/views/app/surveyor/createSurvey.vue'),
          meta: { roles: ['admin', 'surveyor'] },
        },
        {
          path: 'teknisi/pencatatan-meter',
          name: 'Catat Meter',
          component: () => import('@/presentations/views/app/teknisi/MeterReading.vue'),
          meta: { roles: ['admin', 'teknisi'] },
        },
        {
          path: 'teknisi/hasil-instalasi/:id',
          name: 'Hasil Instalasi',
          component: () => import('@/presentations/views/app/teknisi/InstallationResult.vue'),
          meta: { roles: ['admin', 'teknisi'] },
        },
        {
          path: 'pelanggan/dashboard',
          name: 'Dashboard Pelanggan',
          component: () => import('@/presentations/views/app/pelanggan/DashboardMain.vue'),
          meta: { roles: ['admin', 'pelanggan'] },
        },
        {
          path: 'pelanggan/tagihan-detail',
          name: 'Detail Tagihan',
          component: () => import('@/presentations/views/app/pelanggan/BillDetail.vue'),
          meta: { roles: ['admin', 'pelanggan'] },
        },
        {
          path: 'pelanggan/riwayat-tagihan',
          name: 'Riwayat Tagihan',
          component: () => import('@/presentations/views/app/pelanggan/riwayatTagihan.vue'),
          meta: { roles: ['admin', 'pelanggan'] },
        },
        {
          path: 'pelanggan/lapor-gangguan',
          name: 'Lapor Gangguan',
          component: () => import('@/presentations/views/app/pelanggan/LaporGangguan.vue'),
          meta: { roles: ['admin', 'pelanggan'] },
        },
        {
          path: 'pelanggan/lapor-gangguan/form',
          name: 'Form Lapor Gangguan',
          component: () => import('@/presentations/views/app/pelanggan/LaporGangguanForm.vue'),
          meta: { roles: ['admin', 'pelanggan'] },
        },
        {
          path: 'instalasi/teknisiPemakaianAir',
          name: 'Input Pemakaian Air Teknisi',
          component: TeknisiPemakaianAir,
          meta: { roles: ['admin', 'teknisi'] },
        },
        {
          path: 'teknisi/daftar-tagihan',
          name: 'Daftar Tagihan Teknisi',
          component: () => import('@/presentations/views/app/teknisi/DaftarTagihan.vue'),
          meta: { roles: ['admin', 'teknisi'] },
        },

        {
          path: 'transaksi/jurnal-umum',
          name: 'transaksi jurnal umum',
          component: jurnalUmum,
          meta: { roles: ['admin'] },
        },
        {
          path: 'transaksi/tagihan-instalasi',
          name: 'transaksi Intalasi',
          component: tagihanInstalasi,
          meta: { roles: ['admin'] },
        },
        {
          path: 'transaksi/tagihan-bulanan',
          name: 'transaksi bulanan',
          component: tagihanBulanan,
          meta: { roles: ['admin'] },
        },
        {
          path: 'transaksi/E-budgeting',
          name: 'transaksi E-Budgeting',
          component: ebudgeting,
          meta: { roles: ['admin'] },
        },
        {
          path: 'transaksi/tutup-buku',
          name: 'transaksi tutup buku',
          component: tutupBuku,
          meta: { roles: ['admin'] },
        },
        {
          path: 'transaksi/alokasi-laba',
          name: 'transaksi alokasi laba',
          component: alokasiLaba,
          meta: { roles: ['admin'] },
        },
        {
          path: 'transaksi/komisi-sps',
          name: 'transaksi komisi sps',
          component: komisiSPS,
          meta: { roles: ['admin'] },
        },
        {
          path: 'Pelaporan',
          name: 'Pelaporan',
          component: laporan,
          meta: { roles: ['admin'] },
        },
      ],
    },
    {
      path: '/usages/cetak_input',
      name: 'Cetak Input',
      meta: { roles: ['admin', 'teknisi'] },
      component: () =>
        import('@/presentations/views/app/admin/instalasi/partials/view/cetakInput.vue'),
    },
  ],
})

const isExpired = () => {
  const token = localStorage.getItem('auth_token')
  const expiresAt = localStorage.getItem('auth_expires_at')
  if (!token || !expiresAt) return false
  return Date.now() > parseInt(expiresAt, 10)
}

const clearAuth = () => {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user_data')
  localStorage.removeItem('user_role')
  localStorage.removeItem('auth_expires_at')
}

const canAccess = (to, userRole) => {
  const requiredRoles = to.matched
    .slice()
    .reverse()
    .find((record) => record.meta?.roles)?.meta?.roles

  if (!requiredRoles || requiredRoles.length === 0) {
    return { allowed: true }
  }

  if (requiredRoles.includes(userRole)) {
    return { allowed: true }
  }

  return {
    allowed: false,
    reason: `Role "${userRole}" tidak diizinkan mengakses "${to.path}"`,
  }
}

router.beforeEach((to) => {
  const token = localStorage.getItem('auth_token')
  const userRole = localStorage.getItem('user_role') || 'admin'

  if (to.meta?.guest && token) {
    return { path: getDashboardRoute(userRole) }
  }

  if (to.path.startsWith('/app') || to.meta?.auth) {
    if (!token || isExpired()) {
      clearAuth()
      return { name: 'login' }
    }
  }

  if (to.matched.some((record) => record.meta?.roles)) {
    const check = canAccess(to, userRole)
    if (!check.allowed) {
      return {
        name: 'login',
        query: { error: 'unauthorized', from: to.fullPath },
      }
    }
  }

  return true
})

router.afterEach((to) => {
  if (to.query?.error === 'unauthorized') {
    console.warn('[Router] Akses ditolak:', to.query.from)
  }
})

export default router
