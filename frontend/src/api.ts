import type { Brand, Paginated, Vehicle } from './types'

export class ApiError extends Error {
  readonly status: number

  constructor(status: number) {
    super(`API request failed with status ${status}`)
    this.status = status
  }
}

export class NetworkError extends Error {
  constructor(cause: unknown) {
    super('The API could not be reached', { cause })
  }
}

interface RequestOptions {
  signal?: AbortSignal
}

export const REQUEST_TIMEOUT_MS = 10_000

async function getJson<T>(path: string, { signal }: RequestOptions = {}): Promise<T> {
  const timeout = AbortSignal.timeout(REQUEST_TIMEOUT_MS)
  const response = await fetch(path, {
    headers: { Accept: 'application/json' },
    signal: signal ? AbortSignal.any([signal, timeout]) : timeout,
  }).catch((exception: unknown) => {
    throw exception instanceof TypeError ? new NetworkError(exception) : exception
  })

  if (!response.ok) {
    throw new ApiError(response.status)
  }

  return response.json()
}

export async function fetchBrands(): Promise<Brand[]> {
  const { data } = await getJson<{ data: Brand[] }>('/api/brands')

  return data
}

export function fetchVehicles(
  { brands, page }: { brands: string[]; page: number },
  { signal }: RequestOptions = {},
): Promise<Paginated<Vehicle>> {
  const query = new URLSearchParams({ page: String(page) })

  if (brands.length > 0) {
    query.set('brand', brands.join(','))
  }

  return getJson(`/api/vehicles?${query}`, { signal })
}
