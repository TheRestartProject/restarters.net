import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LocaleSwitcher from '../../app/components/LocaleSwitcher.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

function mountComponent(fakeI18n, options = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  vi.stubGlobal('useNuxtApp', () => ({ $i18n: fakeI18n }))

  return mount(LocaleSwitcher, {
    global: { plugins: [i18n] },
    ...options,
  })
}

describe('components/LocaleSwitcher', () => {
  let fakeI18n

  beforeEach(() => {
    setActivePinia(createPinia())

    fakeI18n = {
      locale: { value: 'en' },
      setLocale: vi.fn().mockImplementation(async (code) => {
        fakeI18n.locale.value = code
      }),
    }
  })

  it('shows the current locale\'s native name on the toggle', () => {
    const wrapper = mountComponent(fakeI18n)
    expect(wrapper.find('[data-testid="locale-toggle"]').text()).toBe('English')
  })

  it('opens the menu on toggle, listing en/fr/fr-BE only', async () => {
    const wrapper = mountComponent(fakeI18n)

    expect(wrapper.find('[data-testid="locale-menu"]').exists()).toBe(false)
    await wrapper.find('[data-testid="locale-toggle"]').trigger('click')

    expect(wrapper.find('[data-testid="locale-option-en"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="locale-option-fr"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="locale-option-fr-BE"]').exists()).toBe(true)
  })

  // As Bootstrap's dropdown on live.
  it('closes on a click elsewhere on the page, but not on a click inside it', async () => {
    const wrapper = mountComponent(fakeI18n, { attachTo: document.body })
    await wrapper.find('[data-testid="locale-toggle"]').trigger('click')

    wrapper.find('[data-testid="locale-menu"]').element.click()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('[data-testid="locale-menu"]').exists()).toBe(true)

    document.body.click()
    await wrapper.vm.$nextTick()
    expect(wrapper.find('[data-testid="locale-menu"]').exists()).toBe(false)
    wrapper.unmount()
  })

  it('closes on Escape', async () => {
    const wrapper = mountComponent(fakeI18n, { attachTo: document.body })
    await wrapper.find('[data-testid="locale-toggle"]').trigger('click')

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await wrapper.vm.$nextTick()
    expect(wrapper.find('[data-testid="locale-menu"]').exists()).toBe(false)
    wrapper.unmount()
  })

  it('choosing a locale calls setLocale and does not touch the session when logged out', async () => {
    const wrapper = mountComponent(fakeI18n)
    const sessionStore = useSessionStore()
    const saveLocaleSpy = vi.spyOn(sessionStore, 'saveLocale')

    await wrapper.find('[data-testid="locale-toggle"]').trigger('click')
    await wrapper.find('[data-testid="locale-option-fr"]').trigger('click')

    expect(fakeI18n.setLocale).toHaveBeenCalledWith('fr')
    expect(saveLocaleSpy).not.toHaveBeenCalled()
    // Menu closes after choosing.
    expect(wrapper.find('[data-testid="locale-menu"]').exists()).toBe(false)
  })

  it('choosing a locale while logged in also PATCHes /api/v2/session', async () => {
    useAuthStore().token = 'tok-1'
    useAuthStore().user = { id: 1, name: 'Jane' }

    const wrapper = mountComponent(fakeI18n)
    const sessionStore = useSessionStore()
    const saveLocaleSpy = vi.spyOn(sessionStore, 'saveLocale').mockResolvedValue()

    await wrapper.find('[data-testid="locale-toggle"]').trigger('click')
    await wrapper.find('[data-testid="locale-option-fr-BE"]').trigger('click')

    expect(fakeI18n.setLocale).toHaveBeenCalledWith('fr-BE')
    expect(saveLocaleSpy).toHaveBeenCalledWith('fr-BE')
  })

  it('choosing the already-active locale is a no-op', async () => {
    const wrapper = mountComponent(fakeI18n)

    await wrapper.find('[data-testid="locale-toggle"]').trigger('click')
    await wrapper.find('[data-testid="locale-option-en"]').trigger('click')

    expect(fakeI18n.setLocale).not.toHaveBeenCalled()
  })
})
