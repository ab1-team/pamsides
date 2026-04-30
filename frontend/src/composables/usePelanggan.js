import {
  ref,
  computed,
  onMounted,
  watch
} from 'vue'
import {
  STATUS_TYPES,
  STATUS_COLORS
} from '@/types/pelanggan'
import Swal from 'sweetalert2'
import {
  useUiStore
} from '@/stores/uiStore'
import customerService from '@/services/customer.service'

export function usePelanggan(router = null) {
  const uiStore = useUiStore()
  // State untuk filter pencarian
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // State untuk data pelanggan
  const tableData = ref([])
  const isLoading = ref(false)

  // Fungsi untuk mengambil data dari API
  const fetchCustomers = async () => {
    try {
      isLoading.value = true
      const response = await customerService.getCustomers({
        search: searchQuery.value,
      })

      console.log('Response from API', response)
      // Mapping data dari API ke format tabel
      tableData.value = response.data.map((c) => ({
        id: c.customer_code || c.id,
        realId: c.id,
        nama: c.name,
        initials: c.name
          .split(' ')
          .map((n) => n[0])
          .join('')
          .toUpperCase()
          .substring(0, 2),
        avatarColor: ['#0ea5e9', '#f43f5e', '#10b981', '#8b5cf6', '#f59e0b'][
          c.id % 5
        ],
        nik: c.nik || '-',
        alamat: c.address || '-',
        no_hp: c.phone || '-',
        status: c.status || STATUS_TYPES.AKTIF,
      }))
    } catch (error) {
      console.error('Error fetching customers:', error)
      Swal.fire({
        title: 'Gagal!',
        text: 'Tidak dapat mengambil data pelanggan.',
        icon: 'error',
      })
    } finally {
      isLoading.value = false
    }
  }

  // Ambil data saat komponen di-mount
  onMounted(() => {
    fetchCustomers()
  })

  // Watcher untuk pencarian
  watch(searchQuery, () => {
    currentPage.value = 1
    fetchCustomers()
  })

  // Properti komputasi
  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value
    const q = searchQuery.value.toLowerCase()
    return tableData.value.filter(
      (r) =>
      r.nama.toLowerCase().includes(q) ||
      r.id.toLowerCase().includes(q) ||
      r.nik.includes(q) ||
      r.alamat.toLowerCase().includes(q),
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

  // Fungsi-fungsi penanganan aksi
  const handleEdit = (row) => {
    console.log('Edit Pelanggan:', row)
    if (router) {
      router.push(`/data-pelanggan/edit/${row.id}`)
    }
  }

  const handleDelete = async (row) => {
    const result = await Swal.fire({
      title: 'Hapus Pelanggan?',
      text: `Pelanggan an. "${row.nama}" akan dihapus secara permanent dari aplikasi`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      reverseButtons: true,
    })

    if (result.isConfirmed) {
      try {
        await customerService.deleteCustomer(row.realId || row.id)
        fetchCustomers()

        uiStore.success('Data pelanggan berhasil dihapus')
      } catch (error) {
        console.error('Error deleting customer:', error)
        // Error handled globally
      }
    }
  }

  return {
    // State
    searchQuery,
    currentPage,
    perPage,
    isLoading,

    // Data
    tableData,
    filteredData,

    // Fungsi
    fetchCustomers,

    // Komputasi
    totalPages,
    visiblePages,

    // Konstanta
    STATUS_TYPES,
    STATUS_COLORS,

    // Penanganan Aksi
    handleEdit,
    handleDelete,
  }
}
