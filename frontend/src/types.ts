export interface Brand {
  name: string
  slug: string
  vehicles_count: number
}

export interface Vehicle {
  id: number
  reference: string
  brand: Pick<Brand, 'name' | 'slug'>
  model: string
  version: string | null
  year: number | null
  mileage: number
  energy: string | null
  gearbox: string | null
  price_in_cents: number | null
  photo_url: string | null
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  total: number
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}
