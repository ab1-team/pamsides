export const SEGMENT_LAMA = 'lama'
export const SEGMENT_BARU = 'baru'
export const SEGMENT_RUSAK = 'rusak'
export const SEGMENT_REASON_PREFIX = 'reason:'

export const reasonSegmentValue = (reason) => `${SEGMENT_REASON_PREFIX}${reason ?? ''}`

const applyBill = (row, bill) => {
  if (!bill) return row
  return {
    ...row,
    meterAwal: bill.meterAwal,
    meterAkhir: bill.meterAkhir,
    pemakaian: bill.pemakaian,
    tagihan: bill.tagihan,
    pemakaian_charge: bill.pemakaianCharge,
    abodemen: bill.abodemen,
    denda: bill.denda,
    status: bill.status,
    paid_amount: bill.paidAmount,
    jatuhTempo: bill.dueDate,
    meter_segment: bill.segment,
  }
}

const reasonOf = (segment) => String(segment).slice(SEGMENT_REASON_PREFIX.length)

/**
 * Terapkan pilihan segmen ke baris pemakaian.
 *
 * - 'lama'      : pelanggan normal tetap, pelanggan reset memakai meter lama.
 * - 'baru'      : hanya pelanggan reset, memakai meter baru.
 * - 'reason:xx' : hanya pelanggan reset dengan reason tertentu, memakai meter lama.
 * - lainnya     : baris apa adanya (tagihan aktif/normal).
 */
export const applyUsageSegment = (rows, segment) => {
  const list = Array.isArray(rows) ? rows : []
  if (!segment || segment === 'normal') return list

  if (segment === SEGMENT_BARU) {
    return list
      .filter((row) => row.isReset && row.baruBill)
      .map((row) => applyBill(row, row.baruBill))
  }

  if (segment === SEGMENT_LAMA) {
    return list.map((row) => (row.isReset && row.lamaBill ? applyBill(row, row.lamaBill) : row))
  }

  // "Meter Rusak" = semua pelanggan reset (whatever the reason text),
  // memakai meter lama. Alasan tidak dipakai untuk memfilter.
  if (segment === SEGMENT_RUSAK) {
    return list
      .filter((row) => row.isReset)
      .map((row) => (row.lamaBill ? applyBill(row, row.lamaBill) : row))
  }

  if (String(segment).startsWith(SEGMENT_REASON_PREFIX)) {
    const reason = reasonOf(segment)
    return list
      .filter((row) => row.isReset && String(row.resetReason || '') === reason)
      .map((row) => (row.lamaBill ? applyBill(row, row.lamaBill) : row))
  }

  return list
}

export const capitalizeReason = (reason) => {
  const text = String(reason ?? '').trim()
  if (!text) return ''
  return text.charAt(0).toUpperCase() + text.slice(1)
}
