<template>
  <div class="dashboard-home">
    <component :is="activeDashboard" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useUiStore } from '@/stores/uiStore'

import AdminDashboard from './admin/DashboardMain.vue'
import SurveyorDashboard from './surveyor/DashboardMain.vue'
import TeknisiDashboard from './teknisi/DashboardMain.vue'
import PelangganDashboard from './pelanggan/DashboardMain.vue'

const uiStore = useUiStore()

const activeDashboard = computed(() => {
  const role = uiStore.userRole

  if (role === 'surveyor') return SurveyorDashboard
  if (role === 'teknisi') return TeknisiDashboard
  if (role === 'pelanggan') return PelangganDashboard

  return AdminDashboard
})

// Generate piutang SENGAJA TIDAK dipicu dari komponen ini.
//
// Dulu `checkOverdueGenOnMount()` dipanggil di `onMounted` di sini. Karena
// `onMounted` berjalan setiap kali komponen di-mount, memetik menu
// Dashboard — atau kembali ke dashboard dari halaman lain — akan menjalankan
// generate piutang LAGI. Itu bukan yang diminta: generate harus berjalan
// setiap kali login BERHASIL, bukan setiap kali dashboard dibuka.
//
// Pemicunya sekarang hanya `LoginView.vue`, tepat setelah login sukses.
// Membuka dashboard sebanyak apa pun tidak menambah satu perhitungan pun.
</script>

<style scoped>
.dashboard-home {
  width: 100%;
  height: 100%;
}
</style>
