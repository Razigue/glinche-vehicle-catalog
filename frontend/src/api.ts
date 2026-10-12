import type { Brand, Paginated, Vehicle } from './types'

export class ApiError extends Error {
  readonly status: number

  constructor(status: number) {
    super(`API request failed with status ${status}`)
    this.status = status
  }
}

interface RequestOptions {
  signal?: AbortSignal
}

async function getJson<T>(path: string, { signal }: RequestOptions = {}): Promise<T> {
  const response = await fetch(path, { headers: { Accept: 'application/json' }, signal })

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
