import { ref, computed } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pemakaianAir'

export function usePemakaianAir() {
  // Filter state
  const filter = ref({ tahun: '', bulan: '', cater: '' })
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // Options
  const tahunOptions = computed(() => {
    const y = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => y - i)
  })

  const bulanOptions = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ]

  // Sample data
  const tableData = ref([
    {
      id: '1..08.561 P',
      nama: 'Tarsim',
      initials: 'T',
      avatarColor: '#0ea5e9',
      desa: 'Karangasem',
      dusun: 'Karangasem',
      rt: '',
      meterAwal: 0,
      meterAkhir: 10,
      pemakaian: 10,
      tagihan: 45000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PENDING,
    },
    {
      id: '1.0002.02.568 P',
      nama: 'Fuji Riyanta',
      initials: 'FR',
      avatarColor: '#06b6d4',
      desa: 'Mulo',
      dusun: 'Mulo',
      rt: '02',
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 0,
      tagihan: 10000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PENDING,
    },
    {
      id: '102808473',
      nama: 'Hadi Supriyanto',
      initials: 'HS',
      avatarColor: '#14b8a6',
      desa: 'Mulo',
      dusun: 'Mulo',
      rt: '01',
      meterAwal: 1140,
      meterAkhir: 1165,
      pemakaian: 25,
      tagihan: 125000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PAID,
    },
  ])

  // Computed properties
  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value
    const q = searchQuery.value.toLowerCase()
    return tableData.value.filter((r) => r.nama.toLowerCase().includes(q) || r.id.includes(q))
  })

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredData.value.length / perPage.value)),
  )

  const groupedData = computed(() => {
    const groups = {}
    filteredData.value.forEach((item) => {
      const dusun = item.dusun || 'Lainnya'
      if (!groups[dusun]) {
        groups[dusun] = []
      }
      groups[dusun].push(item)
    })
    return groups
  })

  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= Math.min(3, totalPages.value); i++) {
      pages.push(i)
    }
    return pages
  })

  // Handlers
  const handleApplyFilter = () => console.log('Apply filter:', filter.value)
  const handleCetakFormInput = () => console.log('Cetak Form Input')
  const handleHasilInput = () => console.log('Hasil Input')
  const handleInputPemakaian = () => console.log('Input Pemakaian')
  const handleEdit = (row) => console.log('Edit:', row)
  const handleDelete = (row) => console.log('Delete:', row)

  return {
    // State
    filter,
    searchQuery,
    currentPage,
    perPage,

    // Options
    tahunOptions,
    bulanOptions,

    // Data
    tableData,
    filteredData,
    groupedData,

    // Computed
    totalPages,
    visiblePages,

    // Constants
    STATUS_TYPES,
    STATUS_COLORS,

    // Handlers
    handleApplyFilter,
    handleCetakFormInput,
    handleHasilInput,
    handleInputPemakaian,
    handleEdit,
    handleDelete,
  }
}
