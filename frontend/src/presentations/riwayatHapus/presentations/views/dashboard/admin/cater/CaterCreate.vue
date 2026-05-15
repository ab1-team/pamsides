<template>
  <div class="cater-create-view w-full! pb-20!">
    <div class="mb-8! flex! items-center! gap-4!">
      <BaseButton
        variant="ghost"
        icon="arrow-left"
        @click="$router.back()"
        class="w-12! h-12! p-0! rounded-full! border! border-slate-200! bg-white! hover:bg-slate-50! hover:border-slate-300! text-slate-600! shadow-sm! shrink-0!"
      />
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-0.5!">
          Tambah Petugas Cater
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Silakan isi data lengkap petugas pencatat meter di bawah ini.
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
          <div class="w-1.5! h-6! bg-blue-600! rounded-full!"></div>
          <h2 class="text-xl! font-bold! text-slate-800!">Informasi Petugas</h2>
        </div>

        <!-- Row 1: Nama, Jenis Kelamin, No Telpon -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8! mb-8!">
          <BaseInput v-model="form.nama" label="Nama" placeholder="Masukkan nama petugas" />

          <SelectSearch
            v-model="form.jenis_kelamin"
            :options="genderOptions"
            label="Jenis Kelamin"
            placeholder="Pilih Jenis Kelamin"
          />

          <BaseInput v-model="form.telepon" label="No Telpon" placeholder="Contoh: 0812XXXXXXXX" />
        </div>

        <!-- Row 2: Username & Password -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8! mb-8!">
          <BaseInput v-model="form.username" label="Username" placeholder="Masukkan username" />

          <BaseInput
            v-model="form.password"
            type="password"
            label="Password"
            placeholder="Masukkan password"
          />
        </div>

        <!-- Row 3: Alamat (Full Width) -->
        <div class="grid grid-cols-1 gap-8! mb-4!">
          <BaseInput
            v-model="form.alamat"
            type="textarea"
            label="Alamat"
            placeholder="Masukkan alamat lengkap"
            :rows="3"
          />
        </div>
      </div>
    </ContentCard>

    <!-- Bottom Actions & Note -->
    <div class="mt-10! flex flex-col! md:flex-row! items-center! justify-between! gap-6!">
      <!-- Note Section (Outside Card, Left) -->
      <div class="flex! items-start! gap-3! max-w-md!">
        <font-awesome-icon icon="info-circle" class="text-blue-500! mt-0.5!" />
        <p class="text-xs! text-slate-500! font-medium! leading-relaxed!">
          <strong class="text-slate-800!">Catatan :</strong> Jika Ada data atau inputan yang kosong
          bisa di isi ( 0 ) atau ( - )
        </p>
      </div>

      <BaseButton
        variant="secondary"
        size="md"
        @click="handleSave"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95! shrink-0!"
        icon="save"
      >
        Simpan Petugas
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const router = useRouter()

const form = ref({
  nama: '',
  jenis_kelamin: '',
  alamat: '',
  telepon: '',
  username: '',
  password: '',
})

const genderOptions = [
  { id: 'Laki-laki', text: 'Laki-laki' },
  { id: 'Perempuan', text: 'Perempuan' },
]

const handleSave = () => {
  if (!form.value.nama || !form.value.username) {
    Swal.fire('Error', 'Nama dan Username harus diisi!', 'error')
    return
  }

  Swal.fire({
    title: 'Berhasil!',
    text: 'Data petugas cater telah disimpan.',
    icon: 'success',
    confirmButtonText: 'OK',
    confirmButtonColor: '#3b82f6',
  }).then(() => {
    router.push('/data-cater')
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
