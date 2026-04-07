import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/auth/LoginView.vue'
import MainView from '@/views/dashboard/layout/MainView.vue'
import DashboardIndex from '@/views/dashboard/DashboardIndex.vue'
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
import statusInstalasi from '@/views/dashboard/instalasi/status.vue'
import pemakaianair from '@/views/dashboard/instalasi/pemakaianAir.vue'
import retribusisampah from '@/views/dashboard/instalasi/retribusiSampah.vue'
import JurnalUmum from '@/views/dashboard/transaksi/jurnalUmum/JurnalUmum.vue'
import tagihanInstalasi from '@/views/dashboard/transaksi/Tagihan/tagihanInstalasi.vue'
import tagihanBulanan from '@/views/dashboard/transaksi/Tagihan/tagihanBulanan.vue'
import alokasiLaba from '@/views/dashboard/transaksi/arsip/alokasiLaba.vue'
import EBudgetingView from '@/views/dashboard/transaksi/EBudgetingView.vue'
import tutupBuku from '@/views/dashboard/transaksi/tutupBuku.vue'
import komisiSPS from '@/views/dashboard/transaksi/komisiSPS.vue'
import PelaporanView from '@/views/dashboard/pelaporan/PelaporanView.vue'

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
          component: DashboardIndex,
        },
        {
          path: '/settings/personalisasi-sop',
          name: 'personalisasi-sop',
          component: () => import('@/views/dashboard/settings/SopView.vue'),
        },
        {
          path: '/settings/coa',
          name: 'coa',
          component: () => import('@/views/dashboard/settings/COAView.vue'),
        },
        {
          path: '/settings/kelas-biaya',
          name: 'kelas-biaya',
          component: () => import('@/views/dashboard/settings/KelasBiayaView.vue'),
        },
        {
          path: '/data/pelanggan/create',
          name: 'create-pelanggan',
          component: () => import('@/views/dashboard/basis-data/pelanggan/CreateView.vue'),
        },
        {
          path: '/data/pelanggan',
          name: 'pelanggan',
          component: () => import('@/views/dashboard/basis-data/pelanggan/IndexView.vue'),
        },
        {
          path: '/data/desa/create',
          name: 'create-desa',
          component: () => import('@/views/dashboard/basis-data/desa/CreateView.vue'),
        },
        {
          path: '/data/desa',
          name: 'desa',
          component: () => import('@/views/dashboard/basis-data/desa/IndexView.vue'),
        },
        {
          path: '/data/caters/create',
          name: 'create-caters',
          component: () => import('@/views/dashboard/basis-data/cater/CreateView.vue'),
        },
        {
          path: '/data/caters',
          name: 'caters',
          component: () => import('@/views/dashboard/basis-data/cater/IndexView.vue'),
        },
        {
          path: '/data/instalasi',
          name: 'data-instalasi',
          component: () => import('@/views/dashboard/basis-data/data-instalasi/IndexView.vue'),
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
          path: '/instalasi/pemakaian-air',
          name: 'Pemakaian Air',
          component: pemakaianair,
        },
        {
          path: '/instalasi/retribusi-sampah',
          name: 'Retribusi Sampah',
          component: retribusisampah,
        },
        {
          path: '/transaksi/jurnal-umum',
          name: 'transaksi jurnal umum',
          component: JurnalUmum,
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
          path: '/transaksi/e-budgeting',
          name: 'transaksi E-Budgeting',
          component: EBudgetingView,
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
          component: PelaporanView,
        },
      ],
    },
  ],
})

export default router
