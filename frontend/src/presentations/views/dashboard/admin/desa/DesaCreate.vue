<template>
  <div class="desa-create-view w-full! pb-20!">
    <div class="mb-8! flex! items-center! gap-4!">
      <BaseButton
        variant="ghost"
        icon="arrow-left"
        @click="$router.back()"
        class="w-12! h-12! p-0! rounded-full! border! border-slate-200! bg-white! hover:bg-slate-50! hover:border-slate-300! text-slate-600! shadow-sm! shrink-0!"
      />
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-0.5!">
          Tambah Desa Baru
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Silakan isi data wilayah desa / kelurahan di bawah ini dengan lengkap.
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
          <h2 class="text-xl! font-bold! text-slate-800!">Informasi Wilayah</h2>
        </div>

        <!-- Row 1: Provinsi, Kabupaten, Kecamatan -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8! mb-8!">
          <SelectSearch
            v-model="form.provinsi"
            :options="provinsiOptions"
            label="Provinsi"
            placeholder="Pilih Nama Provinsi"
          />

          <SelectSearch
            v-model="form.kabupaten"
            :options="kabupatenOptions"
            label="Kabupaten"
            placeholder="Pilih Nama Kabupaten"
          />

          <SelectSearch
            v-model="form.kecamatan"
            :options="kecamatanOptions"
            label="Kecamatan"
            placeholder="Pilih Nama Kecamatan"
          />
        </div>

        <!-- Row 2: Desa, Dusun, No Hp -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8! mb-8!">
          <SelectSearch
            v-model="form.desa"
            :options="desaOptions"
            label="Desa/Kalurahan"
            placeholder="Pilih Nama Desa"
          />

          <BaseInput
            v-model="form.dusun"
            label="Dusun/Pedukuhan"
            placeholder="Masukkan Nama Dusun"
          />

          <BaseInput v-model="form.no_hp" label="No Hp" placeholder="Masukkan Nomor HP" />
        </div>

        <!-- Row 3: Alamat -->
        <div class="grid grid-cols-1 gap-8! mb-4!">
          <BaseInput
            v-model="generatedAlamat"
            type="textarea"
            label="Alamat"
            placeholder="Alamat akan terisi otomatis"
            :rows="3"
            disabled
          />
        </div>
      </div>
    </ContentCard>

    <div class="mt-10! flex items-center justify-end! gap-4!">
      <BaseButton
        variant="secondary"
        size="md"
        @click="handleSave"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="save"
      >
        Simpan Desa Baru
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import villageService from '@/services/village.service'
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import Swal from 'sweetalert2'
import axios from 'axios'

const router = useRouter()

const form = ref({
  provinsi: '',
  kabupaten: '',
  kecamatan: '',
  desa: '',
  dusun: '',
  no_hp: '',
})

// OPTIONS (DINAMIS)
const provinsiOptions = ref([])
const kabupatenOptions = ref([])
const kecamatanOptions = ref([])
const desaOptions = ref([])

// LOAD PROVINSI
onMounted(async () => {
  try {
    const res = await axios.get('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json')

    provinsiOptions.value = res.data.map(item => ({
      id: item.id,
      text: item.name
    }))
  } catch (err) {
    console.error('Gagal load provinsi', err)
  }
})

// PILIH PROVINSI → LOAD KABUPATEN
watch(() => form.value.provinsi, async (val) => {
  if (!val) return

  form.value.kabupaten = ''
  form.value.kecamatan = ''
  form.value.desa = ''

  kabupatenOptions.value = []
  kecamatanOptions.value = []
  desaOptions.value = []

  try {
    const res = await axios.get(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${val}.json`)

    kabupatenOptions.value = res.data.map(item => ({
      id: item.id,
      text: item.name
    }))
  } catch (err) {
    console.error('Gagal load kabupaten', err)
  }
})

// PILIH KABUPATEN → LOAD KECAMATAN
watch(() => form.value.kabupaten, async (val) => {
  if (!val) return

  form.value.kecamatan = ''
  form.value.desa = ''

  kecamatanOptions.value = []
  desaOptions.value = []

  try {
    const res = await axios.get(`https://www.emsifa.com/api-wilayah-indonesia/api/districts/${val}.json`)

    kecamatanOptions.value = res.data.map(item => ({
      id: item.id,
      text: item.name
    }))
  } catch (err) {
    console.error('Gagal load kecamatan', err)
  }
})

// PILIH KECAMATAN → LOAD DESA
watch(() => form.value.kecamatan, async (val) => {
  if (!val) return

  form.value.desa = ''
  desaOptions.value = []

  try {
    const res = await axios.get(`https://www.emsifa.com/api-wilayah-indonesia/api/villages/${val}.json`)

    desaOptions.value = res.data.map(item => ({
      id: item.id,
      text: item.name
    }))
  } catch (err) {
    console.error('Gagal load desa', err)
  }
})

// AUTO ALAMAT
const generatedAlamat = computed(() => {
  const parts = []

  if (form.value.dusun) parts.push(`Dusun ${form.value.dusun}`)

  const desa = desaOptions.value.find(d => d.id === form.value.desa)
  if (desa) parts.push(`Desa ${desa.text}`)

  const kec = kecamatanOptions.value.find(k => k.id === form.value.kecamatan)
  if (kec) parts.push(`Kec. ${kec.text}`)

  const kab = kabupatenOptions.value.find(k => k.id === form.value.kabupaten)
  if (kab) parts.push(`Kab. ${kab.text}`)

  const prov = provinsiOptions.value.find(p => p.id === form.value.provinsi)
  if (prov) parts.push(`Prov. ${prov.text}`)

  return parts.join(', ')
})

// SIMPAN
const isLoading = ref(false)
const handleSave = async () => {
  if (!form.value.desa || !form.value.dusun) {
    Swal.fire('Error', 'Desa dan Dusun wajib diisi!', 'error')
    return
  }

  const selectedDesaId = typeof form.value.desa === 'object' ? form.value.desa.id : form.value.desa

  const targetDesa = desaOptions.value.find(d => d.id === selectedDesaId)
  const finalVillageName = targetDesa ? targetDesa.text : (typeof form.value.desa === 'object' ? form.value.desa.text : '')

  if (!finalVillageName) {
    Swal.fire('Error', 'Nama Desa tidak valid atau belum terpilih dengan benar!', 'error')
    return
  }

  try {
    isLoading.value = true 
    
    await villageService.createVillage({
      village_name: finalVillageName, // Nama desa asli (String) yang sudah divalidasi
      hamlet_name: form.value.dusun,
      address: generatedAlamat.value,
      phone: form.value.no_hp
    })

    Swal.fire({
      title: 'Berhasil!',
      text: 'Data desa telah disimpan.',
      icon: 'success',
      confirmButtonColor: '#3b82f6',
    }).then(() => {
      router.push('/data-desa')
    })

  } catch (err) {
    console.error('Detail Error Server:', err.response?.data)
    
    // Tampilkan pesan error validasi spesifik dari Laravel jika ada
    let msg = err.response?.data?.message || 'Gagal menyimpan data'
    if (err.response?.data?.errors) {
      msg = Object.values(err.response.data.errors).flat().join('<br>')
    }
    
    Swal.fire({
      title: 'Error',
      html: msg, // Pakai html agar baris baru rapi jika errornya banyak
      icon: 'error'
    })
  } finally {
    isLoading.value = false
  }
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
