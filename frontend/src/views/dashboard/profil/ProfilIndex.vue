<template>
  <div class="profil-page space-y-6!">
    <!-- Header Section -->
    <ContentCard variant="bordered" padding="normal" rounded="xl" hoverable>
      <div class="flex! flex-row! items-center! gap-6!">
        <div class="relative! group!">
          <div
            class="w-24! h-24! rounded-full! overflow-hidden! border-4! border-slate-50! shadow-md! transition-all! duration-300! group-hover:shadow-lg! group-hover:border-blue-100!"
          >
            <img
              :src="
                profilePhoto ||
                'https://ui-avatars.com/api/?name=' +
                  form.nama_depan +
                  '+' +
                  form.nama_belakang +
                  '&background=0D8ABC&color=fff'
              "
              alt="Profile"
              class="w-full! h-full! object-cover!"
            />
          </div>
          <button
            @click="triggerPhotoUpload"
            class="absolute! bottom-0! right-0! w-8! h-8! bg-blue-600! text-white! rounded-full! flex! items-center! justify-center! border-2! border-white! shadow-lg! transition-all! duration-300! hover:bg-blue-700! hover:scale-110!"
            title="Ubah Foto"
          >
            <font-awesome-icon icon="camera" class="text-xs!" />
          </button>
          <input
            type="file"
            ref="photoInput"
            @change="handlePhotoUpload"
            accept="image/*"
            class="hidden!"
          />
        </div>
        <div class="text-left!">
          <h1 class="text-2xl! font-bold! text-slate-800!">
            {{ form.nama_depan }} {{ form.nama_belakang }}
          </h1>
          <p class="text-slate-500! font-medium!">{{ form.peran || 'Kepala Pabrik' }}</p>
        </div>
      </div>
    </ContentCard>

    <!-- Data Diri Section -->
    <ContentCard variant="bordered" padding="large" rounded="xl" hoverable>
      <template #header>
        <div class="flex! items-center! justify-between! mb-4!">
          <h2 class="text-xl! font-bold! text-slate-800!">Data Diri</h2>
        </div>
      </template>

      <div class="space-y-6!">
        <!-- Row 1: NIK & Nama Depan -->
        <div class="grid! grid-cols-2! gap-6!">
          <BaseInput label="NIK" v-model="form.nik" placeholder="3308128492740001" />
          <BaseInput label="Nama Depan" v-model="form.nama_depan" placeholder="Santoso" />
        </div>

        <!-- Row 2: Nama Belakang & Inisial -->
        <div class="grid! grid-cols-2! gap-6!">
          <BaseInput label="Nama Belakang" v-model="form.nama_belakang" placeholder="SIDBM" />
          <BaseInput label="Inisial" v-model="form.inisial" placeholder="SS" />
        </div>

        <!-- Row 3: Tempat Lahir & Tanggal Lahir -->
        <div class="grid! grid-cols-2! gap-6!">
          <BaseInput label="Tempat Lahir" v-model="form.tempat_lahir" placeholder="Magelang" />
          <AppDatePicker label="Tanggal Lahir" v-model="form.tanggal_lahir" yearRange="1950:2026" />
        </div>

        <!-- Row 4: Alamat (Full Width) -->
        <div class="grid! grid-cols-1! gap-6!">
          <BaseInput
            type="textarea"
            label="Alamat"
            v-model="form.alamat"
            placeholder="Jl. Daranindra No. 1 Borobudur 5655"
            :rows="3"
          />
        </div>

        <!-- Row 5: Telpon & Pendidikan -->
        <div class="grid! grid-cols-2! gap-6!">
          <BaseInput label="Telpon" v-model="form.telpon" placeholder="6281234567890" />
          <SelectSearch
            label="Pendidikan"
            v-model="form.pendidikan"
            :options="pendidikanOptions"
            placeholder="Pilih pendidikan"
          />
        </div>

        <!-- Row 6: Menjabat Sejak -->
        <div class="grid! grid-cols-2! gap-6!">
          <AppDatePicker
            label="Menjabat Sejak"
            v-model="form.menjabat_sejak"
            yearRange="2000:2030"
          />
        </div>

        <!-- Buttons -->
        <div
          class="flex! justify-end! items-center! gap-4! mt-8! pt-6! border-t! border-slate-100!"
        >
          <BaseButton variant="info" @click="showModal = true" class="shadow-sm!">
            EDIT USER
          </BaseButton>
          <button
            @click="saveChanges"
            :disabled="isSaving"
            class="px-6! py-2! bg-[#1A202C]! hover:bg-black! text-white! text-sm! font-bold! rounded-lg! transition-all! duration-300! flex! items-center! gap-2! disabled:opacity-50!"
          >
            <font-awesome-icon v-if="isSaving" icon="spinner" spin />
            SIMPAN PERUBAHAN
          </button>
        </div>
      </div>
    </ContentCard>

    <!-- Edit User Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click="showModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <div class="flex! items-center! gap-3!">
              <div class="w-10! h-10! rounded-lg! bg-blue-50! flex! items-center! justify-center!">
                <font-awesome-icon icon="user-edit" class="text-blue-600!" />
              </div>
              <div>
                <h3 class="text-lg! font-bold! text-slate-800!">Edit Akun</h3>
                <p class="text-xs! text-slate-500!">Update username dan password Anda</p>
              </div>
            </div>
            <button
              @click="showModal = false"
              class="text-slate-400! hover:text-slate-600! transition-colors!"
            >
              <font-awesome-icon icon="times" />
            </button>
          </div>

          <div class="modal-body p-6! space-y-4!">
            <BaseInput
              label="Username"
              v-model="userForm.username"
              placeholder="Masukkan username"
              prefixIcon="user"
            />
            <BaseInput
              label="Password"
              type="password"
              v-model="userForm.password"
              placeholder="Masukkan password baru"
              prefixIcon="key"
            />
            <p class="text-[10px]! text-slate-400! italic!">
              * Kosongkan password jika tidak ingin mengganti
            </p>
          </div>

          <div class="modal-footer">
            <BaseButton variant="secondary" @click="showModal = false">Batal</BaseButton>
            <BaseButton variant="primary" @click="updateUser" :loading="isUpdatingUser"
              >Update</BaseButton
            >
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import AppDatePicker from '@/components/AppDatePicker.vue'
import SelectSearch from '@/components/SelectSearch.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import Swal from 'sweetalert2'

// State
const showModal = ref(false)
const isSaving = ref(false)
const isUpdatingUser = ref(false)
const photoInput = ref(null)
const profilePhoto = ref(null)

// Watchers
watch(showModal, (val) => {
  if (val) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

const form = reactive({
  nik: '3308128492740001',
  nama_depan: 'Santoso',
  nama_belakang: 'SIDBM',
  peran: 'Kepala Pabrik',
  inisial: 'SS',
  tempat_lahir: 'Magelang',
  tanggal_lahir: '1994-10-06',
  alamat: 'Jl. Daranindra No. 1 Borobudur 5655',
  telpon: '6281234567890',
  pendidikan: 'Doktor (S3)',
  menjabat_sejak: '2023-10-03',
})

const userForm = reactive({
  username: 'santoso_sidbm',
  password: '',
})

const pendidikanOptions = [
  { label: 'SD', value: 'SD' },
  { label: 'SMP', value: 'SMP' },
  { label: 'SMA/SMK', value: 'SMA/SMK' },
  { label: 'Diploma (D1-D4)', value: 'Diploma (D1-D4)' },
  { label: 'Sarjana (S1)', value: 'Sarjana (S1)' },
  { label: 'Magister (S2)', value: 'Magister (S2)' },
  { label: 'Doktor (S3)', value: 'Doktor (S3)' },
]

// Methods
const triggerPhotoUpload = () => {
  photoInput.value.click()
}

const handlePhotoUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      profilePhoto.value = e.target.result
      Swal.fire({
        icon: 'success',
        title: 'Foto Berhasil Diupload',
        text: 'Foto profil Anda telah diperbarui secara lokal.',
        timer: 2000,
        showConfirmButton: false,
      })
    }
    reader.readAsDataURL(file)
  }
}

const saveChanges = async () => {
  isSaving.value = true

  // Simulate API call
  await new Promise((resolve) => setTimeout(resolve, 1500))

  isSaving.value = false
  Swal.fire({
    icon: 'success',
    title: 'Perubahan Disimpan',
    text: 'Data diri Anda berhasil diperbarui.',
    confirmButtonColor: '#4f46e5',
  })
}

const updateUser = async () => {
  isUpdatingUser.value = true

  // Simulate API call
  await new Promise((resolve) => setTimeout(resolve, 1500))

  isUpdatingUser.value = false
  showModal.value = false
  Swal.fire({
    icon: 'success',
    title: 'Akun Diperbarui',
    text: 'Username/Password berhasil diperbarui.',
    confirmButtonColor: '#4f46e5',
  })
}
</script>

<style scoped>
.profil-page {
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

/* Modal Styling */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 1.25rem;
  width: 100%;
  max-width: 450px;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modalIn 0.3s ease-out;
}

@keyframes modalIn {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(to right, #f8fafc, #ffffff);
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>
