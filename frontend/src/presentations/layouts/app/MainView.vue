<template>
  <div class="dashboard-layout" :class="{ 'sidebar-collapsed': !sidebarOpen }">
    <div
      class="mobile-overlay"
      :class="{ active: mobileSidebarOpen }"
      @click="closeMobileSidebar"
    ></div>

    <div
      class="modal-backdrop-overlay"
      :class="{ active: uiStore.activeModalCount > 0 }"
      @click="uiStore.activeModalCount = 0"
    ></div>

    <SidebarView
      :sidebar-open="sidebarOpen"
      :mobile-sidebar-open="mobileSidebarOpen"
      @toggle-sidebar="sidebarOpen = !sidebarOpen"
      @close-mobile-sidebar="closeMobileSidebar"
    />

    <div class="main-content">
      <TopNavigationView
        :sidebar-open="sidebarOpen"
        :search-query="searchQuery"
        :mobile-search-open="mobileSearchOpen"
        @toggle-mobile-sidebar="toggleMobileSidebar"
        @toggle-mobile-search="toggleMobileSearch"
        @close-mobile-search="closeMobileSearch"
        @search="handleSearch"
        @select-result="handleSelectSearchResult"
        @logout="handleLogout"
      />

      <main class="dashboard-body">
        <RouterView />
        <FooterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { MySwal } from '@/utils/swal'
import axios from '@/utils/axios.js'
import SidebarView from './SidebarView.vue'
import TopNavigationView from './TopNavigationView.vue'
import FooterView from './FooterView.vue'
import { useUiStore } from '@/stores/uiStore'
import { useInstalasiStore } from '@/stores/instalasiStore'

const uiStore = useUiStore()
const instalasiStore = useInstalasiStore()

const router = useRouter()
const route = useRoute()

const sidebarOpen = ref(true)
const mobileSidebarOpen = ref(false)
const mobileSearchOpen = ref(false)
const searchQuery = ref(instalasiStore.searchQuery || '')

const toggleMobileSidebar = () => {
  mobileSidebarOpen.value = !mobileSidebarOpen.value
}

const closeMobileSidebar = () => {
  mobileSidebarOpen.value = false
}

const toggleMobileSearch = () => {
  mobileSearchOpen.value = !mobileSearchOpen.value
}

const closeMobileSearch = () => {
  mobileSearchOpen.value = false
}

const handleSearch = (event) => {
  const value = event?.target?.value ?? ''
  searchQuery.value = value
  // Sinkronkan ke store agar tabel di halaman Status Instalasi ikut terfilter
  instalasiStore.searchQuery = value
}

const handleSelectSearchResult = ({ routeName, id, category }) => {
  // Tutup mobile search popup jika terbuka
  closeMobileSearch()
  // Bersihkan query dari store & state lokal
  searchQuery.value = ''
  instalasiStore.searchQuery = ''
  // Pastikan activeStatus di store sesuai dengan kategori hasil
  if (category && instalasiStore.activeStatus !== category) {
    instalasiStore.activeStatus = category
  }
  // Reset halaman
  instalasiStore.currentPage = 1
  // Navigasi ke halaman detail
  router
    .push({ name: routeName, params: { id: encodeURIComponent(id) } })
    .catch(() => {})
}

const handleLogout = async () => {
  const result = await MySwal.fire({
    title: 'Konfirmasi Logout',
    text: 'Apakah Anda yakin ingin keluar?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'Ya, keluar',
    cancelButtonText: 'Batal',
    buttonsStyling: true,
    showClass: {
      popup: 'swal2-show',
      backdrop: 'swal2-backdrop-show',
      icon: 'swal2-icon-show',
    },
    hideClass: {
      popup: 'swal2-hide',
      backdrop: 'swal2-backdrop-hide',
      icon: 'swal2-icon-hide',
    },
  })

  if (result.isConfirmed) {
    try {
      const token = localStorage.getItem('auth_token')
      if (token) {
        await axios.post('/logout')
      }
    } catch (error) {
      // Sengaja ditelan: logout harus tetap membersihkan sesi lokal meski
      // POST /logout gagal (koneksi putus, token sudah kedaluwarsa).
      // Menahan pengguna di aplikasi karena request server gagal justru
      // membiarkan sesi half-login. Token server akan ditolak sendiri
      // setelah kedaluwarsa.
      console.warn('[Logout] gagal membatalkan token di server:', error?.message)
    } finally {
      const userData = JSON.parse(localStorage.getItem('user_data') || '{}')
      const userName = userData.name || ''

      // Bersihkan SEMUA key sesi. `auth_expires_at` wajib ikut dihapus:
      // sebelumnya tidak, sehingga di browser bersama timestamp milik user
      // lama bisa membuat user berikutnya ikut ter-logout paksa saat guard
      // menemukan now > expiresAt.
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user_role')
      localStorage.removeItem('user_data')
      localStorage.removeItem('auth_expires_at')

      // Catatan: penanda popup generate piutang yang pernah ada di
      // `sessionStorage` sudah DIHAPUS, jadi logout tidak perlu membersihkan
      // apa pun di sana lagi. Generate kini berjalan ulang di setiap login
      // pada tanggal toleransi, dengan dedup di level command backend.

      // Reset state store ke kondisi "belum login". Nilai lama 'admin'
      // membuat sidebar menampilkan menu admin dan roleTitle "Portal Admin"
      // di halaman login.
      uiStore.setUserRole('')
      uiStore.setUserData(null)
      uiStore.setLembagaName('')

      router.push(`/login?logout=true&name=${encodeURIComponent(userName)}`)
    }
  }
}

const handleKeyboardShortcuts = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    document.querySelector('.topnav-search-modern input')?.focus()
  }
}

// Selalu sinkronkan searchQuery lokal dengan store (untuk kasus navigasi)
watch(
  () => instalasiStore.searchQuery,
  (val) => {
    if (searchQuery.value !== val) searchQuery.value = val
  },
)

onMounted(() => {
  document.addEventListener('keydown', handleKeyboardShortcuts)
  if (route.query.login === 'success') {
    MySwal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Login Berhasil!',
      text: 'Selamat datang di PAMSIDES',
      timer: 2000,
      timerProgressBar: true,
      showConfirmButton: false,
      customClass: {
        popup: 'swal-toast-custom',
        title: 'swal-toast-title',
        container: 'swal-toast-container',
      },
    })
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyboardShortcuts)
})
</script>
