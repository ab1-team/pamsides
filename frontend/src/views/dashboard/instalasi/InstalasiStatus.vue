<template>
  <div class="w-full">
    <ContentCard variant="default" padding="none" hoverable>
      <div class="hidden lg:flex h-full">
        <ContentCard
          variant="minimal"
          padding="normal"
          class="w-56 xl:w-64 flex-shrink-0 border-r border-slate-100"
        >
          <div class="flex flex-col gap-1">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">
              Filter Status
            </p>
            <button
              v-for="menu in menuList"
              :key="menu.key"
              @click="activeStatus = menu.key"
              :class="[
                'w-full flex items-center justify-between px-3 py-2.5 rounded-full text-sm font-medium transition-all duration-200',
                activeStatus === menu.key
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-200'
                  : 'text-slate-600 hover:bg-white hover:shadow-sm',
              ]"
            >
              <div class="flex items-center gap-2.5">
                <span class="text-base">{{ menu.icon }}</span>
                <span>{{ menu.label }}</span>
              </div>
              <span
                :class="[
                  'text-xs font-bold px-2 py-0.5 rounded-full min-w-[22px] text-center',
                  activeStatus === menu.key
                    ? 'bg-white/25 text-white'
                    : 'bg-slate-200 text-slate-500',
                ]"
                >{{ dataMap[menu.key].length }}</span
              >
            </button>
          </div>
        </ContentCard>

        <div class="flex-1 min-w-0">
          <DataTable
            :data="paginatedData"
            :columns="tableColumns"
            title=""
            :current-page="currentPage"
            :per-page="perPage"
            :total-pages="totalPages"
            :visible-pages="visiblePages"
            :total-entries="filteredData.length"
            :show-toolbar="true"
            :search-query="searchQuery"
            search-placeholder="Find customer..."
            v-model:search-query="searchQuery"
            @prev-page="prevPage"
            @next-page="nextPage"
            @go-to-page="goToPage"
            :no-card="true"
          >
            <template #toolbar-actions>
              <h2 class="text-base font-bold text-slate-800">
                {{ activeLabel }}
                <span class="text-slate-400 font-normal text-sm">· Data Table</span>
              </h2>
              <button
                @click="printData"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-xs font-medium shadow-sm"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                  />
                </svg>
                Cetak
              </button>
            </template>

            <template #column-id="{ row }">
              <span class="text-xs font-bold text-blue-600 font-mono">{{ row.id }}</span>
            </template>
            <template #column-name="{ row }">
              <div class="flex items-center gap-2.5">
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                  :style="{ backgroundColor: row.color }"
                >
                  {{ row.initials }}
                </div>
                <div>
                  <p class="text-sm font-semibold text-slate-800 leading-tight">{{ row.name }}</p>
                  <p class="text-xs text-slate-400">{{ row.type }}</p>
                </div>
              </div>
            </template>
            <template #column-address="{ row }">
              <p class="text-sm text-slate-500 max-w-[200px] truncate">{{ row.address }}</p>
            </template>
            <template #column-status="{ row }">
              <span
                class="text-xs font-semibold px-2.5 py-1 rounded-full border"
                :class="statusStyle[row.status]"
                >{{ row.status }}</span
              >
            </template>
          </DataTable>
        </div>
      </div>

      <div class="lg:hidden flex flex-col">
        <div class="px-4 pt-4 pb-3 border-b border-slate-100 bg-white">
          <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">
            Filter Status
          </p>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="menu in menuList"
              :key="menu.key"
              @click="activeStatus = menu.key"
              :class="[
                'flex items-center justify-between px-3 py-2 rounded-full text-xs font-semibold transition-all duration-200',
                activeStatus === menu.key
                  ? 'bg-blue-600 text-white shadow-md shadow-blue-200'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200',
              ]"
            >
              <div class="flex items-center gap-1.5">
                <span class="text-sm leading-none">{{ menu.icon }}</span>
                <span class="truncate">{{ menu.label }}</span>
              </div>
              <span
                :class="[
                  'text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center flex-shrink-0',
                  activeStatus === menu.key
                    ? 'bg-white/25 text-white'
                    : 'bg-slate-300 text-slate-600',
                ]"
                >{{ dataMap[menu.key].length }}</span
              >
            </button>
          </div>
        </div>

        <div class="px-4 py-3 border-b border-slate-100 bg-white">
          <div class="flex items-center justify-between mb-2.5">
            <h2 class="text-sm font-bold text-slate-800">{{ activeLabel }}</h2>
            <button
              @click="printData"
              class="flex items-center gap-1.5 px-2.5 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                />
              </svg>
              Cetak
            </button>
          </div>
          <div class="relative">
            <svg
              class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari customer..."
              class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all"
            />
          </div>
        </div>

        <div class="divide-y divide-slate-50 bg-white">
          <div
            v-for="row in paginatedData"
            :key="row.id"
            class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50/80 transition-colors"
          >
            <div
              class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold flex-shrink-0 shadow-sm"
              :style="{ backgroundColor: row.color }"
            >
              {{ row.initials }}
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-800 truncate">{{ row.name }}</p>
                  <p class="text-xs text-slate-400 truncate">{{ row.type }}</p>
                </div>
                <span
                  class="flex-shrink-0 text-[11px] font-semibold px-2 py-0.5 rounded-full border"
                  :class="statusStyle[row.status]"
                  >{{ row.status }}</span
                >
              </div>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-[11px] font-mono font-bold text-blue-500">{{ row.id }}</span>
                <span class="text-slate-300">·</span>
                <p class="text-xs text-slate-400 truncate flex-1">{{ row.address }}</p>
              </div>
            </div>
          </div>

          <div v-if="paginatedData.length === 0" class="py-12 text-center">
            <p class="text-slate-400 text-sm">Tidak ada data ditemukan</p>
          </div>
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 bg-white">
          <p class="text-xs text-slate-500">
            <span class="font-semibold text-slate-700">{{ filteredData.length }}</span> entries
          </p>
          <div class="flex items-center gap-1.5">
            <button
              @click="prevPage"
              :disabled="currentPage === 1"
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center text-sm transition-all',
                currentPage === 1
                  ? 'text-slate-300 bg-slate-50'
                  : 'text-slate-600 bg-slate-100 hover:bg-slate-200',
              ]"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>

            <button
              v-for="page in visiblePages"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center text-xs font-semibold transition-all',
                currentPage === page
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-slate-600 bg-slate-100 hover:bg-slate-200',
              ]"
            >
              {{ page }}
            </button>

            <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              :class="[
                'w-8 h-8 rounded-lg flex items-center justify-center text-sm transition-all',
                currentPage === totalPages
                  ? 'text-slate-300 bg-slate-50'
                  : 'text-slate-600 bg-slate-100 hover:bg-slate-200',
              ]"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </ContentCard>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import ContentCard from '@/components/ui/ContentCard.vue'

const activeStatus = ref('permohonan')
const currentPage = ref(1)
const perPage = 10
const searchQuery = ref('')

const menuList = [
  { key: 'permohonan', label: 'Permohonan', icon: '📋' },
  { key: 'pasang_baru', label: 'Pasang Baru', icon: '🔵' },
  { key: 'aktif', label: 'Aktif', icon: '🟢' },
  { key: 'blokir', label: 'Blokir', icon: '🟠' },
  { key: 'cabut', label: 'Cabut', icon: '🔴' },
]

const activeLabel = computed(() => {
  return menuList.find((m) => m.key === activeStatus.value)?.label || ''
})

const statusStyle = {
  Permohonan: 'bg-blue-50 text-blue-600 border-blue-200',
  'Pasang Baru': 'bg-sky-50 text-sky-600 border-sky-200',
  Aktif: 'bg-green-50 text-green-600 border-green-200',
  Blokir: 'bg-orange-50 text-orange-600 border-orange-200',
  Cabut: 'bg-red-50 text-red-600 border-red-200',
}

const tableColumns = [
  { key: 'id', title: 'Customer ID', tdClass: '' },
  { key: 'name', title: 'Full Name', tdClass: '' },
  { key: 'address', title: 'Address', tdClass: '' },
  { key: 'status', title: 'Status', tdClass: '' },
]

const dataMap = ref({
  permohonan: [
    {
      id: '#MA-2023-001',
      name: 'Adi Saputra',
      initials: 'AS',
      color: '#3B82F6',
      type: 'Residential Type A',
      address: 'Jl. Merdeka No. 45, Kebon Jeruk, Jakarta',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-084',
      name: 'Rina Maharani',
      initials: 'RM',
      color: '#8B5CF6',
      type: 'Commercial Type B',
      address: 'Blok C5 No. 12, Citra Indah, Bekasi',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-112',
      name: 'Bambang Wijaya',
      initials: 'BW',
      color: '#10B981',
      type: 'Residential Type A',
      address: 'Jl. Sudirman Gg. 3, Sukajadi, Bandung',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-156',
      name: 'Siti Khadijah',
      initials: 'SK',
      color: '#F59E0B',
      type: 'Social Institution',
      address: 'Komp. Permata, Jatiasih, Bekasi',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-178',
      name: 'Dedi Kurniawan',
      initials: 'DK',
      color: '#EF4444',
      type: 'Residential Type B',
      address: 'Jl. Pahlawan No. 7, Depok',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-190',
      name: 'Lestari Putri',
      initials: 'LP',
      color: '#6366F1',
      type: 'Commercial Type A',
      address: 'Ruko Niaga Blok B2, Tangerang',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-204',
      name: 'Agus Santoso',
      initials: 'AG',
      color: '#14B8A6',
      type: 'Residential Type A',
      address: 'Jl. Kebon Raya No. 3, Bogor',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-215',
      name: 'Fitriani',
      initials: 'FT',
      color: '#EC4899',
      type: 'Social Institution',
      address: 'Jl. Mawar Raya No. 11, Tangerang Selatan',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-230',
      name: 'Hendra Gunawan',
      initials: 'HG',
      color: '#F97316',
      type: 'Commercial Type B',
      address: 'Jl. Gatot Subroto No. 88, Jakarta',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-245',
      name: 'Wulandari',
      initials: 'WL',
      color: '#0EA5E9',
      type: 'Residential Type C',
      address: 'Perum Griya Asri Blok D5, Bekasi',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-260',
      name: 'Rudi Hartono',
      initials: 'RH',
      color: '#84CC16',
      type: 'Residential Type A',
      address: 'Jl. Anggrek No. 22, Cibinong',
      status: 'Permohonan',
    },
    {
      id: '#MA-2023-271',
      name: 'Novi Rahayu',
      initials: 'NR',
      color: '#A855F7',
      type: 'Commercial Type A',
      address: 'Jl. Raya Bogor KM 32, Depok',
      status: 'Permohonan',
    },
  ],
  pasang_baru: [
    {
      id: '#MA-2023-050',
      name: 'Hendra Pratama',
      initials: 'HP',
      color: '#0EA5E9',
      type: 'Residential Type B',
      address: 'Jl. Anggrek No. 12, Menteng, Jakarta',
      status: 'Pasang Baru',
    },
    {
      id: '#MA-2023-063',
      name: 'Yulia Sari',
      initials: 'YS',
      color: '#6366F1',
      type: 'Commercial Type A',
      address: 'Jl. Pemuda No. 88, Surabaya',
      status: 'Pasang Baru',
    },
    {
      id: '#MA-2023-077',
      name: 'Budi Santoso',
      initials: 'BS',
      color: '#14B8A6',
      type: 'Residential Type A',
      address: 'Perum Griya Indah Blok A3, Sidoarjo',
      status: 'Pasang Baru',
    },
    {
      id: '#MA-2023-091',
      name: 'Dewi Anggraini',
      initials: 'DA',
      color: '#EC4899',
      type: 'Social Institution',
      address: 'Jl. Diponegoro No. 5, Malang',
      status: 'Pasang Baru',
    },
  ],
  aktif: [
    {
      id: '#MA-2022-010',
      name: 'Santoso Wibowo',
      initials: 'SW',
      color: '#10B981',
      type: 'Residential Type A',
      address: 'Jl. Kebun Raya No. 3, Bogor',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-025',
      name: 'Marlina Susanti',
      initials: 'MS',
      color: '#3B82F6',
      type: 'Commercial Type B',
      address: 'Jl. Ahmad Yani No. 77, Bandung',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-038',
      name: 'Eko Prasetyo',
      initials: 'EP',
      color: '#F59E0B',
      type: 'Residential Type C',
      address: 'Komp. Bumi Asri No. 14, Semarang',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-041',
      name: 'Farida Hanum',
      initials: 'FH',
      color: '#8B5CF6',
      type: 'Social Institution',
      address: 'Jl. Pahlawan No. 2, Yogyakarta',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-055',
      name: 'Guntur Wicaksono',
      initials: 'GW',
      color: '#EF4444',
      type: 'Residential Type A',
      address: 'Jl. Magelang KM 5, Yogyakarta',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-068',
      name: 'Indah Permatasari',
      initials: 'IP',
      color: '#0EA5E9',
      type: 'Commercial Type A',
      address: 'Ruko Niaga No. 9, Solo',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-079',
      name: 'Joko Widodo',
      initials: 'JW',
      color: '#14B8A6',
      type: 'Residential Type B',
      address: 'Jl. Slamet Riyadi No. 45, Solo',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-085',
      name: 'Kartini Dewi',
      initials: 'KD',
      color: '#EC4899',
      type: 'Residential Type A',
      address: 'Perum Griya Sejahtera Blok C1, Klaten',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-099',
      name: 'Lukman Hakim',
      initials: 'LH',
      color: '#F97316',
      type: 'Commercial Type B',
      address: 'Jl. Veteran No. 33, Purwokerto',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-110',
      name: 'Mega Wati',
      initials: 'MW',
      color: '#84CC16',
      type: 'Social Institution',
      address: 'Jl. Sudirman No. 17, Cilacap',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-124',
      name: 'Nanang Kosim',
      initials: 'NK',
      color: '#A855F7',
      type: 'Residential Type C',
      address: 'Jl. Diponegoro No. 8, Tegal',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-136',
      name: 'Oktavia Rini',
      initials: 'OR',
      color: '#6366F1',
      type: 'Residential Type A',
      address: 'Komp. Graha Indah No. 3, Pekalongan',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-148',
      name: 'Purnomo Aji',
      initials: 'PA',
      color: '#3B82F6',
      type: 'Commercial Type A',
      address: 'Jl. Ahmad Dahlan No. 12, Magelang',
      status: 'Aktif',
    },
    {
      id: '#MA-2022-159',
      name: 'Qorina Salsabila',
      initials: 'QS',
      color: '#10B981',
      type: 'Residential Type B',
      address: 'Jl. Tentara Pelajar No. 6, Salatiga',
      status: 'Aktif',
    },
  ],
  blokir: [
    {
      id: '#MA-2021-033',
      name: 'Rahmat Hidayat',
      initials: 'RH',
      color: '#F97316',
      type: 'Residential Type B',
      address: 'Jl. Cempaka No. 8, Medan',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-047',
      name: 'Sari Indrawati',
      initials: 'SI',
      color: '#EF4444',
      type: 'Commercial Type A',
      address: 'Jl. Gatot Subroto No. 21, Medan',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-062',
      name: 'Teguh Prasetya',
      initials: 'TP',
      color: '#8B5CF6',
      type: 'Residential Type A',
      address: 'Komp. Griya Medan Blok E7',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-075',
      name: 'Umar Harun',
      initials: 'UH',
      color: '#0EA5E9',
      type: 'Social Institution',
      address: 'Jl. Iskandar Muda No. 44, Medan',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-088',
      name: 'Vera Yulianti',
      initials: 'VY',
      color: '#14B8A6',
      type: 'Commercial Type B',
      address: 'Ruko Setia Budi Blok A1, Medan',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-101',
      name: 'Wahyu Nugroho',
      initials: 'WN',
      color: '#EC4899',
      type: 'Residential Type C',
      address: 'Jl. SM Raja No. 99, Medan',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-113',
      name: 'Xaverius Lim',
      initials: 'XL',
      color: '#F59E0B',
      type: 'Commercial Type A',
      address: 'Jl. Pemuda No. 5, Pematangsiantar',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-126',
      name: 'Yeni Kurnia',
      initials: 'YK',
      color: '#84CC16',
      type: 'Residential Type A',
      address: 'Jl. Adam Malik No. 17, Binjai',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-140',
      name: 'Zainal Abidin',
      initials: 'ZA',
      color: '#A855F7',
      type: 'Residential Type B',
      address: 'Komp. Cemara Asri Blok B3, Deli Serdang',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-152',
      name: 'Aminah Siregar',
      initials: 'AS',
      color: '#6366F1',
      type: 'Social Institution',
      address: 'Jl. Sisingamangaraja No. 32, Tebing Tinggi',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-164',
      name: 'Baharuddin',
      initials: 'BD',
      color: '#3B82F6',
      type: 'Residential Type A',
      address: 'Jl. Sudirman No. 8, Rantauprapat',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-177',
      name: 'Chairani Nasution',
      initials: 'CN',
      color: '#10B981',
      type: 'Commercial Type B',
      address: 'Jl. Merdeka No. 14, Padangsidimpuan',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-189',
      name: 'Dahlia Lubis',
      initials: 'DL',
      color: '#F97316',
      type: 'Residential Type C',
      address: 'Jl. Imam Bonjol No. 23, Kisaran',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-200',
      name: 'Efendi Pasaribu',
      initials: 'EP',
      color: '#EF4444',
      type: 'Commercial Type A',
      address: 'Komp. Bumi Asahan No. 5, Asahan',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-212',
      name: 'Fauzi Harahap',
      initials: 'FH',
      color: '#8B5CF6',
      type: 'Residential Type A',
      address: 'Jl. Ahmad Yani No. 7, Tanjung Balai',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-225',
      name: 'Ginta Manurung',
      initials: 'GM',
      color: '#0EA5E9',
      type: 'Social Institution',
      address: 'Jl. Sutomo No. 19, Sibolga',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-238',
      name: 'Hamidah Ritonga',
      initials: 'HR',
      color: '#14B8A6',
      type: 'Residential Type B',
      address: 'Jl. Sibolga Raya No. 3, Tapanuli Tengah',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-250',
      name: 'Irfan Batubara',
      initials: 'IB',
      color: '#EC4899',
      type: 'Commercial Type B',
      address: 'Komp. Graha Niaga Blok C2, Langkat',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-263',
      name: 'Juliani Panjaitan',
      initials: 'JP',
      color: '#F59E0B',
      type: 'Residential Type A',
      address: 'Jl. Proklamasi No. 11, Stabat',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-276',
      name: 'Kartolo Simanjuntak',
      initials: 'KS',
      color: '#84CC16',
      type: 'Commercial Type A',
      address: 'Jl. Veteran No. 44, Lubuk Pakam',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-289',
      name: 'Lasma Simbolon',
      initials: 'LS',
      color: '#A855F7',
      type: 'Residential Type C',
      address: 'Jl. Diponegoro No. 6, Kabanjahe',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-302',
      name: 'Maulana Rangkuti',
      initials: 'MR',
      color: '#6366F1',
      type: 'Social Institution',
      address: 'Jl. Pahlawan No. 9, Balige',
      status: 'Blokir',
    },
    {
      id: '#MA-2021-315',
      name: 'Nurhayati Ginting',
      initials: 'NG',
      color: '#3B82F6',
      type: 'Residential Type A',
      address: 'Jl. Merdeka No. 27, Tarutung',
      status: 'Blokir',
    },
  ],
  cabut: [
    {
      id: '#MA-2020-011',
      name: 'Rudi Hartono',
      initials: 'RH',
      color: '#EF4444',
      type: 'Residential Type A',
      address: 'Jl. Sudirman No. 12, Makassar',
      status: 'Cabut',
    },
    {
      id: '#MA-2020-024',
      name: 'Sri Wahyuni',
      initials: 'SW',
      color: '#F97316',
      type: 'Commercial Type B',
      address: 'Jl. Urip Sumoharjo No. 5, Makassar',
      status: 'Cabut',
    },
    {
      id: '#MA-2020-039',
      name: 'Taufik Ismail',
      initials: 'TI',
      color: '#8B5CF6',
      type: 'Residential Type C',
      address: 'Komp. Perumnas Antang Blok C, Makassar',
      status: 'Cabut',
    },
    {
      id: '#MA-2020-053',
      name: 'Ummi Kalsum',
      initials: 'UK',
      color: '#0EA5E9',
      type: 'Social Institution',
      address: 'Jl. Perintis Kemerdekaan KM 10, Makassar',
      status: 'Cabut',
    },
    {
      id: '#MA-2020-066',
      name: 'Vicky Putra',
      initials: 'VP',
      color: '#14B8A6',
      type: 'Commercial Type A',
      address: 'Jl. AP Pettarani No. 33, Makassar',
      status: 'Cabut',
    },
    {
      id: '#MA-2020-078',
      name: 'Wiwik Sulistyowati',
      initials: 'WS',
      color: '#EC4899',
      type: 'Residential Type B',
      address: 'Jl. Cendrawasih No. 7, Makassar',
      status: 'Cabut',
    },
    {
      id: '#MA-2020-090',
      name: 'Yusuf Mansur',
      initials: 'YM',
      color: '#F59E0B',
      type: 'Residential Type A',
      address: 'Jl. Rappocini Raya No. 14, Makassar',
      status: 'Cabut',
    },
    {
      id: '#MA-2020-103',
      name: 'Zulkifli Arif',
      initials: 'ZA',
      color: '#84CC16',
      type: 'Commercial Type B',
      address: 'Ruko Panakukang Mas Blok D3, Makassar',
      status: 'Cabut',
    },
  ],
})

const filteredData = computed(() => {
  const base = dataMap.value[activeStatus.value] || []
  if (!searchQuery.value) return base
  const q = searchQuery.value.toLowerCase()
  return base.filter(
    (r) =>
      r.id.toLowerCase().includes(q) ||
      r.name.toLowerCase().includes(q) ||
      r.address.toLowerCase().includes(q) ||
      r.status.toLowerCase().includes(q),
  )
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredData.value.length / perPage)))

const paginatedData = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredData.value.slice(start, start + perPage)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  if (total <= 5) return Array.from({ length: total }, (_, i) => i + 1)
  if (current <= 3) return [1, 2, 3, 4, 5].filter((p) => p <= total)
  if (current >= total - 2)
    return [total - 4, total - 3, total - 2, total - 1, total].filter((p) => p >= 1)
  return [current - 2, current - 1, current, current + 1, current + 2]
})

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}
const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}
const goToPage = (page) => {
  currentPage.value = page
}

watch(activeStatus, () => {
  currentPage.value = 1
})
watch(searchQuery, () => {
  currentPage.value = 1
})

const printData = () => {
  const printWindow = window.open('', '_blank')
  printWindow.document.write(`
    <!DOCTYPE html><html><head>
    <title>Cetak Data ${activeLabel.value}</title>
    <style>
      body{font-family:Arial,sans-serif;margin:20px}
      h1{text-align:center;margin-bottom:20px}
      table{width:100%;border-collapse:collapse}
      th,td{border:1px solid #ddd;padding:8px;text-align:left}
      th{background:#f2f2f2;font-weight:bold}
      tr:nth-child(even){background:#f9f9f9}
      @media print{body{margin:10px}}
    </style></head><body>
    <h1>Data ${activeLabel.value}</h1>
    <table><thead><tr><th>Customer ID</th><th>Full Name</th><th>Type</th><th>Address</th><th>Status</th></tr></thead>
    <tbody>${filteredData.value.map((r) => `<tr><td>${r.id}</td><td>${r.name}</td><td>${r.type}</td><td>${r.address}</td><td>${r.status}</td></tr>`).join('')}</tbody>
    </table></body></html>
  `)
  printWindow.document.close()
  printWindow.print()
}
</script>
