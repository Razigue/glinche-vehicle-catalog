import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { useBrands } from './useBrands'

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function mountBrands() {
  let brands!: ReturnType<typeof useBrands>
  mount({
    setup() {
      brands = useBrands()
      return () => null
    },
  })

  return brands
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('useBrands', () => {
  it('is loading until the brands are received', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({ data: [{ name: 'Renault', slug: 'renault' }] })))

    const { brands, loading, error } = mountBrands()
    expect(loading.value).toBe(true)
    await flushPromises()

    expect(brands.value.map((brand) => brand.slug)).toEqual(['renault'])
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('exposes the error and loads again on reload', async () => {
    vi.stubGlobal(
      'fetch',
      vi
        .fn()
        .mockResolvedValueOnce(jsonResponse({ message: 'Server Error' }, 500))
        .mockResolvedValueOnce(jsonResponse({ data: [{ name: 'Renault', slug: 'renault' }] })),
    )

    const { brands, loading, error, reload } = mountBrands()
    await flushPromises()
    expect(error.value).toMatchObject({ status: 500 })
    expect(loading.value).toBe(false)

    await reload()

    expect(error.value).toBeNull()
    expect(brands.value).toHaveLength(1)
  })
})
