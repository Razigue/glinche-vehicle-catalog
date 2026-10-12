import { onMounted, ref } from 'vue'
import { fetchBrands } from '../api'

export function useBrands() {
  const brands = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function load() {
    loading.value = true
    error.value = null

    try {
      brands.value = await fetchBrands()
    } catch (exception) {
      error.value = exception
    } finally {
      loading.value = false
    }
  }

  onMounted(load)

  return { brands, loading, error, reload: load }
}
