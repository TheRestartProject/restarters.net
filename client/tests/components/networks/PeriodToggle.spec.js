import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import PeriodToggle from '../../../app/components/networks/PeriodToggle.vue'
import en from '../../../i18n/locales/en.json'

function mountIt(modelValue) {
  return mount(PeriodToggle, {
    props: modelValue === undefined ? {} : { modelValue },
    global: { plugins: [createI18n({ legacy: false, locale: 'en', messages: { en } })] },
  })
}

describe('components/networks/PeriodToggle', () => {
  it('defaults to all time', () => {
    const w = mountIt()

    expect(w.get('[data-testid="period-all"]').classes()).toContain('active')
    expect(w.get('[data-testid="period-all"]').attributes('aria-pressed')).toBe('true')
    expect(w.get('[data-testid="period-last12"]').classes()).not.toContain('active')
    expect(w.get('[data-testid="period-all"]').text()).toBe('All time')
    expect(w.get('[data-testid="period-last12"]').text()).toBe('Last 12 months')
    expect(w.get('[role="group"]').attributes('aria-label')).toBe('Period')
  })

  it('shows the current period as pressed and emits the chosen one', async () => {
    const w = mountIt('last_12_months')
    expect(w.get('[data-testid="period-last12"]').classes()).toContain('active')

    await w.get('[data-testid="period-all"]').trigger('click')
    expect(w.emitted('update:modelValue')[0]).toEqual(['all'])

    await w.get('[data-testid="period-last12"]').trigger('click')
    expect(w.emitted('update:modelValue')[1]).toEqual(['last_12_months'])
  })
})
