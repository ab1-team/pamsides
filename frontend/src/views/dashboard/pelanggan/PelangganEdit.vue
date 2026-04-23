<template>
  <div class="pelanggan-edit-view w-full! pb-20!">
    <div class="mb-8! flex items-center gap-5!">
      <BaseButton
        variant="ghost"
        size="md"
        @click="router.push('/data-pelanggan')"
        class="w-12! h-12! p-0! rounded-full! border! border-slate-200! hover:bg-slate-50! text-slate-600! shadow-sm! transition-all!"
        icon="arrow-left"
        title="Kembali ke Daftar"
      />
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-1!">
          Edit Data Pelanggan
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Perbarui informasi pelanggan di bawah ini.
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
          <h2 class="text-xl! font-bold! text-slate-800!">Informasi Pribadi</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8! mb-8!">
          <BaseInput
            v-model="form.nik"
            label="NIK"
            placeholder="Masukkan 16 digit NIK"
            icon="id-card"
            maxlength="16"
          />

          <BaseInput
            v-model="form.nama_lengkap"
            label="Nama Lengkap"
            placeholder="Masukkan nama sesuai KTP"
            icon="user"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8! mb-8!">
          <BaseInput
            v-model="form.nama_panggilan"
            label="Nama Panggilan"
            placeholder="Contoh: Budi"
            icon="user-tag"
          />

          <BaseInput
            v-model="form.pekerjaan"
            label="Pekerjaan"
            placeholder="Contoh: Karyawan Swasta"
            icon="briefcase"
          />

          <BaseInput
            v-model="form.no_telp"
            label="No. Telepon"
            placeholder="Contoh: 0812XXXXXXXX"
            icon="phone"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8! mb-8!">
          <BaseInput
            v-model="form.tempat_lahir"
            label="Tempat Lahir"
            placeholder="Contoh: Jakarta"
            icon="map-marker-alt"
          />

          <AppDatePicker
            v-model="form.tgl_lahir"
            label="Tgl Lahir"
            placeholder="Pilih Tanggal"
            @date-select="(date) => (form.tgl_lahir = date)"
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
        </div>

        <div class="grid grid-cols-1 gap-8! mb-4!">
          <BaseInput
            v-model="form.alamat_lengkap"
            type="textarea"
            label="Alamat Lengkap"
            placeholder="Masukkan alamat lengkap domisili"
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
        icon="save"
      >
        Perbarui Data Pelanggan
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
import AppDatePicker from '@/components/AppDatePicker.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

const form = ref({
  nik: '',
  nama_lengkap: '',
  nama_panggilan: '',
  tempat_lahir: '',
  tgl_lahir: null,
  jenis_kelamin: '',
  no_telp: '',
  pekerjaan: '',
  alamat_lengkap: '',
})

onMounted(() => {
  const id = route.params.id
  console.log('Fetching data for ID:', id)

  form.value = {
    nik: '3201234567890001',
    nama_lengkap: 'Budi Santoso',
    nama_panggilan: 'Budi',
    tempat_lahir: 'Bandung',
    tgl_lahir: new Date(1990, 5, 15),
    jenis_kelamin: 'Laki-laki',
    no_telp: '08123456789',
    pekerjaan: 'Wiraswasta',
    alamat_lengkap: 'Jl. Merdeka No. 123, Bandung',
  }
})

const handleSave = () => {
  const finalData = { ...form.value }

  Object.keys(finalData).forEach((key) => {
    if (!finalData[key] || finalData[key].toString().trim() === '') {
      if (['nik', 'no_telp'].includes(key)) {
        finalData[key] = '0'
      } else {
        finalData[key] = '-'
      }
    }
  })

  Swal.fire({
    title: 'Berhasil!',
    text: 'Data pelanggan telah diperbarui.',
    icon: 'success',
    confirmButtonText: 'OK',
    confirmButtonColor: '#64748b',
  }).then(() => {
    router.push('/data-pelanggan')
  })
}
</script>

<style scoped>
.pelanggan-edit-view {
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
