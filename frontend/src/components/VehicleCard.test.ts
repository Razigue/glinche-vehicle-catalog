import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import placeholderUrl from '../assets/vehicle-placeholder.svg'
import type { Vehicle } from '../types'
import VehicleCard from './VehicleCard.vue'

const vehicle: Vehicle = {
  id: 1,
  reference: 'GLI00004457',
  brand: { name: 'Toyota', slug: 'toyota' },
  model: 'YARIS CROSS',
  version: 'Hybride 130h Design 2WD',
  year: 2024,
  mileage: 20,
  energy: 'Hybride essence',
  gearbox: 'Automatique',
  price_in_cents: 2_670_000,
  photo_url: 'https://glinche.test/pictures/main.jpg',
}

describe('VehicleCard', () => {
  it('displays the main vehicle information', () => {
    const text = mount(VehicleCard, { props: { vehicle } }).text().replace(/[  ]/g, ' ')

    for (const expected of ['Toyota', 'YARIS CROSS', 'Hybride 130h Design 2WD', '2024', '20 km', 'Hybride essence', 'Automatique', '26 700 €', 'GLI00004457']) {
      expect(text).toContain(expected)
    }
  })

  it('falls back to a placeholder when the photo cannot be loaded', async () => {
    const wrapper = mount(VehicleCard, { props: { vehicle } })

    await wrapper.find('img').trigger('error')

    expect(wrapper.find('img').attributes('src')).toBe(placeholderUrl)
  })

  it('uses the placeholder when the vehicle has no photo', () => {
    const wrapper = mount(VehicleCard, { props: { vehicle: { ...vehicle, photo_url: null } } })

    expect(wrapper.find('img').attributes('src')).toBe(placeholderUrl)
  })

  it('shows a price on request when the price is unknown', () => {
    const wrapper = mount(VehicleCard, { props: { vehicle: { ...vehicle, price_in_cents: null } } })

    expect(wrapper.text()).toContain('Prix sur demande')
  })
})
