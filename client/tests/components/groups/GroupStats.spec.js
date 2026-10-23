import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import GroupStats from '../../../app/components/groups/GroupStats.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(GroupStats, {
    props,
    global: { plugins: [i18n], stubs: GROUP_VIEW_STUBS },
  })
}

const STATS = {
  group_stats: {
    parties: 4,
    participants: 20,
    hours_volunteered: 30,
    waste_total: 12,
    co2_total: 34,
    dead_devices: 2,
    repairable_devices: 1,
    no_weight: 0,
  },
}

describe('components/groups/GroupStats', () => {
  it('shows a loading skeleton', () => {
    const wrapper = mountComponent({ loading: true })
    expect(wrapper.find('[data-testid="group-stats-loading"]').exists()).toBe(true)
  })

  it('shows an unavailable message when there is an error', () => {
    const wrapper = mountComponent({ error: true })
    expect(wrapper.find('[data-testid="group-stats-unavailable"]').exists()).toBe(true)
  })

  it('shows an unavailable message when stats is null (endpoint not implemented yet)', () => {
    const wrapper = mountComponent({ stats: null })
    expect(wrapper.find('[data-testid="group-stats-unavailable"]').exists()).toBe(true)
  })

  it('renders group facts', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-parties"]').text()).toContain('4')
    expect(wrapper.find('[data-testid="group-stats-participants"]').text()).toContain('20')
    expect(wrapper.find('[data-testid="group-stats-hours"]').text()).toContain('30')
  })

  it('renders waste/CO2 impact cards', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-waste"]').text()).toContain('12 kg')
    expect(wrapper.find('[data-testid="group-stats-co2"]').text()).toContain('34 kg')
  })

  it('shows an info popover next to the Environmental impact heading (gap 9)', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-impact-info"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-stats-impact-popover"]').text()).toContain('How do we calculate environmental impact?')
  })

  it('emits share-stats when the CO2 card\'s Share this link is clicked (gap 9)', async () => {
    const wrapper = mountComponent({ stats: STATS })

    await wrapper.find('[data-testid="group-stats-share"]').trigger('click')
    expect(wrapper.emitted('share-stats')).toBeTruthy()
  })

  // develop #946 (Neil, 21 Oct): the group page no longer shows the "not counting" caveat.
  it('does not show the not-counting caveat, even when devices are excluded', () => {
    const wrapper = mountComponent({ stats: STATS })

    expect(wrapper.find('[data-testid="group-stats-not-counting"]').exists()).toBe(false)
  })

  // develop #946: the CO2e figure has its "that's like..." comparison beside it, with the share link.
  it('describes the CO2e figure beside it, next to the share link', () => {
    const wrapper = mountComponent({ stats: { group_stats: { ...STATS.group_stats, co2_total: 600 } } })

    const card = wrapper.find('[data-testid="group-stats-co2"]')
    expect(card.classes()).toContain('stat-card--aside')
    expect(card.find('[data-testid="group-stats-co2-description"]').text()).toBe("that's like growing 10 tree seedlings for 10 years")
    expect(card.find('.stat-card__aside [data-testid="group-stats-share"]').exists()).toBe(true)
  })

  it('has no comparison when there is no CO2e', () => {
    const wrapper = mountComponent({ stats: { group_stats: { ...STATS.group_stats, co2_total: 0 } } })

    expect(wrapper.find('[data-testid="group-stats-co2-description"]').exists()).toBe(false)
  })

  // StatsValue.vue's printableCount(): stat-card counts get thousand
  // separators (finding 52).
  it('formats group-facts stat-card counts with thousand separators', () => {
    const wrapper = mountComponent({
      stats: { group_stats: { ...STATS.group_stats, participants: 12345 } },
    })

    expect(wrapper.find('[data-testid="group-stats-participants"]').text()).toContain('12,345')
  })
})
