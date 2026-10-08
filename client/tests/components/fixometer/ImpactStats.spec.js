import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import ImpactStats from '../../../app/components/fixometer/ImpactStats.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(ImpactStats, {
    props: { impactData: null, ...props },
    global: { plugins: [i18n] },
  })
}

function impactData(overrides = {}) {
  return {
    participants: 120,
    hours_volunteered: 8766, // -> 10 years volunteered
    items_fixed: 300,
    waste_powered: 1000,
    waste_unpowered: 500,
    waste_total: 1500,
    co2_powered: 4000,
    co2_unpowered: 2000,
    co2_total: 6000,
    fixed_powered: 200,
    fixed_unpowered: 100,
    total_powered: 250,
    total_unpowered: 150,
    ...overrides,
  }
}

describe('components/fixometer/ImpactStats', () => {
  it('shows a loading state and no stats while loading', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="impact-stats-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="impact-stat-participants"]').exists()).toBe(false)
  })

  it('shows the unavailable state on error or missing data', () => {
    const wrapper = mountComponent({ error: true })
    expect(wrapper.find('[data-testid="impact-stats-unavailable"]').exists()).toBe(true)

    const wrapper2 = mountComponent({ impactData: null })
    expect(wrapper2.find('[data-testid="impact-stats-unavailable"]').exists()).toBe(true)
  })

  it('renders participants and years volunteered (round(10 * hours / 8766) / 10)', () => {
    const wrapper = mountComponent({ impactData: impactData() })
    expect(wrapper.find('[data-testid="impact-stat-participants"]').text()).toContain('120')
    // 10 * 8766 / 8766 / 10 = 1.0
    expect(wrapper.find('[data-testid="impact-stat-years-volunteered"]').text()).toContain('1')
  })

  it('renders waste and co2 rounded to tonnes (value / 1000)', () => {
    const wrapper = mountComponent({ impactData: impactData({ waste_total: 2500, co2_total: 7400 }) })
    expect(wrapper.find('[data-testid="impact-stat-waste"]').text()).toContain('3')
    expect(wrapper.find('[data-testid="impact-stat-co2"]').text()).toContain('7')
  })

  it('renders powered/unpowered fixed item counts', () => {
    const wrapper = mountComponent({ impactData: impactData({ fixed_powered: 42, fixed_unpowered: 7 }) })
    expect(wrapper.find('[data-testid="impact-stat-powered"]').text()).toContain('42')
    expect(wrapper.find('[data-testid="impact-stat-unpowered"]').text()).toContain('7')
  })

  it('shows the co2 equivalent comparison only when there is a non-zero co2 figure', () => {
    const wrapper = mountComponent({ impactData: impactData({ co2_total: 6000 }) })
    expect(wrapper.find('[data-testid="impact-stat-co2-equivalent"]').exists()).toBe(true)

    const wrapperZero = mountComponent({ impactData: impactData({ co2_total: 0 }) })
    expect(wrapperZero.find('[data-testid="impact-stat-co2-equivalent"]').exists()).toBe(false)
  })
})
