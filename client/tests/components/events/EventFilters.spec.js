import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it, vi } from 'vitest'
import EventFilters from '../../../app/components/events/EventFilters.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// vue-datepicker-next's default export minifies to an internal component
// name rather than "DatePicker", so global.stubs (which matches by resolved
// component name) can't target it - mock the module itself instead, same
// convention as tests/components/events/EventForm.spec.js.
vi.mock('vue-datepicker-next', () => ({
  default: {
    props: ['value', 'placeholder'],
    emits: ['update:value'],
    template:
      '<input data-testid="event-filters-date-stub" :value="value" :placeholder="placeholder" @input="$emit(\'update:value\', $event.target.value)" />',
  },
}))

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

  // GroupEventsScrollTableFilters.vue's country control is a single-select
  // vue-multiselect, not a native <select> - components/forms/TagMultiselect.vue
  // is this project's stand-in (vue-multiselect is Vue 2 only).
  describe('gap 18: country + date-range filters', () => {
    it('renders a country picker built from countryOptions and emits update:country on selection', async () => {
      const wrapper = mountComponent({ search: '', country: '', countryOptions: ['France', 'UK'] })

      const picker = wrapper.findComponent('[data-testid="event-filters-country"]')
      expect(picker.exists()).toBe(true)
      expect(picker.props('options')).toEqual(['France', 'UK'])

      await picker.vm.$emit('update:modelValue', 'UK')

      expect(wrapper.emitted('update:country')).toEqual([['UK']])
    })

    it('emits update:country with an empty string when the selection is cleared', async () => {
      const wrapper = mountComponent({ search: '', country: 'UK', countryOptions: ['France', 'UK'] })

      const picker = wrapper.findComponent('[data-testid="event-filters-country"]')
      await picker.vm.$emit('update:modelValue', null)

      expect(wrapper.emitted('update:country')).toEqual([['']])
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
