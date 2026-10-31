import { ref, computed } from 'vue'
import { STATUS_TYPES, STATUS_COLORS } from '@/types/pemakaianAir'
import { billingService } from '@/services/billing.service'
import { confirmDelete } from '@/utils/deleteHandler'
import { MySwal } from '@/utils/swal'
import api from '@/utils/axios'
import { useUiStore } from '@/stores/uiStore'

export function usePemakaianAir() {
  const uiStore = useUiStore()

  // Role pemanggil.
  //
  // Admin memakai halaman "Data Pemakaian Air" yang punya filter teknisi
  // (bisa lihat semua / pilih satu teknisi). Teknisi memakai halaman
  // "Input Pemakaian Air" yang datanya otomatis dibatasi ke pelanggan
  // miliknya sendiri — jadi dia TIDAK punya filter teknisi, dan nama
  // teknisi yang tampil di kop/cetak adalah nama dia sendiri.
  const isTechnician = computed(() => uiStore.userRole === 'teknisi')

  const bulanOptions = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ]

  const avatarColors = ['#0ea5e9', '#f43f5e', '#10b981', '#8b5cf6', '#f59e0b', '#3b82f6']

  const buildInitials = (name) => {
    if (!name) return '??'
    return name
      .split(' ')
      .filter(Boolean)
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2)
  }

  const buildAvatarColor = (seed) => {
    const key = String(seed ?? 0)
    let hash = 0
    for (let i = 0; i < key.length; i++) {
      hash = (hash * 31 + key.charCodeAt(i)) >>> 0
    }
    return avatarColors[hash % avatarColors.length]
  }

  // State untuk filter pencarian
  const filter = ref({
    tahun: new Date().getFullYear(),
    bulan: bulanOptions[new Date().getMonth()],
    teknisi: '',
  })
  const teknisiOptions = ref([])
  const teknisiError = ref('')
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)
  const isLoading = ref(false)

  // State untuk edit modal
  const showEditModal = ref(false)
  const selectedRow = ref(null)

  // Options
  const tahunOptions = computed(() => {
    const y = new Date().getFullYear()
    return Array.from({ length: 5 }, (_, i) => y - i)
  })

  const tableData = ref([])

  const parseYear = (val) => {
    const n = parseInt(val, 10)
    return Number.isFinite(n) ? n : new Date().getFullYear()
  }

  const parseMonth = (val) => {
    if (val === '' || val === null || val === undefined) {
      return new Date().getMonth() + 1
    }
    if (typeof val === 'number') {
      return val
    }
    // Angka 1-12 (string dari query URL) — selalu dipakai sebagai nomor
    // bulan, bukan sebagai nama bulan.
    if (/^\d{1,2}$/.test(String(val).trim())) {
      const n = parseInt(String(val).trim(), 10)
      if (n >= 1 && n <= 12) return n
    }
    const idx = bulanOptions.indexOf(String(val))
    return idx >= 0 ? idx + 1 : new Date().getMonth() + 1
  }

  /**
   * Pulihkan filter dari query URL.
   *
   * Dipakai setelah halaman "Input Pemakaian" menyimpan meter lalu
   * mengarahkan kembali ke halaman daftar. Tanpa ini, daftar yang dibuka
   * kembali memakai periode default (tahun/bulan hari ini) — padahal baris
   * yang baru disimpan ada di periode yang tadi dicatat, sehingga tabelnya
   * terlihat kosong padahal datanya sudah tersimpan.
   *
   * Query yang dibaca: `tahun`, `bulan` (nama bulan Indonesia ATAU angka
   * 1-12), `teknisi` (id teknisi).
   *
   * Mengembalikan `true` kalau ada filter yang berubah — pemanggil pakai
   * ini untuk tahu apakah data perlu dimuat ulang.
   */
  const applyFilterFromQuery = (query = {}) => {
    if (!query) return false

    let changed = false

    const tahun = parseYear(query.tahun)
    if (filter.value.tahun !== tahun) {
      filter.value.tahun = tahun
      changed = true
    }

    const bulan = parseMonth(query.bulan)
    const bulanLabel = bulanOptions[bulan - 1] ?? filter.value.bulan
    if (filter.value.bulan !== bulanLabel) {
      filter.value.bulan = bulanLabel
      changed = true
    }

    if (query.teknisi) {
      // Disimpan sebagai NUMBER, sama seperti nilai yang keluar dari
      // `SelectSearch` (`teknisiOptions` berisi `id` berupa angka). Kalau
      // disimpan sebagai string, perbandingan `getValue(opt) ===
      // getValue(modelValue)` di SelectSearch tidak cocok dan nama teknisinya
      // tidak tampil di kotak filter.
      const id = Number(query.teknisi)
      const next = Number.isFinite(id) && id > 0 ? id : query.teknisi

      if (String(filter.value.teknisi) !== String(next)) {
        filter.value.teknisi = next
        changed = true
      }
    }

    return changed
  }

  const selectedTeknisi = computed(() => {
    if (!filter.value.teknisi) return null
    return teknisiOptions.value.find((t) => String(t.id) === String(filter.value.teknisi)) || null
  })

  const selectedTeknisiName = computed(() => selectedTeknisi.value?.name || 'Admin')

  /**
   * Nama petugas yang harus tampil di kop / metadata cetakan.
   *
   * - Admin    → "Admin" (nama filter teknisi yang dipilih, atau default)
   * - Teknisi  → nama dia sendiri, karena datanya sudah otomatis dibatasi
   *              ke pelanggan miliknya oleh backend.
   *
   * Halaman cetak (cetakForm / cetakStruk / cetakDaftarTagihan) memakai
   * nilai ini supaya hasil cetak teknisi mencantumkan namanya sendiri —
   * sama seperti admin yang mencantumkan teknisi yang dipilihnya.
   */
  const currentUserLabel = computed(() => {
    if (isTechnician.value) {
      return uiStore.userData?.name || uiStore.userData?.nama || 'Teknisi'
    }
    return selectedTeknisiName.value
  })

  const resolveTeknisiLabel = (rawValue) => {
    if (rawValue === '' || rawValue == null) return ''
    const asString = String(rawValue).trim()
    if (/^\d+$/.test(asString)) {
      const match = teknisiOptions.value.find((t) => String(t.id) === asString)
      if (match) return match.name
    }
    return asString
  }

  /**
   * Ambil daftar teknisi dari backend (admin-only endpoint).
   *
   * Teknisi TIDAK memanggil ini: endpoint `/users?role=teknisi` hanya
   * dibuka untuk admin, dan teknisi memang tidak butuh memilih teknisi
   * karena datanya sudah otomatis miliknya sendiri.
   */
  const loadTeknisiOptions = async () => {
    if (isTechnician.value) {
      teknisiOptions.value = []
      return
    }
    teknisiError.value = ''
    try {
      const res = await api.get('/users', { params: { role: 'teknisi' } })
      const raw = Array.isArray(res?.data) ? res.data : res?.data?.data || []
      teknisiOptions.value = raw.map((u) => ({ id: u.id, name: u.name }))
    } catch (err) {
      teknisiOptions.value = []
      if (err?.response?.status === 403) {
        teknisiError.value = 'Anda tidak memiliki akses untuk melihat daftar teknisi.'
      } else {
        teknisiError.value = 'Gagal memuat daftar teknisi.'
      }
    }
  }

  // Data dinamis dari API
  const loadTableData = async () => {
    try {
      isLoading.value = true
      const monthIndex = parseMonth(filter.value.bulan)
      const yearVal = parseYear(filter.value.tahun)
      const params = {
        month: monthIndex,
        year: yearVal,
      }
      if (filter.value.teknisi) {
        params.user_id = filter.value.teknisi
      }

      const res = await billingService.getUsageList(params)
      if (res?.success && Array.isArray(res.data)) {
        const sorted = [...res.data].sort((a, b) =>
          String(a.nama || a.customer_name || '').localeCompare(
            String(b.nama || b.customer_name || ''),
            'id-ID',
            { sensitivity: 'base' },
          ),
        )
        tableData.value = sorted.map((item) => {
          const name = item.nama || item.customer_name || '-'
          const statusLabel =
            String(item.status || '').toUpperCase() === 'PAID'
              ? STATUS_TYPES.PAID
              : String(item.status || '').toUpperCase() === 'UNPAID'
                ? 'UNPAID'
                : STATUS_TYPES.PENDING

          return {
            id: item.id,
            nama: name,
            initials: buildInitials(name),
            avatarColor: buildAvatarColor(item.id ?? name),
            customer_code: item.customer_code || '-',
            alamat: item.alamat || '-',
            rt: item.rt || '-',
            rw: item.rw || '-',
            dusun: item.dusun || '-',
            desa: item.desa || '-',
            meterAwal: Number(item.meter_awal ?? item.meter_reading_start ?? 0),
            meterAkhir: Number(item.meter_akhir ?? item.meter_reading_end ?? 0),
            pemakaian: Number(item.pemakaian ?? item.usage_m3 ?? 0),
            pemakaian_charge: Number(item.pemakaian_charge ?? item.usage_charge ?? 0),
            tagihan: Number(item.tagihan ?? item.total_amount ?? 0),
            denda: Number(item.denda ?? item.penalty_amount ?? 0),
            // Bulan yang sumber dendanya (N-1), mis. "Juli 2026". null kalau
            // tagihan ini tidak punya denda.
            denda_bulan: item.denda_bulan || null,
            abodemen: Number(item.abodemen ?? 0),
            jatuhTempo: item.due_date || null,
            // Tanggal akhir pemakaian (batas akhir input meter untuk periode
            // ini). Dipakai di modal edit sebagai nilai awal date picker
            // "TANGGAL AKHIR". Kalau backend tidak mengirimnya, fallback ke
            // jatuh tempo tagihan.
            tanggalAkhir: item.reading_recorded_at || item.due_date || null,
            status: statusLabel,
            paid_amount: Number(item.paid_amount ?? 0),
            is_paid: item.is_paid ?? statusLabel === 'PAID',
            package_name: item.package_name || '-',
            // Periode tagihan dari backend. Dipakai saat edit supaya
            // perubahan terkirim ke periode yang benar, bukan ke periode
            // dari filter halaman yang kebetulan sedang aktif.
            periodeBulan: Number(item.billing_period_month ?? 0) || null,
            periodeTahun: Number(item.billing_period_year ?? 0) || null,
            reading_photo: item.reading_photo || null,
            technician_id: item.technician_id || null,
            technician_name: item.technician_name || null,
          }
        })
      }
    } catch (err) {
      MySwal.fire({
        icon: 'error',
        title: 'Gagal Memuat Data',
        text: err.response?.data?.message || 'Tidak dapat memuat data pemakaian air.',
      })
    } finally {
      isLoading.value = false
    }
  }

  const refreshData = () => loadTableData()

  // Properti komputasi
  const filteredData = computed(() => {
    const rows = tableData.value.filter((r) => r.status === 'PAID' || r.status === 'UNPAID')
    if (!searchQuery.value) return rows
    const q = searchQuery.value.toLowerCase()
    return rows.filter(
      (r) =>
        (r.nama || '').toLowerCase().includes(q) ||
        String(r.id).includes(q) ||
        (r.customer_code || '').toLowerCase().includes(q),
    )
  })

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredData.value.length / perPage.value)),
  )

  const groupedData = computed(() => {
    const groups = {}
    tableData.value.forEach((item) => {
      const dusun = item.dusun || 'Lainnya'
      if (!groups[dusun]) {
        groups[dusun] = []
      }
      groups[dusun].push(item)
    })
    return groups
  })

  const visiblePages = computed(() => {
    const total = totalPages.value
    const current = currentPage.value
    const maxPages = 5
    const pages = []

    if (total <= maxPages) {
      for (let i = 1; i <= total; i++) pages.push(i)
      return pages
    }

    let start = Math.max(1, current - 2)
    let end = Math.min(total, start + maxPages - 1)
    if (end - start < maxPages - 1) {
      start = Math.max(1, end - maxPages + 1)
    }

    for (let i = start; i <= end; i++) pages.push(i)

    if (start > 1) pages.unshift('...')
    if (end < total) pages.push(total)

    return pages
  })

  // Fungsi-fungsi penanganan aksi
  const handleApplyFilter = () => {
    currentPage.value = 1
    return loadTableData()
  }
  const handleCetakFormInput = () => {
    /* debug log removed */
  }
  const handleHasilInput = () => {
    /* debug log removed */
  }
  const handleInputPemakaian = () => {
    /* debug log removed */
  }
  const handleEdit = (row) => {
    selectedRow.value = { ...row }
    showEditModal.value = true
  }

  /**
   * Simpan perubahan pemakaian.
   *
   * Tagihan dihitung ulang di BACKEND (tarif progresif paket + abodemen +
   * denda beku), lalu tabel dimuat ulang dari server. Sengaja TIDAK
   * menebak tagihannya di frontend: kalau frontend yang menghitung,
   * nomor yang tampil bisa berbeda dari yang tersimpan di database.
   */
  const handleSaveEdit = async (updatedRow) => {
    const row = selectedRow.value
    if (!row) return

    isLoading.value = true

    try {
      const res = await billingService.updateUsage({
        customer_id: row.id,
        // Pakai periode tagihan dari server. Kalau baris ini belum punya
        // tagihan (mis. PENDING), jatuh ke periode filter halaman.
        month: row.periodeBulan || parseMonth(filter.value.bulan),
        year: row.periodeTahun || parseYear(filter.value.tahun),
        meter_value: Number(updatedRow.meterAkhir),
      })

      if (!res?.success) {
        throw new Error(res?.message || 'Gagal menyimpan perubahan pemakaian.')
      }

      showEditModal.value = false

      // Muat ulang dari server supaya angka pemakaian, tagihan, dan
      // status yang tampil pasti sama dengan yang tersimpan.
      await loadTableData()

      MySwal.fire({
        icon: 'success',
        title: 'Berhasil diperbarui',
        text: res?.message || 'Pemakaian dan tagihan berhasil diperbarui.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        customClass: {
          popup: 'swal-toast-custom',
        },
      })
    } catch (err) {
      const message =
        err?.response?.data?.message || err?.message || 'Tidak dapat menyimpan perubahan pemakaian.'

      MySwal.fire({
        icon: 'error',
        title: 'Gagal Menyimpan',
        text: message,
        confirmButtonColor: '#ef4444',
      })
    } finally {
      isLoading.value = false
    }
  }

  const handleDelete = async (row) => {
    await confirmDelete({
      title: 'Hapus Data Pemakaian?',
      text: `Data pemakaian air untuk "${row.nama}" akan dihapus secara permanent`,
      successMessage: 'Data pemakaian telah berhasil dihapus',
      entity: 'pemakaian air',
      errorCode: 'METER_READING_IN_USE',
      onConfirm: () => {
        tableData.value = tableData.value.filter((item) => item.id !== row.id)
      },
    })
  }

  return {
    // State
    filter,
    searchQuery,
    currentPage,
    perPage,
    showEditModal,
    selectedRow,
    isLoading,

    // Role
    isTechnician,
    currentUserLabel,

    // Pilihan opsi
    tahunOptions,
    bulanOptions,
    teknisiOptions,
    selectedTeknisiName,
    resolveTeknisiLabel,

    // Data
    tableData,
    filteredData,
    groupedData,

    // Komputasi
    totalPages,
    visiblePages,

    // Konstanta
    STATUS_TYPES,
    STATUS_COLORS,

    // Penanganan Aksi
    refreshData,
    handleApplyFilter,
    applyFilterFromQuery,
    handleCetakFormInput,
    handleHasilInput,
    handleInputPemakaian,
    handleEdit,
    handleSaveEdit,
    handleDelete,
    teknisiError,
    loadTeknisiOptions,
  }
}
