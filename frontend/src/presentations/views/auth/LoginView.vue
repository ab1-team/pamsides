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
import { useRouter } from 'vue-router'
import axios from '@/utils/axios.js'
import { MySwal } from '@/utils/swal'
import { useUiStore } from '@/stores/uiStore'
import { useOverdueGenNotification } from '@/composables/useOverdueGenNotification'
import { getDashboardRoute } from '@/router/dashboardRoutes'
import '@/assets/css/login.css'

const router = useRouter()
const uiStore = useUiStore()
const { checkOverdueGenOnMount } = useOverdueGenNotification()

const form = ref({
  email: '',
  password: '',
})

const loading = ref(false)
const showPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

// Sumber tunggal yang sama dengan router (lihat router/dashboardRoutes.js).
// Jangan diduplikasi di sini — itulah penyebab surveyor punya 2 URL berbeda.

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
    const response = await axios.post('/login', {
      email: form.value.email,
      password: form.value.password,
    })

    const res = response.data

    if (res.success) {
      localStorage.setItem('auth_token', res.data.token)
      const expireTime = Date.now() + 8 * 60 * 60 * 1000
      localStorage.setItem('auth_expires_at', expireTime.toString())
      uiStore.setUserData(res.data.user)
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

      // Pindah ke dashboard dulu supaya user langsung melihat hasil login.
      const redirectRoute = getDashboardRoute(res.data.user.role)
      router.push(redirectRoute)

      // Generate piutang TIDAK di-await di sini: user tidak boleh merasa
      // loginnya macet. Prosesnya berjalan di background dan popup
      // loading muncul sendiri setelah user sampai di dashboard — jadi
      // yang tampil adalah progres nyata, bukan spinner kosong.
      //
      // Ini SATU-SATUNYA pemicu generate di seluruh aplikasi. Jangan
      // ditambah pemicu di halaman lain: kalau generate juga berjalan
      // saat dashboard dibuka, setiap klik menu Dashboard akan
      // menghitung ulang.
      //
      // Pengecekan role TIDAK dilakukan di sini. `checkOverdueGenOnMount`
      // sudah berhenti sendiri untuk non-admin SEBELUM mengirim request
      // apa pun, jadi teknisi tidak melihat popup dan tidak membuang
      // satu request sia-sia.
      await new Promise((resolve) => requestAnimationFrame(resolve))
      checkOverdueGenOnMount()
    } else {
      throw new Error(res.message || 'Login Gagal')
    }
  } catch (error) {
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
