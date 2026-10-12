import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import BrandFilter from './BrandFilter.vue'

const brands = [
  { name: 'Peugeot', slug: 'peugeot', vehicles_count: 1 },
  { name: 'Renault', slug: 'renault', vehicles_count: 3 },
  { name: 'Toyota', slug: 'toyota', vehicles_count: 2 },
]

function mountFilter(modelValue) {
  return mount(BrandFilter, { props: { brands, modelValue }, attachTo: document.body })
}

const trigger = (wrapper) => wrapper.find('button[aria-expanded]')

describe('BrandFilter', () => {
  it('summarizes the selection on the dropdown button', () => {
    expect(trigger(mountFilter([])).text()).toContain('Toutes les marques')
    expect(trigger(mountFilter(['renault'])).text()).toContain('Renault')
    expect(trigger(mountFilter(['peugeot', 'toyota'])).text()).toContain('2 marques')
  })

  it('lists every brand with its vehicle count and checks the selected ones', async () => {
    const wrapper = mountFilter(['renault'])

    await trigger(wrapper).trigger('click')

    expect(trigger(wrapper).attributes('aria-expanded')).toBe('true')
    expect(wrapper.findAll('label').map((label) => label.findAll('span').map((span) => span.text()).join(' '))).toEqual([
      'Peugeot 1',
      'Renault 3',
      'Toyota 2',
    ])
    expect(wrapper.findAll('input').map((input) => input.element.checked)).toEqual([false, true, false])
  })

  it('emits the selected slugs in the order of the brand list', async () => {
    const wrapper = mountFilter(['toyota'])

    await wrapper.findAll('input')[0].trigger('change')

    expect(wrapper.emitted('update:modelValue')).toEqual([[['peugeot', 'toyota']]])
  })

  it('removes a brand from its chip and clears the whole selection', async () => {
    const wrapper = mountFilter(['peugeot', 'renault'])

    await wrapper.find('button[aria-label="Retirer Peugeot"]').trigger('click')
    await wrapper.findAll('button').find((button) => button.text() === 'Tout effacer').trigger('click')

    expect(wrapper.emitted('update:modelValue')).toEqual([[['renault']], [[]]])
  })

  it('closes on Escape and on a click outside', async () => {
    const wrapper = mountFilter([])

    await trigger(wrapper).trigger('click')
    await wrapper.find('input').trigger('keydown', { key: 'Escape' })
    expect(trigger(wrapper).attributes('aria-expanded')).toBe('false')

    await trigger(wrapper).trigger('click')
    document.body.dispatchEvent(new Event('pointerdown', { bubbles: true }))
    await wrapper.vm.$nextTick()
    expect(trigger(wrapper).attributes('aria-expanded')).toBe('false')
  })
})
