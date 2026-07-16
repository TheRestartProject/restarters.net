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
})
