import Swal from 'sweetalert2'
import api from '@/utils/axios'
import ticketService from '@/services/ticket.service'

export function useInstalasiActions() {
  const formatRupiah = (val) => {
    if (!val) return '0'
    return Number(val).toLocaleString('id-ID')
  }

  const transitionStatus = async (ticketId, newStatus, confirmText, installationDate = null) => {
    const result = await Swal.fire({
      title: 'Konfirmasi',
      text: confirmText,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3b82f6',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Lanjutkan',
      cancelButtonText: 'Batal',
      reverseButtons: true,
    })

    if (!result.isConfirmed) return { success: false, cancelled: true }

    try {
      await ticketService.transitionStatus(ticketId, newStatus, installationDate)

      await Swal.fire({
        title: 'Berhasil!',
        text: 'Status berhasil diperbarui.',
        icon: 'success',
        confirmButtonColor: '#3b82f6',
        confirmButtonText: 'OK',
      })

      return { success: true }
    } catch (err) {
      const errorMessage =
        err.response?.data?.message ||
        err.response?.data?.errors?.status?.[0] ||
        'Gagal memperbarui status.'

      await Swal.fire({
        title: 'Gagal!',
        text: errorMessage,
        icon: 'error',
        confirmButtonColor: '#ef4444',
      })

      return { success: false, error: err }
    }
  }

  const confirmPayment = async (ticketId, amount, customerName) => {
    const result = await Swal.fire({
      title: 'Konfirmasi Pembayaran',
      html: `
        <div style="text-align:left; font-size:14px;">
          <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #e2e8f0;">
            <span style="color:#64748b; font-weight:600;">Pelanggan</span>
            <strong style="color:#1e293b;">${customerName}</strong>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0;">
            <span style="color:#64748b; font-weight:600;">Jumlah Pembayaran</span>
            <strong style="font-size:18px; color:#0284c7;">Rp ${formatRupiah(amount)}</strong>
          </div>
          <p style="font-size:12px; color:#64748b; margin-top:8px;">Lanjutkan untuk melangkah ke tahap selanjutnya.</p>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#0284c7',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Konfirmasi',
      cancelButtonText: 'Batal',
      reverseButtons: true,
    })

    if (!result.isConfirmed) return { success: false, cancelled: true }

    try {
      await ticketService.confirmTicketPayment(ticketId, amount)

      await Swal.fire({
        title: 'Berhasil!',
        text: 'Pembayaran berhasil dikonfirmasi. Status berubah ke Processing.',
        icon: 'success',
        confirmButtonColor: '#0284c7',
        confirmButtonText: 'OK',
      })

      return { success: true }
    } catch (err) {
      const errorMessage =
        err.response?.data?.message ||
        err.response?.data?.errors?.amount?.[0] ||
        err.response?.data?.errors?.status?.[0] ||
        'Gagal mengkonfirmasi pembayaran.'

      await Swal.fire({
        title: 'Gagal!',
        text: errorMessage,
        icon: 'error',
        confirmButtonColor: '#ef4444',
      })

      return { success: false, error: err }
    }
  }

  const deleteTicket = async (ticketId, customerName) => {
    const result = await Swal.fire({
      title: 'Hapus Pelanggan?',
      text: `Data pelanggan "${customerName}" akan dihapus secara permanen.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Ya, Hapus',
      cancelButtonText: 'Batal',
      reverseButtons: true,
    })

    if (!result.isConfirmed) return { success: false, cancelled: true }

    try {
      await api.delete(`/customers/${ticketId}`)

      await Swal.fire({
        title: 'Terhapus!',
        text: 'Data pelanggan berhasil dihapus.',
        icon: 'success',
        confirmButtonColor: '#3b82f6',
        confirmButtonText: 'OK',
      })

      return { success: true }
    } catch (err) {
      const errorMessage = err.response?.data?.message || 'Gagal menghapus data.'

      await Swal.fire({
        title: 'Gagal!',
        text: errorMessage,
        icon: 'error',
        confirmButtonColor: '#ef4444',
      })

      return { success: false, error: err }
    }
  }

  const printDetail = (customer, statusLabel) => {
    const printWindow = window.open('', '_blank', 'width=800,height=600')
    if (!printWindow) {
      Swal.fire('Error', 'Browser memblokir popup. Mohon izinkan popup.', 'error')
      return
    }

    const formatDate = (dateStr) => {
      if (!dateStr || dateStr === '-') return '-'
      try {
        const d = new Date(dateStr)
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
      } catch {
        return dateStr
      }
    }

    const html = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Detail ${statusLabel} - ${customer.name}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; padding: 30px; color: #1e293b; font-size: 12px; line-height: 1.5; }
    .header { text-align: center; border-bottom: 2px solid #1e293b; padding-bottom: 15px; margin-bottom: 20px; }
    .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
    .header p { font-size: 11px; color: #64748b; }
    .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; background: #f1f5f9; color: #1e293b; margin-top: 8px; }
    .section { margin-bottom: 20px; }
    .section-title { font-size: 13px; font-weight: bold; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    table td { padding: 6px 0; vertical-align: top; }
    table td:first-child { width: 35%; color: #64748b; font-weight: 600; }
    table td:last-child { color: #1e293b; }
    .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; text-align: center; }
    .signature { margin-top: 30px; display: flex; justify-content: space-between; }
    .signature div { text-align: center; width: 200px; }
    .signature p { margin-bottom: 60px; font-size: 11px; }
    .signature span { border-top: 1px solid #1e293b; padding-top: 4px; display: block; font-size: 11px; font-weight: 600; }
    @media print { body { padding: 20px; } }
  </style>
</head>
<body>
  <div class="header">
    <h1>DETAIL ${statusLabel.toUpperCase()} INSTALASI</h1>
    <p>PAMSIDES - Sistem Manajemen Air Bersih</p>
    <span class="badge">${statusLabel}</span>
  </div>

  <div class="section">
    <div class="section-title">Data Pelanggan</div>
    <table>
      <tr><td>Kode Instalasi</td><td>: ${customer.kodeInstalasi || '-'}</td></tr>
      <tr><td>NIK</td><td>: ${customer.nik || '-'}</td></tr>
      <tr><td>Nama Pelanggan</td><td>: ${customer.name || '-'}</td></tr>
      <tr><td>No. Telepon</td><td>: ${customer.phone || '-'}</td></tr>
      <tr><td>Alamat</td><td>: ${customer.address || '-'}</td></tr>
      <tr><td>Desa/Wilayah</td><td>: ${customer.region || '-'}</td></tr>
    </table>
  </div>

  <div class="section">
    <div class="section-title">Data Instalasi</div>
    <table>
      <tr><td>Paket Instalasi</td><td>: ${customer.paket || '-'}</td></tr>
      <tr><td>Biaya Pasang Baru</td><td>: Rp ${customer.abodemen ? Number(customer.abodemen).toLocaleString('id-ID') : '0'}</td></tr>
      <tr><td>Tanggal Order</td><td>: ${formatDate(customer.tglOrder || customer.tglPasang)}</td></tr>
      <tr><td>Status</td><td>: ${statusLabel}</td></tr>
    </table>
  </div>

  <div class="signature">
    <div>
      <p>Pelanggan,</p>
      <span>${customer.name || '-'}</span>
    </div>
    <div>
      <p>Petugas,</p>
      <span>(_______________)</span>
    </div>
  </div>

  <div class="footer">
    Dicetak pada ${new Date().toLocaleString('id-ID')}
  </div>

  <script>
    window.onload = function() { window.print(); }
  </script>
</body>
</html>
`

    printWindow.document.write(html)
    printWindow.document.close()
  }

  return {
    transitionStatus,
    confirmPayment,
    deleteTicket,
    printDetail,
  }
}
