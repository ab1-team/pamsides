import { describe, it, expect } from 'vitest'
import {
  useCurrencyFormat,
  parseCurrency,
  formatInputValue,
  validateCurrencyInput,
  getCurrencySymbol,
  formatLargeAmount,
  convertTextToAmount,
} from './useCurrencyFormat'

describe('useCurrencyFormat', () => {
  it('formats number with default 2 fraction digits and symbol', () => {
    const result = useCurrencyFormat(1500000)
    expect(result).toMatch(/^Rp[\s\u00A0]1\.500\.000,00$/)
  })

  it('returns "Rp 0,00" for null', () => {
    expect(useCurrencyFormat(null)).toBe('Rp 0,00')
  })

  it('returns "Rp 0,00" for undefined', () => {
    expect(useCurrencyFormat(undefined)).toBe('Rp 0,00')
  })

  it('returns "Rp 0,00" for NaN', () => {
    expect(useCurrencyFormat(NaN)).toBe('Rp 0,00')
  })

  it('hides symbol when requested', () => {
    expect(useCurrencyFormat(5000, { showSymbol: false })).toBe('5.000,00')
  })

  it('respects maximumFractionDigits when both bounds match', () => {
    const result = useCurrencyFormat(1500, {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    })
    expect(result).toMatch(/^Rp[\s\u00A0]1\.500$/)
  })
})

describe('parseCurrency', () => {
  it('parses formatted string back to number', () => {
    expect(parseCurrency('Rp 1.500.000,50')).toBe(1500000.5)
  })

  it('returns 0 for invalid input', () => {
    expect(parseCurrency('not a number')).toBe(0)
  })

  it('returns 0 for null', () => {
    expect(parseCurrency(null)).toBe(0)
  })
})

describe('formatInputValue', () => {
  it('returns empty string for empty input', () => {
    expect(formatInputValue('')).toBe('')
  })

  it('returns empty string for null', () => {
    expect(formatInputValue(null)).toBe('')
  })

  it('formats numeric value with thousand separators', () => {
    expect(formatInputValue(1500000)).toBe('1.500.000')
  })

  it('parses and reformats string input', () => {
    expect(formatInputValue('Rp 500.000')).toBe('500.000')
  })
})

describe('validateCurrencyInput', () => {
  it('accepts plain digits', () => {
    expect(validateCurrencyInput('12345')).toBe(true)
  })

  it('accepts digits with separators', () => {
    expect(validateCurrencyInput('1.500.000,50')).toBe(true)
  })

  it('accepts with Rp prefix', () => {
    expect(validateCurrencyInput('Rp 100.000')).toBe(true)
  })

  it('rejects non-string input', () => {
    expect(validateCurrencyInput(12345)).toBe(false)
  })

  it('rejects letters', () => {
    expect(validateCurrencyInput('abc')).toBe(false)
  })
})

describe('getCurrencySymbol', () => {
  it('returns "Rp" for IDR', () => {
    expect(getCurrencySymbol('IDR')).toBe('Rp')
  })

  it('returns "$" for USD', () => {
    expect(getCurrencySymbol('USD')).toBe('$')
  })

  it('returns code as fallback for unknown', () => {
    expect(getCurrencySymbol('XYZ')).toBe('XYZ')
  })
})

describe('formatLargeAmount', () => {
  it('formats billions with M', () => {
    expect(formatLargeAmount(1500000000)).toBe('Rp 1.5M')
  })

  it('formats millions with JT', () => {
    expect(formatLargeAmount(2500000)).toBe('Rp 2.5JT')
  })

  it('formats thousands with RB', () => {
    expect(formatLargeAmount(5000)).toBe('Rp 5.0RB')
  })

  it('uses full format for small amounts', () => {
    const result = formatLargeAmount(500)
    expect(result).toMatch(/^Rp[\s\u00A0]500$/)
  })

  it('returns "Rp 0" for null', () => {
    expect(formatLargeAmount(null)).toBe('Rp 0')
  })

  it('handles negative values', () => {
    const result = formatLargeAmount(-1500000)
    expect(result).toMatch(/-1\.5JT/)
  })
})

describe('convertTextToAmount', () => {
  it('converts "100 ribu" to 100000', () => {
    expect(convertTextToAmount('100 ribu')).toBe(100000)
  })

  it('converts "2 juta" to 2000000', () => {
    expect(convertTextToAmount('2 juta')).toBe(2000000)
  })

  it('converts "1 miliar" to 1000000000', () => {
    expect(convertTextToAmount('1 miliar')).toBe(1000000000)
  })

  it('handles abbreviations (rb, jt, m)', () => {
    expect(convertTextToAmount('500 rb')).toBe(500000)
    expect(convertTextToAmount('3 jt')).toBe(3000000)
  })

  it('returns 0 for empty input', () => {
    expect(convertTextToAmount('')).toBe(0)
  })

  it('returns 0 for non-string input', () => {
    expect(convertTextToAmount(null)).toBe(0)
  })

  it('returns 0 for text without numbers', () => {
    expect(convertTextToAmount('abc def')).toBe(0)
  })

  it('parses plain number as-is', () => {
    expect(convertTextToAmount('500')).toBe(500)
  })
})
