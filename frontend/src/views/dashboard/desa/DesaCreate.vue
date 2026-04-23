<template>
  <div class="desa-create-view w-full! pb-20!">
    <div class="mb-8!">
      <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-2!">Tambah Desa Baru</h1>
      <p class="text-sm font-medium text-slate-500! leading-relaxed">
        Silakan isi data wilayah desa di bawah ini secara berurutan.
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
          <div class="w-1.5! h-6! bg-emerald-600! rounded-full!"></div>
          <h2 class="text-xl! font-bold! text-slate-800!">Informasi Wilayah</h2>
        </div>

        <!-- Baris 1: Provinsi & Kabupaten -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8! gap-y-3! mb-3!">
          <SelectSearch
            v-model="form.provinsi"
            :options="options.provinsi"
            label="Provinsi"
            placeholder="-- Pilih Provinsi --"
            @change="handleProvinsiChange"
            icon="map"
          />

          <SelectSearch
            v-model="form.kabupaten"
            :options="options.kabupaten"
            label="Kabupaten"
            placeholder="-- Pilih Kabupaten --"
            :disabled="!form.provinsi"
            @change="handleKabupatenChange"
            icon="city"
          />
        </div>

        <!-- Baris 2: Kecamatan & Desa -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8! gap-y-3! mb-3!">
          <SelectSearch
            v-model="form.kecamatan"
            :options="options.kecamatan"
            label="Kecamatan"
            placeholder="Pilih Nama Kecamatan"
            :disabled="!form.kabupaten"
            @change="handleKecamatanChange"
            icon="map-signs"
          />

          <SelectSearch
            v-model="form.desa"
            :options="options.desa"
            label="Desa/Kelurahan"
            placeholder="Pilih Nama Desa"
            :disabled="!form.kecamatan"
            icon="home"
          />
        </div>

        <!-- Baris 3: Dusun & No HP -->
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

        <!-- Baris 4: Alamat Lengkap -->
        <div class="grid grid-cols-1 gap-8! mb-2!">
          <BaseInput
            v-model="form.alamat_lengkap"
            type="textarea"
            label="Alamat Lengkap (Otomatis)"
            placeholder="Alamat akan terisi otomatis berdasarkan pilihan di atas"
            :rows="3"
            readonly
          />
        </div>
      </div>
    </ContentCard>

    <div class="mt-10! flex items-center justify-between! gap-4!">
      <div class="text-sm! font-medium! text-slate-400! italic!">
        <font-awesome-icon icon="info-circle" class="mr-2!" />
        Catatan : Mohon isi data wilayah mulai dari Provinsi secara berurutan.
      </div>
      <BaseButton
        variant="secondary"
        size="md"
        @click="handleSave"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="plus-circle"
      >
        Simpan Desa Baru
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const router = useRouter()

const form = ref({
  provinsi: '',
  kabupaten: '',
  kecamatan: '',
  desa: '',
  dusun: '',
  no_hp: '',
  alamat_lengkap: '',
})

// Data Mock untuk Simulasi Wilayah
const options = ref({
  provinsi: [
    { id: '33', text: 'Jawa Tengah' },
    { id: '32', text: 'Jawa Barat' },
    { id: '35', text: 'Jawa Timur' },
  ],
  kabupaten: [],
  kecamatan: [],
  desa: [],
})

// Database Simulasi (Nantinya ini bisa diganti dengan fetch API)
const db = {
  kabupaten: {
    33: [
      { id: '3301', text: 'Cilacap' },
      { id: '3302', text: 'Banyumas' },
      { id: '3374', text: 'Semarang' },
    ],
    32: [
      { id: '3201', text: 'Bogor' },
      { id: '3273', text: 'Bandung' },
    ],
  },
  kecamatan: {
    3374: [
      { id: '337401', text: 'Semarang Tengah' },
      { id: '337402', text: 'Semarang Utara' },
    ],
    3273: [
      { id: '327301', text: 'Coblong' },
      { id: '327302', text: 'Cicendo' },
    ],
  },
  desa: {
    337401: [
      { id: '33740101', text: 'Miroto' },
      { id: '33740102', text: 'Sekayu' },
    ],
    327301: [
      { id: '32730101', text: 'Dago' },
      { id: '32730102', text: 'Sadang Serang' },
    ],
  },
}

// Event Handlers
const handleProvinsiChange = (val) => {
  form.value.kabupaten = ''
  form.value.kecamatan = ''
  form.value.desa = ''
  options.value.kabupaten = db.kabupaten[val] || []
}

const handleKabupatenChange = (val) => {
  form.value.kecamatan = ''
  form.value.desa = ''
  options.value.kecamatan = db.kecamatan[val] || []
}

const handleKecamatanChange = (val) => {
  form.value.desa = ''
  options.value.desa = db.desa[val] || []
}

// Watch untuk merangkai Alamat Lengkap secara otomatis
watch(
  () => [
    form.value.provinsi,
    form.value.kabupaten,
    form.value.kecamatan,
    form.value.desa,
    form.value.dusun,
  ],
  () => {
    const p = options.value.provinsi.find((x) => x.id === form.value.provinsi)?.text || ''
    const k = options.value.kabupaten.find((x) => x.id === form.value.kabupaten)?.text || ''
    const kc = options.value.kecamatan.find((x) => x.id === form.value.kecamatan)?.text || ''
    const d = options.value.desa.find((x) => x.id === form.value.desa)?.text || ''
    const ds = form.value.dusun ? `Dusun ${form.value.dusun}, ` : ''

    let full = ds
    if (d) full += `${d}, `
    if (kc) full += `Kec. ${kc}, `
    if (k) full += `${k}, `
    if (p) full += `Prov. ${p}`

    form.value.alamat_lengkap = full
  },
  { deep: true },
)

const handleSave = () => {
  Swal.fire({
    title: 'Simpan Desa?',
    text: 'Pastikan data wilayah sudah benar.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Simpan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#059669',
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire('Berhasil!', 'Data desa baru telah disimpan.', 'success').then(() => {
        router.push('/data-desa')
      })
    }
  })
}
</script>

<style scoped>
.desa-create-view {
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
