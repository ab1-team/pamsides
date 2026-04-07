/**
 * =============================================
   DATE FORMATTING UTILITY - STATIC
   =============================================
 */

/**
 * Format date to Indonesian format
 * @param {Date|string|number} date - Date to format
 * @param {Object} options - Formatting options
 * @returns {string} Formatted date string
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
 * Get month name in Indonesian
 * @param {number} monthIndex - Month index (0-11)
 * @returns {string} Month name
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
 * Format month period (e.g., "April 2025")
 * @param {Date|string|number} date - Date
 * @returns {string} Formatted month period
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
 * Check if date is valid
 * @param {Date|string|number} date - Date to check
 * @returns {boolean} Is valid date
 */
export function isValidDate(date) {
  const dateObj = new Date(date)
  return !isNaN(dateObj.getTime())
}

/**
 * Get current date in DD/MM/YYYY format
 * @returns {string} Current date
 */
export function getCurrentDate() {
  return useDateFormat(new Date(), { format: 'DD/MM/YYYY' })
}
