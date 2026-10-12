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
  it('reads the brands and page from the URL', () => {
    window.history.replaceState(null, '', '/?brand=peugeot,renault,,peugeot&page=2')

    const { brands, page } = mountUrlQuery()

    expect(brands.value).toEqual(['peugeot', 'renault'])
    expect(page.value).toBe(2)
  })

  it('defaults to all brands on the first page for missing or invalid values', () => {
    window.history.replaceState(null, '', '/?page=abc')

    const { brands, page } = mountUrlQuery()

    expect(brands.value).toEqual([])
    expect(page.value).toBe(1)
  })

  it('writes the selection to the URL and omits the defaults', async () => {
    const { brands, page } = mountUrlQuery()

    brands.value = ['peugeot', 'renault']
    page.value = 2
    await nextTick()
    expect(window.location.search).toBe('?brand=peugeot%2Crenault&page=2')

    brands.value = []
    page.value = 1
    await nextTick()
    expect(window.location.search).toBe('')
  })

  it('replaces the current history entry when correcting the selection', async () => {
    window.history.replaceState(null, '', '/?brand=unknown&page=9')
    const historyLength = window.history.length
    const { brands, page, replace } = mountUrlQuery()

    replace({ brands: [], page: 1 })
    await nextTick()

    expect(brands.value).toEqual([])
    expect(page.value).toBe(1)
    expect(window.location.search).toBe('')
    expect(window.history.length).toBe(historyLength)
  })

  it('keeps adding history entries for user changes after a correction that changed nothing', async () => {
    const historyLength = window.history.length
    const { brands, replace } = mountUrlQuery()

    replace({ page: 1 })
    brands.value = ['renault']
    await nextTick()

    expect(window.history.length).toBe(historyLength + 1)
  })

  it('restores the selection when navigating back in the history', async () => {
    const { brands, page } = mountUrlQuery()
    brands.value = ['renault']
    await nextTick()

    window.history.replaceState(null, '', '/?brand=peugeot&page=3')
    window.dispatchEvent(new PopStateEvent('popstate'))
    await nextTick()

    const historyLength = window.history.length
    await nextTick()

    expect(brands.value).toEqual(['peugeot'])
    expect(page.value).toBe(3)
    expect(window.history.length).toBe(historyLength)
  })
})
