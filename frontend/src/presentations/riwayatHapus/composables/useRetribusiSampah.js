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
  const tableData = ref([])

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
