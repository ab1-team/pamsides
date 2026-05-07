<template>
  <div class="login-container">
    <div class="wrapper">
      <div class="left">
        <div class="badge">
          <span>PAMSIMAS DIGITAL PLATFORM</span>
        </div>

        <div class="headline">
          <h1 class="black">Aliran Air</h1>
          <h1 class="blue">Masa Depan.</h1>
        </div>

        <div class="desc-group">
          <p class="desc">
            Kelola kebutuhan air bersih dengan lebih transparan, mudah, dan terintegrasi dalam satu
            sentuhan digital.
          </p>

          <div class="chips">
            <div class="chip">
              <font-awesome-icon icon="shield-halved" class="icon-blue" />
              <span>Kualitas Terjamin</span>
            </div>
            <div class="chip">
              <font-awesome-icon icon="check-circle" class="icon-green" />
              <span>Respon Cepat</span>
            </div>
          </div>
        </div>
      </div>

      <div class="right">
        <div class="card">
          <h2 class="card-title">Selamat Datang</h2>
          <p class="card-sub">Login untuk akses dashboard</p>

          <form @submit.prevent="handleLogin" class="form">
            <div class="form-group">
              <label for="email">Alamat Email</label>
              <div class="input-wrap">
                <span class="icon-left">
                  <font-awesome-icon icon="user" />
                </span>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="Masukkan Alamat Email"
                  required
                  autocomplete="email"
                />
              </div>
            </div>

            <div class="form-group">
              <div class="label-row">
                <label for="password">Kata Sandi</label>
                <a href="#" class="forgot">Lupa sandi?</a>
              </div>
              <div class="input-wrap">
                <span class="icon-left">
                  <font-awesome-icon icon="lock" />
                </span>
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="••••••••"
                  required
                  autocomplete="current-password"
                />
                <button
                  type="button"
                  class="eye-btn"
                  @click="togglePassword"
                  aria-label="Toggle password visibility"
                >
                  <font-awesome-icon :icon="showPassword ? 'eye-slash' : 'eye'" />
                </button>
              </div>
            </div>

            <button type="submit" class="btn-submit" :disabled="loading">
              <span v-if="!loading">Masuk Sekarang</span>
              <span v-else> <font-awesome-icon icon="spinner" spin /> Memproses... </span>
            </button>
          </form>

          <div class="quick">
            <p class="quick-label">AKSES CEPAT</p>
            <div class="quick-btns">
              <button class="quick-btn">
                <div class="q-icon">
                  <font-awesome-icon icon="credit-card" />
                </div>
                <span>Bayar Cepat</span>
              </button>

              <button class="quick-btn">
                <div class="q-icon">
                  <font-awesome-icon icon="user-plus" />
                </div>
                <span>Daftar Akun</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { MySwal } from '@/main.js'
import { useUiStore } from '@/stores/uiStore'
import '@/assets/css/login.css'

const router = useRouter()
const route = useRoute()
const uiStore = useUiStore()

const form = ref({
  email: '',
  password: '',
})

const loading = ref(false)
const showPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get('logout')) {
    const name = urlParams.get('name')
    MySwal.fire({
      toast: true,
      position: 'top-start', 
      icon: 'success',
      title: 'Logout Berhasil',
      text: name ? `Terima kasih, ${name}` : 'Terima kasih!',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      customClass: {
        popup: 'swal-toast-custom',
        title: 'swal-toast-title',
        container: 'swal-toast-container',
      },
    })
    window.history.replaceState({}, document.title, window.location.pathname)
  }
})

const handleLogin = async () => {
  if (!form.value.email || !form.value.password) {
    MySwal.fire({
      icon: 'warning',
      title: 'Oops...',
      text: 'Email dan password wajib diisi!',
      confirmButtonColor: '#3b82f6',
    })
    return
  }

  loading.value = true
  try {
    const response = await axios.post('http://127.0.0.1:8000/api/login', {
      email: form.value.email,
      password: form.value.password,
    })

    const res = response.data

    if (res.success) {
      localStorage.setItem('auth_token', res.data.token)
      localStorage.setItem('user_data', JSON.stringify(res.data.user))
      uiStore.setUserRole(res.data.user.role)
      
      MySwal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Login Berhasil',
        text: `Selamat datang, ${res.data.user.name}`,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
          popup: 'swal-toast-custom',
          title: 'swal-toast-title',
          container: 'swal-toast-container',
        },
      })
      router.push('/dashboard')
    } else {
      throw new Error(res.message || 'Login Gagal')
    }
  } catch (error) {
    console.error('Login Error Details:', error)
    const errorMessage = error.response?.data?.message || 'Email atau password salah.'
    
    MySwal.fire({
      icon: 'error',
      title: 'Akses Ditolak',
      text: errorMessage,
      confirmButtonColor: '#3b82f6',
    })
  } finally {
    loading.value = false
  }
}
</script>
