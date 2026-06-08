import { ref, computed, onMounted } from 'vue'
import Swal from 'sweetalert2'
import villageService from '@/services/village.service'

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

      console.log('DATA DESA:', tableData.value)
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
    console.log('Edit Desa:', row)
    if (router) {
      router.push(`/data-desa/edit/${row.id}`)
    }
  }

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

        const status = err.response?.status
        const data = err.response?.data

        if (status === 409 && data?.code === 'VILLAGE_IN_USE') {
          await Swal.fire({
            title: 'Tidak Dapat Menghapus!',
            html: `
              <div style="text-align:left; font-size:13px; color:#475569;">
                <p style="margin-bottom:10px;">${
                  data.message ||
                  `Desa "${row.desa}" tidak dapat dihapus karena masih digunakan pada data lain.`
                }</p>
                <div style="background:#fef3c7; border-left:3px solid #f59e0b; padding:10px 12px; border-radius:6px; font-size:12px; color:#92400e;">
                  <strong>Solusi:</strong> Hapus atau pindahkan data yang terkait dengan desa ini terlebih dahulu (misalnya tiket instalasi atau pelanggan) sebelum menghapus desa.
                </div>
              </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Saya Mengerti',
            customClass: {
              popup: 'desa-fk-popup',
              icon: 'desa-fk-icon',
            },
          })
        } else {
          const message =
            data?.message ||
            (status === 404
              ? 'Data desa tidak ditemukan.'
              : status === 403
                ? 'Anda tidak memiliki akses untuk menghapus data ini.'
                : 'Gagal menghapus data desa.')

          Swal.fire({
            title: 'Gagal Menghapus!',
            text: message,
            icon: 'error',
            confirmButtonColor: '#ef4444',
          })
        }
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
    getData,
  }
}
