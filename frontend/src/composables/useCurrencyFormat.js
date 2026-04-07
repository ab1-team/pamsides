/**
 * =============================================
   CURRENCY FORMATTING UTILITY - MAKS MONEY
   =============================================
 */

/**
 * Format number to Indonesian currency format (maksMoney)
 * @param {number} amount - Amount to format
 * @param {Object} options - Formatting options
 * @returns {string} Formatted currency string
 */
export function useCurrencyFormat(amount, options = {}) {
  const {
    locale = 'id-ID',
    minimumFractionDigits = 2,
    maximumFractionDigits = 2,
    showSymbol = true,
  } = options

  if (amount === null || amount === undefined || isNaN(amount)) {
    return showSymbol ? 'Rp 0,00' : '0,00'
  }

  const formattedAmount = Number(amount).toLocaleString(locale, {
    minimumFractionDigits,
    maximumFractionDigits,
  })

  return showSymbol ? `Rp ${formattedAmount}` : formattedAmount
}

/**
 * Parse formatted currency string back to number
 * @param {string} formattedString - Formatted currency string
 * @returns {number} Parsed number
 */
export function parseCurrency(formattedString) {
  if (!formattedString || typeof formattedString !== 'string') {
    return 0
  }

  // Remove currency symbol, dots, and spaces, then replace comma with dot
  const cleanString = formattedString
    .replace(/[Rp\s]/g, '') // Remove 'Rp' and spaces
    .replace(/\./g, '') // Remove thousand separators
    .replace(/,/g, '.') // Replace decimal comma with dot

  const parsed = parseFloat(cleanString)
  return isNaN(parsed) ? 0 : parsed
}

/**
 * Format number for input display (with thousand separators)
 * @param {number|string} value - Value to format
 * @returns {string} Formatted string for input
 */
export function formatInputValue(value) {
  if (value === '' || value === null || value === undefined) {
    return ''
  }

  const numValue = typeof value === 'string' ? parseCurrency(value) : Number(value)

  if (isNaN(numValue)) {
    return ''
  }

  return numValue.toLocaleString('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
}

/**
 * Validate currency input
 * @param {string} input - Input string to validate
 * @returns {boolean} Is valid currency input
 */
export function validateCurrencyInput(input) {
  if (typeof input !== 'string') {
    return false
  }

  // Allow empty string, numbers, dots, and commas
  const cleanInput = input.replace(/[Rp\s]/g, '')

  // Check if it's a valid number format
  const regex = /^[0-9.,]*$/
  return regex.test(cleanInput)
}

/**
 * Get currency symbol
 * @param {string} currencyCode - Currency code
 * @returns {string} Currency symbol
 */
export function getCurrencySymbol(currencyCode = 'IDR') {
  const symbols = {
    IDR: 'Rp',
    USD: '$',
    EUR: '€',
    GBP: '£',
    JPY: '¥',
  }
  return symbols[currencyCode] || currencyCode
}

/**
 * Format large amounts with abbreviations (K, M, B)
 * @param {number} amount - Amount to format
 * @returns {string} Formatted string with abbreviation
 */
export function formatLargeAmount(amount) {
  if (amount === null || amount === undefined || isNaN(amount)) {
    return 'Rp 0'
  }

  const absAmount = Math.abs(Number(amount))

  if (absAmount >= 1000000000) {
    return `Rp ${(amount / 1000000000).toFixed(1)}M`
  } else if (absAmount >= 1000000) {
    return `Rp ${(amount / 1000000).toFixed(1)}JT`
  } else if (absAmount >= 1000) {
    return `Rp ${(amount / 1000).toFixed(1)}RB`
  } else {
    return useCurrencyFormat(amount, {
      maximumFractionDigits: 0,
    })
  }
}

/**
 * Convert text to number (for "100 ribu" -> 100000)
 * @param {string} text - Text to convert
 * @returns {number} Converted number
 */
export function convertTextToAmount(text) {
  if (!text || typeof text !== 'string') {
    return 0
  }

  const cleanText = text
    .toLowerCase()
    .replace(/[^0-9a-z\s]/g, '')
    .trim()

  // Extract number and unit
  const match = cleanText.match(/(\d+(?:\.\d+)?)\s*(ribu|juta|miliar|jt|rb|m)?/)

  if (!match) {
    return 0
  }

  const number = parseFloat(match[1])
  const unit = match[2] || ''

  const multipliers = {
    ribu: 1000,
    rb: 1000,
    juta: 1000000,
    jt: 1000000,
    miliar: 1000000000,
    m: 1000000000,
    '': 1,
  }

  return number * (multipliers[unit] || 1)
}

// Export all functions as default object
export default {
  useCurrencyFormat,
  parseCurrency,
  formatInputValue,
  validateCurrencyInput,
  getCurrencySymbol,
  formatLargeAmount,
  convertTextToAmount,
}
