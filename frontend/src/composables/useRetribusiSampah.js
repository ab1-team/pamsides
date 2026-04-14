import { ref, computed } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/retribusiSampah'

export function useRetribusiSampah() {
  // Filter state
  const filter = ref({ tahun: '', bulan: '', cater: '' })
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = 10

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

  // Sample data for Retribusi Sampah
  const tableData = ref([
    {
      id: 'RS-2024-0001',
      nama: 'Hadi Supriyanto',
      initials: 'HS',
      avatarColor: '#0ea5e9',
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 1, // Flat unit
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PAID,
    },
    {
      id: 'RS-2024-0002',
      nama: 'Siti Nurhaliza',
      initials: 'SN',
      avatarColor: '#06b6d4',
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 1,
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PENDING,
    },
    {
      id: 'RS-2024-0003',
      nama: 'Rudy Wijaya',
      initials: 'RW',
      avatarColor: '#14b8a6',
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 1,
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PAID,
    },
  ])

  // Computed properties
  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value
    const q = searchQuery.value.toLowerCase()
    return tableData.value.filter(
      (r) => r.nama.toLowerCase().includes(q) || r.id.toLowerCase().includes(q),
    )
  })

  const totalPages = computed(() => Math.max(1, Math.ceil(tableData.value.length / perPage)))
  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
    return pages
  })

  // Handlers
  const handleApplyFilter = () => console.log('Apply filter Retribusi Sampah:', filter.value)
  const handleCetakFormInput = () => console.log('Cetak Form Input Retribusi Sampah')
  const handleHasilInput = () => console.log('Hasil Input Retribusi Sampah')
  const handleInputPemakaian = () => console.log('Input Pemakaian Retribusi Sampah')
  const handleEdit = (row) => console.log('Edit Retribusi Sampah:', row)
  const handleDelete = (row) => console.log('Delete Retribusi Sampah:', row)

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
