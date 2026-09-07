import { ref, computed, onMounted, watch } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pelanggan'
import customerService from '@/services/customer.service'
import { confirmDelete } from '@/utils/deleteHandler'
import { MySwal } from '@/utils/swal'

export function usePelanggan(router = null) {
  // State untuk filter pencarian
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)
  let fetchId = 0

  // State untuk data pelanggan
  const tableData = ref([])
  const isLoading = ref(false)

  const mapRow = (c) => ({
    id: c.customer_code || c.id,
    realId: c.id,
    nama: c.name,
    initials: c.name
      ? c.name
          .split(' ')
          .map((n) => n[0])
          .join('')
          .toUpperCase()
          .substring(0, 2)
      : '??',
    avatarColor: ['#0ea5e9', '#f43f5e', '#10b981', '#8b5cf6', '#f59e0b'][c.id % 5],
    nik: c.nik || '-',
    alamat: c.address || '-',
    no_telp: c.no_telp || '-',
    customer_code: c.customer_code || null,
    status: c.status || 'draft',
  })

  // Fungsi untuk mengambil data dari API (semua halaman)
  const fetchCustomers = async (search = '') => {
    const myId = ++fetchId
    try {
      isLoading.value = true
      const pageSize = 100
      let page = 1
      let lastPage = 1
      let aggregated = []

      do {
        const response = await customerService.getCustomers({
          search,
          page,
          per_page: pageSize,
        })
        if (myId !== fetchId) return

        const payload = response?.data || {}
        const items = payload.data || []
        aggregated = aggregated.concat(items)

        lastPage = payload.last_page || 1
        page += 1
      } while (page <= lastPage)
      if (myId !== fetchId) return

      tableData.value = aggregated.map(mapRow)
    } catch (error) {
      console.error('Error fetching customers:', error)
      MySwal.fire({
        title: 'Gagal!',
        text: 'Tidak dapat mengambil data pelanggan.',
        icon: 'error',
      })
    } finally {
      if (myId === fetchId) isLoading.value = false
    }
  }

  // Watcher untuk pencarian (satu-satunya trigger fetch)
  watch(
    searchQuery,
    (val) => {
      currentPage.value = 1
      fetchCustomers(val)
    },
    { immediate: true },
  )

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

  // Fungsi-fungsi penanganan aksi
  const handleEdit = (row) => {
    if (router) {
      router.push(`/app/data-pelanggan/edit/${row.id}`)
    }
  }

  const handleDelete = async (row) => {
    await confirmDelete({
      title: 'Hapus Pelanggan?',
      text: `Pelanggan an. "${row.nama}" akan dihapus secara permanent dari aplikasi`,
      successMessage: 'Data pelanggan berhasil dihapus',
      entity: 'pelanggan',
      onConfirm: async () => {
        await customerService.deleteCustomer(row.realId || row.id)
        await fetchCustomers()
      },
    })
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

    // Konstanta
    STATUS_TYPES,
    STATUS_COLORS,

    // Penanganan Aksi
    handleEdit,
    handleDelete,
  }
}
