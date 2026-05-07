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
</script>

<style scoped>
.dashboard-home {
  width: 100%;
  height: 100%;
}
</style>
