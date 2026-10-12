import { ref, watch, type Ref } from 'vue'
import { fetchVehicles } from '../api'
import type { PaginationMeta, Vehicle } from '../types'

export function useVehicles(brand: Ref<string | null>, page: Ref<number>) {
  const vehicles = ref<Vehicle[]>([])
  const pagination = ref<PaginationMeta | null>(null)
  const loading = ref(false)
  const error = ref<unknown>(null)

  let pendingRequest: AbortController | null = null

  async function load() {
    pendingRequest?.abort()
    const request = new AbortController()
    pendingRequest = request

    loading.value = true
    error.value = null

    try {
      const { data, meta } = await fetchVehicles(
        { brand: brand.value, page: page.value },
        { signal: request.signal },
      )
      vehicles.value = data
      pagination.value = meta
    } catch (exception) {
      if (request.signal.aborted) {
        return
      }
      error.value = exception
    } finally {
      if (pendingRequest === request) {
        loading.value = false
      }
    }
  }

  watch([brand, page], load, { immediate: true })

  return { vehicles, pagination, loading, error, reload: load }
}
