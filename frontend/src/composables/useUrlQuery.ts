import { onBeforeUnmount, ref, watch } from 'vue'

interface UrlQuery {
  brand: string | null
  page: number
}

function readQuery(): UrlQuery {
  const params = new URLSearchParams(window.location.search)
  const page = Number.parseInt(params.get('page') ?? '', 10)

  return {
    brand: params.get('brand') || null,
    page: Number.isInteger(page) && page > 0 ? page : 1,
  }
}

export function useUrlQuery() {
  const initial = readQuery()
  const brand = ref(initial.brand)
  const page = ref(initial.page)

  let isRestoringHistory = false

  watch([brand, page], () => {
    if (isRestoringHistory) {
      isRestoringHistory = false
      return
    }

    const url = new URL(window.location.href)
    url.searchParams.delete('brand')
    url.searchParams.delete('page')

    if (brand.value) {
      url.searchParams.set('brand', brand.value)
    }
    if (page.value > 1) {
      url.searchParams.set('page', String(page.value))
    }

    if (url.href !== window.location.href) {
      window.history.pushState(null, '', url)
    }
  })

  function restoreFromHistory() {
    const query = readQuery()

    if (query.brand !== brand.value || query.page !== page.value) {
      isRestoringHistory = true
      brand.value = query.brand
      page.value = query.page
    }
  }

  window.addEventListener('popstate', restoreFromHistory)
  onBeforeUnmount(() => window.removeEventListener('popstate', restoreFromHistory))

  return { brand, page }
}
