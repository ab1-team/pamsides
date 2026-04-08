<template>
  <div class="dashboard-layout" :class="{ 'sidebar-collapsed': !sidebarOpen }">
    <div
      class="mobile-overlay"
      :class="{ active: mobileSidebarOpen }"
      @click="closeMobileSidebar"
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
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { MySwal } from '../../../main.js'
import SidebarView from './SidebarView.vue'
import TopNavigationView from './TopNavigationView.vue'
import FooterView from './FooterView.vue'

const router = useRouter()
const route = useRoute()

const sidebarOpen = ref(true)
const mobileSidebarOpen = ref(false)
const mobileSearchOpen = ref(false)
const searchQuery = ref('')

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
  searchQuery.value = event.target.value
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
    router.push('/login?logout=success')
  }
}

const handleKeyboardShortcuts = (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    document.querySelector('.topnav-search-modern input')?.focus()
  }
}

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
