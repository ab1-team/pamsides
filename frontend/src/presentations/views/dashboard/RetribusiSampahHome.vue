<template>
  <component :is="activeComponent" />
</template>

<script setup>
import { computed } from 'vue'
import { useUiStore } from '@/stores/uiStore'
import adminRetribusiSampah from './admin/instalasi/retribusiSampah.vue'
import teknisiRetribusiSampah from './teknisi/retribusiSampah.vue'

const uiStore = useUiStore()

const activeComponent = computed(() => {
  return uiStore.userRole === 'teknisi' ? teknisiRetribusiSampah : adminRetribusiSampah
})
</script>
