import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import LoadingPlaceholder from '../../app/components/LoadingPlaceholder.vue'
import en from '../../i18n/locales/en.json'

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })
  return mount(LoadingPlaceholder, { props, global: { plugins: [i18n] } })
}

// Live's .vue-placeholder: "Loading..." text on the page background, not a grey box.
describe('components/LoadingPlaceholder', () => {
  it('says Loading...', () => {
    const wrapper = mountComponent()
    expect(wrapper.text()).toBe('Loading...')
    expect(wrapper.attributes('role')).toBe('status')
  })

  it('keeps the room it is given', () => {
    const wrapper = mountComponent({ height: '416px' })
    expect(wrapper.attributes('style')).toContain('height: 416px')
  })
})
