import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { nextTick, ref } from 'vue'
import { useVehicles } from './useVehicles'

function jsonResponse(body, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })
}

function vehiclesPage(ids) {
  return { data: ids.map((id) => ({ id })), meta: { current_page: 1, last_page: 1, total: ids.length } }
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('useVehicles', () => {
  it('loads the vehicles of the requested brands and page', async () => {
    const fetch = vi.fn().mockResolvedValue(jsonResponse(vehiclesPage([1, 2])))
    vi.stubGlobal('fetch', fetch)

    const { vehicles, pagination, loading, error } = useVehicles(ref(['peugeot', 'renault']), ref(2))
    expect(loading.value).toBe(true)
    await flushPromises()

    expect(fetch).toHaveBeenCalledWith('/api/vehicles?page=2&brand=peugeot%2Crenault', expect.any(Object))
    expect(vehicles.value.map((vehicle) => vehicle.id)).toEqual([1, 2])
    expect(pagination.value.total).toBe(2)
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('ignores the response of a request superseded by a newer one', async () => {
    let resolveFirstRequest
    const fetch = vi
      .fn()
      .mockImplementationOnce((url, { signal }) => new Promise((resolve, reject) => {
        resolveFirstRequest = resolve
        signal.addEventListener('abort', () => reject(new DOMException('Aborted', 'AbortError')))
      }))
      .mockResolvedValueOnce(jsonResponse(vehiclesPage([2])))
    vi.stubGlobal('fetch', fetch)

    const brands = ref([])
    const { vehicles, loading, error } = useVehicles(brands, ref(1))
    brands.value = ['renault']
    await nextTick()
    resolveFirstRequest(jsonResponse(vehiclesPage([1])))
    await flushPromises()

    expect(vehicles.value.map((vehicle) => vehicle.id)).toEqual([2])
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('exposes the error when the request fails', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(jsonResponse({ message: 'Server Error' }, 500)))

    const { vehicles, loading, error } = useVehicles(ref([]), ref(1))
    await flushPromises()

    expect(error.value.status).toBe(500)
    expect(vehicles.value).toEqual([])
    expect(loading.value).toBe(false)
  })
})
