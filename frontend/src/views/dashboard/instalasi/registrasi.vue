<template>
  <div class="registrasi-root!">
    <div class="mb-2!">
      <h1 class="text-2xl! font-bold! text-slate-800! tracking-tight!">Register Instalasi</h1>
      <p class="text-sm! text-slate-500! mt-1!">
        Create a new service connection for validated customers.
      </p>
    </div>

    <div class="mb-5!">
      <div class="relative!" ref="customerSelectRef">
        <ContentCard variant="bordered" padding="none" rounded="xl" class="overflow-hidden!">
          <div
            class="flex! items-center! gap-3! w-full! bg-white! rounded-xl! pl-4! py-0! transition-all! duration-200!"
            :class="[
              isCustomerDropdownOpen
                ? 'border-blue-400! ring-2! ring-blue-100! shadow-xl! shadow-blue-500/10!'
                : 'border-slate-100! hover:border-slate-200!',
            ]"
          >
            <font-awesome-icon icon="search" class="w-4! h-4! text-slate-400! shrink-0!" />

            <input
              v-model="customerSearch"
              type="text"
              placeholder="Search by Name, NIK, or Customer ID..."
              class="flex-1! bg-transparent! border-none! text-sm! text-slate-700! placeholder-slate-400! focus:outline-none! py-4!"
              @focus="isCustomerDropdownOpen = true"
              @input="isCustomerDropdownOpen = true"
            />

            <div class="flex! items-center! gap-0! shrink-0! h-full!">
              <BaseButton
                v-if="selectedCustomer || customerSearch"
                variant="ghost"
                size="sm"
                class="w-10! h-10! p-0! rounded-full! mr-2!"
                @click.stop="clearCustomer"
                icon="times"
              />
              <BaseButton
                variant="primary"
                class="h-[52px]! px-4! sm:px-5! rounded-l-none! rounded-r-xl! border-none! bg-linear-to-r! from-indigo-600! via-blue-600! to-cyan-500! shadow-lg! shadow-indigo-500/20! hover:shadow-indigo-500/40! text-white! ring-offset-0! focus:ring-0!"
                icon="user-plus"
                @click.stop="handleNewCustomerRegistration"
              >
                <span class="hidden! sm:inline-block! text-xs! font-bold!"
                  >Registrasi Pelanggan</span
                >
              </BaseButton>
            </div>
          </div>
        </ContentCard>

        <Transition
          enter-active-class="transition! duration-150! ease-out!"
          enter-from-class="opacity-0! translate-y-1! scale-95!"
          enter-to-class="opacity-100! translate-y-0! scale-100!"
          leave-active-class="transition! duration-100! ease-in!"
          leave-from-class="opacity-100! translate-y-0! scale-100!"
          leave-to-class="opacity-0! translate-y-1! scale-95!"
        >
          <div
            v-if="isCustomerDropdownOpen"
            class="absolute! top-full! left-0! right-0! mt-2! bg-white! border! border-slate-200! rounded-xl! shadow-xl! z-50! overflow-hidden!"
          >
            <div class="max-h-60! overflow-y-auto!">
              <div v-if="filteredCustomerOptions.length === 0" class="py-12! px-6! text-center!">
                <div
                  class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! border-2! border-dashed! border-slate-200!"
                >
                  <font-awesome-icon icon="users" class="w-6! h-6! text-slate-300!" />
                </div>
                <p class="text-sm! font-bold! text-slate-600! mb-1!">No customers found</p>
                <p class="text-xs! text-slate-400! leading-relaxed!">
                  We couldn't find any customers matching your search query or filter.
                </p>
              </div>
              <BaseButton
                v-for="customer in filteredCustomerOptions"
                :key="customer.id"
                @click.stop="selectCustomer(customer)"
                variant="ghost"
                class="w-full! px-4! py-1.5! hover:bg-slate-50! transition-all! duration-200! text-left! border-b! border-slate-50! last:border-0! rounded-none! shadow-none!"
              >
                <div class="flex! items-center! gap-3! w-full!">
                  <div
                    class="w-8! h-8! rounded-full! shrink-0! flex! items-center! justify-center! text-white! text-xs! font-extrabold! shadow-sm!"
                    :style="{ backgroundColor: customer.color }"
                  >
                    {{ customer.initials }}
                  </div>
                  <div class="flex-1! min-w-0!">
                    <div
                      class="text-[13px]! font-bold! text-slate-700! truncate! group-hover:text-blue-600! transition-colors!"
                    >
                      {{ customer.name }}
                    </div>
                    <div class="text-[9px]! text-slate-400! font-mono! font-bold! leading-none!">
                      {{ customer.id }}
                    </div>
                  </div>
                  <div
                    :class="[
                      'text-[9px]! font-black! px-2! py-0.5! rounded-md! border! shrink-0! uppercase! tracking-wider!',
                      customer.status === 'Aktif'
                        ? statusBadge['Terdaftar']
                        : statusBadge['Belum Terdaftar'],
                    ]"
                  >
                    {{ customer.status === 'Aktif' ? 'Terdaftar' : 'Belum Terdaftar' }}
                  </div>
                </div>
              </BaseButton>
            </div>

            <div class="px-4! py-2! border-t! border-slate-100! bg-slate-50/50!">
              <p class="text-[11px]! text-slate-400!">
                {{ filteredCustomerOptions.length }} customer ditemukan
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <ContentCard variant="bordered" padding="none" hoverable class="overflow-hidden!">
      <!-- Unified Header: Selected Customer -->
      <Transition
        enter-active-class="transition! duration-300! ease-out!"
        enter-from-class="opacity-0! -translate-y-4!"
        enter-to-class="opacity-100! translate-y-0!"
        leave-active-class="transition! duration-200! ease-in!"
        leave-from-class="opacity-100! translate-y-0!"
        leave-to-class="opacity-0! -translate-y-4!"
      >
        <div
          v-if="selectedCustomer"
          class="bg-linear-to-br! from-indigo-600! via-blue-600! to-cyan-500! p-4! sm:p-5! text-white! flex! items-center! gap-4! sm:gap-5! border-b! border-white/10!"
        >
          <div
            class="w-10! h-10! sm:w-14! sm:h-14! rounded-full! bg-white/20! backdrop-blur-md! flex! items-center! justify-center! text-white! font-bold! text-base! sm:text-xl! border-2! border-white/40! shrink-0!"
          >
            {{ selectedCustomer.initials }}
          </div>
          <div class="flex-1! min-w-0!">
            <div class="font-bold! text-white! text-base! sm:text-lg! truncate! tracking-tight!">
              {{ selectedCustomer.name }}
            </div>
            <div
              class="flex! items-center! gap-2! text-blue-50! text-[10px]! sm:text-xs! mt-0.5! sm:mt-1! opacity-90!"
            >
              <span class="font-mono! font-bold!">{{ selectedCustomer.id }}</span>
              <span class="hidden! sm:inline!">·</span>
              <span class="truncate! hidden! sm:inline!">{{ selectedCustomer.address }}</span>
            </div>
          </div>
          <div
            :class="[
              'backdrop-blur-md! text-white! border! text-[9px]! sm:text-[10px]! font-bold! px-2! sm:px-3! py-1! sm:py-1.5! rounded-full! shrink-0! uppercase! tracking-wider!',
              selectedCustomer.status === 'Aktif'
                ? 'bg-emerald-500/40! border-emerald-400/50!'
                : 'bg-white/20! border-white/30!',
            ]"
          >
            ✓ {{ selectedCustomer.status === 'Aktif' ? 'Terdaftar' : 'Belum Terdaftar' }}
          </div>
        </div>
      </Transition>
      <div
        class="grid! grid-cols-1! lg:grid-cols-2! gap-2! divide-y! lg:divide-y-0! lg:divide-x! divide-slate-100!"
      >
        <!-- Service Details Section -->
        <div class="p-5! lg:p-6!">
          <div class="flex! items-center! gap-2.5! mb-5!">
            <div class="w-8! h-8! bg-blue-100! rounded-lg! flex! items-center! justify-center!">
              <font-awesome-icon icon="file-alt" class="w-4! h-4! text-blue-600!" />
            </div>
            <h2 class="text-base! font-bold! text-slate-800!">Service Details</h2>
          </div>

          <AppDatePicker
            v-model="form.tanggalOrder"
            label="Tanggal Order"
            placeholder="Pilih tanggal order"
          />

          <BaseInput
            v-model="form.namaCater"
            label="Nama Cater"
            placeholder="Enter surveyor name"
          />

          <div class="grid! grid-cols-2! gap-3!">
            <BaseSelect v-model="form.kategori" label="Kategori">
              <option value="Rumah Tangga">Rumah Tangga</option>
              <option value="Komersial">Komersial</option>
              <option value="Sosial">Sosial</option>
              <option value="Industri">Industri</option>
            </BaseSelect>

            <BaseSelect v-model="form.paket" label="Paket/Kelas">
              <option value="Gold - A1">Gold - A1</option>
              <option value="Silver - B1">Silver - B1</option>
              <option value="Bronze - C1">Bronze - C1</option>
              <option value="Platinum - S1">Platinum - S1</option>
            </BaseSelect>
          </div>

          <div class="grid! grid-cols-2! gap-3!">
            <BaseInput
              v-model="form.kodeInstalasi"
              label="Kode Instalasi"
              placeholder="INST-0000"
              prefixIcon="hashtag"
              customClass="font-mono"
            />

            <MaksMoneyInput v-model="form.nominal" label="Nominal (Rp)" placeholder="0" />
          </div>

          <BaseInput
            v-model="form.catatan"
            type="textarea"
            label="Catatan (opsional)"
            placeholder="Tambahkan catatan instalasi..."
            :rows="3"
          />
        </div>

        <!-- Deployment Site Section -->
        <div class="p-5! lg:p-6!">
          <div class="flex! items-center! gap-2.5! mb-5!">
            <div class="w-8! h-8! bg-cyan-100! rounded-lg! flex! items-center! justify-center!">
              <font-awesome-icon icon="map-marker-alt" class="w-4! h-4! text-cyan-600!" />
            </div>
            <h2 class="text-base! font-bold! text-slate-800!">Deployment Site</h2>
          </div>

          <BaseInput
            v-model="form.namaDesa"
            label="Nama Desa & Dusun"
            placeholder="e.g. Sukamaju, Dusun III"
          />

          <BaseInput v-model="form.jalan" label="Jalan" placeholder="Street name and number" />

          <div class="grid! grid-cols-2! gap-3!">
            <BaseInput
              v-model="form.rtrw"
              label="RT/RW"
              placeholder="00/00"
              customClass="font-mono"
            />

            <BaseInput
              v-model="form.koordinat"
              label="Koordinat"
              placeholder="-6.123, 106.123"
              customClass="font-mono pr-10"
            >
              <BaseButton
                variant="ghost"
                @click="getCurrentLocation"
                class="absolute! right-2! top-1/2! -translate-y-1/2! p-2! w-8! h-8! text-cyan-500! hover:text-cyan-700!"
                title="Gunakan lokasi saat ini"
                icon="map-marker-alt"
              />
            </BaseInput>
          </div>

          <div>
            <label class="block! text-xs! font-semibold! text-slate-500! mb-1.5!"
              >Preview Lokasi</label
            >
            <div
              class="relative! w-full! h-44! bg-linear-to-br! from-slate-100! to-slate-200! rounded-xl! overflow-hidden! border! border-slate-200!"
            >
              <div
                class="absolute! inset-0!"
                style="
                  background-image:
                    linear-gradient(rgba(148, 163, 184, 0.15) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(148, 163, 184, 0.15) 1px, transparent 1px);
                  background-size: 20px 20px;
                "
              ></div>

              <div class="absolute! inset-0! flex! items-center! justify-center!">
                <div v-if="!hasCoordinates" class="text-center!">
                  <div
                    class="w-12! h-12! rounded-full! bg-white/80! border-2! border-dashed! border-slate-300! flex! items-center! justify-center! mx-auto! mb-2!"
                  >
                    <font-awesome-icon icon="map-marker-alt" class="w-5! h-5! text-slate-400!" />
                  </div>
                  <p class="text-xs! text-slate-400! font-medium!">
                    Masukkan koordinat untuk preview
                  </p>
                </div>

                <div v-else class="relative!">
                  <div
                    class="w-8! h-8! rounded-full! bg-blue-600! border-3! border-white! shadow-lg! flex! items-center! justify-center! animate-bounce!"
                  >
                    <font-awesome-icon icon="map-marker-alt" class="w-4! h-4! text-white!" />
                  </div>
                  <div
                    class="absolute! -inset-2! rounded-full! bg-blue-400/20! animate-ping!"
                  ></div>
                </div>
              </div>

              <div class="absolute! bottom-3! left-1/2! -translate-x-1/2!">
                <BaseButton
                  variant="glass"
                  size="sm"
                  @click="openMapPreview"
                  class="px-3! py-1.5! backdrop-blur-sm! shadow-sm! hover:shadow-md!"
                >
                  <font-awesome-icon icon="eye" class="mr-2! text-blue-500!" />
                  PREVIEW LOCATION
                </BaseButton>
              </div>

              <div
                v-if="hasCoordinates"
                class="absolute! top-2! right-2! bg-white/90! backdrop-blur-sm! rounded-lg! px-2! py-1! text-[10px]! font-mono! text-slate-600! border! border-slate-200! shadow-sm!"
              >
                {{ form.koordinat }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </ContentCard>

    <div class="mt-8! sm:mt-10! flex! flex-col! sm:flex-row! items-center! justify-between! gap-5!">
      <div class="flex! items-center! gap-2! text-xs! text-slate-400! opacity-80!">
        <div
          class="w-4! h-4! rounded-full! bg-blue-50! flex! items-center! justify-center! shrink-0!"
        >
          <font-awesome-icon icon="info-circle" class="w-2.5! h-2.5! text-blue-500!" />
        </div>
        Pastikan data diisi semua jika ada yg kosong bisa isi - atau 0
      </div>

      <div class="w-full! sm:w-auto!">
        <BaseButton
          variant="primary-gradient"
          class="w-full! sm:w-auto! px-10! py-3.5! rounded-xl! shadow-lg! text-sm! font-bold! uppercase! tracking-wide!"
          @click="handleSubmit"
          :disabled="!isFormValid"
        >
          Daftar & Simpan
          <font-awesome-icon icon="check-circle" class="ml-2!" />
        </BaseButton>
      </div>
    </div>

    <Transition
      enter-active-class="transition! duration-300! ease-out!"
      enter-from-class="opacity-0! translate-y-4!"
      enter-to-class="opacity-100! translate-y-0!"
      leave-active-class="transition! duration-200! ease-in!"
      leave-from-class="opacity-100! translate-y-0!"
      leave-to-class="opacity-0! translate-y-4!"
    >
      <div
        v-if="showSuccessToast"
        class="fixed! bottom-6! right-6! z-50! bg-emerald-600! text-white! px-5! py-3.5! rounded-2xl! shadow-xl! flex! items-center! gap-3!"
      >
        <div
          class="w-6! h-6! rounded-full! bg-white/20! flex! items-center! justify-center! shrink-0!"
        >
          <font-awesome-icon icon="check" class="w-3.5! h-3.5!" />
        </div>
        <div>
          <p class="text-sm! font-bold!">Instalasi berhasil didaftarkan!</p>
          <p class="text-xs! text-emerald-100!">Data telah disimpan ke sistem</p>
        </div>
      </div>
    </Transition>

    <div
      v-if="isCustomerDropdownOpen"
      class="fixed! inset-0! z-40!"
      @click="closeCustomerDropdown"
    ></div>
  </div>
</template>
<script>
export default {
  name: 'InstalasiRegistrasi',
}
</script>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import ContentCard from '@/components/ui/ContentCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseSelect from '@/components/ui/BaseSelect.vue'
import AppDatePicker from '@/components/AppDatePicker.vue'
import MaksMoneyInput from '@/components/MaksMoneyInput.vue'

const isCustomerDropdownOpen = ref(false)
const customerSearch = ref('')
const activeFilterTab = ref('Semua')
const selectedCustomer = ref(null)
const customerSelectRef = ref(null)

const form = ref({
  tanggalOrder: new Date(),
  namaCater: '',
  kategori: 'Rumah Tangga',
  paket: 'Gold - A1',
  kodeInstalasi: '',
  nominal: '',
  catatan: '',
  namaDesa: '',
  jalan: '',
  rtrw: '',
  koordinat: '',
})

const showSuccessToast = ref(false)

const customerOptions = ref([
  {
    id: '#MA-2023-001',
    name: 'Adi Saputra',
    initials: 'AS',
    color: '#3B82F6',
    address: 'Jl. Merdeka No. 45, Jakarta',
    status: 'Permohonan',
  },
  {
    id: '#MA-2023-084',
    name: 'Rina Maharani',
    initials: 'RM',
    color: '#8B5CF6',
    address: 'Blok C5 No. 12, Bekasi',
    status: 'Permohonan',
  },
  {
    id: '#MA-2022-010',
    name: 'Santoso Wibowo',
    initials: 'SW',
    color: '#10B981',
    address: 'Jl. Kebun Raya No. 3, Bogor',
    status: 'Aktif',
  },
  {
    id: '#MA-2022-025',
    name: 'Marlina Susanti',
    initials: 'MS',
    color: '#3B82F6',
    address: 'Jl. Ahmad Yani No. 77, Bandung',
    status: 'Aktif',
  },
  {
    id: '#MA-2023-050',
    name: 'Hendra Pratama',
    initials: 'HP',
    color: '#0EA5E9',
    address: 'Jl. Anggrek No. 12, Jakarta',
    status: 'Pasang Baru',
  },
  {
    id: '#MA-2023-063',
    name: 'Yulia Sari',
    initials: 'YS',
    color: '#6366F1',
    address: 'Jl. Pemuda No. 88, Surabaya',
    status: 'Pasang Baru',
  },
  {
    id: '#MA-2022-041',
    name: 'Farida Hanum',
    initials: 'FH',
    color: '#8B5CF6',
    address: 'Jl. Pahlawan No. 2, Yogyakarta',
    status: 'Aktif',
  },
  {
    id: '#MA-2022-055',
    name: 'Guntur Wicaksono',
    initials: 'GW',
    color: '#EF4444',
    address: 'Jl. Magelang KM 5, Yogyakarta',
    status: 'Aktif',
  },
  {
    id: '#MA-2023-112',
    name: 'Bambang Wijaya',
    initials: 'BW',
    color: '#10B981',
    address: 'Jl. Sudirman Gg. 3, Bandung',
    status: 'Permohonan',
  },
  {
    id: '#MA-2023-156',
    name: 'Siti Khadijah',
    initials: 'SK',
    color: '#F59E0B',
    address: 'Komp. Permata, Bekasi',
    status: 'Permohonan',
  },
  {
    id: '#MA-2023-077',
    name: 'Budi Santoso',
    initials: 'BS',
    color: '#14B8A6',
    address: 'Perum Griya Indah, Sidoarjo',
    status: 'Pasang Baru',
  },
  {
    id: '#MA-2022-068',
    name: 'Indah Permatasari',
    initials: 'IP',
    color: '#0EA5E9',
    address: 'Ruko Niaga No. 9, Solo',
    status: 'Aktif',
  },
])

const statusBadge = {
  Terdaftar: 'bg-emerald-50! text-emerald-600! border-emerald-100!',
  'Belum Terdaftar': 'bg-slate-50! text-slate-400! border-slate-100!',
}

const filteredCustomerOptions = computed(() => {
  let list = customerOptions.value
  if (activeFilterTab.value !== 'Semua') {
    list = list.filter((c) => c.status === activeFilterTab.value)
  }
  if (customerSearch.value.trim()) {
    const q = customerSearch.value.toLowerCase()
    list = list.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        c.id.toLowerCase().includes(q) ||
        c.address.toLowerCase().includes(q),
    )
  }
  return list
})

const hasCoordinates = computed(() => form.value.koordinat.trim().length > 3)

const isFormValid = computed(() => {
  return (
    selectedCustomer.value !== null &&
    form.value.tanggalOrder !== '' &&
    form.value.namaCater.trim() !== '' &&
    form.value.kodeInstalasi.trim() !== '' &&
    form.value.namaDesa.trim() !== ''
  )
})

const closeCustomerDropdown = () => {
  isCustomerDropdownOpen.value = false
  customerSearch.value = ''
}

const selectCustomer = (customer) => {
  selectedCustomer.value = customer
  isCustomerDropdownOpen.value = false
  customerSearch.value = customer.name
  form.value.kodeInstalasi = `INST-${customer.id.replace('#MA-', '').replace('-', '')}`
}

const clearCustomer = () => {
  selectedCustomer.value = null
  customerSearch.value = ''
  form.value.kodeInstalasi = ''
}

const getCurrentLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        form.value.koordinat = `${pos.coords.latitude.toFixed(6)}, ${pos.coords.longitude.toFixed(6)}`
      },
      () => {
        form.value.koordinat = '-7.797068, 110.370529'
      },
    )
  } else {
    form.value.koordinat = '-7.797068, 110.370529'
  }
}

const openMapPreview = () => {
  if (form.value.koordinat) {
    const [lat, lng] = form.value.koordinat.split(',').map((s) => s.trim())
    window.open(`https://maps.google.com/?q=${lat},${lng}`, '_blank')
  }
}

const handleNewCustomerRegistration = () => {
  console.log('Redirecting to new customer registration...')
  // Typically window.location.href = '/customer/register' or similar
  alert('Fitur Registrasi Pelanggan Baru akan segera hadir!')
}

const handleSubmit = () => {
  if (!isFormValid.value) return
  console.log('Submitting:', { customer: selectedCustomer.value, form: form.value })
  showSuccessToast.value = true
  setTimeout(() => {
    showSuccessToast.value = false
  }, 3500)
}

const handleKeydown = (e) => {
  if (e.key === 'Escape') closeCustomerDropdown()
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
