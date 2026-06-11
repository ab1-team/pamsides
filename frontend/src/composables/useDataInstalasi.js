import { ref, computed, onMounted } from 'vue'
import ticketService from '@/services/ticket.service'
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

  const fetchData = async () => {
    try {
      isLoading.value = true
      const res = await ticketService.getTickets({ per_page: 200 })
      if (res?.success && Array.isArray(res?.data?.data)) {
        tableData.value = res.data.data.map((t) => ({
          kodeInstalasi:
            t.customer?.[0]?.customer_code ||
            `#INS-${String(t.id).padStart(4, '0')}`,
          nama: t.applicant_name || '-',
          alamat: t.address || '-',
          rawStatus: t.status,
          status: STATUS_LABELS[t.status] || t.status || '-',
        }))
      } else {
        tableData.value = []
      }
    } catch (err) {
      console.error('Gagal memuat data instalasi:', err)
      Swal.fire({
        title: 'Gagal!',
        text: 'Tidak dapat mengambil data instalasi.',
        icon: 'error',
      })
    } finally {
      isLoading.value = false
    }
  }

  onMounted(fetchData)

  const filteredData = computed(() => {
    if (!searchQuery.value) return tableData.value
    const q = searchQuery.value.toLowerCase()
    return tableData.value.filter((r) => r.nama.toLowerCase().includes(q))
  })

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredData.value.length / perPage.value)),
  )

  const visiblePages = computed(() => {
    const pages = []
    for (let i = 1; i <= Math.min(3, totalPages.value); i++) pages.push(i)
    return pages
  })

  const handleCetakDataInstalasi = () => {
    const printWindow = window.open('', '_blank', 'width=900,height=700')
    if (!printWindow) {
      Swal.fire({
        title: 'Error',
        text: 'Browser memblokir popup. Mohon izinkan popup.',
        icon: 'error',
      })
      return
    }

    const rowsHtml = filteredData.value.length
      ? filteredData.value
          .map(
            (row, idx) => `
      <tr>
        <td class="center">${idx + 1}</td>
        <td>${escapeHtml(row.kodeInstalasi)}</td>
        <td>${escapeHtml(row.nama)}</td>
        <td>${escapeHtml(row.alamat)}</td>
        <td class="center">${escapeHtml(row.status)}</td>
      </tr>`,
          )
          .join('')
      : '<tr><td colspan="5" class="center empty">Tidak ada data instalasi</td></tr>'

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
    <span>Total Data: <strong>${filteredData.value.length}</strong> instalasi</span>
    <span>Tanggal Cetak: <strong>${tanggalCetak}</strong></span>
  </div>

  <table>
    <thead>
      <tr>
        <th class="center" style="width:40px;">No</th>
        <th>Kode Instalasi</th>
        <th>Nama Pelanggan</th>
        <th>Alamat</th>
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

  return {
    searchQuery,
    currentPage,
    perPage,
    tableData,
    filteredData,
    isLoading,
    totalPages,
    visiblePages,
    STATUS_COLORS,
    fetchData,
    handleCetakDataInstalasi,
  }
}
