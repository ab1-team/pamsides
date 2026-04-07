/**
 * =============================================
   CURRENCY FORMATTING UTILITY - STATIC
   =============================================
 */

/**
 * Format number to Indonesian Rupiah currency
 * @param {number} value - Number to format
 * @param {Object} options - Formatting options
 * @returns {string} Formatted currency string
 */
export function useFormatCurrency(value, options = {}) {
  const {
    currency = 'IDR',
    locale = 'id-ID',
    minimumFractionDigits = 0,
    maximumFractionDigits = 0,
    showSymbol = true,
  } = options

  if (value === null || value === undefined || isNaN(value)) {
    return showSymbol ? 'Rp 0' : '0'
  }

  const formatter = new Intl.NumberFormat(locale, {
    style: showSymbol ? 'currency' : 'decimal',
    currency,
    minimumFractionDigits,
    maximumFractionDigits,
  })

  return formatter.format(value)
}

/**
 * Simple Rupiah formatter (alternative)
 * @param {number} value - Number to format
 * @returns {string} Formatted Rupiah string
 */
export function formatRupiah(value) {
  if (value === null || value === undefined || isNaN(value)) {
    return 'Rp 0'
  }

  return `Rp ${value.toLocaleString('id-ID')}`
}

/**
 * Parse currency string back to number
 * @param {string} currencyString - Currency string to parse
 * @returns {number} Parsed number
 */
export function parseCurrency(currencyString) {
  if (!currencyString || typeof currencyString !== 'string') {
    return 0
  }

  // Remove currency symbols and formatting
  const cleanString = currencyString
    .replace(/[Rp\s$€£¥]/g, '')
    .replace(/\./g, '')
    .replace(/,/g, '.')

  const parsed = parseFloat(cleanString)
  return isNaN(parsed) ? 0 : parsed
}
