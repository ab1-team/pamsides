import { ref, computed, onMounted } from 'vue'
import villageService from '@/services/village.service'
import { confirmDelete } from '@/utils/deleteHandler'

export function useDesa(router) {
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  const tableData = ref([])

  const getData = async () => {
    try {
      const res = await villageService.getVillages()

      tableData.value = res.data.data.map((item) => ({
        id: item.id,
        desa: item.village_name,
        dusun: item.hamlet_name,
        alamat: item.address,
        telepon: item.phone,
      }))
    } catch (err) {
      console.error('Gagal ambil desa:', err)
    }
  }

  onMounted(() => {
    getData()
  })

  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value

    const q = searchQuery.value.toLowerCase()

    return tableData.value.filter((r) => {
      return (
        (r.desa && r.desa.toLowerCase().includes(q)) ||
        (r.dusun && r.dusun.toLowerCase().includes(q)) ||
        (r.alamat && r.alamat.toLowerCase().includes(q)) ||
        (r.telepon && r.telepon.toLowerCase().includes(q))
      )
    })
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

  const handleEdit = (row) => {
    if (router) {
      router.push(`/app/data-desa/edit/${row.id}`)
    }
  }

  const handleDelete = async (row) => {
    await confirmDelete({
      title: 'Hapus Desa?',
      text: `Desa "${row.desa}" akan dihapus`,
      successMessage: 'Data desa berhasil dihapus',
      entity: 'desa',
      errorCode: 'VILLAGE_IN_USE',
      fallbackMessage: `Desa "${row.desa}" tidak dapat dihapus karena masih digunakan pada data lain.`,
      onConfirm: async () => {
        await villageService.deleteVillage(row.id)
        await getData()
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
    getData,
  }
}
