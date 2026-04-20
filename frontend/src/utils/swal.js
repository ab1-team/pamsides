import Swal from 'sweetalert2'

export const swalOptions = {
  confirmButtonColor: '#ff0000',
  cancelButtonColor: '#ff7674',
}

export const MySwal = Swal.mixin(swalOptions)
