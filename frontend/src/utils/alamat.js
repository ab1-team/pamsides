/**
 * Susun alamat pelanggan dari beberapa sumber field.
 *
 * Data pelanggan banyak yang namanya sama persis dan satu kata (mis.
 * 'Sunarto'), sehingga kolom `address` saja membuat beberapa baris tampil
 * identik — petugas mengira pelanggan hilang padahal datanya ada. API sudah
 * mengirim `village`, `hamlet`, `rt`, dan `rw` (setelah perbaikan backend
 * fix/customer-rt-from-ticket), jadi alamat dirakit dari semua sumber itu
 * dengan urutan: address -> village -> hamlet -> RT/RW.
 *
 * Aturan:
 *   - Bagian kosong / '-' dilewati agar tidak muncul koma ganda atau koma
 *     menggantung.
 *   - RT/RW diformat 'RT 07' bila rw kosong, 'RT 07 / RW 03' bila keduanya ada.
 *   - Bila SEMUA bagian kosong, hasilnya '-'.
 *
 * @param {Object} [source] - baris data pelanggan (nama field camelCase).
 * @returns {string} alamat siap tampil.
 */
export function formatAlamat(source = {}) {
  const part = (value) => {
    if (value === null || value === undefined) return ''
    const s = String(value).trim()
    return s === '' || s === '-' ? '' : s
  }

  const segments = []

  const address = part(source.address ?? source.alamat)
  if (address) segments.push(address)

  const village = part(source.village)
  if (village) segments.push(village)

  const hamlet = part(source.hamlet)
  if (hamlet) segments.push(hamlet)

  const rt = part(source.rt)
  const rw = part(source.rw)
  if (rt && rw) {
    segments.push(`RT ${rt} / RW ${rw}`)
  } else if (rt) {
    segments.push(`RT ${rt}`)
  } else if (rw) {
    segments.push(`RW ${rw}`)
  }

  return segments.length > 0 ? segments.join(', ') : '-'
}

export default formatAlamat
