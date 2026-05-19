import {
  ref,
  computed,
  onMounted
} from 'vue'
import Swal from 'sweetalert2'
import villageService from '@/services/village.service'

export function useDesa(router) {
  // State
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // Data dari API
  const tableData = ref([])

  // FETCH DATA
  const getData = async () => {
    try {
      const res = await villageService.getVillages()

      // mapping supaya sesuai dengan table kamu
      tableData.value = res.data.data.map(item => ({
        id: item.id,
        desa: item.village_name,
        dusun: item.hamlet_name,
        alamat: item.address,
        telepon: item.phone,
      }))

      console.log('DATA DESA:', tableData.value)

    } catch (err) {
      console.error('Gagal ambil desa:', err)
    }
  }

  onMounted(() => {
    getData()
  })

  // FILTER
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

  // PAGINATION
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

  // EDIT
  const handleEdit = (row) => {
    console.log('Edit Desa:', row)
    if (router) {
      router.push(`/data-desa/edit/${row.id}`)
    }
  }

  // DELETE 
  const handleDelete = async (row) => {
    const result = await Swal.fire({
      title: 'Hapus Desa?',
      text: `Desa "${row.desa}" akan dihapus`,
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
        await villageService.deleteVillage(row.id)

        // refresh data dari server
        await getData()

        Swal.fire({
          title: 'Terhapus!',
          text: 'Data desa berhasil dihapus.',
          icon: 'success',
          timer: 1500,
          showConfirmButton: false,
        })
      } catch (err) {
        console.error('Gagal hapus:', err)

        Swal.fire('Error', 'Gagal menghapus data', 'error')
      }
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
