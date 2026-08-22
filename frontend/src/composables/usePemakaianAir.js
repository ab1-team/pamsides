import { ref, computed, onMounted } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pemakaianAir'
import { billingService } from '@/services/billing.service'
import { confirmDelete } from '@/utils/deleteHandler'
import { MySwal } from '@/utils/swal'

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

  const avatarColors = ['#0ea5e9', '#f43f5e', '#10b981', '#8b5cf6', '#f59e0b', '#3b82f6']

  const buildInitials = (name) => {
    if (!name) return '??'
    return name
      .split(' ')
      .filter(Boolean)
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2)
  }

  const buildAvatarColor = (seed) => {
    const key = String(seed ?? 0)
    let hash = 0
    for (let i = 0; i < key.length; i++) {
      hash = (hash * 31 + key.charCodeAt(i)) >>> 0
    }
    return avatarColors[hash % avatarColors.length]
  }

  // State untuk filter pencarian
  const filter = ref({
    tahun: new Date().getFullYear(),
    bulan: bulanOptions[new Date().getMonth()],
  })
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)
  const isLoading = ref(false)

  // State untuk edit modal
  const showEditModal = ref(false)
  const selectedRow = ref(null)

  // Options
  const tahunOptions = computed(() => {
    const y = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => y - i)
  })

  const tableData = ref([])

  const parseYear = (val) => {
    const n = parseInt(val, 10)
    return Number.isFinite(n) ? n : new Date().getFullYear()
  }

  const parseMonth = (val) => {
    if (val === '' || val === null || val === undefined) {
      return new Date().getMonth() + 1
    }
    if (typeof val === 'number') {
      return val
    }
    const idx = bulanOptions.indexOf(String(val))
    return idx >= 0 ? idx + 1 : new Date().getMonth() + 1
  }

  // Data dinamis dari API
  const loadTableData = async () => {
    try {
      isLoading.value = true
      const monthIndex = parseMonth(filter.value.bulan)
      const yearVal = parseYear(filter.value.tahun)

      const res = await billingService.getUsageList({ month: monthIndex, year: yearVal })
      if (res?.success && Array.isArray(res.data)) {
        tableData.value = res.data.map((item) => {
          const name = item.nama || item.customer_name || '-'
          const statusLabel =
            String(item.status || '').toUpperCase() === 'PAID'
              ? STATUS_TYPES.PAID
              : String(item.status || '').toUpperCase() === 'UNPAID'
                ? 'UNPAID'
                : STATUS_TYPES.PENDING

          return {
            id: item.id,
            nama: name,
            initials: buildInitials(name),
            avatarColor: buildAvatarColor(item.id ?? name),
            customer_code: item.customer_code || '-',
            alamat: item.alamat || '-',
            dusun: item.dusun || '-',
            desa: item.desa || '-',
            meterAwal: Number(item.meter_awal ?? item.meter_reading_start ?? 0),
            meterAkhir: Number(item.meter_akhir ?? item.meter_reading_end ?? 0),
            pemakaian: Number(item.pemakaian ?? item.usage_m3 ?? 0),
            tagihan: Number(item.tagihan ?? item.total_amount ?? 0),
            denda: Number(item.denda ?? item.penalty_amount ?? 0),
            abodemen: Number(item.abodemen ?? 0),
            jatuhTempo: item.due_date || null,
            status: statusLabel,
            package_name: item.package_name || '-',
            reading_photo: item.reading_photo || null,
          }
        })
      }
    } catch (err) {
      console.error('Gagal memuat data pemakaian air:', err)
      MySwal.fire({
        icon: 'error',
        title: 'Gagal Memuat Data',
        text: err.response?.data?.message || 'Tidak dapat memuat data pemakaian air.',
      })
    } finally {
      isLoading.value = false
    }
  }

  const refreshData = () => loadTableData()

  // Properti komputasi
  const filteredData = computed(() => {
    const rows = tableData.value.filter((r) => r.status === 'PAID' || r.status === 'UNPAID')
    if (!searchQuery.value) return rows
    const q = searchQuery.value.toLowerCase()
    return rows.filter(
      (r) =>
        (r.nama || '').toLowerCase().includes(q) ||
        String(r.id).includes(q) ||
        (r.customer_code || '').toLowerCase().includes(q),
    )
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
    currentPage.value = 1
    return loadTableData()
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
      tableData.value[index] = { ...tableData.value[index], ...updatedRow }
    }
    showEditModal.value = false
  }

  const handleDelete = async (row) => {
    await confirmDelete({
      title: 'Hapus Data Pemakaian?',
      text: `Data pemakaian air untuk "${row.nama}" akan dihapus secara permanent`,
      successMessage: 'Data pemakaian telah berhasil dihapus',
      entity: 'pemakaian air',
      errorCode: 'METER_READING_IN_USE',
      onConfirm: () => {
        tableData.value = tableData.value.filter((item) => item.id !== row.id)
      },
    })
  }

  onMounted(() => {
    loadTableData()
  })

  return {
    // State
    filter,
    searchQuery,
    currentPage,
    perPage,
    showEditModal,
    selectedRow,
    isLoading,

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
