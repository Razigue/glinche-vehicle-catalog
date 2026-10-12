export class ApiError extends Error {
  constructor(status) {
    super(`API request failed with status ${status}`)
    this.status = status
  }
}

async function getJson(path, { signal } = {}) {
  const response = await fetch(path, { headers: { Accept: 'application/json' }, signal })

  if (!response.ok) {
    throw new ApiError(response.status)
  }

  return response.json()
}

export async function fetchBrands() {
  const { data } = await getJson('/api/brands')

  return data
}

export function fetchVehicles({ brands, page }, { signal } = {}) {
  const query = new URLSearchParams({ page: String(page) })

  if (brands.length > 0) {
    query.set('brand', brands.join(','))
  }

  return getJson(`/api/vehicles?${query}`, { signal })
}
