/**
 * Format tanggal ke format Indonesia
 * @param {Date|string|number} date - Tanggal yang akan diformat
 * @param {Object} options - Opsi format
 * @returns {string} String tanggal yang diformat
 */
export function useDateFormat(date, options = {}) {
  const { format = 'DD/MM/YYYY', locale = 'id-ID' } = options

  if (!date) {
    return ''
  }

  const dateObj = new Date(date)
  if (isNaN(dateObj.getTime())) {
    return ''
  }

  const day = String(dateObj.getDate()).padStart(2, '0')
  const month = String(dateObj.getMonth() + 1).padStart(2, '0')
  const year = dateObj.getFullYear()

  switch (format) {
    case 'DD/MM/YYYY':
      return `${day}/${month}/${year}`
    case 'YYYY-MM-DD':
      return `${year}-${month}-${day}`
    case 'DD MMMM YYYY':
      return dateObj.toLocaleDateString(locale, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      })
    case 'MMMM YYYY':
      return dateObj.toLocaleDateString(locale, {
        month: 'long',
        year: 'numeric',
      })
    default:
      return dateObj.toLocaleDateString(locale)
  }
}

/**
 * Dapatkan nama bulan dalam bahasa Indonesia
 * @param {number} monthIndex - Indeks bulan (0-11)
 * @returns {string} Nama bulan
 */
export function getMonthName(monthIndex) {
  const months = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
  ]
  return months[monthIndex] || ''
}

/**
 * Format periode bulan (contoh: "April 2025")
 * @param {Date|string|number} date - Tanggal
 * @returns {string} Periode bulan yang diformat
 */
export function formatMonthPeriod(date) {
  const dateObj = new Date(date)
  if (isNaN(dateObj.getTime())) {
    return ''
  }

  const month = getMonthName(dateObj.getMonth())
  const year = dateObj.getFullYear()

  return `${month} ${year}`
}

/**
 * Periksa apakah tanggal valid
 * @param {Date|string|number} date - Tanggal yang diperiksa
 * @returns {boolean} Valid atau tidak
 */
export function isValidDate(date) {
  const dateObj = new Date(date)
  return !isNaN(dateObj.getTime())
}

/**
 * Dapatkan tanggal hari ini dalam format DD/MM/YYYY
 * @returns {string} Tanggal hari ini
 */
export function getCurrentDate() {
  return useDateFormat(new Date(), { format: 'DD/MM/YYYY' })
}
