import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventFilters from '../../../app/components/events/EventFilters.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventFilters, { props, global: { plugins: [i18n] } })
}

describe('components/events/EventFilters', () => {
  it('renders the given search value', () => {
    const wrapper = mountComponent({ search: 'repair' })

    expect(wrapper.find('[data-testid="event-filters-search"]').element.value).toBe('repair')
  })

  it('emits update:search on input', async () => {
    const wrapper = mountComponent({ search: '' })

    const input = wrapper.find('[data-testid="event-filters-search"]')
    await input.setValue('cafe')

    expect(wrapper.emitted('update:search')).toEqual([['cafe']])
  })

  it('does not render the country or date controls by default (party/all-past.vue usage is unaffected)', () => {
    const wrapper = mountComponent({ search: '' })

    expect(wrapper.find('[data-testid="event-filters-country"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="event-filters-start"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="event-filters-end"]').exists()).toBe(false)
  })

  describe('gap 18: country + date-range filters', () => {
    it('renders a country dropdown built from countryOptions and emits update:country on change', async () => {
      const wrapper = mountComponent({ search: '', country: '', countryOptions: ['France', 'UK'] })

      const select = wrapper.find('[data-testid="event-filters-country"]')
      expect(select.exists()).toBe(true)
      expect(select.findAll('option').map((o) => o.text())).toEqual([
        en.groups.search_country_placeholder,
        'France',
        'UK',
      ])

      await select.setValue('UK')

      expect(wrapper.emitted('update:country')).toEqual([['UK']])
    })

    it('renders start/end date inputs when dateRange is set and emits update:start/update:end', async () => {
      const wrapper = mountComponent({ search: '', dateRange: true, start: '', end: '' })

      const start = wrapper.find('[data-testid="event-filters-start"]')
      const end = wrapper.find('[data-testid="event-filters-end"]')
      expect(start.attributes('type')).toBe('date')
      expect(end.attributes('type')).toBe('date')

      await start.setValue('2026-01-01')
      await end.setValue('2026-01-31')

      expect(wrapper.emitted('update:start')).toEqual([['2026-01-01']])
      expect(wrapper.emitted('update:end')).toEqual([['2026-01-31']])
    })
  })
})
