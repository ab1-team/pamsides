import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/presentations/views/auth/LoginView.vue'
import MainView from '@/presentations/layouts/dashboard/MainView.vue'
import AdminDashbord from '@/presentations/views/dashboard/admin/DashboardMain.vue'
import SurveyorDashboard from '@/presentations/views/dashboard/surveyor/DashboardMain.vue'
import TeknisiDashboard from '@/presentations/views/dashboard/teknisi/DashboardMain.vue'
import PelangganDashboard from '@/presentations/views/dashboard/pelanggan/DashboardMain.vue'
import { useUiStore } from '@/stores/uiStore'
import SopIndex from '@/presentations/views/dashboard/admin/sop/SopIndex.vue'
import KelasBiayaView from '@/presentations/views/dashboard/admin/kelas/KelasIndex.vue'
import CreateKelasView from '@/presentations/views/dashboard/admin/kelas/KelasCreate.vue'
import EditKelasView from '@/presentations/views/dashboard/admin/kelas/KelasEdit.vue'
import pelangganView from '@/presentations/views/dashboard/admin/pelanggan/PelangganIndex.vue'
import PelangganCreate from '@/presentations/views/dashboard/admin/pelanggan/PelangganCreate.vue'
import PelangganEdit from '@/presentations/views/dashboard/admin/pelanggan/PelangganEdit.vue'
import caterView from '@/presentations/views/dashboard/admin/cater/CaterIndex.vue'
import CaterCreate from '@/presentations/views/dashboard/admin/cater/CaterCreate.vue'
import CaterEdit from '@/presentations/views/dashboard/admin/cater/CaterEdit.vue'
import desaView from '@/presentations/views/dashboard/admin/desa/DesaIndex.vue'
import DesaCreate from '@/presentations/views/dashboard/admin/desa/DesaCreate.vue'
import DesaEdit from '@/presentations/views/dashboard/admin/desa/DesaEdit.vue'
import datainstalasiView from '@/presentations/views/dashboard/admin/instalasi/dataInstalasi.vue'
import registerInstalasi from '@/presentations/views/dashboard/admin/instalasi/registrasi.vue'
import statusInstalasi from '@/presentations/views/dashboard/admin/instalasi/InstalasiStatus.vue'
import TeknisiPemakaianAir from '@/presentations/views/dashboard/teknisi/PemakaianAir.vue'
import pemakaianair from '@/presentations/views/dashboard/admin/instalasi/pemakaianAir.vue'
import retribusisampah from '@/presentations/views/dashboard/admin/instalasi/retribusiSampah.vue'
import DetailPermohonan from '@/presentations/views/dashboard/admin/instalasi/partials/permohonan.vue'
import DetailPasangBaru from '@/presentations/views/dashboard/admin/instalasi/partials/pasangBaru.vue'
import DetailAktif from '@/presentations/views/dashboard/admin/instalasi/partials/aktif.vue'
import DetailBlokir from '@/presentations/views/dashboard/admin/instalasi/partials/blokir.vue'
import DetailCabut from '@/presentations/views/dashboard/admin/instalasi/partials/cabut.vue'
import jurnalUmum from '@/presentations/views/dashboard/admin/transaksi/jurnalUmum/JurnalUmumIndex.vue'
import tagihanInstalasi from '@/presentations/views/dashboard/admin/transaksi/Tagihan/tagihanInstalasi.vue'
import tagihanBulanan from '@/presentations/views/dashboard/admin/transaksi/Tagihan/tagihanBulanan.vue'
import alokasiLaba from '@/presentations/views/dashboard/admin/transaksi/arsip/alokasiLaba.vue'
import ebudgeting from '@/presentations/views/dashboard/admin/transaksi/EBudgetingView.vue'
import tutupBuku from '@/presentations/views/dashboard/admin/transaksi/tutupBuku.vue'
import komisiSPS from '@/presentations/views/dashboard/admin/transaksi/komisiSPS.vue'
import laporan from '@/presentations/views/dashboard/admin/pelaporan/PelaporanIndex.vue'
import profil from '@/presentations/views/dashboard/admin/profil/ProfilIndex.vue'
import detailPemakaianAir from '@/presentations/views/dashboard/admin/instalasi/partials/detailPemakaianAir.vue'

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
    },
    {
      path: '/dashboard',
      name: 'layout-dashboard',
      component: MainView,
      children: [
        {
          path: '',
          name: 'dashboard',
          component: () => {
            const uiStore = useUiStore()
            switch (uiStore.userRole) {
              case 'surveyor':
                return SurveyorDashboard
              case 'teknisi':
                return TeknisiDashboard
              case 'pelanggan':
                return PelangganDashboard
              default:
                return AdminDashbord
            }
          },
        },
        {
          path: '/profil',
          name: 'profil',
          component: profil,
        },
        {
          path: '/settings/personalisasi-sop',
          name: 'personalisasi-sop',
          component: SopIndex,
        },
        {
          path: '/settings/coa',
          name: 'coa',
          component: () => import('@/presentations/views/dashboard/admin/sop/CoaIndex.vue'),
        },
        {
          path: '/kelas-biaya',
          name: 'kelas biaya',
          component: KelasBiayaView,
        },
        {
          path: '/kelas-biaya/config',
          name: 'Tambah Kelas',
          component: CreateKelasView,
        },
        {
          path: '/kelas-biaya/config/:id',
          name: 'Edit Kelas',
          component: EditKelasView,
        },
        {
          path: '/data-pelanggan',
          name: 'Data Pelanggan',
          component: pelangganView,
        },
        {
          path: '/data-pelanggan/tambah',
          name: 'Tambah Pelanggan',
          component: PelangganCreate,
        },
        {
          path: '/data-pelanggan/edit/:id',
          name: 'Edit Pelanggan',
          component: PelangganEdit,
        },
        {
          path: '/data-cater',
          name: 'Data Cater',
          component: caterView,
        },
        {
          path: '/data-cater/tambah',
          name: 'Tambah Cater',
          component: CaterCreate,
        },
        {
          path: '/data-cater/edit/:id',
          name: 'Edit Cater',
          component: CaterEdit,
        },
        {
          path: '/data-desa',
          name: 'Data Desa',
          component: desaView,
        },
        {
          path: '/data-desa/tambah',
          name: 'Tambah Desa',
          component: DesaCreate,
        },
        {
          path: '/data-desa/edit/:id',
          name: 'Edit Desa',
          component: DesaEdit,
        },
        {
          path: '/dataInstalasi',
          name: 'Data Instalasi',
          component: datainstalasiView,
        },
        {
          path: '/instalasi/register',
          name: 'Register Instalasi',
          component: registerInstalasi,
        },
        {
          path: '/instalasi/status',
          name: 'Status Instalasi',
          component: statusInstalasi,
        },
        {
          path: '/instalasi/status/permohonan/:id',
          name: 'Detail Permohonan',
          component: DetailPermohonan,
        },
        {
          path: '/instalasi/status/pasang-baru/:id',
          name: 'Detail Pasang Baru',
          component: DetailPasangBaru,
        },
        {
          path: '/instalasi/status/aktif/:id',
          name: 'Detail Aktif',
          component: DetailAktif,
        },
        {
          path: '/instalasi/status/blokir/:id',
          name: 'Detail Blokir',
          component: DetailBlokir,
        },
        {
          path: '/instalasi/status/cabut/:id',
          name: 'Detail Cabut',
          component: DetailCabut,
        },
        {
          path: '/instalasi/pemakaian-air',
          name: 'Pemakaian Air',
          component: pemakaianair,
        },
        {
          path: '/instalasi/pemakaian-air/input',
          name: 'Input Pemakaian Air',
          component: detailPemakaianAir,
        },
        {
          path: '/survey/input',
          name: 'Input Survey',
          component: () => import('@/presentations/views/dashboard/surveyor/TicketSurvey.vue'),
        },
        {
          path: '/teknisi/pencatatan-meter',
          name: 'Catat Meter',
          component: () => import('@/presentations/views/dashboard/teknisi/MeterReading.vue'),
        },
        {
          path: '/pelanggan/tagihan-detail',
          name: 'Detail Tagihan',
          component: () => import('@/presentations/views/dashboard/pelanggan/BillDetail.vue'),
        },
        {
          path: '/instalasi/teknisiPemakaianAir',
          name: 'Input Pemakaian Air Teknisi',
          component: TeknisiPemakaianAir,
        },
        {
          path: '/instalasi/retribusi-sampah',
          name: 'Retribusi Sampah',
          component: retribusisampah,
        },
        {
          path: '/transaksi/jurnal-umum',
          name: 'transaksi jurnal umum',
          component: jurnalUmum,
        },
        {
          path: '/transaksi/tagihan-instalasi',
          name: 'transaksi Intalasi',
          component: tagihanInstalasi,
        },
        {
          path: '/transaksi/tagihan-bulanan',
          name: 'transaksi bulanan',
          component: tagihanBulanan,
        },
        {
          path: '/transaksi/E-budgeting',
          name: 'transaksi E-Budgeting',
          component: ebudgeting,
        },
        {
          path: '/transaksi/tutup-buku',
          name: 'transaksi tutup buku',
          component: tutupBuku,
        },
        {
          path: '/transaksi/alokasi-laba',
          name: 'transaksi alokasi laba',
          component: alokasiLaba,
        },
        {
          path: '/transaksi/komisi-sps',
          name: 'transaksi komisi sps',
          component: komisiSPS,
        },
        {
          path: '/Pelaporan',
          name: 'Pelaporan',
          component: laporan,
        },
      ],
    },
    {
      path: '/usages/cetak_input',
      name: 'Cetak Input',
      component: () =>
        import('@/presentations/views/dashboard/admin/instalasi/partials/view/cetakInput.vue'),
    },
  ],
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  const isAuthPage = to.name === 'login'

  if (to.path.startsWith('/dashboard') || to.path === '/') {
    if (!token) {
      next({ name: 'login' })
    } else {
      next()
    }
  } else if (isAuthPage && token) {
    next({ name: 'dashboard' })
  } else {
    next()
  }
})

export default router
