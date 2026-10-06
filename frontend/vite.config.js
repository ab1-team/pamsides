import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Vite TIDAK mengisi `process.env` dari file `.env`. Nilai `VITE_*` hanya
  // tersedia lewat `import.meta.env` di dalam modul frontend. Karena itu
  // `server.proxy` yang membaca `process.env.VITE_BACKEND_URL` selalu
  // bernilai `undefined` dan jatuh ke fallback — bukan ke URL yang benar.
  //
  // `loadEnv` membaca `.env` sungguhan, jadi proxy dan frontend sekarang
  // pasti mengarah ke backend yang sama.
  const env = loadEnv(mode, process.cwd(), '')
  const backendTarget = env.VITE_BACKEND_URL || 'http://localhost/backend/public'

  return {
    plugins: [vue(), vueDevTools(), tailwindcss()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
        jquery: fileURLToPath(
          new URL('./node_modules/.pnpm/jquery@3.7.1/node_modules/jquery/dist/jquery.js', import.meta.url),
        ),
      },
      dedupe: ['jquery'],
    },
    optimizeDeps: {
      include: ['jquery', 'jstree'],
    },
    server: {
      // Dua dev server sempat berjalan bersamaan dari direktori yang sama
      // (5173 + 5174). Hanya satu yang memegang port 5173, dan browser yang
      // tersambung ke instance yang tidak memegang HMR socket akan gagal WS
      // lalu diam-diam memakai modul versi lama. `strictPort` membuat server
      // kedua gagal start dengan pesan jelas, bukan diam-diam pindah port.
      strictPort: true,
      proxy: {
        '/api': {
          target: backendTarget,
          changeOrigin: true,
        },
        '/storage': {
          target: backendTarget,
          changeOrigin: true,
        },
      },
    },
  }
})
