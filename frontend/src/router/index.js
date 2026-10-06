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
import ArsipTagihan from '@/presentations/views/app/admin/arsipDashbord/ArsipTagihan.vue'
import ArsipTunggakan from '@/presentations/views/app/admin/arsipDashbord/ArsipTunggakan.vue'
import ArsipPemakaian from '@/presentations/views/app/admin/arsipDashbord/ArsipPemakaian.vue'
import ArsipInstalasi from '@/presentations/views/app/admin/arsipDashbord/ArsipInstalasi.vue'
import NotFoundView from '@/presentations/views/app/NotFoundView.vue'

import { getDashboardRoute } from './dashboardRoutes'

// NOTE: DashboardHome merender dashboard per-role lewat dynamic component,
// jadi admin/surveyor/pelanggan berbagi path /app. Teknisi punya route khusus
// (/app/teknisi) yang me-render komponen yang sama.


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
          // Rute lama menunjuk MeterReading.vue, yaitu halaman MOCK:
          // nama pelanggan, nomor ID, dan angka meter di-hardcode
          // ("Budi Darmawan", 1240 m³) dan submit-nya memanggil
          // POST /installation-tickets/METER-MOCK/installation-result
          // yang selalu 404 karena route-model binding.
          //
          // Alur pencatatan meter yang benar sudah ada di PemakaianAir.vue
          // → detailPemakaianAir.vue dan memakai meterService. Route ini
          // sekarang mengarah ke sana supaya tidak ada tautan/yangl/error
          // menuju halaman yang tidak bisa dipakai.
          path: 'teknisi/pencatatan-meter',
          name: 'Catat Meter',
          redirect: '/app/instalasi/teknisiPemakaianAir',
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
        {
          path: 'arsip/tagihan',
          name: 'Arsip Tagihan',
          component: ArsipTagihan,
        },
        {
          path: 'arsip/tunggakan',
          name: 'Arsip Tunggakan',
          component: ArsipTunggakan,
        },
        {
          path: 'arsip/pemakaian',
          name: 'Arsip Pemakaian',
          component: ArsipPemakaian,
        },
        {
          path: 'arsip/instalasi',
          name: 'Arsip Instalasi',
          component: ArsipInstalasi,
        },
        {
          path: ':pathMatch(.*)*',
          name: 'NotFound',
          component: NotFoundView,
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
    {
      path: '/:pathMatch(.*)*',
      redirect: '/app',
    },
  ],
})

/**
 * Peta role → path yang boleh dibuka, dan siapa saja yang boleh.
 *
 * Format: `prefixes` untuk route bertipe prefix (agar guard tetap benar
 * meski penulisan trailed slash berbeda), `roles` untuk role yang diizinkan.
 *
 * PENTING: admin sengaja TIDAK diberi akses ke prefix role lain. Sebelumnya
 * admin exempted dari semua cek (`!['pelanggan','admin'].includes(userRole)`),
 * sehingga admin bisa membuka /app/pelanggan/* lalu mendapat 403 dari backend
 * karena route-nya `role:pelanggan`. Admin yang salah klik tidak seharusnya
 * melihat halaman kosong.
 */
const ROLE_GUARDS = [
  {
    key: 'surveyor',
    prefixes: ['/app/survey/create', '/app/surveyor'],
    roles: ['surveyor', 'admin'],
  },
  {
    key: 'teknisi',
    // Perhatikan: prefix TIDAK memakai trailing slash supaya `/app/teknisi`
    // (tanpa garis miring akhir) ikut tertangkap. Sebelumnya daftar memakai
    // '/app/teknisi/' sehingga path persis '/app/teknisi' lolos tanpa cek.
    prefixes: ['/app/teknisi', '/app/instalasi/teknisiPemakaianAir'],
    roles: ['teknisi', 'admin'],
  },
  {
    key: 'pelanggan',
    prefixes: ['/app/pelanggan'],
    roles: ['pelanggan'],
  },
  {
    key: 'admin',
    prefixes: [
      '/app/data-pelanggan',
      '/app/data-desa',
      // PENTING: '/app/dataInstalasi' memakai huruf I besar, sedangkan
      // '/app/data-pelanggan' memakai tanda hubung. Sebelumnya hanya yang
      // terdaftar sehingga /app/dataInstalasi tidak pernah terproteksi.
      '/app/dataInstalasi',
      '/app/arsip',
      '/app/settings',
      '/app/kelas-biaya',
      '/app/transaksi',
      '/app/pelaporan',
      // Halaman instalasi yang di sidebar eksklusif admin. Tanpa prefix
      // di sini, teknisi/pelanggan yang mengetik URL secara manual akan
      // lolos ke halaman lalu baru kena 403 dari backend — UX buruk yang
      // persis seperti yang terjadi di /app/pelanggan dulu.
      '/app/instalasi/register',
      '/app/instalasi/status',
      '/app/instalasi/daftar-tagihan',
    ],
    roles: ['admin'],
  },
  {
    key: 'admin-teknisi',
    // Route backend `meter-readings/*` dan `monthly-bills/*` dibuka untuk
    // admin DAN teknisi, jadi halaman pemakaiknya tidak boleh dikunci
    // admin-only. Yang dilindungi di sini hanya akses role lain
    // (pelanggan/surveyor) yang memang tidak punya endpoint-nya.
    prefixes: ['/app/instalasi/pemakaian-air'],
    roles: ['admin', 'teknisi'],
  },
]

/**
 * Cocokkan path dengan daftar prefix, aman terhadap perbedaan trailing slash.
 * '/app/teknisi' cocok dengan '/app/teknisi', '/app/teknisi/',
 * dan '/app/teknisi/pencatatan-meter'.
 */
const matchesPrefix = (path, prefix) => {
  if (path === prefix) return true
  return path.startsWith(prefix + '/')
}

/**
 * Semua prefix yang PUNYA batasan role selain "hanya butuh login".
 * Rute seperti /app/profil dan /usages/cetak_* sengaja tidak ada di sini.
 */
const GUARDED_PREFIXES = ROLE_GUARDS.flatMap((guard) =>
  guard.prefixes.map((prefix) => ({ prefix, roles: guard.roles })),
)

router.beforeEach((to) => {
  const token = localStorage.getItem('auth_token')
  const expiresAt = localStorage.getItem('auth_expires_at')
  const storedRole = localStorage.getItem('user_role')
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

  // Cek autentikasi DULUAN, sebelum bicara soal role.
  //
  // Urutan ini penting: `userRole` lama diberi fallback ke 'admin' ketika
  // localStorage kosong, sehingga setiap keputusan role untuk tamu selalu
  // berakhir "diizinkan" dan satu-satunya penjaga adalah `if (!token)`.
  // Menolak tamu lebih dulu membuat keputusan role hanya berlaku untuk user
  // yang benar-benar punya sesi.
  if (!token) {
    if (isAuthPage) return true
    return {
      name: 'login'
    }
  }

  // Sudah login: jangan tampilkan halaman login lagi.
  if (isAuthPage) {
    return {
      path: getDashboardRoute(storedRole || 'admin')
    }
  }

  // Role yang tidak dikenal (mis. enum DB ditambah nanti) tidak boleh
  // mendapat akses apa pun yang dibatasi — fail closed.
  const userRole = ROLE_GUARDS.some((g) => g.roles.includes(storedRole))
    ? storedRole
    : 'unknown'

  if (userRole === 'unknown') {
    // Paksa logout supaya user bisa login ulang dan mendapat role yang valid.
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user_data')
    localStorage.removeItem('user_role')
    localStorage.removeItem('auth_expires_at')

    return {
      name: 'login'
    }
  }

  // Temukan prefix yang cocok. Prefix terpanjang didahulukan supaya
  // '/app/instalasi/teknisiPemakaianAir' menang atas '/app/instalasi'.
  const matched = GUARDED_PREFIXES
    .filter(({ prefix }) => matchesPrefix(to.path, prefix))
    .sort((a, b) => b.prefix.length - a.prefix.length)[0]

  if (matched && !matched.roles.includes(userRole)) {
    // Jangan lempar ke halaman login (user sudah login dan itu membingungkan,
    // serta memicu redirect bolak-balik). Kembalikan ke dashboard role-nya.
    return {
      path: getDashboardRoute(userRole)
    }
  }

  return true
})

const APP_NAME = 'PAMSIDES'

const humanize = (name) => {
  if (!name || typeof name !== 'string') return ''
  return name
    .replace(/[-_]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .split(' ')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')
}

router.afterEach((to) => {
  const baseName = humanize(to.name)
  document.title = baseName ? `${baseName} - ${APP_NAME}` : APP_NAME
})

export default router
