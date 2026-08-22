import { ref, computed } from 'vue'
import { confirmDelete } from '@/utils/deleteHandler'

export function useCater(router) {
  // State untuk filter pencarian
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // Data tiruan untuk Cater
  const tableData = ref([])

  // Properti komputasi data (filter dan paginasi)
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

  // Fungsi-fungsi penanganan aksi
  const handleEdit = (row) => {
    console.log('Edit Cater:', row)
    if (router) {
      router.push(`/app/data-cater/edit/${row.id}`)
    }
  }

  const handleDelete = async (row) => {
    await confirmDelete({
      title: 'Hapus Petugas?',
      text: `Petugas an. "${row.nama}" akan dihapus secara permanent dari aplikasi`,
      successMessage: 'Data petugas telah berhasil dihapus',
      entity: 'petugas cater',
      onConfirm: () => {
        tableData.value = tableData.value.filter((item) => item.id !== row.id)
      },
    })
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
