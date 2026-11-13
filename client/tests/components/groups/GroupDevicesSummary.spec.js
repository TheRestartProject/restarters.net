import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupDevicesSummary from '../../../app/components/groups/GroupDevicesSummary.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupDevicesSummary, { props, global: { plugins: [i18n] } })
}

const STATS = {
  device_stats: { fixed: 5, repairable: 3, dead: 2 },
  top_devices: [
    { name: 'Laptop', counter: 5 },
    { name: 'Toaster', counter: 3 },
    { name: 'Kettle', counter: 1 },
  ],
}

describe('components/groups/GroupDevicesSummary', () => {
  it('renders device stats and totals', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-fixed"]').text()).toContain('5')
    expect(wrapper.find('[data-testid="group-stats-repairable"]').text()).toContain('3')
    expect(wrapper.find('[data-testid="group-stats-dead"]').text()).toContain('2')
    expect(wrapper.find('[data-testid="group-stats-total"]').text()).toContain('10')
  })

  it('renders a podium (not a plain list) for the top 3 most-repaired devices (gap 6)', () => {
    const wrapper = mountComponent({ stats: STATS })

    const first = wrapper.find('[data-testid="group-stats-top-device-0"]')
    expect(first.text()).toContain('5')
    expect(first.find('img').attributes('src')).toBe('/images/rosette_1_ico.svg')

    const second = wrapper.find('[data-testid="group-stats-top-device-1"]')
    expect(second.find('img').attributes('src')).toBe('/images/rosette_2_ico.svg')

    const third = wrapper.find('[data-testid="group-stats-top-device-2"]')
    expect(third.find('img').attributes('src')).toBe('/images/rosette_3_ico.svg')
  })

  it('renders nothing for devices/top-devices when stats is null', () => {
    const wrapper = mountComponent({ stats: null })

    expect(wrapper.find('[data-testid="group-stats-devices"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="group-stats-top-device-0"]').exists()).toBe(false)
  })

  // StatsValue.vue's printableCount(): stat-card counts get thousand
  // separators (finding 52).
  it('formats stat-card counts with thousand separators', () => {
    const wrapper = mountComponent({
      stats: { device_stats: { fixed: 1234, repairable: 0, dead: 0 }, top_devices: [] },
    })

    expect(wrapper.find('[data-testid="group-stats-fixed"]').text()).toContain('1,234')
  })

  // GroupDeviceRepairPodium.vue's device.counter is NOT run through
  // StatsValue/printableCount in develop - it's a plain interpolation - so
  // the podium count should stay unformatted (finding 52's scope is the
  // StatsValue-backed cards, not this component).
  it('does not add thousand separators to the podium count (matches develop)', () => {
    const wrapper = mountComponent({
      stats: {
        device_stats: null,
        top_devices: [{ name: 'Laptop', counter: 1234 }],
      },
    })

    expect(wrapper.find('[data-testid="group-stats-top-device-0"]').text()).toContain('1234')
    expect(wrapper.find('[data-testid="group-stats-top-device-0"]').text()).not.toContain('1,234')
  })

  // GroupDevicesMostRepaired.vue's `.d-block.d-md-none` +
  // GroupDeviceRepairPodium.vue's `.mobile` grid: mobile gets its own flat,
  // full-width rosette/count/name rows in natural 1st/2nd/3rd order (finding
  // 50), distinct from the staggered desktop podium.
  it('renders a flat mobile podium counterpart in natural 1st/2nd/3rd order', () => {
    const wrapper = mountComponent({ stats: STATS })

    const first = wrapper.find('[data-testid="group-stats-top-device-mobile-0"]')
    expect(first.text()).toContain('5')
    expect(first.text()).toContain('Laptop')
    expect(first.find('img').attributes('src')).toBe('/images/rosette_1_ico.svg')

    const second = wrapper.find('[data-testid="group-stats-top-device-mobile-1"]')
    expect(second.find('img').attributes('src')).toBe('/images/rosette_2_ico.svg')

    const third = wrapper.find('[data-testid="group-stats-top-device-mobile-2"]')
    expect(third.find('img').attributes('src')).toBe('/images/rosette_3_ico.svg')
  })
})
