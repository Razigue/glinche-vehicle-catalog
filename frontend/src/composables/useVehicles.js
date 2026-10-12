import { ref, watch } from 'vue'
import { fetchVehicles } from '../api'

export function useVehicles(brands, page) {
  const vehicles = ref([])
  const pagination = ref(null)
  const loading = ref(false)
  const error = ref(null)

  let pendingRequest = null

  async function load() {
    pendingRequest?.abort()
    const request = new AbortController()
    pendingRequest = request

    loading.value = true
    error.value = null

    try {
      const { data, meta } = await fetchVehicles(
        { brands: brands.value, page: page.value },
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

  watch([brands, page], load, { immediate: true })

  return { vehicles, pagination, loading, error, reload: load }
}
