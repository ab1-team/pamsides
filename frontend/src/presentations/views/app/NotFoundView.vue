<template>
  <div class="not-found-root flex flex-col items-center justify-center text-center py-16 px-6">
    <div
      class="w-20 h-20 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white flex items-center justify-center text-3xl font-black shadow-lg mb-6"
    >
      404
    </div>
    <h1 class="text-2xl font-extrabold text-slate-800 mb-2">Halaman Tidak Ditemukan</h1>
    <p class="text-sm text-slate-500 max-w-md mb-6">
      Alamat yang Anda buka tidak ada dalam sistem. Periksa kembali tautan, atau kembali ke
      beranda.
    </p>
    <div class="flex flex-wrap gap-3 justify-center">
      <BaseButton variant="info-gradient" size="md" icon="home" @click="goHome">
        Kembali ke Beranda
      </BaseButton>
      <BaseButton variant="secondary" size="md" icon="arrow-left" @click="goBack">
        Halaman Sebelumnya
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'

const router = useRouter()

const goHome = () => {
  router.push('/app')
}

const goBack = () => {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/app')
  }
}
</script>

<style scoped>
.not-found-root {
  min-height: 60vh;
}
</style>
