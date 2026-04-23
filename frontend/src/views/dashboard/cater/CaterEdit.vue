<template>
  <div class="cater-edit-view w-full! pb-20!">
    <div class="mb-8! flex items-center gap-5!">
      <BaseButton
        variant="ghost"
        size="md"
        @click="router.push('/data-cater')"
        class="w-12! h-12! p-0! rounded-full! border! border-slate-200! hover:bg-slate-50! text-slate-600! shadow-sm! transition-all!"
        icon="arrow-left"
        title="Kembali ke Daftar"
      />
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-1!">
          Edit Petugas Cater
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Perbarui informasi petugas pencatat meter (Cater).
        </p>
      </div>
    </div>

    <ContentCard
      variant="elevated"
      padding="none"
      hoverable
      class="border-0! shadow-lg! hover:shadow-2xl! transition-all! duration-300! overflow-visible! bg-white! rounded-3xl!"
    >
      <div class="p-6! sm:p-10!">
        <div class="flex items-center gap-3! mb-8!">
          <div class="w-1.5! h-6! bg-indigo-600! rounded-full!"></div>
          <h2 class="text-xl! font-bold! text-slate-800!">Data Akun & Pribadi</h2>
        </div>

        <!-- Baris 1: Nama, Jenis Kelamin, No. Telepon -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8! gap-y-3! mb-3!">
          <BaseInput
            v-model="form.nama"
            label="Nama Lengkap"
            placeholder="Masukkan nama lengkap petugas"
            icon="user"
          />

          <SelectSearch
            v-model="form.jenis_kelamin"
            :options="[
              { id: 'Laki-laki', text: 'Laki-laki' },
              { id: 'Perempuan', text: 'Perempuan' },
            ]"
            label="Jenis Kelamin"
            placeholder="Pilih Jenis Kelamin"
            icon="users"
          />

          <BaseInput
            v-model="form.no_telp"
            label="No. Telepon"
            placeholder="Contoh: 0812XXXXXXXX"
            icon="phone"
          />
        </div>

        <!-- Baris 2: Username & Password -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8! gap-y-3! mb-3!">
          <BaseInput
            v-model="form.username"
            label="Username"
            placeholder="Masukkan username untuk login"
            icon="at"
          />

          <BaseInput
            v-model="form.password"
            type="password"
            label="Password"
            placeholder="Biarkan kosong jika tidak ingin mengubah"
            icon="lock"
          />
        </div>
        <div class="grid grid-cols-1 gap-8! mb-2!">
          <BaseInput
            v-model="form.alamat"
            type="textarea"
            label="Alamat Lengkap"
            placeholder="Masukkan alamat domisili petugas"
            :rows="3"
          />
        </div>
      </div>
    </ContentCard>

    <div class="mt-10! flex items-center justify-between! gap-4!">
      <div class="text-sm! font-medium! text-slate-400! italic!">
        <font-awesome-icon icon="info-circle" class="mr-2!" />
        Catatan : ( Jika Ada data atau inputan yang kosong bisa di isi ( 0 ) atau ( - ) )
      </div>
      <BaseButton
        variant="secondary"
        size="md"
        @click="handleUpdate"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="save"
      >
        Perbarui Data Petugas
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

const form = ref({
  nama: '',
  jenis_kelamin: '',
  no_telp: '',
  alamat: '',
  username: '',
  password: '',
})

onMounted(() => {
  // Simulasi fetch data berdasarkan ID
  console.log('Fetching data for ID:', route.params.id)

  form.value = {
    nama: 'Asep Sunandar',
    jenis_kelamin: 'Laki-laki',
    no_telp: '08122334455',
    alamat: 'Kp. Durian Runtuh RT 02/05, Desa Sukamaju',
    username: 'asep_cater',
    password: '', // Password tidak ditampilkan demi keamanan
  }
})

const handleUpdate = () => {
  Swal.fire({
    title: 'Perbarui Petugas?',
    text: 'Pastikan data petugas sudah benar.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Perbarui',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#4f46e5',
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire('Berhasil!', 'Data petugas telah diperbarui.', 'success').then(() => {
        router.push('/data-cater')
      })
    }
  })
}
</script>

<style scoped>
.cater-edit-view {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
