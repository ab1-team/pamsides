<script setup>
import { watch } from 'vue'
import { RouterView } from 'vue-router'
import { useUiStore } from '@/stores/uiStore'
import { useToast } from 'primevue/usetoast'

const uiStore = useUiStore()
const toast = useToast()

// Watch for global toast messages
watch(
  () => uiStore.toastMessage,
  (newMsg) => {
    if (newMsg) {
      toast.add(newMsg)
    }
  },
)
</script>

<template>
  <div id="app-root">
    <PrimeToast />
    <div v-if="uiStore.loading" class="global-loader">
      <ProgressBar mode="indeterminate" style="height: 4px" />
    </div>
    <RouterView />
  </div>
</template>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html,
body {
  height: 100%;
  width: 100%;
  overflow-x: hidden;
}

body {
  font-family:
    'Inter',
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    Roboto,
    sans-serif;
}

#app,
#app-root {
  height: 100%;
  width: 100%;
  min-width: 100%;
}

.swal-progress-success {
  background: #22c55e !important;
}
.swal-toast-custom .swal2-title {
  margin-bottom: 0 !important;
}

.swal-toast-custom .swal2-html-container {
  margin-top: 5px !important;
}
.global-loader {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
}
</style>
