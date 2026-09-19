import { ref, computed, onMounted } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pemakaianAir'
import { billingService } from '@/services/billing.service'
import { confirmDelete } from '@/utils/deleteHandler'
import { MySwal } from '@/utils/swal'
import api from '@/utils/axios'

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
    teknisi: '',
  })
  const teknisiOptions = ref([])
  const teknisiError = ref('')
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

  const selectedTeknisi = computed(() => {
    if (!filter.value.teknisi) return null
    return teknisiOptions.value.find((t) => String(t.id) === String(filter.value.teknisi)) || null
  })

  const selectedTeknisiName = computed(() => selectedTeknisi.value?.name || 'Admin')

  const resolveCaterLabel = (rawValue) => {
    if (rawValue === '' || rawValue == null) return ''
    const asString = String(rawValue).trim()
    if (/^\d+$/.test(asString)) {
      const match = teknisiOptions.value.find((t) => String(t.id) === asString)
      if (match) return match.name
    }
    return asString
  }

  // Ambil daftar teknisi dari backend (admin-only endpoint)
  const loadTeknisiOptions = async () => {
    teknisiError.value = ''
    try {
      const res = await api.get('/users', { params: { role: 'teknisi' } })
      const raw = Array.isArray(res?.data) ? res.data : res?.data?.data || []
      teknisiOptions.value = raw.map((u) => ({ id: u.id, name: u.name }))
    } catch (err) {
      teknisiOptions.value = []
      if (err?.response?.status === 403) {
        teknisiError.value = 'Anda tidak memiliki akses untuk melihat daftar teknisi.'
      } else {
        teknisiError.value = 'Gagal memuat daftar teknisi.'
      }
      console.error('Gagal memuat daftar teknisi:', err)
    }
  }

  // Data dinamis dari API
  const loadTableData = async () => {
    try {
      isLoading.value = true
      const monthIndex = parseMonth(filter.value.bulan)
      const yearVal = parseYear(filter.value.tahun)
      const params = {
        month: monthIndex,
        year: yearVal,
      }
      if (filter.value.teknisi) {
        params.user_id = filter.value.teknisi
      }

      const res = await billingService.getUsageList(params)
      if (res?.success && Array.isArray(res.data)) {
        const sorted = [...res.data].sort((a, b) =>
          String(a.nama || a.customer_name || '').localeCompare(
            String(b.nama || b.customer_name || ''),
            'id-ID',
            { sensitivity: 'base' },
          ),
        )
        tableData.value = sorted.map((item) => {
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
            technician_id: item.technician_id || null,
            technician_name: item.technician_name || null,
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
    const total = totalPages.value
    const current = currentPage.value
    const maxPages = 5
    const pages = []

    if (total <= maxPages) {
      for (let i = 1; i <= total; i++) pages.push(i)
      return pages
    }

    let start = Math.max(1, current - 2)
    let end = Math.min(total, start + maxPages - 1)
    if (end - start < maxPages - 1) {
      start = Math.max(1, end - maxPages + 1)
    }

    for (let i = start; i <= end; i++) pages.push(i)

    if (start > 1) pages.unshift('...')
    if (end < total) pages.push(total)

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
    loadTeknisiOptions()
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
    teknisiOptions,
    selectedTeknisiName,
    resolveCaterLabel,

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
    teknisiError,
  }
}