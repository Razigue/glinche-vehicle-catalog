import { onBeforeUnmount, ref, watch } from 'vue'

interface UrlQuery {
  brand: string | null
  page: number
}

type HistoryAction = 'push' | 'replace' | 'none'

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

  let nextHistoryAction: HistoryAction = 'push'

  function assign(query: UrlQuery, historyAction: HistoryAction) {
    if (query.brand === brand.value && query.page === page.value) {
      return
    }

    nextHistoryAction = historyAction
    brand.value = query.brand
    page.value = query.page
  }

  watch([brand, page], () => {
    const historyAction = nextHistoryAction
    nextHistoryAction = 'push'

    if (historyAction === 'none') {
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

    if (url.href === window.location.href) {
      return
    }

    if (historyAction === 'replace') {
      window.history.replaceState(null, '', url)
    } else {
      window.history.pushState(null, '', url)
    }
  })

  function restoreFromHistory() {
    assign(readQuery(), 'none')
  }

  function replace(query: Partial<UrlQuery>) {
    assign({ brand: brand.value, page: page.value, ...query }, 'replace')
  }

  window.addEventListener('popstate', restoreFromHistory)
  onBeforeUnmount(() => window.removeEventListener('popstate', restoreFromHistory))

  return { brand, page, replace }
}
