import Swal from 'sweetalert2'

export const swalOptions = {
  confirmButtonColor: '#3b82f6',
  cancelButtonColor: '#ef4444',
}

export const MySwal = Swal.mixin(swalOptions)

export const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  customClass: {
    popup: 'swal-toast-custom',
    title: 'swal-toast-title',
  },
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  },
})

export const showSuccessToast = (title = 'Berhasil') => {
  return Toast.fire({
    icon: 'success',
    title,
    timer: 3000,
  })
}

export const showErrorToast = (error) => {
  const message = error?.response?.data?.message || error?.message || 'Terjadi kesalahan'
  return Toast.fire({
    icon: 'error',
    title: message,
    timer: 4000,
  })
}

export const showWarningToast = (title = 'Peringatan') => {
  return Toast.fire({
    icon: 'warning',
    title,
    timer: 3500,
  })
}

export const showInfoToast = (title = 'Informasi') => {
  return Toast.fire({
    icon: 'info',
    title,
    timer: 3000,
  })
}
