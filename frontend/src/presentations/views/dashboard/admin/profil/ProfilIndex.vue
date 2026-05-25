<template>
  <div class="profil-page space-y-6!">
    <ContentCard variant="bordered" padding="normal" rounded="xl" hoverable>
      <div class="flex! flex-row! items-center! gap-6!">
        <div class="relative! group!">
          <button
            type="button"
            @click="triggerAvatarUpload"
            :disabled="isUploadingAvatar"
            class="block! w-24! h-24! rounded-full! overflow-hidden! border-4! border-slate-50! shadow-md! transition-all! duration-300! hover:border-blue-100! hover:shadow-lg! disabled:opacity-60! disabled:cursor-not-allowed! cursor-pointer!"
            title="Klik untuk ubah foto profil"
          >
            <img
              :src="
                avatarPreview ||
                'https://ui-avatars.com/api/?name=' +
                  encodeURIComponent(form.name || 'User') +
                  '&background=0D8ABC&color=fff'
              "
              alt="Profile"
              class="w-full! h-full! object-cover!"
            />
            <span
              class="absolute! inset-0! flex! items-center! justify-center! bg-slate-900/50! text-white! opacity-0! group-hover:opacity-100! transition-opacity! duration-300! rounded-full!"
            >
              <font-awesome-icon
                :icon="isUploadingAvatar ? 'spinner' : 'camera'"
                :spin="isUploadingAvatar"
                class="text-lg!"
              />
            </span>
          </button>
          <input
            ref="avatarInput"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            class="hidden!"
            @change="handleAvatarChange"
          />
        </div>
        <div class="text-left!">
          <h1 class="text-2xl! font-bold! text-slate-800!">
            {{ form.name || '-' }}
          </h1>
          <p class="text-slate-500! font-medium!">{{ form.email || '-' }}</p>
          <span
            v-if="role"
            class="inline-block! mt-2! px-3! py-1! bg-blue-50! text-blue-600! text-[10px]! font-bold! uppercase! tracking-widest! rounded-full!"
          >
            {{ role }}
          </span>
        </div>
      </div>
    </ContentCard>

    <ContentCard variant="bordered" padding="large" rounded="xl" hoverable>
      <template #header>
        <div class="flex! items-center! justify-between! mb-4!">
          <h2 class="text-xl! font-bold! text-slate-800!">Data Akun</h2>
        </div>
      </template>

      <div class="space-y-6!">
        <div class="grid! grid-cols-1! md:grid-cols-2! gap-6!">
          <BaseInput
            label="Nama Lengkap"
            v-model="form.name"
            placeholder="Masukkan nama lengkap"
            prefix-icon="user"
          />
          <BaseInput
            label="Email"
            type="email"
            v-model="form.email"
            placeholder="email@domain.com"
            prefix-icon="envelope"
          />
        </div>

        <div
          class="flex! flex-col! sm:flex-row! justify-end! items-stretch! sm:items-center! gap-3! mt-8! pt-6! border-t! border-slate-100!"
        >
          <BaseButton
            variant="info"
            @click="showModal = true"
            class="shadow-sm!"
            icon="key"
          >
            Ubah Password
          </BaseButton>
          <BaseButton
            variant="secondary"
            @click="saveProfile"
            :loading="isSaving"
            icon="save"
          >
            Simpan Perubahan
          </BaseButton>
        </div>
      </div>
    </ContentCard>

    <ContentCard
      v-if="hasIdentity"
      variant="bordered"
      padding="large"
      rounded="xl"
      hoverable
    >
      <template #header>
        <div class="flex! items-center! justify-between! mb-1!">
          <div>
            <h2 class="text-xl! font-bold! text-slate-800!">Data Diri</h2>
            <p class="text-xs! text-slate-400! font-medium!">
              Diambil dari data pendaftaran instalasi. Tidak dapat diedit dari halaman ini.
            </p>
          </div>
          <span
            class="px-3! py-1! bg-slate-100! text-slate-500! text-[10px]! font-bold! uppercase! tracking-widest! rounded-full!"
          >
            Read Only
          </span>
        </div>
      </template>

      <div class="grid! grid-cols-1! md:grid-cols-2! gap-x-6! gap-y-4! mt-4!">
        <ProfileField label="NIK" :value="identity.nik" icon="id-card" />
        <ProfileField
          label="Kode Pelanggan"
          :value="identity.customer_code"
          icon="hashtag"
        />
        <ProfileField label="No. Telepon" :value="identity.phone" icon="phone" />
        <ProfileField
          label="Jenis Kelamin"
          :value="genderLabel"
          icon="venus-mars"
        />
        <ProfileField
          label="Tempat Lahir"
          :value="identity.birth_place"
          icon="map-marker-alt"
        />
        <ProfileField
          label="Tanggal Lahir"
          :value="formattedBirthDate"
          icon="calendar"
        />
        <div class="md:col-span-2!">
          <ProfileField
            label="Alamat"
            :value="identity.address"
            icon="home"
            multiline
          />
        </div>
      </div>
    </ContentCard>

    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click="closeModal">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <div class="flex! items-center! gap-3!">
              <div class="w-10! h-10! rounded-lg! bg-blue-50! flex! items-center! justify-center!">
                <font-awesome-icon icon="key" class="text-blue-600!" />
              </div>
              <div>
                <h3 class="text-lg! font-bold! text-slate-800!">Ubah Password</h3>
                <p class="text-xs! text-slate-500!">Pastikan password baru Anda aman</p>
              </div>
            </div>
            <button
              @click="closeModal"
              class="text-slate-400! hover:text-slate-600! transition-colors!"
            >
              <font-awesome-icon icon="times" />
            </button>
          </div>

          <div class="modal-body p-6! space-y-4!">
            <BaseInput
              label="Password Saat Ini"
              type="password"
              v-model="passwordForm.current_password"
              placeholder="Masukkan password saat ini"
              prefix-icon="lock"
            />
            <BaseInput
              label="Password Baru"
              type="password"
              v-model="passwordForm.new_password"
              placeholder="Masukkan password baru"
              prefix-icon="key"
            />
            <BaseInput
              label="Konfirmasi Password Baru"
              type="password"
              v-model="passwordForm.new_password_confirmation"
              placeholder="Ulangi password baru"
              prefix-icon="key"
            />
          </div>

          <div class="modal-footer">
            <BaseButton variant="secondary" @click="closeModal">Batal</BaseButton>
            <BaseButton
              variant="primary"
              @click="changePassword"
              :loading="isUpdatingPassword"
            >
              Simpan Password
            </BaseButton>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted, computed, h } from 'vue'
import ContentCard from '@/presentations/components/ui/ContentCard.vue'
import BaseInput from '@/presentations/components/ui/BaseInput.vue'
import BaseButton from '@/presentations/components/ui/BaseButton.vue'
import profileService from '@/services/profile.service'
import { MySwal } from '@/utils/swal'

const ProfileField = {
  name: 'ProfileField',
  props: {
    label: { type: String, required: true },
    value: { type: [String, Number], default: '' },
    icon: { type: String, default: 'circle-info' },
    multiline: { type: Boolean, default: false },
  },
  setup(props) {
    return () =>
      h(
        'div',
        {
          class:
            'flex flex-col gap-1.5! p-3! bg-slate-50/60! border! border-slate-100! rounded-xl!',
        },
        [
          h(
            'div',
            {
              class:
                'flex items-center gap-2! text-[10px]! font-bold! text-slate-400! uppercase! tracking-widest!',
            },
            [
              h('font-awesome-icon', { icon: props.icon, class: 'text-slate-400!' }),
              h('span', null, props.label),
            ],
          ),
          h(
            'p',
            {
              class: [
                'text-sm! font-bold! text-slate-700! leading-relaxed! break-words!',
                props.multiline ? 'whitespace-pre-line!' : '',
              ],
            },
            props.value || '-',
          ),
        ],
      )
  },
}

const showModal = ref(false)
const isSaving = ref(false)
const isUpdatingPassword = ref(false)
const isUploadingAvatar = ref(false)
const role = ref('')
const avatarInput = ref(null)
const avatarPreview = ref('')

const form = reactive({
  name: '',
  email: '',
})

const identity = reactive({
  nik: '',
  customer_code: '',
  phone: '',
  gender: '',
  birth_place: '',
  birth_date: '',
  address: '',
})

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const hasIdentity = computed(() =>
  Object.values(identity).some((v) => v !== '' && v !== null && v !== undefined),
)

const genderLabel = computed(() => {
  if (identity.gender === 'male') return 'Laki-laki'
  if (identity.gender === 'female') return 'Perempuan'
  return identity.gender || ''
})

const formattedBirthDate = computed(() => {
  if (!identity.birth_date) return ''
  const d = new Date(identity.birth_date)
  if (Number.isNaN(d.getTime())) return identity.birth_date
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
})

watch(showModal, (val) => {
  document.body.style.overflow = val ? 'hidden' : ''
})

const showSuccessToast = (title) => {
  MySwal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title,
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    customClass: { popup: 'swal-toast-custom', title: 'swal-toast-title' },
  })
}

const showErrorToast = (error) => {
  const message =
    error?.response?.data?.message || error?.message || 'Terjadi kesalahan pada server'
  MySwal.fire({
    toast: true,
    position: 'top-end',
    icon: 'error',
    title: message,
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    customClass: { popup: 'swal-toast-custom', title: 'swal-toast-title' },
  })
}

const loadProfile = async () => {
  try {
    const res = await profileService.getMe()
    const data = res?.data || res
    if (!data) return

    form.name = data.name || ''
    form.email = data.email || ''
    role.value = data.role || ''
    avatarPreview.value = data.avatar_url || ''

    const id = data.identity || data.customer || {}
    const ticket = id.ticket || data.ticket || {}

    identity.nik = ticket.nik || id.nik || ''
    identity.customer_code = id.customer_code || ''
    identity.phone = ticket.phone || id.phone || ''
    identity.gender = ticket.gender || id.gender || ''
    identity.birth_place = ticket.birth_place || id.birth_place || ''
    identity.birth_date = ticket.birth_date || id.birth_date || ''
    identity.address = ticket.address || id.address || ''
  } catch (error) {
    showErrorToast(error)
  }
}

const saveProfile = async () => {
  try {
    isSaving.value = true
    await profileService.updateProfile({ name: form.name, email: form.email })
    showSuccessToast('Profil berhasil diperbarui')
  } catch (error) {
    showErrorToast(error)
  } finally {
    isSaving.value = false
  }
}

const triggerAvatarUpload = () => {
  if (isUploadingAvatar.value) return
  avatarInput.value?.click()
}

const handleAvatarChange = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  if (!file.type.startsWith('image/')) {
    showErrorToast({ message: 'File harus berupa gambar' })
    event.target.value = ''
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    showErrorToast({ message: 'Ukuran gambar maksimal 2 MB' })
    event.target.value = ''
    return
  }

  const previousPreview = avatarPreview.value
  const reader = new FileReader()
  reader.onload = (e) => {
    avatarPreview.value = e.target.result
  }
  reader.readAsDataURL(file)

  try {
    isUploadingAvatar.value = true
    const res = await profileService.uploadAvatar(file)
    const data = res?.data || res
    if (data?.avatar_url) {
      avatarPreview.value = data.avatar_url
    }
    showSuccessToast('Foto profil berhasil diperbarui')
  } catch (error) {
    avatarPreview.value = previousPreview
    showErrorToast(error)
  } finally {
    isUploadingAvatar.value = false
    event.target.value = ''
  }
}

const resetPasswordForm = () => {
  passwordForm.current_password = ''
  passwordForm.new_password = ''
  passwordForm.new_password_confirmation = ''
}

const closeModal = () => {
  showModal.value = false
  resetPasswordForm()
}

const changePassword = async () => {
  if (!passwordForm.current_password || !passwordForm.new_password) {
    showErrorToast({ message: 'Mohon lengkapi semua kolom password' })
    return
  }
  if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
    showErrorToast({ message: 'Konfirmasi password baru tidak cocok' })
    return
  }

  try {
    isUpdatingPassword.value = true
    await profileService.updatePassword({ ...passwordForm })
    showSuccessToast('Password berhasil diperbarui')
    closeModal()
  } catch (error) {
    showErrorToast(error)
  } finally {
    isUpdatingPassword.value = false
  }
}

onMounted(() => {
  loadProfile()
})
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
