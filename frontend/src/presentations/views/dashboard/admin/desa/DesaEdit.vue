<template>
  <div class="desa-edit-view w-full! pb-20!">
    <div class="mb-8! flex! items-center! gap-4!">
      <BaseButton
        variant="ghost"
        icon="arrow-left"
        @click="$router.back()"
        class="w-12! h-12! p-0! rounded-full! border! border-slate-200! bg-white! hover:bg-slate-50! hover:border-slate-300! text-slate-600! shadow-sm! shrink-0!"
      />
      <div>
        <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-0.5!">
          Edit Data Desa
        </h1>
        <p class="text-sm font-medium text-slate-500! leading-relaxed">
          Perbarui informasi desa / kelurahan di bawah ini.
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

        <!-- Row 1: Provinsi, Kabupaten, Kecamatan (DISABLED) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8! mb-8!">
          <SelectSearch
            v-model="form.provinsi"
            :options="provinsiOptions"
            label="Provinsi"
            placeholder="Pilih Nama Provinsi"
            disabled
          />

          <SelectSearch
            v-model="form.kabupaten"
            :options="kabupatenOptions"
            label="Kabupaten"
            placeholder="Pilih Nama Kabupaten"
            disabled
          />

          <SelectSearch
            v-model="form.kecamatan"
            :options="kecamatanOptions"
            label="Kecamatan"
            placeholder="Pilih Nama Kecamatan"
            disabled
          />
        </div>

        <!-- Row 2: Desa (DISABLED), Dusun (ENABLED), No Hp (ENABLED) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8! mb-8!">
          <SelectSearch
            v-model="form.desa"
            :options="desaOptions"
            label="Desa/Kalurahan"
            placeholder="Pilih Nama Desa"
            disabled
          />

          <BaseInput
            v-model="form.dusun"
            label="Dusun/Pedukuhan"
            placeholder="Masukkan Nama Dusun"
          />

          <BaseInput v-model="form.no_hp" label="No Hp" placeholder="Masukkan Nomor HP" />
        </div>

        <!-- Row 3: Alamat (DISABLED) -->
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
        Simpan Perubahan
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

const form = ref({
  id: '',
  provinsi: 'Lampung',
  kabupaten: 'Pringsewu',
  kecamatan: 'Gading Rejo',
  desa: 'Gading Rejo Utara',
  dusun: 'Krajan',
  no_hp: '081234567890',
  alamat: '',
})

const provinsiOptions = [{ id: 'Lampung', text: 'Lampung' }]
const kabupatenOptions = [{ id: 'Pringsewu', text: 'Pringsewu' }]
const kecamatanOptions = [{ id: 'Gading Rejo', text: 'Gading Rejo' }]
const desaOptions = [{ id: 'Gading Rejo Utara', text: 'Gading Rejo Utara' }]

const generatedAlamat = computed(() => {
  const parts = []
  if (form.value.dusun) parts.push(`Dusun ${form.value.dusun}`)
  if (form.value.desa) parts.push(`Desa ${form.value.desa}`)
  if (form.value.kecamatan) parts.push(`Kec. ${form.value.kecamatan}`)
  if (form.value.kabupaten) parts.push(`Kab. ${form.value.kabupaten}`)
  if (form.value.provinsi) parts.push(`Prov. ${form.value.provinsi}`)

  return parts.join(', ')
})

onMounted(() => {
  const id = route.params.id
  form.value.id = id
})

const handleSave = () => {
  form.value.alamat = generatedAlamat.value

  Swal.fire({
    title: 'Berhasil!',
    text: 'Perubahan data desa telah disimpan.',
    icon: 'success',
    confirmButtonText: 'OK',
    confirmButtonColor: '#3b82f6',
  }).then(() => {
    router.push('/data-desa')
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
