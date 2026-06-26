import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import tailwindcss from '@tailwindcss/vite'

// https://vite.dev/config/
export default defineConfig({
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
    proxy: {
      '/api': {
        target: process.env.VITE_BACKEND_URL || 'http://localhost/backend/public',
        changeOrigin: true,
      },
      '/storage': {
        target: process.env.VITE_BACKEND_URL || 'http://localhost/backend/public',
        changeOrigin: true,
      },
    },
  },
})
