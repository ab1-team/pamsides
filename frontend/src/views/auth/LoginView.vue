<template>
  <div class="login-container">
    <!-- Background is handled via CSS pseudo-elements for performance -->
    <div class="wrapper">
      <div class="left">
        <div class="badge">
          <span>PAMSIMAS DIGITAL PLATFORM</span>
        </div>

        <div class="headline">
          <h1 class="black">Aliran Air</h1>
          <h1 class="blue">Masa Depan.</h1>
        </div>

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

      <div class="right">
        <div class="card">
          <h2 class="card-title">Selamat Datang</h2>
          <p class="card-sub">Silakan masuk untuk mengakses dashboard</p>

          <form @submit.prevent="handleLogin" class="form">
            <div class="form-group">
              <label for="username">ID Pelanggan / Username</label>
              <div class="input-wrap">
                <span class="icon-left">
                  <font-awesome-icon icon="user" />
                </span>
                <input
                  id="username"
                  v-model="form.username"
                  type="text"
                  placeholder="Masukkan ID anda"
                  required
                  autocomplete="username"
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
import { MySwal } from '@/main.js'
import '@/assets/login.css'

const router = useRouter()
const route = useRoute()

const form = ref({
  username: '',
  password: '',
})

const loading = ref(false)
const showPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

onMounted(() => {
  if (route.query.logout === 'success') {
    MySwal.fire({
      toast: true,
      position: 'top-start',
      icon: 'success',
      title: 'Logout Berhasil',
      text: 'Anda telah keluar dari sistem',
      timer: 2000,
      timerProgressBar: true,
      showConfirmButton: false,
      customClass: {
        popup: 'swal-toast-custom',
        title: 'swal-toast-title',
        container: 'swal-toast-container',
      },
    })
  }
})

const handleLogin = async () => {
  loading.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 1500))

    if (form.value.username && form.value.password) {
      router.push('/dashboard?login=success')
    } else {
      throw new Error('username atau kata sandi tidak boleh kosong')
    }
  } catch (error) {
    MySwal.fire({
      icon: 'error',
      title: 'Login Gagal!',
      text: error.message || 'Terjadi kesalahan saat login',
      confirmButtonColor: '#ef4444',
    })
  } finally {
    loading.value = false
  }
}
</script>
