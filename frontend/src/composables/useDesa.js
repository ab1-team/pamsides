import { ref, computed } from 'vue'

export function useDesa() {
  // Filter state
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = 10

  // Sample data for Desa
  const tableData = ref([
    {
      id: 'D-001',
      kode: '320101',
      desa: 'Sukamaju',
      dusun: 'Krajan',
      alamat: 'Jl. Merdeka No. 1, Balai Desa Sukamaju',
      telepon: '021-1234567',
    },
    {
      id: 'D-002',
      kode: '320102',
      desa: 'Sukarame',
      dusun: 'Sukamukti',
      alamat: 'Jl. Pahlawan No. 45, Balai Desa Sukarame',
      telepon: '021-1234568',
    },
    {
      id: 'D-003',
      kode: '320103',
      desa: 'Mulyasari',
      dusun: 'Mekarjati',
      alamat: 'Jl. Raya Mulyasari No. 10',
      telepon: '021-1234569',
    },
    {
      id: 'D-004',
      kode: '320104',
      desa: 'Karanganyar',
      dusun: 'Karangtengah',
      alamat: 'Jl. Bima No. 22',
      telepon: '021-1234570',
    },
    {
      id: 'D-005',
      kode: '320105',
      desa: 'Caringin',
      dusun: 'Caringin Kulon',
      alamat: 'Jl. Raya Caringin No. 5',
      telepon: '021-1234571',
    },
  ])

  // Computed properties
  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value
    const q = searchQuery.value.toLowerCase()
    return tableData.value.filter(
      (r) =>
        r.desa.toLowerCase().includes(q) ||
        r.kode.toLowerCase().includes(q) ||
        r.dusun.toLowerCase().includes(q) ||
        r.alamat.toLowerCase().includes(q),
    )
  })

  const totalPages = computed(() => Math.max(1, Math.ceil(filteredData.value.length / perPage)))
  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= totalPages.value; i++) {
      pages.push(i)
    }
    return pages
  })

  // Handlers
  const handleEdit = (row) => {
    console.log('Edit Desa:', row)
  }

  const handleDelete = (row) => {
    console.log('Delete Desa:', row)
    if (confirm(`Apakah Anda yakin ingin menghapus data desa ${row.desa}?`)) {
      tableData.value = tableData.value.filter((item) => item.id !== row.id)
    }
  }

  return {
    // State
    searchQuery,
    currentPage,
    perPage,

    // Data
    tableData,
    filteredData,

    // Computed
    totalPages,
    visiblePages,

    // Handlers
    handleEdit,
    handleDelete,
  }
}
