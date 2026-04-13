// Type definitions for Pelanggan (Customer) data structure

/**
 * @typedef {Object} PelangganRecord
 * @property {string} id - Unique identifier for the customer
 * @property {string} nama - Customer name
 * @property {string} initials - Customer initials for avatar
 * @property {string} avatarColor - Avatar background color
 * @property {string} nik - National Identification Number
 * @property {string} alamat - Customer address
 * @property {string} no_hp - Customer phone number
 * @property {'AKTIF'|'NON-AKTIF'|'SUSPENDED'} status - Customer status
 */

export const STATUS_TYPES = {
  AKTIF: 'AKTIF',
  NON_AKTIF: 'NON-AKTIF',
  SUSPENDED: 'SUSPENDED',
}

export const STATUS_COLORS = {
  [STATUS_TYPES.AKTIF]: 'bg-emerald-100 text-emerald-700',
  [STATUS_TYPES.NON_AKTIF]: 'bg-slate-100 text-slate-700',
  [STATUS_TYPES.SUSPENDED]: 'bg-red-100 text-red-700',
}
