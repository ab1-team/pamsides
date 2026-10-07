import { ref, computed, watch } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pelanggan'
import customerService from '@/services/customer.service'
import { confirmDelete } from '@/utils/deleteHandler'
import { MySwal } from '@/utils/swal'

export function usePelanggan(router = null) {
  // State untuk filter pencarian
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)

  // State untuk data pelanggan
  const tableData = ref([])
  const isLoading = ref(false)

  // Metadata paginasi dari server. Sengaja TIDAK memakai nilai bawaan dari
  // backend: `serverTotal` yang di-nol-kan membuat DataTable sempat menggambar
  // "Showing 1 to 0 of 0" di layar pertama sebelum balasan tiba.
  const serverTotal = ref(0)
  const serverLastPage = ref(1)

  let fetchId = 0
  let searchTimer = null

  const mapRow = (c) => ({
    // `id` dipakai handleEdit untuk membuka /customers/{id}, jadi harus
    // customer_code bila ada — itulah yang ditampilkan di kolom ID dan yang
    // dibaca show()/update()/destroy() lewat findTicketByIdentifier().
    //
    // Kalau customer_code belum ada (pelanggan hasil form Tambah, yang belum
    // diaktivasi), `id` berupa ANGKA. Karena itu nilainya dipaksa jadi string:
    // `row.id.toLowerCase()` dulu melempar TypeError tepat ketika user
    // mengetik di kotak search, dan tabel ikut kosong walau data hasil simpan
    // memang ada.
    id: c.customer_code || String(c.id),
    realId: c.id,
    nama: c.name || '-',
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

  /**
   * Ambil satu halaman dari server.
   *
   * Tidak ada argumen — kata kunci & nomor halaman dibaca dari state, bukan
   * dari parameter. `@click="fetchCustomers"` di toolbar DataTable mengirim
   * PointerEvent sebagai argumen pertama. Artefak itu pernah ikut terkirim
   * sebagai `?search=[object PointerEvent]` dan me-reset hasil pencarian
   * setiap kali tombol Muat Ulang ditekan.
   */
  const fetchCustomers = async () => {
    const myId = ++fetchId
    try {
      isLoading.value = true

      const params = {
        page: currentPage.value,
        per_page: perPage.value,
      }
      const keyword = searchQuery.value.trim()
      if (keyword !== '') {
        params.search = keyword
      }

      const response = await customerService.getCustomers(params)
      // Balasan request lama sudah tidak relevan (user sudah mengetik lagi).
      if (myId !== fetchId) return

      const payload = response?.data || {}
      const items = Array.isArray(payload.data) ? payload.data : []

      tableData.value = items.map(mapRow)
      serverTotal.value = Number(payload.total) || 0
      serverLastPage.value = Math.max(1, Number(payload.last_page) || 1)

      // Hasil pencarian bisa jadi lebih sedikit halaman (mis. sedang cari di
      // halaman 5 lalu tersisa 1 halaman). Kembalikan ke halaman terakhir yang
      // valid, jangan biarkan tabel kosong.
      if (currentPage.value > serverLastPage.value) {
        currentPage.value = serverLastPage.value
      }
    } catch (error) {
      if (myId !== fetchId) return
      tableData.value = []
      serverTotal.value = 0
      serverLastPage.value = 1
      MySwal.fire({
        title: 'Gagal!',
        text: 'Tidak dapat mengambil data pelanggan.',
        icon: 'error',
      })
    } finally {
      if (myId === fetchId) isLoading.value = false
    }
  }

  /**
   * Kembali ke halaman 1 lalu muat ulang.
   *
   * Kalau halaman sudah 1, setter tidak menghasilkan perubahan sehingga
   * watcher di bawah tidak berjalan — maka fetchCustomers() dipanggil langsung.
   */
  const backToFirstPage = () => {
    if (currentPage.value === 1) {
      fetchCustomers()
    } else {
      currentPage.value = 1
    }
  }

  // Debounce 350ms: tanpa itu tiap ketikan huruf menembak request ke server
  // dan tabel berkedip karena `loading` aktif tiap ketikan.
  watch(searchQuery, () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(backToFirstPage, 350)
  })

  // Nomor halaman berubah = perlu ambil isi halaman itu.
  watch(currentPage, () => {
    fetchCustomers()
  })

  // `perPage` ditangani v-model:per-page milik DataTable. Nomor halaman
  // dikembalikan ke 1 karena mengganti ukuran halaman membuat posisi halaman
  // lama bisa menunjuk ke luar jangkauan.
  watch(perPage, () => {
    backToFirstPage()
  })

  // Muat halaman pertama saat composable dibuat (pengganti `immediate: true`).
  fetchCustomers()

  // Filter & paginasi sudah diurus server, jadi `tableData` apa adanya adalah
  // isi halaman yang sedang ditampilkan. filteredData sengaja dipertahankan
  // sebagai nama yang dipakai PelangganIndex.vue supaya template tidak berubah.
  const filteredData = computed(() => tableData.value)

  const totalPages = computed(() => serverLastPage.value)

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
        // Pakai id yang sama dengan handleEdit (customer_code || id) supaya
        // backend menerima identifier yang konsisten. Keduanya sudah didukung
        // findTicketByIdentifier(), jadi ini aman untuk kolom ID manapun.
        await customerService.deleteCustomer(row.id)
        // fetchCustomers() membaca keyword & nomor halaman dari state, jadi
        // hasil pencarian yang sedang aktif tetap terjaga.
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
    serverTotal,

    // Konstanta
    STATUS_TYPES,
    STATUS_COLORS,

    // Penanganan Aksi
    handleEdit,
    handleDelete,
  }
}