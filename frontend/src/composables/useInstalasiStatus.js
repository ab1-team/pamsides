import { ref, computed, watch } from 'vue'
import { INSTALASI_STATUS_COLORS, INSTALASI_MENU_LIST } from '@/types/instalasiStatus'

export function useInstalasiStatus() {
  const activeStatus = ref('permohonan')
  const currentPage = ref(1)
  const perPage = ref(10)
  const searchQuery = ref('')

  const menuList = INSTALASI_MENU_LIST

  const activeLabel = computed(() => {
    return menuList.find((m) => m.key === activeStatus.value)?.label || ''
  })

  // Mock data map
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
    ],
    aktif: [
      {
        id: '#MA-2022-010',
        name: 'Santoso Wibowo',
        initials: 'SW',
        color: '#10B981',
        type: 'Residential Type A',
        address: 'Jl. Kebon Raya No. 3, Bogor',
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
    ],
  })

  const currentData = computed(() => dataMap.value[activeStatus.value] || [])

  const filteredData = computed(() => {
    if (!searchQuery.value) return currentData.value
    const q = searchQuery.value.toLowerCase()
    return currentData.value.filter(
      (item) =>
        item.name.toLowerCase().includes(q) ||
        item.id.toLowerCase().includes(q) ||
        item.address.toLowerCase().includes(q),
    )
  })

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredData.value.length / perPage.value)),
  )

  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
    return pages
  })

  const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value
    return filteredData.value.slice(start, start + perPage.value)
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

  // Reset page on status change or search
  watch([activeStatus, searchQuery], () => {
    currentPage.value = 1
  })

  const exportData = () => console.log('Export Excel for', activeLabel.value)
  const printData = () => console.log('Print Table for', activeLabel.value)

  return {
    activeStatus,
    activeLabel,
    currentPage,
    perPage,
    searchQuery,
    menuList,
    dataMap,
    filteredData,
    totalPages,
    visiblePages,
    paginatedData,
    statusStyle: INSTALASI_STATUS_COLORS,
    prevPage,
    nextPage,
    goToPage,
    exportData,
    printData,
  }
}
