import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ForbiddenPage from '../../app/pages/forbidden.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BButtonStub = { template: '<button v-bind="$attrs" @click="$emit(\'click\')"><slot /></button>' }

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  return mount(ForbiddenPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BButton: BButtonStub },
    },
  })
}

describe('pages/forbidden', () => {
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  it('shows the support/bug-report guidance with a mailto link and the Discourse forum link', () => {
    const wrapper = mountPage()
    const report = wrapper.find('[data-testid="forbidden-report"]')

    expect(report.find('a[href="mailto:community@therestartproject.org"]').exists()).toBe(true)
    expect(report.find('a[href="https://talk.restarters.net/c/restarters-dev"]').exists()).toBe(true)
  })

  it('shows the diagnostic block with time/URL/previous URL but no user line when logged out', async () => {
    const wrapper = mountPage()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="forbidden-diagnostic-user"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="forbidden-diagnostic-time"]').text()).toContain('Time')
    expect(wrapper.find('[data-testid="forbidden-diagnostic-url"]').text()).toContain('URL')
    expect(wrapper.find('[data-testid="forbidden-diagnostic-previous-url"]').text()).toContain('Previous URL')
  })

  it('hides the authenticated-only recovery links when logged out', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="forbidden-recovery"]').exists()).toBe(false)
  })

  it('includes the user name in the diagnostic block and shows recovery links when logged in', async () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, name: 'Jane Bloggs' }
    authStore.logout = vi.fn().mockResolvedValue()

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="forbidden-diagnostic-user"]').text()).toContain('Jane Bloggs')

    const recovery = wrapper.find('[data-testid="forbidden-recovery"]')
    expect(recovery.exists()).toBe(true)
    expect(recovery.find('[data-testid="forbidden-back-link"]').exists()).toBe(true)
    expect(recovery.find('[data-testid="forbidden-dashboard-link"]').attributes('href')).toBe('/dashboard')
    expect(recovery.find('[data-testid="forbidden-logout-link"]').exists()).toBe(true)
  })

  it('logs out and returns to /login when the "log out and back in" action is used', async () => {
    const authStore = useAuthStore()
    authStore.token = 'tok-1'
    authStore.user = { id: 1, name: 'Jane Bloggs' }
    authStore.logout = vi.fn().mockResolvedValue()

    const wrapper = mountPage()
    await wrapper.vm.$nextTick()

    await wrapper.find('[data-testid="forbidden-logout-link"]').trigger('click')

    expect(authStore.logout).toHaveBeenCalled()
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  it('still shows the Back to home link', () => {
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="forbidden-home-link"]').attributes('href')).toBe('/')
  })
})
