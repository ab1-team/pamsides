<template>
  <div class="desa-edit-view w-full! pb-20!">
    <div class="mb-8! flex items-center gap-5!">
      <BaseButton
        variant="ghost"
        size="md"
        @click="router.push('/data-desa')"
        class="w-12! h-12! p-0! rounded-full! border! border-slate-200! hover:bg-slate-50! text-slate-600! shadow-sm! transition-all!"
        icon="arrow-left"
        title="Kembali ke Daftar"
      />
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-1!">Edit Data Desa</h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Perbarui informasi dusun atau nomor kontak desa.
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
          <h2 class="text-xl! font-bold! text-slate-800!">Informasi Wilayah</h2>
        </div>

        <!-- Baris 1: Provinsi & Kabupaten (DISABLED) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8! gap-y-3! mb-3!">
          <SelectSearch
            v-model="form.provinsi"
            :options="options.provinsi"
            label="Provinsi"
            placeholder="-- Pilih Provinsi --"
            disabled
            icon="map"
          />

          <SelectSearch
            v-model="form.kabupaten"
            :options="options.kabupaten"
            label="Kabupaten"
            placeholder="-- Pilih Kabupaten --"
            disabled
            icon="city"
          />
        </div>

        <!-- Baris 2: Kecamatan & Desa (DISABLED) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8! gap-y-3! mb-3!">
          <SelectSearch
            v-model="form.kecamatan"
            :options="options.kecamatan"
            label="Kecamatan"
            placeholder="Pilih Nama Kecamatan"
            disabled
            icon="map-signs"
          />

          <SelectSearch
            v-model="form.desa"
            :options="options.desa"
            label="Desa/Kelurahan"
            placeholder="Pilih Nama Desa"
            disabled
            icon="home"
          />
        </div>

        <!-- Baris 3: Dusun & No HP (EDITABLE) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8! gap-y-3! mb-3!">
          <BaseInput
            v-model="form.dusun"
            label="Dusun/Pedukuhan"
            placeholder="Contoh: Dusun Krajan"
            icon="map-pin"
          />

          <BaseInput
            v-model="form.no_hp"
            label="No. HP"
            placeholder="Contoh: 0812XXXXXXXX"
            icon="phone"
          />
        </div>

        <!-- Baris 4: Alamat Lengkap (READONLY) -->
        <div class="grid grid-cols-1 gap-8! mb-2!">
          <BaseInput
            v-model="form.alamat_lengkap"
            type="textarea"
            label="Alamat Lengkap"
            placeholder="Alamat akan terisi otomatis"
            :rows="3"
            readonly
          />
        </div>
      </div>
    </ContentCard>

    <div class="mt-10! flex items-center justify-between! gap-4!">
      <div class="text-sm! font-medium! text-slate-400! italic!">
        <font-awesome-icon icon="info-circle" class="mr-2!" />
        Catatan : Wilayah administratif tidak dapat diubah pada halaman edit.
      </div>
      <BaseButton
        variant="secondary"
        size="md"
        @click="handleUpdate"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="save"
      >
        Perbarui Data Desa
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

const form = ref({
  provinsi: '',
  kabupaten: '',
  kecamatan: '',
  desa: '',
  dusun: '',
  no_hp: '',
  alamat_lengkap: '',
})

// Data Mock untuk kebutuhan tampilan disabled
const options = ref({
  provinsi: [{ id: '33', text: 'Jawa Tengah' }],
  kabupaten: [{ id: '3374', text: 'Semarang' }],
  kecamatan: [{ id: '337401', text: 'Semarang Tengah' }],
  desa: [{ id: '33740101', text: 'Miroto' }],
})

onMounted(() => {
  // Simulasi fetch data berdasarkan ID
  console.log('Fetching data for ID:', route.params.id)

  form.value = {
    provinsi: '33',
    kabupaten: '3374',
    kecamatan: '337401',
    desa: '33740101',
    dusun: 'Krajan Tengah',
    no_hp: '08122334455',
    alamat_lengkap:
      'Dusun Krajan Tengah, Miroto, Kec. Semarang Tengah, Semarang, Prov. Jawa Tengah',
  }
})

// Watch untuk merangkai Alamat Lengkap secara otomatis saat Dusun diubah
watch(
  () => form.value.dusun,
  () => {
    const p = options.value.provinsi[0]?.text || ''
    const k = options.value.kabupaten[0]?.text || ''
    const kc = options.value.kecamatan[0]?.text || ''
    const d = options.value.desa[0]?.text || ''
    const ds = form.value.dusun ? `Dusun ${form.value.dusun}, ` : ''

    form.value.alamat_lengkap = `${ds}${d}, Kec. ${kc}, ${k}, Prov. ${p}`
  },
)

const handleUpdate = () => {
  Swal.fire({
    title: 'Perbarui Data Desa?',
    text: 'Pastikan informasi dusun dan nomor HP sudah benar.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Perbarui',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#4f46e5',
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire('Berhasil!', 'Data desa telah diperbarui.', 'success').then(() => {
        router.push('/data-desa')
      })
    }
  })
}
</script>

<style scoped>
.desa-edit-view {
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
