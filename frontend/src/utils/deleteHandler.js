import Swal from 'sweetalert2'
import { showSuccessToast, showErrorToast } from './swal'

const FALLBACK_MESSAGES = {
  404: 'Data tidak ditemukan.',
  403: 'Anda tidak memiliki akses untuk menghapus data ini.',
  500: 'Terjadi kesalahan pada server. Silakan coba lagi.',
}

export const confirmDelete = async ({
  title,
  text,
  confirmText = 'Ya, Hapus!',
  successMessage,
  onConfirm,
  entity,
  errorCode,
  fallbackMessage,
}) => {
  const result = await Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: 'Batal',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
  })

  if (!result.isConfirmed) return false

  try {
    await onConfirm()
    if (successMessage) showSuccessToast(successMessage)
    return true
  } catch (err) {
    console.error('Gagal hapus:', err)

    const status = err.response?.status
    const data = err.response?.data
    const code = data?.code

    if (status === 409 && code === (errorCode || 'RESOURCE_IN_USE')) {
      await Swal.fire({
        title: 'Tidak Dapat Menghapus!',
        html: `
          <div style="text-align:left; font-size:13px; color:#475569;">
            <p style="margin-bottom:10px;">${
              data.message ||
              fallbackMessage ||
              `Data ${entity || 'ini'} tidak dapat dihapus karena masih digunakan pada data lain.`
            }</p>
            ${
              data.usage
                ? `<p style="margin-bottom:10px; font-size:12px;"><strong>Sedang digunakan pada:</strong> ${data.usage}</p>`
                : ''
            }
            <div style="background:#fef3c7; border-left:3px solid #f59e0b; padding:10px 12px; border-radius:6px; font-size:12px; color:#92400e;">
              <strong>Solusi:</strong> Hapus atau pindahkan data yang terkait terlebih dahulu sebelum menghapus data ini.
            </div>
          </div>
        `,
        icon: 'warning',
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Saya Mengerti',
      })
    } else {
      const message = data?.message || FALLBACK_MESSAGES[status] || 'Gagal menghapus data.'
      showErrorToast({ message })
    }
    return false
  }
}
