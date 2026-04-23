<template>
  <div class="cater-create-view w-full! pb-20!">
    <div class="mb-8!">
      <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-2!">
        Tambah Petugas Cater
      </h1>
      <p class="text-sm font-medium text-slate-500! leading-relaxed">
        Daftarkan petugas pencatat meter (Cater) baru untuk wilayah Anda.
      </p>
    </div>

    <ContentCard
      variant="elevated"
      padding="none"
      hoverable
      class="border-0! shadow-lg! hover:shadow-2xl! transition-all! duration-300! overflow-visible! bg-white! rounded-3xl!"
    >
      <div class="p-6! sm:p-10!">
        <div class="flex items-center gap-3! mb-8!">
          <div class="w-1.5! h-6! bg-blue-600! rounded-full!"></div>
          <h2 class="text-xl! font-bold! text-slate-800!">Data Akun & Pribadi</h2>
        </div>

        <!-- Baris 1: Nama, Jenis Kelamin, No. Telpon -->
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
            placeholder="Masukkan password akun"
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
        @click="handleSave"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="user-plus"
      >
        Simpan Petugas Baru
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const router = useRouter()

const form = ref({
  nama: '',
  jenis_kelamin: '',
  no_telp: '',
  alamat: '',
  username: '',
  password: '',
})

const handleSave = () => {
  const finalData = { ...form.value }

  Object.keys(finalData).forEach((key) => {
    if (!finalData[key] || finalData[key].toString().trim() === '') {
      finalData[key] = key === 'no_telp' ? '0' : '-'
    }
  })

  Swal.fire({
    title: 'Simpan Petugas?',
    text: 'Pastikan data yang dimasukkan sudah benar.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Simpan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#3b82f6',
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire('Berhasil!', 'Petugas Cater baru telah ditambahkan.', 'success').then(() => {
        router.push('/data-cater')
      })
    }
  })
}
</script>

<style scoped>
.cater-create-view {
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
