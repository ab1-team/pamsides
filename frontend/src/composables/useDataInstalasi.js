import { ref, computed, onMounted, watch } from 'vue'
import ticketService from '@/services/ticket.service'
import ticketHistoryService from '@/services/ticketHistory.service'
import packageService from '@/services/package.service'
import Swal from 'sweetalert2'

const APP_NAME = 'PAMSIDES'

const STATUS_LABELS = {
  draft: 'Draft',
  pending: 'Permohonan',
  surveyed: 'Disurvey',
  unpaid: 'Belum Bayar',
  processing: 'Diproses',
  completed: 'Aktif',
  suspended: 'Blokir',
  terminated: 'Cabut',
}

/**
 * Hanya 3 status instalasi yang berstatus "aktif" (sudah selesai dipasang).
 * - completed  → Aktif
 * - suspended  → Blokir
 * - terminated → Cabut
 * Ticket yang masih pending (draft, pending, surveyed, unpaid, processing)
 * tidak ditampilkan di halaman ini karena belum jadi pelanggan aktif.
 */
const ACTIVE_CONNECTION_STATUSES = ['completed', 'suspended', 'terminated']

const STATUS_COLORS = {
  draft: 'bg-slate-100 text-slate-700',
  pending: 'bg-blue-100 text-blue-700',
  surveyed: 'bg-amber-100 text-amber-700',
  unpaid: 'bg-orange-100 text-orange-700',
  processing: 'bg-sky-100 text-sky-700',
  completed: 'bg-emerald-100 text-emerald-700',
  suspended: 'bg-rose-100 text-rose-700',
  terminated: 'bg-red-100 text-red-700',
}

const formatTanggalIndonesia = (date = new Date()) => {
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

const escapeHtml = (val) => {
  if (val === null || val === undefined) return '-'
  return String(val)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

export function useDataInstalasi() {
  const searchQuery = ref('')
  const currentPage = ref(1)
  const perPage = ref(10)
  const tableData = ref([])
  const isLoading = ref(false)

  // Total & jumlah halaman dihitung di SERVER (mode server-side).
  const serverTotal = ref(0)
  const serverLastPage = ref(1)

  // --- State untuk fitur perubahan paket ---
  const packages = ref([])
  const isPackagesLoading = ref(false)
  const isDetailLoading = ref(false)
  const isUpdateSubmitting = ref(false)
  const detailData = ref(null)         // hasil GET /history
  const updateForm = ref({
    ticketId: null,
    currentPackageId: null,
    newPackageId: null,
    reason: '',
  })

  /**
   * Map 1 tiket -> 1 baris tabel.
   *
   * `kodeInstalasi` memakai customer_code milik pelanggan yang PALING BARU
   * (relasi `customer` bisa berisi >1 baris untuk tiket yang sama, lihat
   * filter di atas). Tanpa itu, kode yang tampil bisa milik pelanggan lama.
   */
  const mapTicket = (t) => {
    const customers = Array.isArray(t.customer) ? t.customer : t.customer ? [t.customer] : []
    const latestCustomer = customers.length
      ? [...customers].sort((a, b) => (b.id || 0) - (a.id || 0))[0]
      : null

    return {
      id: t.id,
      ticketId: t.id,
      kodeInstalasi: latestCustomer?.customer_code || `#INS-${String(t.id).padStart(4, '0')}`,
      nama: t.applicant_name || '-',
      alamat: t.address || '-',
      paket: t.package?.name || t.package_name || '-',
      currentPackageId: t.package_id ?? null,
      rawStatus: t.status,
      status: STATUS_LABELS[t.status] || t.status || '-',
    }
  }

  /**
   * Ambil data instalasi dari server (server-side pagination + search).
   *
   * PENTING: jangan pakai `per_page: 200` tanpa pagination sungguhan.
   * Ada 1627 tiket berstatus aktif, dan `per_page` dibatasi 200 di server,
   * sehingga pelanggan di luar 200 baris pertama (mis. "Ngatiyo" ada di
   * urutan ke-911) TIDAK PERNAH dikirim ke browser dan tidak bisa dicari.
   * Sekarang filter status + pencarian + paginasi semuanya di server.
   */
  const fetchData = async (options = {}) => {
    const page = options.page ?? currentPage.value
    const size = options.perPage ?? perPage.value
    const search = options.search ?? searchQuery.value

    try {
      isLoading.value = true
      const res = await ticketService.getTickets({
        lite: 1,
        // Filter di server (backend mendukung daftar status dipisah koma)
        status: ACTIVE_CONNECTION_STATUSES.join(','),
        page,
        per_page: size,
        ...(search ? { search } : {}),
      })

      const rows = res?.data?.data

      if (res?.success && Array.isArray(rows)) {
        // Pengaman tambahan di sisi client: pastikan hanya 3 status aktif.
        tableData.value = rows
          .filter((t) => ACTIVE_CONNECTION_STATUSES.includes(t.status))
          .map(mapTicket)
        serverTotal.value = Number(res?.data?.total ?? tableData.value.length)
        serverLastPage.value = Math.max(1, Number(res?.data?.last_page ?? 1))
      } else {
        tableData.value = []
        serverTotal.value = 0
        serverLastPage.value = 1
      }
    } catch (err) {
      Swal.fire({
        title: 'Gagal!',
        text: 'Tidak dapat mengambil data instalasi.',
        icon: 'error',
      })
    } finally {
      isLoading.value = false
    }
  }

  onMounted(() => {
    fetchData()
  })

  /**
   * Muat ulang data dengan parameter saat ini (dipakai tombol "Muat Ulang").
   */
  const reload = () => fetchData()

  /**
   * Perubahan halaman / jumlah baris / kata kunci pencarian → fetch ke server.
   *
   * Pencarian di-DEBOUNCE 300ms supaya tidak memanggil API tiap ketikan
   * huruf. Reset ke halaman 1 saat keyword berubah supaya hasil pencarian
   * tidak "kosong" karena pengguna sedang berada di halaman 5.
   *
   * PERHATIKAN: trigger memakai pola "kalau sedang bukan di halaman 1,
   * cukup set currentPage = 1 dan biarkan watcher currentPage yang
   * memanggil fetch". Kalau `currentPage` sudah 1, assignment tidak
   * mengubah nilai sehingga watcher TIDAK berjalan — itu sebabnya ada
   * fetch manual di cabang else. Tanpa pola ini, satu perubahan bisa
   * memicu 2 request.
   */
  let searchTimer = null

  watch(searchQuery, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
      if (currentPage.value !== 1) {
        currentPage.value = 1
      } else {
        fetchData()
      }
    }, 300)
  })

  watch(currentPage, () => {
    fetchData()
  })

  watch(perPage, () => {
    if (currentPage.value !== 1) {
      currentPage.value = 1
    } else {
      fetchData()
    }
  })

  /**
   * Filter & pagination sudah dijalankan di server, jadi `filteredData`
   * cukup mengembalikan isi halaman yang sedang aktif.
   */
  const filteredData = computed(() => tableData.value)

  const totalPages = computed(() => serverLastPage.value)

  /**
   * Ambil SEMUA data instalasi yang cocok filter + pencarian (tanpa paginasi),
   * dipakai untuk tombol "Cetak Data Instalasi".
   *
   * Memakai `all=1` (bukan `lite=1`) supaya backend mengembalikan seluruh
   * baris aktif, bukan cuma satu halaman. Response-nya ringan (~950 KB)
   * karena relasi yang di-eager-load hanya field minimum.
   */
  const fetchAllForPrint = async () => {
    try {
      const res = await ticketService.getTickets({
        all: 1,
        status: ACTIVE_CONNECTION_STATUSES.join(','),
        ...(searchQuery.value ? { search: searchQuery.value } : {}),
      })
      const rows = Array.isArray(res?.data) ? res.data : []
      return rows
        .filter((t) => ACTIVE_CONNECTION_STATUSES.includes(t.status))
        .map(mapTicket)
    } catch (err) {
      Swal.fire({
        title: 'Gagal!',
        text: 'Tidak dapat mengambil data instalasi untuk dicetak.',
        icon: 'error',
      })
      return []
    }
  }

  const handleCetakDataInstalasi = async () => {
    const printWindow = window.open('', '_blank', 'width=900,height=700')
    if (!printWindow) {
      Swal.fire({
        title: 'Error',
        text: 'Browser memblokir popup. Mohon izinkan popup.',
        icon: 'error',
      })
      return
    }

    // Ambil seluruh data yang cocok (bukan cuma halaman yang tampil).
    isLoading.value = true
    const printRows = await fetchAllForPrint()
    isLoading.value = false

    const rowsHtml = printRows.length
      ? printRows
          .map(
            (row, idx) => `
      <tr>
        <td class="center">${idx + 1}</td>
        <td>${escapeHtml(row.kodeInstalasi)}</td>
        <td>${escapeHtml(row.nama)}</td>
        <td>${escapeHtml(row.alamat)}</td>
        <td class="center">${escapeHtml(row.paket)}</td>
        <td class="center">${escapeHtml(row.status)}</td>
      </tr>`,
          )
          .join('')
      : '<tr><td colspan="6" class="center empty">Tidak ada data instalasi</td></tr>'

    const today = new Date()
    const tanggalIndonesia = formatTanggalIndonesia(today)
    const tanggalCetak = today.toLocaleString('id-ID')

    const html = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Instalasi - ${APP_NAME}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; padding: 30px; color: #0f172a; font-size: 12px; }
    .header { text-align: center; padding-bottom: 14px; margin-bottom: 22px; border-bottom: 2px solid #0f172a; }
    .header h1 { font-size: 22px; font-weight: 800; letter-spacing: 1px; margin-bottom: 6px; text-transform: uppercase; }
    .header p { font-size: 12px; color: #475569; font-weight: 500; }
    .meta { display: flex; justify-content: space-between; margin-bottom: 14px; font-size: 11px; color: #475569; }
    .meta strong { color: #0f172a; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; font-size: 12px; vertical-align: top; }
    th { background: #f1f5f9; color: #1e293b; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; font-size: 11px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .center { text-align: center; }
    .empty { padding: 20px; color: #94a3b8; font-style: italic; }
    .footer { margin-top: 28px; font-size: 10px; color: #94a3b8; text-align: center; padding-top: 10px; border-top: 1px solid #e2e8f0; }
    @media print {
      body { padding: 20px; }
      .no-print { display: none; }
      tr { page-break-inside: avoid; }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1>${APP_NAME}</h1>
    <p>Data Instalasi per tanggal ${tanggalIndonesia}</p>
  </div>

  <div class="meta">
    <span>Total Data: <strong>${printRows.length}</strong> instalasi</span>
    <span>Tanggal Cetak: <strong>${tanggalCetak}</strong></span>
  </div>

  <table>
    <thead>
      <tr>
        <th class="center" style="width:40px;">No</th>
        <th>Kode Instalasi</th>
        <th>Nama Pelanggan</th>
        <th>Alamat</th>
        <th class="center" style="width:140px;">Paket</th>
        <th class="center" style="width:120px;">Status</th>
      </tr>
    </thead>
    <tbody>
      ${rowsHtml}
    </tbody>
  </table>

  <div class="footer">
    Dokumen ini dicetak otomatis oleh sistem ${APP_NAME}.
  </div>

  <script>
    window.onload = function() { setTimeout(function(){ window.print(); }, 300); }
  </script>
</body>
</html>`

    printWindow.document.write(html)
    printWindow.document.close()
  }

  /* =========================================================
   * FITUR PERUBAHAN PAKET & DETAIL HISTORY
   * ========================================================= */

  /** Ambil semua paket aktif untuk dropdown pada modal update. */
  const fetchPackages = async () => {
    try {
      isPackagesLoading.value = true
      const res = await packageService.getPackages()
      // packageService.getPackages() return { success, data: [...] }
      const list = Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : [])
      packages.value = list.map((p) => ({
        id: p.id,
        name: p.name,
        installation_fee: Number(p.installation_fee ?? 0),
        monthly_abodemen: Number(p.monthly_abodemen ?? 0),
        late_penalty: Number(p.late_penalty ?? 0),
      }))
    } catch (err) {
      Swal.fire({
        title: 'Gagal!',
        text: 'Tidak dapat memuat daftar paket.',
        icon: 'error',
      })
    } finally {
      isPackagesLoading.value = false
    }
  }

  /**
   * Buka popup detail: paket aktif, snapshot paket lama, ringkasan tagihan.
   * Dipanggil saat row di-klik.
   */
  const handleShowDetail = async (row) => {
    if (!row?.ticketId) return
    try {
      isDetailLoading.value = true
      detailData.value = null
      const res = await ticketHistoryService.getDetail(row.ticketId)
      if (res?.success) {
        detailData.value = res.data
      } else {
        Swal.fire({
          title: 'Gagal!',
          text: res?.message || 'Tidak dapat memuat detail paket.',
          icon: 'error',
        })
      }
    } catch (err) {
      Swal.fire({
        title: 'Gagal!',
        text: err?.response?.data?.message || 'Tidak dapat memuat detail paket.',
        icon: 'error',
      })
    } finally {
      isDetailLoading.value = false
    }
  }

  /** Tutup popup detail. */
  const closeDetail = () => {
    detailData.value = null
  }

  /**
   * Buka modal update paket. Prefill form dengan paket aktif saat ini.
   * `fetchPackages` dipanggil jika list paket belum ada.
   */
  const openUpdatePackage = async (row) => {
    if (!row?.ticketId) return
    if (!packages.value.length) {
      await fetchPackages()
    }
    updateForm.value = {
      ticketId: row.ticketId,
      currentPackageId: row.currentPackageId ?? null,
      newPackageId: null,
      reason: '',
    }
  }

  /** Tutup modal update paket. */
  const closeUpdatePackage = () => {
    updateForm.value = {
      ticketId: null,
      currentPackageId: null,
      newPackageId: null,
      reason: '',
    }
  }

  /**
   * Submit perubahan paket ke server.
   * Tagihan Lama otomatis ter-snapshot pada `monthly_bills.abodemen`/`usage_charge`
   * sehingga tidak berubah walaupun paket diganti.
   */
  const submitUpdatePackage = async () => {
    const form = updateForm.value
    if (!form.ticketId) return
    if (!form.newPackageId) {
      Swal.fire({
        title: 'Perhatian',
        text: 'Pilih paket baru terlebih dahulu.',
        icon: 'warning',
      })
      return
    }
    if (Number(form.newPackageId) === Number(form.currentPackageId)) {
      Swal.fire({
        title: 'Perhatian',
        text: 'Paket baru tidak boleh sama dengan paket saat ini.',
        icon: 'warning',
      })
      return
    }

    // Konfirmasi sebelum submit
    const oldPkg =
      packages.value.find((p) => p.id === form.currentPackageId) || null
    const newPkg =
      packages.value.find((p) => p.id === form.newPackageId) || null
    const oldPkgName = oldPkg?.name || '-'
    const newPkgName = newPkg?.name || '-'
    const oldAbodemen = oldPkg ? formatRupiah(oldPkg.monthly_abodemen) : '-'
    const newAbodemen = newPkg ? formatRupiah(newPkg.monthly_abodemen) : '-'
    const confirm = await Swal.fire({
      title: 'Konfirmasi Perubahan Paket',
      width: '560px',
      padding: '0',
      html: `
        <div class="text-left" style="padding: 0 4px;">
          <p style="margin: 0 0 14px 0; font-size: 13px; color: #64748b; line-height: 1.55;">
            Paket pelanggan akan diperbarui. Perubahan ini akan langsung berlaku untuk tagihan
            berikutnya.
          </p>

          <!-- Card paket lama → baru -->
          <div style="display: grid; grid-template-columns: 1fr 36px 1fr; gap: 8px; align-items: stretch; margin-bottom: 14px;">
            <!-- Dari -->
            <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px;">
              <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 4px;">
                Paket Saat Ini
              </div>
              <div style="font-size: 14px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 6px;">
                ${escapeHtml(oldPkgName)}
              </div>
              <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 8px; border-top: 1px dashed #cbd5e1;">
                <span style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;">Abodemen</span>
                <span style="font-size: 12px; font-weight: 700; color: #475569;">${oldAbodemen}<span style="font-size: 9px; color: #94a3b8; margin-left: 2px;">/bln</span></span>
              </div>
            </div>

            <!-- Arrow -->
            <div style="display: flex; align-items: center; justify-content: center;">
              <div style="width: 36px; height: 36px; border-radius: 9999px; background: linear-gradient(135deg, #0891b2 0%, #0e7490 50%, #4338ca 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px -2px rgba(8, 145, 178, 0.4);">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M13 6l6 6-6 6"/>
                </svg>
              </div>
            </div>

            <!-- Ke -->
            <div style="background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); border: 2px solid #06b6d4; border-radius: 12px; padding: 12px; box-shadow: 0 4px 12px -2px rgba(6, 182, 212, 0.25);">
              <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #0e7490; margin-bottom: 4px;">
                Paket Baru
              </div>
              <div style="font-size: 14px; font-weight: 800; color: #164e63; line-height: 1.2; margin-bottom: 6px;">
                ${escapeHtml(newPkgName)}
              </div>
              <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 8px; border-top: 1px dashed #67e8f9;">
                <span style="font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #0891b2;">Abodemen</span>
                <span style="font-size: 12px; font-weight: 700; color: #0e7490;">${newAbodemen}<span style="font-size: 9px; color: #0e7490; opacity: 0.7; margin-left: 2px;">/bln</span></span>
              </div>
            </div>
          </div>

          <!-- Warning box -->
          <div style="display: flex; align-items: flex-start; gap: 8px; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a; border-radius: 10px; padding: 10px 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
              <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
              <line x1="12" y1="9" x2="12" y2="13"/>
              <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <div style="font-size: 11px; line-height: 1.5; color: #78350f;">
              <strong style="color: #92400e;">Catatan penting:</strong>
              Tagihan yang sudah terbit tetap menggunakan tarif paket lama (snapshot di kolom
              abodemen &amp; usage_charge). Hanya tagihan baru ke depan yang memakai tarif paket baru.
            </div>
          </div>
        </div>
      `,
      icon: 'question',
      iconColor: '#0891b2',
      showCancelButton: true,
      confirmButtonText: '<i class="swal-btn-icon"></i> Ya, Ubah Paket',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#0891b2',
      buttonsStyling: true,
      customClass: {
        popup: 'swal-popup-modern',
        confirmButton: 'swal-btn-confirm',
        cancelButton: 'swal-btn-cancel',
        icon: 'swal-icon-modern',
        title: 'swal-title-modern',
        htmlContainer: 'swal-html-modern',
        actions: 'swal-actions-modern',
      },
      reverseButtons: true,
    })
    if (!confirm.isConfirmed) return

    try {
      isUpdateSubmitting.value = true
      const res = await ticketHistoryService.changePackage(form.ticketId, {
        new_package_id: form.newPackageId,
        reason: form.reason || undefined,
      })

      if (res?.success) {
        // Refresh tabel supaya kolom Paket ter-update
        await fetchData()

        await Swal.fire({
          title: 'Berhasil!',
          text: 'Paket pelanggan telah diperbarui. Riwayat perubahan tersimpan.',
          icon: 'success',
          timer: 1800,
          showConfirmButton: false,
        })

        // Refresh detail popup kalau sedang terbuka untuk ticket yang sama
        if (detailData.value?.ticket?.id === form.ticketId) {
          await handleShowDetail({
            ticketId: form.ticketId,
          })
        }

        closeUpdatePackage()
      } else {
        Swal.fire({
          title: 'Gagal!',
          text: res?.message || 'Tidak dapat memperbarui paket.',
          icon: 'error',
        })
      }
    } catch (err) {
      Swal.fire({
        title: 'Gagal!',
        text:
          err?.response?.data?.message ||
          'Terjadi kesalahan saat memperbarui paket.',
        icon: 'error',
      })
    } finally {
      isUpdateSubmitting.value = false
    }
  }

  const formatRupiah = (val) => {
    const n = Number(val ?? 0)
    if (!isFinite(n)) return 'Rp 0'
    return 'Rp ' + n.toLocaleString('id-ID', { maximumFractionDigits: 0 })
  }

  const formatDateTime = (iso) => {
    if (!iso) return '-'
    try {
      return new Date(iso).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    } catch {
      return iso
    }
  }

  return {
    searchQuery,
    currentPage,
    perPage,
    tableData,
    filteredData,
    isLoading,
    totalPages,
    serverTotal,
    STATUS_COLORS,
    fetchData,
    reload,

    // Fitur perubahan paket & detail
    packages,
    isPackagesLoading,
    isDetailLoading,
    isUpdateSubmitting,
    detailData,
    updateForm,
    fetchPackages,
    handleShowDetail,
    closeDetail,
    openUpdatePackage,
    closeUpdatePackage,
    submitUpdatePackage,
    formatRupiah,
    formatDateTime,

    handleCetakDataInstalasi,
  }
}
