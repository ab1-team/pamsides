<template>
  <div class="dashboard-home">
    <component :is="activeDashboard" />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import { useOverdueGenNotification } from '@/composables/useOverdueGenNotification'

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

// Hanya role yang punya akses ke dashboard/auto-generate-overdue
// (admin & teknisi) yang boleh memicu generate otomatis.
const { checkOverdueGenOnMount } = useOverdueGenNotification()

onMounted(() => {
  const role = uiStore.userRole
  if (role === 'admin' || role === 'teknisi') {
    // Jalankan tanpa await: pop up akan muncul sendiri saat hasil siap.
    checkOverdueGenOnMount()
  }
})
</script>

<style scoped>
.dashboard-home {
  width: 100%;
  height: 100%;
}
</style>
