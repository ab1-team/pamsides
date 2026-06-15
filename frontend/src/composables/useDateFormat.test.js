import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { useDateFormat, getMonthName, formatMonthPeriod, isValidDate, getCurrentDate } from './useDateFormat'

describe('useDateFormat', () => {
  it('formats date as DD/MM/YYYY by default', () => {
    expect(useDateFormat('2025-04-15')).toBe('15/04/2025')
  })

  it('formats date as YYYY-MM-DD', () => {
    expect(useDateFormat('2025-04-15', { format: 'YYYY-MM-DD' })).toBe('2025-04-15')
  })

  it('formats date as long Indonesian format', () => {
    expect(useDateFormat('2025-04-15', { format: 'DD MMMM YYYY' })).toBe('15 April 2025')
  })

  it('formats date as MMMM YYYY', () => {
    expect(useDateFormat('2025-04-15', { format: 'MMMM YYYY' })).toBe('April 2025')
  })

  it('returns empty string for null', () => {
    expect(useDateFormat(null)).toBe('')
  })

  it('returns empty string for undefined', () => {
    expect(useDateFormat(undefined)).toBe('')
  })

  it('returns empty string for invalid date', () => {
    expect(useDateFormat('not-a-date')).toBe('')
  })

  it('accepts Date object', () => {
    const d = new Date(2025, 0, 5) // Jan 5 2025
    expect(useDateFormat(d)).toBe('05/01/2025')
  })

  it('accepts timestamp number', () => {
    const ts = new Date('2025-12-31').getTime()
    expect(useDateFormat(ts)).toBe('31/12/2025')
  })

  it('pads single-digit day with zero', () => {
    expect(useDateFormat('2025-04-05')).toBe('05/04/2025')
  })
})

describe('getMonthName', () => {
  it('returns correct Indonesian month name', () => {
    expect(getMonthName(0)).toBe('Januari')
    expect(getMonthName(3)).toBe('April')
    expect(getMonthName(11)).toBe('Desember')
  })

  it('returns empty string for invalid index', () => {
    expect(getMonthName(12)).toBe('')
    expect(getMonthName(-1)).toBe('')
  })
})

describe('formatMonthPeriod', () => {
  it('formats date as "Month YYYY"', () => {
    expect(formatMonthPeriod('2025-04-15')).toBe('April 2025')
  })

  it('returns empty string for invalid date', () => {
    expect(formatMonthPeriod('invalid')).toBe('')
  })
})

describe('isValidDate', () => {
  it('returns true for valid date', () => {
    expect(isValidDate('2025-04-15')).toBe(true)
  })

  it('returns false for invalid string', () => {
    expect(isValidDate('not-a-date')).toBe(false)
  })

  it('returns false for empty', () => {
    expect(isValidDate('')).toBe(false)
  })
})

describe('getCurrentDate', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2025-06-15T10:00:00Z'))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('returns today in DD/MM/YYYY', () => {
    expect(getCurrentDate()).toBe('15/06/2025')
  })
})
