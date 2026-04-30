import Swal from 'sweetalert2'

export const swalOptions = {
  confirmButtonColor: '#3b82f6',
  cancelButtonColor: '#ef4444',
}

export const MySwal = Swal.mixin(swalOptions)

/**
 * Toast Notification - Pojok Kanan Atas
 */
export const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
})
