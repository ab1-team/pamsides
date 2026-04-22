import { ref, computed } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pelanggan'
import Swal from 'sweetalert2'

export function usePelanggan(router = null) {
  // State untuk filter pencarian
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // Data dummy untuk Pelanggan
  const tableData = ref([
    {
      id: 'P-2024-0001',
      nama: 'Budi Santoso',
      initials: 'BS',
      avatarColor: '#0ea5e9',
      nik: '3201012345678001',
      alamat: 'Jl. Merdeka No. 123, Desa Sukamaju',
      no_hp: '0812-3456-7890',
      status: STATUS_TYPES.AKTIF,
    },
    {
      id: 'P-2024-0002',
      nama: 'Santi Wijaya',
      initials: 'SW',
      avatarColor: '#f43f5e',
      nik: '3201012345678002',
      alamat: 'Perum Gading blok C-15, Desa Sukamaju',
      no_hp: '0856-9876-5432',
      status: STATUS_TYPES.AKTIF,
    },
    {
      id: 'P-2024-0003',
      nama: 'Asep Sunandar',
      initials: 'AS',
      avatarColor: '#10b981',
      nik: '3201012345678003',
      alamat: 'Kp. Durian Runtuh RT 02/05',
      no_hp: '0821-2233-4455',
      status: STATUS_TYPES.NON_AKTIF,
    },
    {
      id: 'P-2024-0004',
      nama: 'Diana Putri',
      initials: 'DP',
      avatarColor: '#8b5cf6',
      nik: '3201012345678004',
      alamat: 'Jl. Melati No. 45, Desa Sukamaju',
      no_hp: '0813-1122-3344',
      status: STATUS_TYPES.AKTIF,
    },
    {
      id: 'P-2024-0005',
      nama: 'Eko Prasetyo',
      initials: 'EP',
      avatarColor: '#f59e0b',
      nik: '3201012345678005',
      alamat: 'Gg. Kelinci No. 7, Kp. Baru',
      no_hp: '0877-6655-4433',
      status: STATUS_TYPES.SUSPENDED,
    },
  ])

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
      tableData.value = tableData.value.filter((item) => item.id !== row.id)

      Swal.fire({
        title: 'Terhapus!',
        text: 'Data pelanggan telah berhasil dihapus.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false,
      })
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
