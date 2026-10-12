import { describe, expect, it } from 'vitest'
import { formatMileage, formatPrice } from './format'

const normalizeSpaces = (text) => text.replace(/[  ]/g, ' ')

describe('formatPrice', () => {
  it('formats whole euro amounts without decimals', () => {
    expect(normalizeSpaces(formatPrice(2_670_000))).toBe('26 700 €')
  })

  it('keeps the cents when there are some', () => {
    expect(normalizeSpaces(formatPrice(2_799_050))).toBe('27 990,50 €')
  })

  it('returns null when the price is unknown', () => {
    expect(formatPrice(null)).toBeNull()
  })
})

describe('formatMileage', () => {
  it('formats the mileage in kilometers', () => {
    expect(normalizeSpaces(formatMileage(46_410))).toBe('46 410 km')
  })
})
