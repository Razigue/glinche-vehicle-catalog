import { onBeforeUnmount, ref, watch } from 'vue'

function readQuery() {
  const params = new URLSearchParams(window.location.search)
  const page = Number.parseInt(params.get('page') ?? '', 10)

  return {
    brands: [...new Set((params.get('brand') ?? '').split(',').filter(Boolean))],
    page: Number.isInteger(page) && page > 0 ? page : 1,
  }
}

export function useUrlQuery() {
  const initial = readQuery()
  const brands = ref(initial.brands)
  const page = ref(initial.page)

  let nextHistoryAction = 'push'

  function assign(query, historyAction) {
    if (query.brands.join(',') === brands.value.join(',') && query.page === page.value) {
      return
    }

    nextHistoryAction = historyAction
    brands.value = query.brands
    page.value = query.page
  }

  watch([brands, page], () => {
    const historyAction = nextHistoryAction
    nextHistoryAction = 'push'

    if (historyAction === 'none') {
      return
    }

    const url = new URL(window.location.href)
    url.searchParams.delete('brand')
    url.searchParams.delete('page')

    if (brands.value.length > 0) {
      url.searchParams.set('brand', brands.value.join(','))
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

  function replace(query) {
    assign({ brands: brands.value, page: page.value, ...query }, 'replace')
  }

  window.addEventListener('popstate', restoreFromHistory)
  onBeforeUnmount(() => window.removeEventListener('popstate', restoreFromHistory))

  return { brands, page, replace }
}
