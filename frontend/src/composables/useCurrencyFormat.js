/**
 * Fungsi untuk memformat angka menjadi format mata uang Rupiah
 * @param {number} amount - Jumlah yang akan diformat
 * @param {Object} options - Opsi format
 * @returns {string} String mata uang yang diformat
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
 * Mengubah string mata uang yang diformat kembali menjadi angka
 * @param {string} formattedString - String mata uang
 * @returns {number} Angka hasil parsing
 */
export function parseCurrency(formattedString) {
  if (!formattedString || typeof formattedString !== 'string') {
    return 0
  }

  // Hapus simbol mata uang, titik, dan spasi, lalu ganti koma dengan titik
  const cleanString = formattedString
    .replace(/[Rp\s]/g, '') // Remove 'Rp' and spaces
    .replace(/\./g, '') // Remove thousand separators
    .replace(/,/g, '.') // Replace decimal comma with dot

  const parsed = parseFloat(cleanString)
  return isNaN(parsed) ? 0 : parsed
}

/**
 * Memformat angka untuk tampilan input (dengan pemisah ribuan)
 * @param {number|string} value - Nilai yang diformat
 * @returns {string} String untuk input
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
 * Validasi input mata uang
 * @param {string} input - String input yang divalidasi
 * @returns {boolean} Valid atau tidak
 */
export function validateCurrencyInput(input) {
  if (typeof input !== 'string') {
    return false
  }

  // Bolehkan string kosong, angka, titik, dan koma
  const cleanInput = input.replace(/[Rp\s]/g, '')

  // Check if it's a valid number format
  const regex = /^[0-9.,]*$/
  return regex.test(cleanInput)
}

/**
 * Mengambil simbol mata uang
 * @param {string} currencyCode - Kode mata uang
 * @returns {string} Simbol mata uang
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
 * Memformat jumlah besar dengan singkatan (RB, JT, M)
 * @param {number} amount - Jumlah
 * @returns {string} String format dengan singkatan
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
    return useCurrencyFormat(amount, { maximumFractionDigits: 0 })
  }
}

/**
 * Konversi teks menjadi angka (contoh: "100 ribu" -> 100000)
 * @param {string} text - Teks yang akan dikonversi
 * @returns {number} Angka konversi
 */
export function convertTextToAmount(text) {
  if (!text || typeof text !== 'string') {
    return 0
  }

  const cleanText = text
    .toLowerCase()
    .replace(/[^0-9a-z\s]/g, '')
    .trim()

  // Ekstrak angka dan unit
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

// Ekspor semua fungsi sebagai objek default
export default {
  useCurrencyFormat,
  parseCurrency,
  formatInputValue,
  validateCurrencyInput,
  getCurrencySymbol,
  formatLargeAmount,
  convertTextToAmount,
}
