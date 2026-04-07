// Type definitions for Pemakaian Air data structure

/**
 * @typedef {Object} PemakaianAirRecord
 * @property {string} id - Unique identifier for the customer
 * @property {string} nama - Customer name
 * @property {string} initials - Customer initials for avatar
 * @property {string} avatarColor - Avatar background color
 * @property {number} meterAwal - Initial meter reading
 * @property {number} meterAkhir - Final meter reading
 * @property {number} pemakaian - Water usage in m³
 * @property {number} tagihan - Billing amount in Rupiah
 * @property {string} jatuhTempo - Due date
 * @property {'PAID'|'PENDING'|'OVERDUE'} status - Payment status
 */

/**
 * @typedef {Object} FilterState
 * @property {number} tahun - Usage year
 * @property {string} bulan - Usage month
 * @property {string} cater - Cater/zone filter
 */

/**
 * @typedef {Object} PaginationState
 * @property {number} currentPage - Current page number
 * @property {number} perPage - Items per page
 * @property {number} totalPages - Total pages
 * @property {number[]} visiblePages - Visible page numbers
 */

export const STATUS_TYPES = {
  PAID: 'PAID',
  PENDING: 'PENDING',
  OVERDUE: 'OVERDUE',
}

export const STATUS_COLORS = {
  [STATUS_TYPES.PAID]: 'bg-emerald-100 text-emerald-700',
  [STATUS_TYPES.PENDING]: 'bg-amber-100 text-amber-700',
  [STATUS_TYPES.OVERDUE]: 'bg-red-100 text-red-700',
}
