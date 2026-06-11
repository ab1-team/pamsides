<template>
  <div class="pelanggan-create-view w-full! pb-20!">
    <div class="mb-8!">
      <h1 class="text-3xl font-extrabold text-slate-900! tracking-tight mb-2!">
        Tambah Pelanggan Baru
      </h1>
      <p class="text-sm font-medium text-slate-500! leading-relaxed">
        Silakan isi data pelanggan di bawah ini dengan lengkap.
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
          <h2 class="text-xl! font-bold! text-slate-800!">Informasi Pribadi</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4! mb-4!">
          <div>
            <BaseInput
              v-model="form.nik"
              label="NIK"
              placeholder="Masukkan 16 digit NIK"
              icon="id-card"
              maxlength="16"
            />
            <div v-if="nikChecking" class="mt-1.5! flex! items-center! gap-1.5! text-xs! text-slate-500!">
              <font-awesome-icon icon="spinner" spin class="text-[12px]!" />
              <span>Memeriksa NIK...</span>
            </div>
            <div
              v-else-if="nikExists"
              class="mt-1.5! flex! items-center! gap-1.5! text-xs! font-medium! text-amber-600!"
            >
              <font-awesome-icon icon="exclamation-circle" class="text-[12px]!" />
              <span>NIK sudah digunakan</span>
            </div>
          </div>

          <BaseInput
            v-model="form.nama_lengkap"
            label="Nama Lengkap"
            placeholder="Nama Lengkap"
            icon="user"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4! mb-4!">
          <BaseInput
            v-model="form.tempat_lahir"
            label="Tempat Lahir"
            placeholder="Tempat Lahir"
            icon="map-marker-alt"
          />

          <AppDatePicker
            v-model="form.tgl_lahir"
            label="Tgl Lahir"
            placeholder="Pilih Tanggal"
            @date-select="(date) => (form.tgl_lahir = date)"
          />

          <BaseInput
            v-model="form.no_telp"
            label="No. Telepon"
            placeholder="No. Telepon"
            icon="phone"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4! mb-4!">
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
            v-model="form.email"
            label="Email"
            placeholder="Masukkan Email"
            icon="envelope"
          />

          <BaseInput
            v-model="form.password"
            type="password"
            label="Password"
            placeholder="Masukkan Password"
            icon="lock"
          />
        </div>

        <div class="grid grid-cols-1 gap-4! mb-4!">
          <BaseInput
            v-model="form.alamat_lengkap"
            type="textarea"
            label="Alamat Lengkap"
            placeholder="Masukkan alamat lengkap domisili"
            :rows="2"
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
        :loading="isLoading"
        class="px-12! py-4! font-bold! rounded-2xl! shadow-xl! shadow-slate-200! transform! transition-all! hover:translate-y-[-2px]! active:scale-95!"
        icon="save"
      >
        Simpan Pelanggan Baru
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
import SelectSearch from '@/presentations/components/SelectSearch.vue'
import Swal from 'sweetalert2'
import { useUiStore } from '@/stores/uiStore'
import customerService from '@/services/customer.service'

const router = useRouter()
const uiStore = useUiStore()
const isLoading = ref(false)

const form = ref({
  nik: '',
  nama_lengkap: '',
  email: '',
  password: '',

  tempat_lahir: '',
  tgl_lahir: new Date(),

  jenis_kelamin: '',
  no_telp: '',
  alamat_lengkap: '',
})

const nikExists = ref(null)
const nikChecking = ref(false)
let nikDebounceTimer = null

const applyAutofill = (d) => {
  if (d?.name) form.value.nama_lengkap = d.name
  if (d?.email) form.value.email = d.email
  if (d?.phone && d.phone !== '-' && d.phone !== '0') {
    form.value.no_telp = d.phone
  }
  if (d?.address && d.address !== '-') {
    form.value.alamat_lengkap = d.address
  }
  if (d?.birth_place && d.birth_place !== '-') {
    form.value.tempat_lahir = d.birth_place
  }
  if (d?.gender) {
    form.value.jenis_kelamin = d.gender === 'female' ? 'Perempuan' : 'Laki-laki'
  }
}

watch(
  () => form.value.nik,
  (val) => {
    if (nikDebounceTimer) clearTimeout(nikDebounceTimer)
    const nik = (val || '').trim()
    if (nik.length < 4) {
      nikExists.value = null
      nikChecking.value = false
      return
    }
    nikChecking.value = true
    nikDebounceTimer = setTimeout(async () => {
      try {
        const res = await customerService.getCustomers({ search: nik })
        const list = Array.isArray(res?.data)
          ? res.data
          : Array.isArray(res?.data?.data)
            ? res.data.data
            : []
        const match = list.find((r) => r.nik === nik)
        nikExists.value = match || null
        if (match?.id) {
          const detailRes = await customerService.getCustomerDetail(match.id)
          const detail = detailRes?.data
          if (detail) applyAutofill(detail)
        }
      } catch (e) {
        nikExists.value = null
      } finally {
        nikChecking.value = false
      }
    }, 500)
  },
)

onUnmounted(() => {
  if (nikDebounceTimer) clearTimeout(nikDebounceTimer)
})

const handleSave = async () => {
  const finalData = {
    ...form.value,
  }

  // Validasi wajib
  if (!finalData.nik || !finalData.nama_lengkap || !finalData.email || !finalData.password) {
    Swal.fire({
      title: 'Peringatan!',
      text: 'NIK, Nama Lengkap, Email, dan Password wajib diisi.',
      icon: 'warning',
    })
    return
  }

  // Isi default jika kosong
  Object.keys(finalData).forEach((key) => {
    if (
      finalData[key] === null ||
      finalData[key] === undefined ||
      finalData[key].toString().trim() === ''
    ) {
      if (['nik', 'no_telp'].includes(key)) {
        finalData[key] = '0'
      } else {
        finalData[key] = '-'
      }
    }
  })

  try {
    isLoading.value = true

    console.log('DATA DIKIRIM : ', finalData)

    await customerService.createCustomer(finalData)

    isLoading.value = false

    const result = await Swal.fire({
      title: 'Data berhasil disimpan!',
      text: 'Apakah Anda ingin menambah data pelanggan lagi?',
      icon: 'success',
      showCloseButton: false,
      showCancelButton: false,
      showDenyButton: true,
      allowOutsideClick: false,
      confirmButtonText: 'Ya, Tambah Lagi',
      denyButtonText: 'Tidak, Cek Data',
      customClass: {
        popup: 'pelanggan-success-popup',
        confirmButton: 'pelanggan-success-confirm',
        denyButton: 'pelanggan-success-deny',
      },
      didOpen: (popup) => {
        const close = popup.querySelector('.swal2-close')
        if (close) close.style.setProperty('display', 'none', 'important')
        const cancel = popup.querySelector('.swal2-cancel')
        if (cancel) cancel.style.setProperty('display', 'none', 'important')
      },
      reverseButtons: true,
    })

    if (result.isConfirmed) {
      uiStore.success('Data pelanggan berhasil disimpan')
      resetForm()
    } else if (result.isDenied) {
      uiStore.success('Data pelanggan berhasil disimpan')
      router.push('/app/data-pelanggan')
    }
  } catch (error) {
    console.error('Error saving customer:', error)

    console.log(error.response?.data)

    Swal.fire({
      title: 'Gagal!',
      text: error.response?.data?.message || 'Terjadi kesalahan saat menyimpan data',
      icon: 'error',
    })
  } finally {
    isLoading.value = false
  }
}

const resetForm = () => {
  form.value = {
    nik: '',
    nama_lengkap: '',
    email: '',
    password: '',
    tempat_lahir: '',
    tgl_lahir: new Date(),
    jenis_kelamin: '',
    no_telp: '',
    alamat_lengkap: '',
  }
}
</script>

<style scoped>
.pelanggan-create-view {
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

<style>
.pelanggan-success-popup .swal2-close,
.pelanggan-success-popup .swal2-cancel {
  display: none !important;
}

.swal2-confirm.pelanggan-success-confirm {
  background-color: #60a5fa !important;
  color: #ffffff !important;
}

.swal2-confirm.pelanggan-success-confirm:hover {
  background-color: #3b82f6 !important;
}

.swal2-deny.pelanggan-success-deny {
  background-color: #64748b !important;
  color: #ffffff !important;
}

.swal2-deny.pelanggan-success-deny:hover {
  background-color: #475569 !important;
}
</style>
