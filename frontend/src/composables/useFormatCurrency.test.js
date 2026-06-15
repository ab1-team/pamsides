import { describe, it, expect } from 'vitest'
import { useFormatCurrency, formatRupiah, parseCurrency } from './useFormatCurrency'

describe('useFormatCurrency', () => {
  it('formats IDR with default options', () => {
    const result = useFormatCurrency(1500000)
    expect(result).toMatch(/^Rp[\s\u00A0]1\.500\.000$/)
  })

  it('formats zero correctly', () => {
    const result = useFormatCurrency(0)
    expect(result).toMatch(/^Rp[\s\u00A0]0$/)
  })

  it('returns "Rp 0" for null', () => {
    expect(useFormatCurrency(null)).toBe('Rp 0')
  })

  it('returns "Rp 0" for undefined', () => {
    expect(useFormatCurrency(undefined)).toBe('Rp 0')
  })

  it('returns "Rp 0" for NaN', () => {
    expect(useFormatCurrency(NaN)).toBe('Rp 0')
  })

  it('formats negative values', () => {
    const result = useFormatCurrency(-50000)
    expect(result).toContain('-')
    expect(result).toMatch(/50\.000/)
  })

  it('hides symbol when showSymbol is false', () => {
    expect(useFormatCurrency(1000, { showSymbol: false })).toBe('1.000')
  })

  it('respects minimumFractionDigits option', () => {
    const result = useFormatCurrency(1500, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    expect(result).toMatch(/^Rp[\s\u00A0]1\.500,00$/)
  })

  it('supports different currency codes', () => {
    const result = useFormatCurrency(100, { currency: 'USD', locale: 'en-US' })
    expect(result).toMatch(/\$|US\$/)
    expect(result).toMatch(/100/)
  })

  it('handles very large numbers', () => {
    const result = useFormatCurrency(1500000000)
    expect(result).toMatch(/^Rp[\s\u00A0]1\.500\.000\.000$/)
  })
})

describe('formatRupiah', () => {
  it('formats basic value', () => {
    const result = formatRupiah(250000)
    expect(result).toMatch(/^Rp[\s\u00A0]250\.000$/)
  })

  it('returns "Rp 0" for null', () => {
    expect(formatRupiah(null)).toBe('Rp 0')
  })

  it('returns "Rp 0" for NaN', () => {
    expect(formatRupiah(NaN)).toBe('Rp 0')
  })
})

describe('parseCurrency', () => {
  it('parses "Rp 1.500.000" to 1500000', () => {
    expect(parseCurrency('Rp 1.500.000')).toBe(1500000)
  })

  it('parses "Rp 1.500,50" with decimal to 1500.5', () => {
    expect(parseCurrency('Rp 1.500,50')).toBe(1500.5)
  })

  it('returns 0 for null', () => {
    expect(parseCurrency(null)).toBe(0)
  })

  it('returns 0 for empty string', () => {
    expect(parseCurrency('')).toBe(0)
  })

  it('returns 0 for non-string input', () => {
    expect(parseCurrency(12345)).toBe(0)
  })

  it('returns 0 for invalid string', () => {
    expect(parseCurrency('abc')).toBe(0)
  })

  it('strips other currency symbols', () => {
    // Fungsi parseCurrency dirancang untuk format IDR (titik=ribuan, koma=desimal)
    // Untuk format US ($1,000.50) hasilnya 1.0005 karena koma dianggap desimal
    // Test ini memastikan simbol $ dihilangkan dari output
    const result = parseCurrency('$1,000.50')
    expect(result).not.toBe(NaN)
    expect(result).toBeGreaterThan(0)
  })
})
