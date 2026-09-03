import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventDevicesReadOnly from '../../../app/components/events/EventDevicesReadOnly.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

function device(overrides = {}) {
  return {
    id: 1,
    item_type: 'Toaster',
    category: { name: 'devices.category_toasters', powered: true },
    brand: 'Acme',
    age: '3',
    short_problem: 'Wont heat',
    repair_status: 'Fixed',
    spare_parts: 'No',
    ...overrides,
  }
}

function mountComponent(props = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: { ...en, ...clientEn, devices: { ...en.devices, category_toasters: 'Toasters' } },
    },
  })

  return mount(EventDevicesReadOnly, {
    props: { devices: [], ...props },
    global: { plugins: [i18n], stubs: { BBadge: BBadgeStub } },
  })
}

describe('components/events/EventDevicesReadOnly', () => {
  it('shows a loading skeleton and no tabs while loading', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="event-devices-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-devices-tab-powered"]').exists()).toBe(false)
  })

  it('splits devices into powered/unpowered tabs by category.powered', async () => {
    const powered = device({ id: 1, category: { name: 'x', powered: true } })
    const unpowered = device({ id: 2, category: { name: 'x', powered: false } })
    const wrapper = mountComponent({ devices: [powered, unpowered] })

    expect(wrapper.find('[data-testid="event-device-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-device-2"]').exists()).toBe(false)

    await wrapper.find('[data-testid="event-devices-tab-unpowered"]').trigger('click')

    expect(wrapper.find('[data-testid="event-device-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-device-1"]').exists()).toBe(false)
  })

  it('shows the empty state when a tab has no devices', () => {
    const wrapper = mountComponent({ devices: [] })
    expect(wrapper.find('[data-testid="event-devices-empty"]').exists()).toBe(true)
  })

  it('maps repair_status to a translated label and badge variant', () => {
    const fixed = device({ id: 1, repair_status: 'Fixed' })
    const repairable = device({ id: 2, repair_status: 'Repairable' })
    const endOfLife = device({ id: 3, repair_status: 'End of life' })

    const wrapper = mountComponent({ devices: [fixed, repairable, endOfLife] })

    expect(wrapper.find('[data-testid="event-device-status-1"]').text()).toBe('Fixed')
    expect(wrapper.find('[data-testid="event-device-status-1"]').attributes('variant')).toBe('success')

    // repairable/endOfLife devices are unpowered=false by default (category.powered true here),
    // so they remain visible on the powered tab too since all three share category.powered: true.
    expect(wrapper.find('[data-testid="event-device-status-2"]').text()).toBe('Repairable')
    expect(wrapper.find('[data-testid="event-device-status-2"]').attributes('variant')).toBe('warning')
    expect(wrapper.find('[data-testid="event-device-status-3"]').text()).toBe('End')
    expect(wrapper.find('[data-testid="event-device-status-3"]').attributes('variant')).toBe('danger')
  })

  it('shows a spare-parts marker only when spare_parts needs sourcing', () => {
    const manufacturer = device({ id: 1, spare_parts: 'Manufacturer' })
    const none = device({ id: 2, spare_parts: 'No' })
    const wrapper = mountComponent({ devices: [manufacturer, none] })

    expect(wrapper.find('[data-testid="event-device-spare-parts-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-device-spare-parts-2"]').exists()).toBe(false)
  })

  it('shows the brand column only on the powered tab', async () => {
    const powered = device({ id: 1, category: { name: 'x', powered: true }, brand: 'Acme' })
    const wrapper = mountComponent({ devices: [powered] })

    expect(wrapper.find('[data-testid="event-devices-table-powered"]').text()).toContain('Acme')

    await wrapper.find('[data-testid="event-devices-tab-unpowered"]').trigger('click')
    expect(wrapper.find('[data-testid="event-devices-table-unpowered"]').text()).not.toContain('Acme')
  })
})
