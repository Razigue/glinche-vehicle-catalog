import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import PaginationNav from './PaginationNav.vue'

describe('PaginationNav', () => {
  it('is hidden when there is a single page', () => {
    const wrapper = mount(PaginationNav, { props: { modelValue: 1, lastPage: 1 } })

    expect(wrapper.find('nav').exists()).toBe(false)
  })

  it('numbers the pages around the current one and marks it as current', () => {
    const wrapper = mount(PaginationNav, { props: { modelValue: 6, lastPage: 12 } })

    expect(wrapper.findAll('li').map((item) => item.text())).toEqual(['1', '…', '5', '6', '7', '…', '12'])
    expect(wrapper.find('[aria-current="page"]').text()).toBe('6')
  })

  it('emits the clicked page and disables the arrow at the edges', async () => {
    const wrapper = mount(PaginationNav, { props: { modelValue: 1, lastPage: 3 } })

    expect(wrapper.find('button[aria-label="Page précédente"]').attributes('disabled')).toBeDefined()
    await wrapper.find('button[aria-label="Page suivante"]').trigger('click')
    await wrapper.find('button[aria-label="Page 3"]').trigger('click')

    expect(wrapper.emitted('update:modelValue')).toEqual([[2], [3]])
    expect(wrapper.find('button[aria-label="Page suivante"]').attributes('disabled')).toBeDefined()
  })
})
