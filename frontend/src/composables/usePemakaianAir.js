import { ref, computed } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pemakaianAir'
import { billingService } from '@/services/billing.service'
import Swal from 'sweetalert2'

export function usePemakaianAir() {
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

  // State untuk filter pencarian
  const filter = ref({
    tahun: new Date().getFullYear(),
    bulan: bulanOptions[new Date().getMonth()],
  })
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // State untuk edit modal
  const showEditModal = ref(false)
  const selectedRow = ref(null)

  // Options
  const tahunOptions = computed(() => {
    const y = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => y - i)
  })

  const tableData = ref([])

  // Data dinamis dari API
  const loadTableData = async () => {
    try {
      const monthIndex = filter.value.bulan
        ? bulanOptions.indexOf(filter.value.bulan) + 1
        : new Date().getMonth() + 1
      const yearVal = filter.value.tahun || new Date().getFullYear()

      const res = await billingService.getUsageList({ month: monthIndex, year: yearVal })
      if (res.success && res.data) {
        tableData.value = res.data.map((item) => ({
          ...item,
          status:
            item.status === 'PAID'
              ? STATUS_TYPES.PAID
              : item.status === 'UNPAID'
                ? STATUS_TYPES.PENDING
                : STATUS_TYPES.PENDING, // Pending for unpaid or no bill yet
        }))
      }
    } catch (err) {
      console.error('Gagal memuat data pemakaian air:', err)
    }
  }

  const refreshData = () => {
    return loadTableData()
  }

  // Properti komputasi
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
    tableData.value.forEach((item) => {
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

  // Fungsi-fungsi penanganan aksi
  const handleApplyFilter = () => {
    loadTableData()
  }
  const handleCetakFormInput = () => console.log('Cetak Form Input')
  const handleHasilInput = () => console.log('Hasil Input')
  const handleInputPemakaian = () => console.log('Input Pemakaian')
  const handleEdit = (row) => {
    selectedRow.value = { ...row }
    showEditModal.value = true
  }

  const handleSaveEdit = (updatedRow) => {
    const index = tableData.value.findIndex((item) => item.id === updatedRow.id)
    if (index !== -1) {
      tableData.value[index] = updatedRow
    }
    showEditModal.value = false
  }

  const handleDelete = async (row) => {
    const result = await Swal.fire({
      title: 'Hapus Data Pemakaian?',
      text: `Data pemakaian air untuk "${row.nama}" akan dihapus secara permanent`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      reverseButtons: true,
    })

    if (result.isConfirmed) {
      tableData.value = tableData.value.filter((item) => item.id !== row.id)

      Swal.fire({
        title: 'Terhapus!',
        text: 'Data pemakaian telah berhasil dihapus.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false,
      })
    }
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
    groupedData,

    // Komputasi
    totalPages,
    visiblePages,

    // Konstanta
    STATUS_TYPES,
    STATUS_COLORS,

    // Penanganan Aksi
    refreshData,
    handleApplyFilter,
    handleCetakFormInput,
    handleHasilInput,
    handleInputPemakaian,
    handleEdit,
    handleSaveEdit,
    handleDelete,
  }
}
