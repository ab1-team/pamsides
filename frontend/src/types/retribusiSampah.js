// Type definitions for Retribusi Sampah (Garbage Collection Fees)

/**
 * @typedef {Object} RetribusiSampahRecord
 * @property {string} id - Unique identifier for the record
 * @property {string} nama - Customer name
 * @property {string} initials - Customer initials for avatar
 * @property {string} avatarColor - Avatar background color
 * @property {number} meterAwal - Initial meter reading (if applicable, though usually flat rate)
 * @property {number} meterAkhir - Final meter reading
 * @property {number} pemakaian - Usage
 * @property {number} tagihan - Billing amount in Rupiah
 * @property {string} jatuhTempo - Due date
 * @property {'PAID'|'PENDING'|'OVERDUE'} status - Payment status
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
