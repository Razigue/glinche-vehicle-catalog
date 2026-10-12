import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BrandFilter from './BrandFilter.vue'

const brands = [
  { name: 'Peugeot', slug: 'peugeot', vehicles_count: 1 },
  { name: 'Renault', slug: 'renault', vehicles_count: 3 },
]

describe('BrandFilter', () => {
  it('lists every brand after an option for all brands with the total count', () => {
    const wrapper = mount(BrandFilter, { props: { brands, modelValue: null } })

    const labels = wrapper.findAll('button').map((button) => button.text().replace(/\s+/g, ' '))
    expect(labels).toEqual(['Toutes les marques 4', 'Peugeot 1', 'Renault 3'])
  })

  it('marks the selected brand as pressed', () => {
    const wrapper = mount(BrandFilter, { props: { brands, modelValue: 'renault' } })

    const pressed = wrapper.findAll('button').map((button) => button.attributes('aria-pressed'))
    expect(pressed).toEqual(['false', 'false', 'true'])
  })

  it('emits the slug of the clicked brand, or null for all brands', async () => {
    const wrapper = mount(BrandFilter, { props: { brands, modelValue: 'renault' } })

    await wrapper.findAll('button')[1].trigger('click')
    await wrapper.findAll('button')[0].trigger('click')

    expect(wrapper.emitted('update:modelValue')).toEqual([['peugeot'], [null]])
  })
})
