import { ref, computed } from 'vue'

export function useCater() {
  // Filter state
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // Sample data for Cater
  const tableData = ref([
    {
      id: 'C-001',
      nama: 'Ahmad Fauzi',
      alamat: 'Jl. Melati No. 12, Pringsewu',
      telepon: '081234567890',
      username: 'ahmad.f',
    },
    {
      id: 'C-002',
      nama: 'Budi Santoso',
      alamat: 'Perum Bumi Kencana Blok B/4',
      telepon: '085612345678',
      username: 'budis',
    },
    {
      id: 'C-003',
      nama: 'Cyntia Wulandari',
      alamat: 'Jl. Merdeka Barat No. 99',
      telepon: '081398765432',
      username: 'cyntia_w',
    },
    {
      id: 'C-004',
      nama: 'Dedi Irawan',
      alamat: 'Kp. Sawah Baru RT 02/05',
      telepon: '082155667788',
      username: 'dedi.irawan',
    },
    {
      id: 'C-005',
      nama: 'Eka Saputra',
      alamat: 'Jl. Pahlawan Revolusi No. 8',
      telepon: '089911223344',
      username: 'eka.sap',
    },
  ])

  // Computed properties
  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value
    const q = searchQuery.value.toLowerCase()
    return tableData.value.filter(
      (r) =>
        r.nama.toLowerCase().includes(q) ||
        r.alamat.toLowerCase().includes(q) ||
        r.username.toLowerCase().includes(q) ||
        r.telepon.includes(q),
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

  // Handlers
  const handleEdit = (row) => {
    console.log('Edit Cater:', row)
  }

  const handleDelete = (row) => {
    console.log('Delete Cater:', row)
    if (confirm(`Apakah Anda yakin ingin menghapus data cater ${row.nama}?`)) {
      tableData.value = tableData.value.filter((item) => item.id !== row.id)
    }
  }

  return {
    searchQuery,
    currentPage,
    perPage,
    tableData,
    filteredData,
    totalPages,
    visiblePages,
    handleEdit,
    handleDelete,
  }
}
