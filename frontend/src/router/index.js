import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/auth/LoginView.vue'
import MainView from '@/views/dashboard/layout/MainView.vue'
import Dashbord from '@/views/dashboard/DashboardMain.vue'
// import SopView from '@/views/dashboard/settings/SopView.vue'
// import COAView from '@/views/dashboard/settings/COAView.vue'
// import KelasBiayaView from '@/views/dashboard/settings/KelasBiayaView.vue'
// import cretaepelangganView from '@/views/dashboard/basis-data/pelanggan/CreateView.vue'
// import editpelangganView from '@/views/dashboard/basis-data/pelanggan/EditView.vue'
// import pelangganView from '@/views/dashboard/basis-data/pelanggan/IndexView.vue'
// import createdesaView from '@/views/dashboard/basis-data/desa/CreateView.vue'
// import editdesaView from '@/views/dashboard/basis-data/desa/EditView.vue'
// import desaView from '@/views/dashboard/basis-data/desa/IndexView.vue'
// import createcaterView from '@/views/dashboard/basis-data/cater/CreateView.vue'
// import caterView from '@/views/dashboard/basis-data/cater/IndexView.vue'
// import datainstalasiView from '@/views/dashboard/basis-data/data-instalasi/IndexView.vue'
import registerInstalasi from '@/views/dashboard/instalasi/registrasi.vue'
import statusInstalasi from '@/views/dashboard/instalasi/InstalasiStatus.vue'
import Caterpemakaianair from '@/views/dashboard/instalasi/caterPemakaianAir.vue'
import pemakaianair from '@/views/dashboard/instalasi/pemakaianAir.vue'
import retribusisampah from '@/views/dashboard/instalasi/retribusiSampah.vue'
import jurnalUmum from '@/views/dashboard/transaksi/jurnalUmum/JurnalUmumIndex.vue'
import tagihanInstalasi from '@/views/dashboard/transaksi/Tagihan/tagihanInstalasi.vue'
import tagihanBulanan from '@/views/dashboard/transaksi/Tagihan/tagihanBulanan.vue'
import alokasiLaba from '@/views/dashboard/transaksi/arsip/alokasiLaba.vue'
import ebudgeting from '@/views/dashboard/transaksi/EBudgetingView.vue'
import tutupBuku from '@/views/dashboard/transaksi/tutupBuku.vue'
import komisiSPS from '@/views/dashboard/transaksi/komisiSPS.vue'
import laporan from '@/views/dashboard/pelaporan/PelaporanIndex.vue'

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
        // {
        //   path: '/settings',
        //   name: 'settings',
        //   component: SopView,
        // },
        // {
        //   path: '/settings',
        //   name: 'settings',
        //   component: COAView,
        // },
        // {
        //   path: '/settings',
        //   name: 'settings',
        //   component: KelasBiayaView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: cretaepelangganView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: editpelangganView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: pelangganView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: createdesaView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: editdesaView,
        // },
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: desaView,
        // },
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
        // {
        //   path: '/BasisData',
        //   name: 'BasisData',
        //   component: datainstalasiView,
        // },
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
          path: '/instalasi/pemakaian-air',
          name: 'Pemakaian Air',
          component: pemakaianair,
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
  ],
})

export default router
