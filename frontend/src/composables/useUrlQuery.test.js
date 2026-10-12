import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import { useUrlQuery } from './useUrlQuery'

function mountUrlQuery() {
  let query
  const wrapper = mount({
    setup() {
      query = useUrlQuery()
      return () => null
    },
  })

  return { wrapper, ...query }
}

afterEach(() => {
  window.history.replaceState(null, '', '/')
})

describe('useUrlQuery', () => {
  it('reads the brand and page from the URL', () => {
    window.history.replaceState(null, '', '/?brand=renault&page=2')

    const { brand, page } = mountUrlQuery()

    expect(brand.value).toBe('renault')
    expect(page.value).toBe(2)
  })

  it('defaults to all brands on the first page for missing or invalid values', () => {
    window.history.replaceState(null, '', '/?page=abc')

    const { brand, page } = mountUrlQuery()

    expect(brand.value).toBeNull()
    expect(page.value).toBe(1)
  })

  it('writes the selection to the URL and omits the defaults', async () => {
    const { brand, page } = mountUrlQuery()

    brand.value = 'renault'
    page.value = 2
    await nextTick()
    expect(window.location.search).toBe('?brand=renault&page=2')

    brand.value = null
    page.value = 1
    await nextTick()
    expect(window.location.search).toBe('')
  })

  it('replaces the current history entry when correcting the selection', async () => {
    window.history.replaceState(null, '', '/?brand=unknown&page=9')
    const historyLength = window.history.length
    const { brand, page, replace } = mountUrlQuery()

    replace({ brand: null, page: 1 })
    await nextTick()

    expect(brand.value).toBeNull()
    expect(page.value).toBe(1)
    expect(window.location.search).toBe('')
    expect(window.history.length).toBe(historyLength)
  })

  it('keeps adding history entries for user changes after a correction that changed nothing', async () => {
    const historyLength = window.history.length
    const { brand, replace } = mountUrlQuery()

    replace({ page: 1 })
    brand.value = 'renault'
    await nextTick()

    expect(window.history.length).toBe(historyLength + 1)
  })

  it('restores the selection when navigating back in the history', async () => {
    const { brand, page } = mountUrlQuery()
    brand.value = 'renault'
    await nextTick()

    window.history.replaceState(null, '', '/?brand=peugeot&page=3')
    window.dispatchEvent(new PopStateEvent('popstate'))
    await nextTick()

    const historyLength = window.history.length
    await nextTick()

    expect(brand.value).toBe('peugeot')
    expect(page.value).toBe(3)
    expect(window.history.length).toBe(historyLength)
  })
})
