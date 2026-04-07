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

        <p class="desc">
          Kelola kebutuhan air bersih dengan lebih<br />
          transparan, mudah, dan terintegrasi dalam satu<br />
          sentuhan digital.
        </p>

        <div class="chips">
          <div class="chip">
            <font-awesome-icon icon="shield-halved" class="icon-blue" />
            Kualitas Terjamin
          </div>
          <div class="chip">
            <font-awesome-icon icon="check-circle" class="icon-green" />
            Respon Cepat
          </div>
        </div>
      </div>

      <div class="right">
        <div class="card">
          <p class="card-title">Selamat Datang</p>
          <p class="card-sub">Silakan masuk ke akun Anda</p>

          <form @submit.prevent="handleLogin" class="form">
            <div class="form-group">
              <label for="email">ID Pelanggan / Email</label>
              <div class="input-wrap">
                <span class="icon-left">
                  <font-awesome-icon icon="user" />
                </span>
                <input
                  id="email"
                  v-model="form.email"
                  type="text"
                  placeholder="Masukkan ID anda"
                  required
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
                />
                <button type="button" class="eye-btn" @click="togglePassword">
                  <font-awesome-icon :icon="showPassword ? 'eye-slash' : 'eye'" />
                </button>
              </div>
            </div>

            <button type="submit" class="btn-submit" :disabled="loading">
              {{ loading ? 'Memproses...' : 'Masuk Sekarang' }}
            </button>
          </form>

          <div class="quick">
            <p class="quick-label">AKSES CEPAT</p>
            <div class="quick-btns">
              <button class="quick-btn">
                <span class="q-icon">
                  <font-awesome-icon icon="credit-card" />
                </span>
                Bayar Cepat
              </button>

              <button class="quick-btn">
                <span class="q-icon">
                  <font-awesome-icon icon="user-plus" />
                </span>
                Daftar Akun
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
  email: '',
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
      position: 'top-end',
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

    if (form.value.email && form.value.password) {
      router.push('/dashboard?login=success')
    } else {
      throw new Error('Email atau kata sandi tidak boleh kosong')
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
