import { ref, computed, onMounted } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pemakaianAir'
import Swal from 'sweetalert2'
import billingService from '@/services/billing.service'

export function usePemakaianAir() {
  // State untuk filter pencarian
  const filter = ref({ tahun: '', bulan: '', cater: '' })
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)
  const isLoading = ref(false)

  // Pilihan opsi
  // Edit Modal state
  const showEditModal = ref(false)
  const selectedRow = ref(null)

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

  // Data dinamis dari database
  const tableData = ref([])

  const fetchConsumptionHistory = async () => {
    try {
      isLoading.value = true
      const response = await billingService.getBills()
      if (response?.success && response?.data?.bills) {
        tableData.value = response.data.bills.map((bill) => {
          const ticket = bill.customer?.ticket
          return {
            id: bill.customer?.customer_code || `BILL-${bill.id}`,
            realBillId: bill.id,
            nama: ticket?.applicant_name || '-',
            initials: ticket?.applicant_name
              ? ticket.applicant_name
                  .split(' ')
                  .map((n) => n[0])
                  .join('')
                  .toUpperCase()
                  .substring(0, 2)
              : '?',
            avatarColor: ['#0ea5e9', '#06b6d4', '#14b8a6', '#f59e0b', '#ef4444'][bill.id % 5],
            desa: '-',
            dusun: '-',
            rt: '',
            meterAwal: Number(bill.meter_reading_start) || 0,
            meterAkhir: Number(bill.meter_reading_end) || 0,
            pemakaian: Number(bill.usage_m3) || 0,
            tagihan: Number(bill.total_amount) || 0,
            tanggalAkhir: bill.due_date || '-',
            jatuhTempo: bill.due_date ? new Date(bill.due_date).toLocaleDateString('id-ID') : '-',
            status: bill.status === 'paid' ? STATUS_TYPES.PAID : STATUS_TYPES.PENDING,
          }
        })
      }
    } catch (error) {
      console.error('Failed to fetch consumption data:', error)
    } finally {
      isLoading.value = false
    }
  }

  onMounted(() => {
    fetchConsumptionHistory()
  })

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

  // Fungsi-fungsi penanganan aksi
  const handleApplyFilter = () => console.log('Apply filter:', filter.value)
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
    handleApplyFilter,
    handleCetakFormInput,
    handleHasilInput,
    handleInputPemakaian,
    handleEdit,
    handleSaveEdit,
    handleDelete,
  }
}
