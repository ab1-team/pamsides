import { ref, computed } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/retribusiSampah'

export function useRetribusiSampah() {
  // State untuk filter pencarian
  const filter = ref({ tahun: '', bulan: '', cater: '' })
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // Pilihan opsi
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

  // Data dummy untuk Retribusi Sampah
  const tableData = ref([
    {
      id: 'RS-2024-0001',
      nama: 'Hadi Supriyanto',
      initials: 'HS',
      avatarColor: '#0ea5e9',
      meterAwal: 0,
      meterAkhir: 0,
      pemakaian: 1, // Unit flat
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PAID,
      jenis: 'Reguler',
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
      jenis: 'Bisnis',
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
      jenis: 'Reguler',
    },
    {
      id: 'RS-2024-0004',
      nama: 'Bambang Pamungkas',
      initials: 'BP',
      avatarColor: '#f59e0b',
      pemakaian: 1,
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PENDING,
      jenis: 'Reguler',
    },
    {
      id: 'RS-2024-0005',
      nama: 'Dewi Sartika',
      initials: 'DS',
      avatarColor: '#ec4899',
      pemakaian: 1,
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.PAID,
      jenis: 'Sosial',
    },
    {
      id: 'RS-2024-0006',
      nama: 'Eko Prasetyo',
      initials: 'EP',
      avatarColor: '#8b5cf6',
      pemakaian: 1,
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.UNPAID,
      jenis: 'Reguler',
    },
    {
      id: 'RS-2024-0007',
      nama: 'Fatimah Az-Zahra',
      initials: 'FA',
      avatarColor: '#10b981',
      pemakaian: 1,
      tagihan: 25000,
      jatuhTempo: '20 Mei 2024',
      status: STATUS_TYPES.UNPAID,
      jenis: 'Reguler',
    },
  ])

  // Properti komputasi
  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value
    const q = searchQuery.value.toLowerCase()
    return tableData.value.filter(
      (r) => r.nama.toLowerCase().includes(q) || r.id.toLowerCase().includes(q),
    )
  })

  const totalPages = computed(() => Math.max(1, Math.ceil(tableData.value.length / perPage.value)))
  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
    return pages
  })

  // State untuk modal edit (Admin)
  const showEditModal = ref(false)
  const selectedRow = ref(null)

  // Fungsi-fungsi penanganan aksi
  const handleApplyFilter = () => console.log('Apply filter Retribusi Sampah:', filter.value)
  const handleCetakFormInput = () => console.log('Cetak Form Input Retribusi Sampah')
  const handleHasilInput = () => console.log('Hasil Input Retribusi Sampah')
  const handleInputPemakaian = () => console.log('Input Pemakaian Retribusi Sampah')
  
  const handleEdit = (row) => {
    selectedRow.value = { ...row }
    showEditModal.value = true
  }

  const handleSaveEdit = (updatedData) => {
    const index = tableData.value.findIndex((item) => item.id === updatedData.id)
    if (index !== -1) {
      tableData.value[index] = updatedData
    }
    showEditModal.value = false
  }

  const handleDelete = (row) => {
    tableData.value = tableData.value.filter((item) => item.id !== row.id)
  }

  return {
    // State
    filter,
    searchQuery,
    currentPage,
    perPage,
    showEditModal,
    selectedRow,

    // Pilihan opsi
    tahunOptions,
    bulanOptions,

    // Data
    tableData,
    filteredData,

    // Komputasi
    totalPages,
    visiblePages,

    // Konstanta
    STATUS_TYPES,
    STATUS_COLORS,

    // Penanganan Aksi
    handleApplyFilter,
    handleCetakFormInput,
    handleHasilInput,
    handleInputPemakaian,
    handleEdit,
    handleSaveEdit,
    handleDelete,
  }
}
