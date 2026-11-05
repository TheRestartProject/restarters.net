import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import IndexPage from '../../app/pages/index.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(IndexPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub },
    },
  })
}

describe('pages/index', () => {
  let capturedMeta

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('navigateTo', vi.fn())

    // definePageMeta is stubbed to a no-op in tests/setup.ts (Nuxt's real
    // route middleware pipeline never runs under Vitest - it's the
    // router, not the component's setup(), that invokes
    // meta.middleware[] on navigation). Capture what the page passed so
    // the redirect closure can be invoked directly, the same way Nuxt
    // would on navigation to "/".
    capturedMeta = null
    vi.stubGlobal('definePageMeta', (meta) => {
      capturedMeta = meta
      return meta
    })
  })

  it('renders the guest landing content, preserving the legacy h2 wording/testid (landingpage.test.js)', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="home-page"]').exists()).toBe(true)
    const firstH2 = wrapper.find('h2')
    expect(firstH2.text()).toBe('Learn and share repair skills with others')
    expect(firstH2.attributes('data-testid')).toBe('landing-learn-heading')
  })

  it('shows join/login calls to action for a guest', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="landing-join"]').attributes('href')).toBe('/user/register')
    expect(wrapper.find('[data-testid="landing-login"]').attributes('href')).toBe('/login')
  })

  it('route middleware redirects a logged-in visitor to /dashboard', () => {
    const navigateToMock = vi.fn().mockReturnValue('redirected')
    vi.stubGlobal('navigateTo', navigateToMock)

    mountPage()
    useAuthStore().token = 'tok-1'

    const [middleware] = capturedMeta.middleware
    const result = middleware()

    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
    expect(result).toBe('redirected')
  })

  it('route middleware is a no-op for a guest', () => {
    const navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)

    mountPage()

    const [middleware] = capturedMeta.middleware
    middleware()

    expect(navigateToMock).not.toHaveBeenCalled()
  })
})
