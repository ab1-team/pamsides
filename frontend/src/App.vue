<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <RouterView />
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

#app {
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
</style>
