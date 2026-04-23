import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/auth/LoginView.vue'
import MainView from '@/views/dashboard/layout/MainView.vue'
import Dashbord from '@/views/dashboard/DashboardMain.vue'
import SopIndex from '@/views/dashboard/sop/SopIndex.vue'
import KelasBiayaView from '@/views/dashboard/kelas/KelasIndex.vue'
import CreateKelasView from '@/views/dashboard/kelas/KelasCreate.vue'
import EditKelasView from '@/views/dashboard/kelas/KelasEdit.vue'
import pelangganView from '@/views/dashboard/pelanggan/PelangganIndex.vue'
import PelangganCreate from '@/views/dashboard/pelanggan/PelangganCreate.vue'
import PelangganEdit from '@/views/dashboard/pelanggan/PelangganEdit.vue'
import caterView from '@/views/dashboard/cater/CaterIndex.vue'
import CaterCreate from '@/views/dashboard/cater/CaterCreate.vue'
import CaterEdit from '@/views/dashboard/cater/CaterEdit.vue'
import desaView from '@/views/dashboard/desa/DesaIndex.vue'
import DesaCreate from '@/views/dashboard/desa/DesaCreate.vue'
import DesaEdit from '@/views/dashboard/desa/DesaEdit.vue'
// import desaView from '@/views/dashboard/basis-data/desa/IndexView.vue'
// import createcaterView from '@/views/dashboard/basis-data/cater/CreateView.vue'
// import caterView from '@/views/dashboard/basis-data/cater/IndexView.vue'
import datainstalasiView from '@/views/dashboard/instalasi/dataInstalasi.vue'
import registerInstalasi from '@/views/dashboard/instalasi/registrasi.vue'
import statusInstalasi from '@/views/dashboard/instalasi/InstalasiStatus.vue'
import Caterpemakaianair from '@/views/dashboard/instalasi/caterPemakaianAir.vue'
import pemakaianair from '@/views/dashboard/instalasi/pemakaianAir.vue'
import retribusisampah from '@/views/dashboard/instalasi/retribusiSampah.vue'
import DetailPermohonan from '@/views/dashboard/instalasi/partials/permohonan.vue'
import DetailPasangBaru from '@/views/dashboard/instalasi/partials/pasangBaru.vue'
import DetailAktif from '@/views/dashboard/instalasi/partials/aktif.vue'
import DetailBlokir from '@/views/dashboard/instalasi/partials/blokir.vue'
import DetailCabut from '@/views/dashboard/instalasi/partials/cabut.vue'
import jurnalUmum from '@/views/dashboard/transaksi/jurnalUmum/JurnalUmumIndex.vue'
import tagihanInstalasi from '@/views/dashboard/transaksi/Tagihan/tagihanInstalasi.vue'
import tagihanBulanan from '@/views/dashboard/transaksi/Tagihan/tagihanBulanan.vue'
import alokasiLaba from '@/views/dashboard/transaksi/arsip/alokasiLaba.vue'
import ebudgeting from '@/views/dashboard/transaksi/EBudgetingView.vue'
import tutupBuku from '@/views/dashboard/transaksi/tutupBuku.vue'
import komisiSPS from '@/views/dashboard/transaksi/komisiSPS.vue'
import laporan from '@/views/dashboard/pelaporan/PelaporanIndex.vue'
import profil from '@/views/dashboard/profil/ProfilIndex.vue'
import detailPemakaianAir from '@/views/dashboard/instalasi/partials/detailPemakaianAir.vue'

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
          component: Dashbord,
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
          component: () => import('@/views/dashboard/sop/CoaIndex.vue'),
        },
        // {
        //   path: '/settings',
        //   name: 'settings',
        //   component: COAView,
        // },
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
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: createcaterView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: createcaterView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: caterView,
        // },
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
          path: '/instalasi/caterPemakaianAir',
          name: 'Cater Input Pemakaian Air',
          component: Caterpemakaianair,
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
      component: () => import('@/views/dashboard/instalasi/partials/view/cetakInput.vue'),
    },
  ],
})

export default router
