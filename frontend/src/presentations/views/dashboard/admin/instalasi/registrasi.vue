<template>
  <div class="registrasi-root!">
    <div class="mb-2!">
      <h1 class="text-2xl! font-bold! text-slate-800! tracking-tight!">Register Instalasi</h1>
      <p class="text-sm! text-slate-500! mt-1!">
        Buat koneksi layanan baru untuk pelanggan yang telah divalidasi.
      </p>
    </div>

    <div class="mb-5!">
      <div class="relative!" ref="customerSelectRef">
        <ContentCard variant="bordered" padding="none" rounded="xl" class="overflow-hidden!">
          <div
            class="flex! items-center! gap-3! w-full! bg-white! rounded-xl! pl-4! py-0! transition-all! duration-200!"
            :class="[
              isCustomerDropdownOpen ?
                'border-blue-400! ring-2! ring-blue-100! shadow-xl! shadow-blue-500/10!'
                : 'border-slate-100! hover:border-slate-200!',
            ]">
            <font-awesome-icon icon="search" class="w-4! h-4! text-slate-400! shrink-0!" />

            <input v-model="customerSearch" type="text" placeholder="Search Nama, NIK.."
              class="flex-1! bg-transparent! border-none! text-sm! text-slate-700! placeholder-slate-400! focus:outline-none! py-4!"
              @focus="isCustomerDropdownOpen = true" @input="isCustomerDropdownOpen = true" />

            <div class="flex! items-center! gap-0! shrink-0! h-full!">
              <BaseButton v-if="selectedCustomer || customerSearch" variant="ghost" size="sm"
                class="w-10! h-10! p-0! rounded-full! mr-2!" @click.stop="clearCustomer"
                icon="times" />
              <BaseButton variant="primary"
                class="h-[52px]! px-4! sm:px-5! rounded-l-none! rounded-r-xl! border-none! bg-linear-to-r! from-indigo-600! via-blue-600! to-cyan-500! shadow-lg! shadow-indigo-500/20! hover:shadow-indigo-500/40! text-white! ring-offset-0! focus:ring-0!"
                icon="user-plus" @click.stop="handleNewCustomerRegistration">
                <span class="hidden! sm:inline-block! text-xs! font-bold!">Registrasi Pelanggan</span>
              </BaseButton>
            </div>
          </div>
        </ContentCard>

        <Transition enter-active-class="transition! duration-150! ease-out!"
          enter-from-class="opacity-0! translate-y-1! scale-95!"
          enter-to-class="opacity-100! translate-y-0! scale-100!"
          leave-active-class="transition! duration-100! ease-in!"
          leave-from-class="opacity-100! translate-y-0! scale-100!"
          leave-to-class="opacity-0! translate-y-1! scale-95!">
          <div v-if="isCustomerDropdownOpen"
            class="absolute! top-full! left-0! right-0! mt-2! bg-white! border! border-slate-200! rounded-xl! shadow-xl! z-50! overflow-hidden!">
            <div class="max-h-60! overflow-y-auto!">
              <div v-if="filteredCustomerOptions.length === 0" class="py-12! px-6! text-center!">
                <div
                  class="w-16! h-16! bg-slate-50! rounded-full! flex! items-center! justify-center! mx-auto! mb-4! border-2! border-dashed! border-slate-200!">
                  <font-awesome-icon icon="users" class="w-6! h-6! text-slate-300!" />
                </div>
                <p class="text-sm! font-bold! text-slate-600! mb-1!">No customers found</p>
                <p class="text-xs! text-slate-400! leading-relaxed!">
                  We couldn't find any customers matching your search query or filter.
                </p>
              </div>
              <BaseButton v-for="customer in filteredCustomerOptions" :key="customer.id"
                @click.stop="selectCustomer(customer)" variant="ghost"
                class="w-full! px-4! py-1.5! hover:bg-slate-50! transition-all! duration-200! text-left! border-b! border-slate-50! last:border-0! rounded-none! shadow-none!">
                <div class="flex! items-center! gap-3! w-full!">
                  <div
                    class="w-8! h-8! rounded-full! shrink-0! flex! items-center! justify-center! text-white! text-xs! font-extrabold! shadow-sm!"
                    :style="{ backgroundColor: '#3b82f6' }">
                    {{ customer.name ? customer.name.charAt(0).toUpperCase() : 'C' }}
                  </div>
                  <div class="flex-1! min-w-0!">
                    <div class="text-[13px]! font-bold! text-slate-700! truncate!">
                      {{ customer.name }}
                    </div>
                    <div class="text-[9px]! text-slate-400! font-mono! font-bold! leading-none!">
                      NIK: {{ customer.nik }}
                    </div>
                  </div>
                  <div :class="[
                      'text-[9px]! font-black! px-2! py-0.5! rounded-md! border! shrink-0! uppercase! tracking-wider!',
                      customer.status === 'Aktif'
                        ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                        : 'bg-slate-50 border-slate-200 text-slate-600',
                    ]">
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
      <Transition enter-active-class="transition! duration-300! ease-out!"
        enter-from-class="opacity-0! -translate-y-4!" enter-to-class="opacity-100! translate-y-0!">
        <div v-if="selectedCustomer"
          class="bg-linear-to-br! from-indigo-600! via-blue-600! to-cyan-500! p-4! sm:p-5! text-white! flex! items-center! gap-4! sm:gap-5! border-b! border-white/10!">
          <div
            class="w-10! h-10! sm:w-14! sm:h-14! rounded-full! bg-white/20! backdrop-blur-md! flex! items-center! justify-center! text-white! font-bold! text-base! sm:text-xl! border-2! border-white/40! shrink-0!">
            {{ selectedCustomer.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1! min-w-0!">
            <div class="font-bold! text-white! text-base! sm:text-lg! truncate! tracking-tight!">
              {{ selectedCustomer.name }}
            </div>
            <div
              class="flex! items-center! gap-2! text-blue-50! text-[10px]! sm:text-xs! mt-0.5! sm:mt-1! opacity-90!">
              <span class="font-mono! font-bold!">ID Tiket: {{ selectedCustomer.id }}</span>
              <span class="hidden! sm:inline!">·</span>
              <span class="truncate! hidden! sm:inline!">NIK: {{ selectedCustomer.nik }}</span>
            </div>
          </div>
          <div
            class="backdrop-blur-md! text-white! border! text-[9px]! sm:text-[10px]! font-bold! px-2! sm:px-3! py-1! sm:py-1.5! rounded-full! shrink-0! uppercase! tracking-wider! bg-white/20! border-white/30!">
            ✓ {{ selectedCustomer.status }}
          </div>
        </div>
      </Transition>

      <div class="grid! grid-cols-1! lg:grid-cols-2! gap-2! divide-y! lg:divide-y-0! lg:divide-x! divide-slate-100!">
        <div class="p-5! lg:p-6!">
          <div class="flex! items-center! gap-2.5! mb-5!">
            <div class="w-8! h-8! bg-blue-100! rounded-lg! flex! items-center! justify-center!">
              <font-awesome-icon icon="file-alt" class="w-4! h-4! text-blue-600!" />
            </div>
            <h2 class="text-base! font-bold! text-slate-800!">Detail Layanan</h2>
          </div>

          <AppDatePicker v-model="form.tanggalOrder" label="Tanggal Order" placeholder="Pilih tanggal order" />

          <div class="space-y-4!">
            <div class="grid! grid-cols-2! gap-3!">
              <BaseSelect v-model="form.user_id" label="Nama Cater">
                <option value="">Pilih Petugas Cater</option>
                <option v-for="user in caterUsers" :key="user.id" :value="user.id">
                  {{ user.name }} ({{ user.role }})
                </option>
              </BaseSelect>

              <BaseSelect v-model="form.package_id" label="Paket/Kelas">
                <option value="" disabled>Pilih Paket</option>
                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                  {{ pkg.name }}
                </option>
              </BaseSelect>
            </div>

            <Transition enter-active-class="transition! duration-200! ease-out!"
              enter-from-class="opacity-0! -translate-y-2!"
              enter-to-class="opacity-100! translate-y-0!">
              <div v-if="form.package_id && selectedPackageDetails" class="space-y-4!">

                <div
                  v-if="selectedPackageDetails && (selectedPackageDetails.water_tariff_blocks || selectedPackageDetails.tariffBlocks)"
                  class="bg-white! border! border-slate-200! rounded-xl! divide-y! divide-slate-100! shadow-xs! overflow-hidden!">
                  
                  <div class="px-3.5! py-2! grid! grid-cols-3! bg-slate-50/70! text-[10px]! font-bold! text-slate-400! uppercase! tracking-wider!">
                    <div>Nama</div>
                    <div class="text-center!">Volume</div>
                    <div class="text-right!">Harga / m³</div>
                  </div>

                  <div
                    v-for="(block, index) in (selectedPackageDetails.water_tariff_blocks || selectedPackageDetails.tariffBlocks)"
                    :key="block.id || index"
                    class="p-3.5! grid! grid-cols-3! items-center! gap-4! hover:bg-slate-50/50! transition-colors!">
                    
                    <div class="flex! items-center! gap-3!">
                      <div class="w-6! h-6! bg-blue-50! text-blue-600! rounded-full! flex! items-center! justify-center! text-[10px]! font-black!">
                        {{ index + 1 }}
                      </div>
                      <div class="text-[11px]! font-bold! text-slate-700! uppercase! tracking-tight!">
                        Blok {{ index + 1 }}
                      </div>
                    </div>

                    <div class="text-center! text-xs! font-bold! text-slate-600!">
                      {{ parseFloat(block.usage_min_m3).toFixed(2) }} -
                      {{ block.usage_max_m3 ? parseFloat(block.usage_max_m3).toFixed(2) : '∞' }}
                      <span class="text-[10px]! text-slate-400! font-normal!">m³</span>
                    </div>

                    <div class="text-right! text-xs! font-extrabold! text-blue-600!">
                      Rp {{ formatRupiah(block.price_per_m3) }}
                    </div>

                  </div>
                </div>

                <div class="space-y-3!">
                  <div class="grid! grid-cols-2! gap-3!">
                    <div class="bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-2.5! flex! justify-between! items-center! h-[46px]!">
                      <div class="text-[11px]! font-semibold! text-slate-500! uppercase! tracking-wider!">Pasang Baru</div>
                      <div class="text-sm! font-extrabold! text-blue-600!">
                        Rp {{ formatRupiah(form.nominal) }}
                      </div>
                    </div>

                    <div class="bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-2.5! flex! justify-between! items-center! h-[46px]!">
                      <div class="text-[11px]! font-semibold! text-slate-500! uppercase! tracking-wider!">Abodemen</div>
                      <div class="text-sm! font-extrabold! text-emerald-600!">
                        Rp {{ formatRupiah(selectedPackageDetails.monthly_abodemen) }}
                      </div>
                    </div>
                  </div>

                  <div class="grid! grid-cols-2! gap-3! items-center!">
                      <div class="bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-2.5! flex! justify-between! items-center! h-[46px]!">
                        <div class="text-[11px]! font-semibold! text-slate-500! uppercase! tracking-wider!">Denda</div>
                        <div class="text-sm! font-extrabold! text-rose-600!">
                          Rp {{ formatRupiah(selectedPackageDetails.late_penalty) }}
                        </div>
                      </div>

                      <div class="bg-slate-50! border! border-slate-200! rounded-xl! px-4! py-2.5! flex! justify-between! items-center! h-[46px]!">
                        <div class="text-[11px]! font-semibold! text-slate-500! uppercase! tracking-wider!">Nominal</div>
                        <div class="text-sm! font-extrabold! text-blue-600!">
                          Rp {{ formatRupiah(form.nominal) }}
                        </div>
                      </div>
                    </div>
                </div>

              </div>
            </Transition>
          </div>
        </div>

        <div class="p-5! lg:p-6!">
          <div class="flex! items-center! gap-2.5! mb-5!">
            <div class="w-8! h-8! bg-cyan-100! rounded-lg! flex! items-center! justify-center!">
              <font-awesome-icon icon="map-marker-alt" class="w-4! h-4! text-cyan-600!" />
            </div>
            <h2 class="text-base! font-bold! text-slate-800!">Lokasi Penyebaran</h2>
          </div>

          <BaseSelect v-model="form.village_id" label="Nama Desa" @change="handleVillageChange">
            <option value="" disabled>Pilih Desa</option>
            <option v-for="village in villageOptions" :key="village.id" :value="village.id">
              {{ village.village_name }}
            </option>
          </BaseSelect>

          <BaseInput v-model="form.jalan" label="Jalan" disabled placeholder="Alamat Penyebaran" class="bg-slate-50!" />

          <div class="grid! grid-cols-1! gap-3!">
            <BaseInput v-model="form.koordinat" label="Koordinat" placeholder="-6.123, 106.123" customClass="font-mono pr-10">
              <BaseButton variant="ghost" @click="getCurrentLocation"
                class="absolute! right-2! top-1/2! -translate-y-1/2! p-2! w-8! h-8! text-cyan-500! hover:text-cyan-700!"
                title="Gunakan lokasi saat ini" icon="map-marker-alt" />
            </BaseInput>
          </div>

          <div>
            <label class="block! text-xs! font-semibold! text-slate-500! mb-1.5!">Preview Lokasi</label>
            <div class="relative! w-full! h-44! bg-linear-to-br! from-slate-100! to-slate-200! rounded-xl! overflow-hidden! border! border-slate-200!">
              <div class="absolute! inset-0!" style="background-image: linear-gradient(rgba(148, 163, 184, 0.15) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.15) 1px, transparent 1px); background-size: 20px 20px;"></div>

              <div class="absolute! inset-0! flex! items-center! justify-center! pointer-events-none! z-0!">
                <div v-if="!hasCoordinates" class="text-center!">
                  <div class="w-12! h-12! rounded-full! bg-white/80! border-2! border-dashed! border-slate-300! flex! items-center! justify-center! mx-auto! mb-2!">
                    <font-awesome-icon icon="map-marker-alt" class="w-5! h-5! text-slate-400!" />
                  </div>
                  <p class="text-xs! text-slate-400! font-medium!">Masukkan koordinat untuk preview</p>
                </div>

                <div v-else class="relative!">
                  <div class="w-8! h-8! rounded-full! bg-blue-600! border-3! border-white! shadow-lg! flex! items-center! justify-center! animate-bounce!">
                    <font-awesome-icon icon="map-marker-alt" class="w-4! h-4! text-white!" />
                  </div>
                  <div class="absolute! -inset-2! rounded-full! bg-blue-400/20! animate-ping!"></div>
                </div>
              </div>

              <div class="absolute! bottom-3! left-1/2! -translate-x-1/2! z-30!">
                <button type="button" @click="openMapPreview" class="flex! items-center! justify-center! px-4! py-2! bg-white/90! hover:bg-white! text-slate-700! text-xs! font-bold! rounded-lg! border! border-slate-200! shadow-sm! hover:shadow-md! transition-all! cursor-pointer!">
                  <font-awesome-icon icon="eye" class="mr-2! text-blue-500!" />
                  PREVIEW LOCATION
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ContentCard>

    <div class="mt-8! sm:mt-10! flex! flex-col! sm:flex-row! items-center! justify-between! gap-5!">
      <div class="flex! items-center! gap-2! text-xs! text-slate-400! opacity-80!">
        <div class="w-4! h-4! rounded-full! bg-blue-50! flex! items-center! justify-center! shrink-0!">
          <font-awesome-icon icon="info-circle" class="w-2.5! h-2.5! text-blue-500!" />
        </div>
        Pastikan semua data terisi dengan benar sebelum menyimpan transaksi.
      </div>

      <div class="w-full! sm:w-auto!">
        <BaseButton variant="primary-gradient" class="w-full! sm:w-auto! px-10! py-3.5! rounded-xl! shadow-lg! text-sm! font-bold! uppercase! tracking-wide!" @click="handleSubmit" :disabled="!isFormValid">
          Daftar & Simpan
          <font-awesome-icon icon="check-circle" class="ml-2!" />
        </BaseButton>
      </div>
    </div>

    <Transition enter-active-class="transition! duration-300! ease-out!" enter-from-class="opacity-0! translate-y-4!" enter-to-class="opacity-100! translate-y-0!">
      <div v-if="showSuccessToast" class="fixed! bottom-6! right-6! z-50! bg-emerald-600! text-white! px-5! py-3.5! rounded-2xl! shadow-xl! flex! items-center! gap-3!">
        <div class="w-6! h-6! rounded-full! bg-white/20! flex! items-center! justify-center! shrink-0!">
          <font-awesome-icon icon="check" class="w-3.5! h-3.5!" />
        </div>
        <div>
          <p class="text-sm! font-bold!">Instalasi berhasil didaftarkan!</p>
          <p class="text-xs! text-emerald-100!">Status berubah menjadi pending</p>
        </div>
      </div>
    </Transition>

    <div v-if="isCustomerDropdownOpen" class="fixed! inset-0! z-40!" @click="isCustomerDropdownOpen = false"></div>
  </div>
</template>

<script setup>
  import api from '@/utils/axios'
  import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import ContentCard from '@/presentations/components/ui/ContentCard.vue'
  import BaseButton from '@/presentations/components/ui/BaseButton.vue'
  import BaseInput from '@/presentations/components/ui/BaseInput.vue'
  import BaseSelect from '@/presentations/components/ui/BaseSelect.vue'
  import AppDatePicker from '@/presentations/components/AppDatePicker.vue'
  import MaksMoneyInput from '@/presentations/components/MaksMoneyInput.vue'
  import ticketService from '@/services/ticket.service.js'

  const isCustomerDropdownOpen = ref(false)
  const customerSearch = ref('')
  const selectedCustomer = ref(null)
  const villageOptions = ref([])
  const router = useRouter()

  const form = ref({
    tanggalOrder: new Date(),
    user_id: '',
    package_id: '',
    nominal: 0,
    village_id: '',
    namaDesa: '',
    jalan: '',
    koordinat: '',
    lat: '',
    lng: ''
  })

  const showSuccessToast = ref(false)
  const customerOptions = ref([])
  const packages = ref([])
  const caterUsers = ref([])

  // 1. FETCH CUSTOMERS 
  const fetchCustomers = async () => {
    try {
      const res = await ticketService.getTickets()
      const list = res.data?.data || res.data || []
      const grouped = {}

      list.forEach(item => {
        const nik = item.nik
        if (!grouped[nik]) {
          grouped[nik] = {
            nik: nik,
            name: item.applicant_name || '',
            phone: item.phone || '',
            gender: item.gender || '',
            birth_place: item.birth_place || '',
            birth_date: item.birth_date || '',
            tickets: []
          }
        }

        if (!grouped[nik].phone && item.phone) grouped[nik].phone = item.phone
        if (!grouped[nik].gender && item.gender) grouped[nik].gender = item.gender
        if (!grouped[nik].birth_place && item.birth_place) grouped[nik].birth_place = item.birth_place
        if (!grouped[nik].birth_date && item.birth_date) grouped[nik].birth_date = item.birth_date

        grouped[nik].tickets.push({
          id: item.id,
          village_id: item.village_id || '',
          village_name: item.village?.village_name || '',
          village_address: item.village?.address || '',
          lat: item.lat || '',
          lng: item.lng || '',
          package_id: item.package_id || '',
          package: item.package || null,
          user_id: item.user_id || '',
          order_date: item.order_date || '',
          nominal: item.package?.installation_fee || 0,
          status: item.status || 'draft'
        })
      })

      customerOptions.value = Object.values(grouped)
    } catch (err) {
      console.error('Gagal ambil customer:', err)
    }
  }

  // 2. FETCH DATA PAKET
  const fetchPackages = async () => {
    try {
      const res = await api.get('/installation-packages')
      packages.value = res.data?.data || res.data || []
    } catch (err) {
      console.error('Gagal mengambil paket data:', err)
    }
  }

  // 3. FETCH USERS 
  const fetchCaterUsers = async () => {
    try {
      const res = await api.get('/users?role=teknisi')
      caterUsers.value = res.data?.data || res.data || []
    } catch (err) {
      console.error('Gagal memuat pengguna cater:', err)
      caterUsers.value = []
    }
  }

  // 4. FETCH VILLAGES
  const fetchVillages = async () => {
    try {
      const res = await api.get('/villages')
      villageOptions.value = res.data?.data || res.data || []
    } catch (err) {
      console.error('Gagal mengambil data desa dari database:', err)
    }
  }

  // FILTER SEARCH CUSTOMER
  const filteredCustomerOptions = computed(() => {
    if (!customerSearch.value) return customerOptions.value
    const q = customerSearch.value.toLowerCase()
    return customerOptions.value.filter((c) => {
      return c.name.toLowerCase().includes(q) || c.nik.toLowerCase().includes(q)
    })
  })

  // VALIDASI TAMPILAN MAP PREVIEW
  const hasCoordinates = computed(() => {
    const lat = parseFloat(form.value.lat)
    const lng = parseFloat(form.value.lng)
    return (!isNaN(lat) && !isNaN(lng) && !(lat === 0 && lng === 0))
  })

  // KETIKA PILIH CUSTOMER
  const selectCustomer = (customer) => {
    selectedCustomer.value = customer
    customerSearch.value = customer.name
    isCustomerDropdownOpen.value = false

    const lastTicket = customer.tickets[customer.tickets.length - 1]
    if (!lastTicket) return

    form.value.village_id = lastTicket.village_id
    form.value.namaDesa = lastTicket.village_name
    form.value.jalan = lastTicket.village_address

    if (lastTicket.lat && lastTicket.lng && !(lastTicket.lat == 0 && lastTicket.lng == 0)) {
      form.value.lat = lastTicket.lat
      form.value.lng = lastTicket.lng
      form.value.koordinat = `${lastTicket.lat}, ${lastTicket.lng}`
    } else {
      form.value.lat = ''
      form.value.lng = ''
      form.value.koordinat = ''
    }

    form.value.package_id = ''
    
        const userExist = caterUsers.value.find(u => u.id == lastTicket.user_id)

        form.value.user_id = userExist ? lastTicket.user_id : ''
    form.value.tanggalOrder = lastTicket.order_date ? new Date(lastTicket.order_date) : new Date()

    if (lastTicket.package && lastTicket.package.tariff_blocks) {
      const existingPkg = packages.value.find(p => p.id == lastTicket.package_id)
      if (existingPkg) {
        existingPkg.tariffBlocks = lastTicket.package.tariff_blocks
      }
    }
  }

  const clearCustomer = () => {
    selectedCustomer.value = null
    customerSearch.value = ''
    form.value.tanggalOrder = new Date()
    form.value.user_id = ''
    form.value.package_id = ''
    form.value.nominal = 0
    form.value.village_id = ''
    form.value.namaDesa = ''
    form.value.jalan = ''
  }

  // WATCHER PAKET
  watch(() => form.value.package_id, (newPackageId) => {
    const selectedPkg = packages.value.find(p => p.id == newPackageId)
    if (selectedPkg) {
      form.value.nominal = selectedPkg.installation_fee
      if ((!selectedPkg.tariffBlocks || selectedPkg.tariffBlocks.length === 0) && selectedCustomer.value) {
        const matchedTicket = selectedCustomer.value.tickets.find(t => t.package_id == newPackageId)
        if (matchedTicket && matchedTicket.package && matchedTicket.package.tariff_blocks) {
          selectedPkg.tariffBlocks = matchedTicket.package.tariff_blocks
        }
      }
    } else {
      form.value.nominal = 0
    }
  })

  watch(() => form.value.koordinat, (val) => {
    if (!val) return
    const parts = val.split(',')
    if (parts.length === 2) {
      const lat = parseFloat(parts[0].trim())
      const lng = parseFloat(parts[1].trim())
      if (!isNaN(lat) && !isNaN(lng)) {
        form.value.lat = lat
        form.value.lng = lng
      }
    }
  }, { immediate: true })

  const handleVillageChange = () => {
    const selectedVillage = villageOptions.value.find(v => v.id == form.value.village_id)
    if (selectedVillage) {
      form.value.namaDesa = selectedVillage.village_name
      form.value.jalan = selectedVillage.address
    } else {
      form.value.namaDesa = ''
      form.value.jalan = ''
    }
  }

  const getCurrentLocation = () => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          form.value.lat = pos.coords.latitude.toFixed(6)
          form.value.lng = pos.coords.longitude.toFixed(6)
          form.value.koordinat = `${form.value.lat}, ${form.value.lng}`
        },
        () => {
          form.value.lat = '-6.123000'
          form.value.lng = '106.123000'
          form.value.koordinat = '-6.123000, 106.123000'
        }
      )
    }
  }

  const openMapPreview = () => {
    let lat = form.value.lat
    let lng = form.value.lng
    if ((!lat || !lng) && form.value.koordinat) {
      const parts = form.value.koordinat.split(',')
      if (parts.length === 2) {
        lat = parseFloat(parts[0].trim())
        lng = parseFloat(parts[1].trim())
      }
    }
    if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
      alert('Koordinat belum diisi atau tidak valid')
      return
    }
    window.open(`https://www.google.com/maps/search/?api=1&query=${lat},${lng}`, '_blank')
  }

  const isFormValid = computed(() => {
    return (
      selectedCustomer.value !== null &&
      form.value.package_id !== '' &&
      form.value.user_id !== '' &&
      form.value.tanggalOrder !== null &&
      form.value.tanggalOrder !== '' &&
      hasCoordinates.value
    )
  })

  const handleSubmit = async () => {
    if (!isFormValid.value) return
    try {
      const targetDate = new Date(form.value.tanggalOrder)
      const yyyy = targetDate.getFullYear()
      const mm = String(targetDate.getMonth() + 1).padStart(2, '0')
      const dd = String(targetDate.getDate()).padStart(2, '0')
      const formattedDate = `${yyyy}-${mm}-${dd}`

      const payload = {
        package_id: form.value.package_id,
        user_id: form.value.user_id,
        order_date: formattedDate,
        village_id: form.value.village_id,
        lat: parseFloat(form.value.lat),
        lng: parseFloat(form.value.lng)
      }

      const lastTicket = selectedCustomer.value.tickets[selectedCustomer.value.tickets.length - 1]
      await api.put(`/installation-tickets/${lastTicket.id}/register`, payload)

      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
        clearCustomer()
      }, 2000)
    } catch (err) {
      console.error('Gagal mengirim registrasi instalasi:', err.response?.data || err)
    }
  }

  const handleNewCustomerRegistration = () => {
    router.push('/master-instalasi/register-instalasi')
  }

  const selectedPackageDetails = computed(() => {
    if (!form.value.package_id) return null
    return packages.value.find(p => p.id == form.value.package_id) || null
  })

  const formatRupiah = (angka) => {
    if (!angka) return '0'
    const val = Math.floor(parseFloat(angka))
    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")
  }

  const handleKeydown = (e) => {
    if (e.key === 'Escape') isCustomerDropdownOpen.value = false
  }

  onMounted(() => {
    fetchCustomers()
    fetchPackages()
    fetchCaterUsers()
    fetchVillages()
    document.addEventListener('keydown', handleKeydown)
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
  })
</script>