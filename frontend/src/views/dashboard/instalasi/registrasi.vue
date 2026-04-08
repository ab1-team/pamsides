<template>
  <div class="mb-2">
    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Register Instalasi</h1>
    <p class="text-sm text-slate-500 mt-1">
      Create a new service connection for validated customers.
    </p>
  </div>

  <div class="mb-2">
    <div class="relative" ref="customerSelectRef">
      <div
        @click="toggleCustomerDropdown"
        :class="[
          'flex items-center gap-3 w-full bg-white border rounded-xl px-4 py-3 cursor-pointer transition-all duration-200 shadow-sm',
          isCustomerDropdownOpen
            ? 'border-blue-400 ring-2 ring-blue-100 shadow-md'
            : 'border-slate-200 hover:border-slate-300',
        ]"
      >
        <font-awesome-icon icon="search" class="w-4 h-4 text-slate-400 flex-shrink-0" />
        <span v-if="!selectedCustomer" class="text-slate-400 text-sm flex-1"
          >Search by Name, NIK, or Customer ID...</span
        >
        <div v-else class="flex items-center gap-2 flex-1 min-w-0">
          <div
            class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold"
            :style="{ backgroundColor: selectedCustomer.color }"
          >
            {{ selectedCustomer.initials }}
          </div>
          <span class="text-sm font-semibold text-slate-800 truncate">{{
            selectedCustomer.name
          }}</span>
          <span class="text-xs text-slate-400 flex-shrink-0">· {{ selectedCustomer.id }}</span>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <button
            v-if="selectedCustomer"
            @click.stop="clearCustomer"
            class="w-5 h-5 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors"
          >
            <font-awesome-icon icon="times" class="w-3 h-3 text-slate-500" />
          </button>
          <div
            class="flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 rounded-lg border border-slate-200"
          >
            <font-awesome-icon icon="filter" class="w-3 h-3 text-slate-500" />
            <span class="text-xs font-semibold text-slate-500">FILTER</span>
          </div>
        </div>
      </div>

      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 translate-y-1 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-1 scale-95"
      >
        <div
          v-if="isCustomerDropdownOpen"
          class="absolute top-full left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 overflow-hidden"
        >
          <div class="p-3 border-b border-slate-100">
            <div class="relative">
              <font-awesome-icon
                icon="search"
                class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
              />
              <input
                ref="searchInputRef"
                v-model="customerSearch"
                type="text"
                placeholder="Ketik nama, NIK, atau ID customer..."
                class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400"
                @click.stop
              />
            </div>
          </div>

          <div class="flex gap-1 px-3 py-2 border-b border-slate-100">
            <button
              v-for="tab in ['Semua', 'Aktif', 'Permohonan', 'Pasang Baru']"
              :key="tab"
              @click.stop="activeFilterTab = tab"
              :class="[
                'px-2.5 py-1 rounded-full text-[11px] font-semibold transition-all',
                activeFilterTab === tab
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-100 text-slate-500 hover:bg-slate-200',
              ]"
            >
              {{ tab }}
            </button>
          </div>

          <div class="max-h-60 overflow-y-auto">
            <div v-if="filteredCustomerOptions.length === 0" class="py-8 text-center">
              <font-awesome-icon icon="frown" class="w-8 h-8 text-slate-300 mx-auto mb-2" />
              <p class="text-sm text-slate-400">Tidak ada customer ditemukan</p>
            </div>
            <button
              v-for="customer in filteredCustomerOptions"
              :key="customer.id"
              @click.stop="selectCustomer(customer)"
              class="w-full flex items-center gap-3 px-4 py-3 hover:bg-blue-50/60 transition-colors text-left group border-b border-slate-50 last:border-0"
            >
              <div
                class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-white text-sm font-bold shadow-sm"
                :style="{ backgroundColor: customer.color }"
              >
                {{ customer.initials }}
              </div>
              <div class="flex-1 min-w-0">
                <div
                  class="text-sm font-semibold text-slate-800 truncate group-hover:text-blue-700"
                >
                  {{ customer.name }}
                </div>
                <div class="flex items-center gap-2 mt-0.5">
                  <span class="text-xs text-slate-400 font-mono">{{ customer.id }}</span>
                  <span class="text-slate-200">·</span>
                  <span class="text-xs text-slate-400">{{ customer.address }}</span>
                </div>
              </div>
              <span
                :class="[
                  'text-[10px] font-bold px-2 py-0.5 rounded-full border flex-shrink-0',
                  statusBadge[customer.status],
                ]"
              >
                {{ customer.status }}
              </span>
            </button>
          </div>

          <div class="px-4 py-2 border-t border-slate-100 bg-slate-50/50">
            <p class="text-[11px] text-slate-400">
              {{ filteredCustomerOptions.length }} customer ditemukan
            </p>
          </div>
        </div>
      </Transition>
    </div>
  </div>

  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0 -translate-y-2"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-2"
  >
    <div
      v-if="selectedCustomer"
      class="mb-6 bg-gradient-to-r from-blue-600 to-cyan-600 rounded-xl p-4 text-white flex items-center gap-4 shadow-lg shadow-blue-200/50"
    >
      <div
        class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-lg border-2 border-white/30 flex-shrink-0"
      >
        {{ selectedCustomer.initials }}
      </div>
      <div class="flex-1 min-w-0">
        <div class="font-bold text-white text-base truncate">{{ selectedCustomer.name }}</div>
        <div class="text-blue-100 text-xs mt-0.5">
          {{ selectedCustomer.id }} · {{ selectedCustomer.address }}
        </div>
      </div>
      <span
        class="bg-emerald-400/20 text-emerald-100 border border-emerald-400/30 text-xs font-bold px-3 py-1 rounded-full flex-shrink-0"
      >
        ✓ {{ selectedCustomer.status }}
      </span>
    </div>
  </Transition>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
    <ContentCard variant="bordered" padding="normal" hoverable>
      <div class="flex items-center gap-2.5 px-5 py-4 border-b border-slate-100 bg-slate-50/50">
        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
          <font-awesome-icon icon="file-alt" class="w-4 h-4 text-blue-600" />
        </div>
        <h2 class="text-base font-bold text-slate-800">Service Details</h2>
      </div>

      <div class="p-5 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Tanggal Order</label>
          <input
            v-model="form.tanggalOrder"
            type="date"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Nama Cater</label>
          <input
            v-model="form.namaCater"
            type="text"
            placeholder="Enter surveyor name"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Kategori</label>
            <div class="relative">
              <select
                v-model="form.kategori"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all appearance-none pr-8"
              >
                <option value="Rumah Tangga">Rumah Tangga</option>
                <option value="Komersial">Komersial</option>
                <option value="Sosial">Sosial</option>
                <option value="Industri">Industri</option>
              </select>
              <font-awesome-icon
                icon="chevron-down"
                class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
              />
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Paket/Kelas</label>
            <div class="relative">
              <select
                v-model="form.paket"
                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all appearance-none pr-8"
              >
                <option value="Gold - A1">Gold - A1</option>
                <option value="Silver - B1">Silver - B1</option>
                <option value="Bronze - C1">Bronze - C1</option>
                <option value="Platinum - S1">Platinum - S1</option>
              </select>
              <font-awesome-icon
                icon="chevron-down"
                class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
              />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Kode Instalasi</label>
            <input
              v-model="form.kodeInstalasi"
              type="text"
              placeholder="INST-0000"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all font-mono"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Nominal (Rp)</label>
            <div class="relative">
              <span
                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-medium"
                >Rp</span
              >
              <input
                v-model="form.nominal"
                type="number"
                placeholder="0"
                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all"
              />
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5"
            >Catatan (opsional)</label
          >
          <textarea
            v-model="form.catatan"
            rows="3"
            placeholder="Tambahkan catatan instalasi..."
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 focus:bg-white transition-all resize-none"
          ></textarea>
        </div>
      </div>
    </ContentCard>

    <ContentCard variant="bordered" padding="normal" hoverable>
      <div class="flex items-center gap-2.5 px-5 py-4 border-b border-slate-100 bg-slate-50/50">
        <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
          <font-awesome-icon icon="map-marker-alt" class="w-4 h-4 text-cyan-600" />
        </div>
        <h2 class="text-base font-bold text-slate-800">Deployment Site</h2>
      </div>

      <div class="p-5 space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Nama Desa & Dusun</label>
          <input
            v-model="form.namaDesa"
            type="text"
            placeholder="e.g. Sukamaju, Dusun III"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-400 focus:bg-white transition-all"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Jalan</label>
          <input
            v-model="form.jalan"
            type="text"
            placeholder="Street name and number"
            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-400 focus:bg-white transition-all"
          />
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">RT/RW</label>
            <input
              v-model="form.rtrw"
              type="text"
              placeholder="00/00"
              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-400 focus:bg-white transition-all font-mono"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">Koordinat</label>
            <div class="relative">
              <input
                v-model="form.koordinat"
                type="text"
                placeholder="-6.123, 106.123"
                class="w-full px-3.5 py-2.5 pr-10 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-400 focus:bg-white transition-all font-mono"
              />
              <button
                @click="getCurrentLocation"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-cyan-500 hover:text-cyan-700 transition-colors"
                title="Gunakan lokasi saat ini"
              >
                <font-awesome-icon icon="map-marker-alt" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Preview Lokasi</label>
          <div
            class="relative w-full h-44 bg-gradient-to-br from-slate-100 to-slate-200 rounded-xl overflow-hidden border border-slate-200"
          >
            <div
              class="absolute inset-0"
              style="
                background-image:
                  linear-gradient(rgba(148, 163, 184, 0.15) 1px, transparent 1px),
                  linear-gradient(90deg, rgba(148, 163, 184, 0.15) 1px, transparent 1px);
                background-size: 20px 20px;
              "
            ></div>

            <div class="absolute inset-0 flex items-center justify-center">
              <div v-if="!hasCoordinates" class="text-center">
                <div
                  class="w-12 h-12 rounded-full bg-white/80 border-2 border-dashed border-slate-300 flex items-center justify-center mx-auto mb-2"
                >
                  <font-awesome-icon icon="map-marker-alt" class="w-5 h-5 text-slate-400" />
                </div>
                <p class="text-xs text-slate-400 font-medium">Masukkan koordinat untuk preview</p>
              </div>

              <div v-else class="relative">
                <div
                  class="w-8 h-8 rounded-full bg-blue-600 border-3 border-white shadow-lg flex items-center justify-center animate-bounce"
                >
                  <font-awesome-icon icon="map-marker-alt" class="w-4 h-4 text-white" />
                </div>
                <div class="absolute -inset-2 rounded-full bg-blue-400/20 animate-ping"></div>
              </div>
            </div>

            <div class="absolute bottom-3 left-1/2 -translate-x-1/2">
              <button
                @click="openMapPreview"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-white/90 backdrop-blur-sm border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-white shadow-sm transition-all hover:shadow-md"
              >
                <font-awesome-icon icon="eye" class="w-3 h-3 text-blue-500" />
                PREVIEW LOCATION
              </button>
            </div>

            <div
              v-if="hasCoordinates"
              class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm rounded-lg px-2 py-1 text-[10px] font-mono text-slate-600 border border-slate-200 shadow-sm"
            >
              {{ form.koordinat }}
            </div>
          </div>
        </div>
      </div>
    </ContentCard>
  </div>

  <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-2 text-xs text-slate-400">
      <div class="w-4 h-4 rounded-full bg-blue-100 flex items-center justify-center">
        <font-awesome-icon icon="info-circle" class="w-2.5 h-2.5 text-blue-500" />
      </div>
      Changes are saved as draft automatically
    </div>

    <div class="flex items-center gap-3">
      <button
        @click="handleCancel"
        class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm"
      >
        Cancel
      </button>
      <button
        @click="handleSubmit"
        :disabled="!isFormValid"
        :class="[
          'flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white rounded-xl transition-all shadow-md',
          isFormValid
            ? 'bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 shadow-blue-200 hover:shadow-lg hover:shadow-blue-200/50'
            : 'bg-slate-300 cursor-not-allowed shadow-none',
        ]"
      >
        Daftar & Simpan
        <font-awesome-icon icon="arrow-right" class="w-4 h-4" />
      </button>
    </div>
  </div>

  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0 translate-y-4"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-4"
  >
    <div
      v-if="showSuccessToast"
      class="fixed bottom-6 right-6 z-50 bg-emerald-600 text-white px-5 py-3.5 rounded-2xl shadow-xl flex items-center gap-3"
    >
      <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
        <font-awesome-icon icon="check" class="w-3.5 h-3.5" />
      </div>
      <div>
        <p class="text-sm font-bold">Instalasi berhasil didaftarkan!</p>
        <p class="text-xs text-emerald-100">Data telah disimpan ke sistem</p>
      </div>
    </div>
  </Transition>

  <div
    v-if="isCustomerDropdownOpen"
    class="fixed inset-0 z-40"
    @click="closeCustomerDropdown"
  ></div>
</template>

<script>
export default {
  name: 'InstalasiRegistrasi',
}
</script>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import ContentCard from '@/components/ui/ContentCard.vue'

const isCustomerDropdownOpen = ref(false)
const customerSearch = ref('')
const activeFilterTab = ref('Semua')
const selectedCustomer = ref(null)
const searchInputRef = ref(null)
const customerSelectRef = ref(null)

const form = ref({
  tanggalOrder: '',
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
  Aktif: 'bg-green-50 text-green-600 border-green-200',
  Permohonan: 'bg-blue-50 text-blue-600 border-blue-200',
  'Pasang Baru': 'bg-sky-50 text-sky-600 border-sky-200',
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

const toggleCustomerDropdown = () => {
  isCustomerDropdownOpen.value = !isCustomerDropdownOpen.value
  if (isCustomerDropdownOpen.value) {
    nextTick(() => searchInputRef.value?.focus())
  }
}

const closeCustomerDropdown = () => {
  isCustomerDropdownOpen.value = false
  customerSearch.value = ''
}

const selectCustomer = (customer) => {
  selectedCustomer.value = customer
  isCustomerDropdownOpen.value = false
  customerSearch.value = ''
  form.value.kodeInstalasi = `INST-${customer.id.replace('#MA-', '').replace('-', '')}`
}

const clearCustomer = () => {
  selectedCustomer.value = null
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

const handleCancel = () => {
  if (confirm('Batalkan registrasi? Data yang belum disimpan akan hilang.')) {
    selectedCustomer.value = null
    form.value = {
      tanggalOrder: '',
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
    }
  }
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
