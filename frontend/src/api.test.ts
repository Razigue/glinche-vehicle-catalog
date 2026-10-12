import { afterEach, describe, expect, it, vi } from 'vitest'
import { fetchVehicles, REQUEST_TIMEOUT_MS } from './api'

function pendingFetch() {
  return vi.fn((_url: string, { signal }: RequestInit) => new Promise<Response>((_resolve, reject) => {
    signal?.addEventListener('abort', () => reject(signal.reason))
  }))
}

afterEach(() => {
  vi.restoreAllMocks()
  vi.unstubAllGlobals()
})

describe('api', () => {
  it('gives up on a request that takes too long', async () => {
    const timeout = new AbortController()
    const timeoutSpy = vi.spyOn(AbortSignal, 'timeout').mockReturnValue(timeout.signal)
    vi.stubGlobal('fetch', pendingFetch())

    const request = fetchVehicles({ brands: [], page: 1 })
    timeout.abort(new DOMException('Timed out', 'TimeoutError'))

    expect(timeoutSpy).toHaveBeenCalledWith(REQUEST_TIMEOUT_MS)
    await expect(request).rejects.toMatchObject({ name: 'TimeoutError' })
  })

  it('still lets the caller cancel the request', async () => {
    vi.stubGlobal('fetch', pendingFetch())
    const controller = new AbortController()

    const request = fetchVehicles({ brands: [], page: 1 }, { signal: controller.signal })
    controller.abort()

    await expect(request).rejects.toMatchObject({ name: 'AbortError' })
  })
})
