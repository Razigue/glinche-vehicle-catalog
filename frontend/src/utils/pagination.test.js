import { describe, expect, it } from 'vitest'
import { pageItems } from './pagination'

describe('pageItems', () => {
  it('lists every page when there are only a few', () => {
    expect(pageItems(1, 1)).toEqual([1])
    expect(pageItems(2, 5)).toEqual([1, 2, 3, 4, 5])
  })

  it('keeps the first, last and neighbouring pages and replaces the rest with gaps', () => {
    expect(pageItems(1, 10)).toEqual([1, 2, null, 10])
    expect(pageItems(6, 12)).toEqual([1, null, 5, 6, 7, null, 12])
    expect(pageItems(10, 10)).toEqual([1, null, 9, 10])
  })

  it('shows a single hidden page instead of a gap', () => {
    expect(pageItems(4, 10)).toEqual([1, 2, 3, 4, 5, null, 10])
  })
})
