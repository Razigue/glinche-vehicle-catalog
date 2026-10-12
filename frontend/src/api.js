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

export function fetchVehicles({ brand, page }, { signal } = {}) {
  const query = new URLSearchParams({ page: String(page) })

  if (brand) {
    query.set('brand', brand)
  }

  return getJson(`/api/vehicles?${query}`, { signal })
}
