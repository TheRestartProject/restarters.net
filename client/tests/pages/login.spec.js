import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LoginPage from '../../app/pages/login.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import en from '../../i18n/locales/en.json'

const { goTo } = vi.hoisted(() => ({ goTo: vi.fn() }))
vi.mock('../../app/composables/useSsoBridge.js', () => ({ useSsoBridge: () => ({ goTo }) }))

const NuxtLinkStub = {
  props: ['to'],
  // `to` can be a plain path string or a { path, query } object (e.g. the
  // "Create account" link forwards route.query) - render both the same way
  // a real router-link would, so tests can assert on the resolved href.
  template: '<a :href="hrefFor(to)"><slot /></a>',
  methods: {
    hrefFor(to) {
      if (typeof to === 'string') return to
      const qs = new URLSearchParams(to.query || {}).toString()
      return qs ? `${to.path}?${qs}` : to.path
    },
  },
}

const bvnStubs = {
  BForm: { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' },
  BFormGroup: { template: '<div><slot name="label" /><slot /></div>' },
  BFormInput: {
    props: ['modelValue'],
    template:
      '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs" />',
  },
  BButton: { template: '<button v-bind="$attrs"><slot /></button>' },
  BAlert: { template: '<div><slot /></div>' },
}

function mountLogin(query = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })
  vi.stubGlobal('useRoute', () => ({ query, params: {}, fullPath: '/login' }))

  return mount(LoginPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, ...bvnStubs },
    },
  })
}

describe('pages/login', () => {
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  it('submits email/password to the auth store and redirects to /dashboard by default', async () => {
    const authStore = useAuthStore()
    authStore.login = vi.fn().mockResolvedValue({ token: 'tok-1', user: { id: 1 } })

    const wrapper = mountLogin()

    await wrapper.find('[data-testid="login-email"]').setValue('jane@bloggs.net')
    await wrapper.find('[data-testid="login-password"]').setValue('passw0rd')
    await wrapper.find('[data-testid="login-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(authStore.login).toHaveBeenCalledWith(
      expect.objectContaining({ email: 'jane@bloggs.net', password: 'passw0rd' })
    )
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('honours the redirect query param on success', async () => {
    const authStore = useAuthStore()
    authStore.login = vi.fn().mockResolvedValue({ token: 'tok-1', user: { id: 1 } })

    const wrapper = mountLogin({ redirect: '/group/view/9' })

    await wrapper.find('[data-testid="login-email"]').setValue('jane@bloggs.net')
    await wrapper.find('[data-testid="login-password"]').setValue('passw0rd')
    await wrapper.find('[data-testid="login-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(navigateToMock).toHaveBeenCalledWith('/group/view/9')
  })

  // Talk's own "Log in" button: Discourse asks /discourse/sso, which sends a
  // browser with no Laravel web session here with redirect=<that SSO URL>
  // (AuthenticateRedirectingToFrontend). The SPA login is token-only, so the
  // way back to Talk is through the SSO bridge, which makes the web session.
  describe('redirect back to Talk', () => {
    const SSO_URL = 'https://restarters.net/discourse/sso?sso=abc&sig=def'

    beforeEach(() => {
      goTo.mockReset()
      vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: 'https://restarters.net' } }))
    })

    async function logIn(wrapper) {
      await wrapper.find('[data-testid="login-email"]').setValue('jane@bloggs.net')
      await wrapper.find('[data-testid="login-password"]').setValue('passw0rd')
      await wrapper.find('[data-testid="login-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()
    }

    it('goes back to Talk through the SSO bridge after logging in', async () => {
      useAuthStore().login = vi.fn().mockResolvedValue({ token: 'tok-1', user: { id: 1 } })

      const wrapper = mountLogin({ redirect: SSO_URL })
      await logIn(wrapper)

      expect(goTo).toHaveBeenCalledWith(SSO_URL)
      expect(navigateToMock).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="login-error"]').exists()).toBe(false)
    })

    it('goes straight back to Talk when already logged in to the app', async () => {
      useAuthStore().token = 'tok-1'

      mountLogin({ redirect: SSO_URL })
      await Promise.resolve()

      expect(goTo).toHaveBeenCalledWith(SSO_URL)
    })

    it('does not leave a logged-in user on the login page for an app path', async () => {
      useAuthStore().token = 'tok-1'

      mountLogin({ redirect: '/group/view/9' })
      await Promise.resolve()

      expect(goTo).not.toHaveBeenCalled()
    })

    it('ignores a redirect to any other site', async () => {
      useAuthStore().login = vi.fn().mockResolvedValue({ token: 'tok-1', user: { id: 1 } })

      const wrapper = mountLogin({ redirect: 'https://evil.example.com/discourse/sso?sso=abc' })
      await logIn(wrapper)

      expect(goTo).not.toHaveBeenCalled()
      expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
    })
  })

  it('passes invite_code/invite_type/invite_hash query params through to login()', async () => {
    const authStore = useAuthStore()
    authStore.login = vi.fn().mockResolvedValue({ token: 'tok-1', user: { id: 1 } })

    const wrapper = mountLogin({
      invite_code: 'abc123',
      invite_type: 'group',
      invite_hash: 'deadbeef',
    })

    await wrapper.find('[data-testid="login-email"]').setValue('jane@bloggs.net')
    await wrapper.find('[data-testid="login-password"]').setValue('passw0rd')
    await wrapper.find('[data-testid="login-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(authStore.login).toHaveBeenCalledWith(
      expect.objectContaining({
        invite_code: 'abc123',
        invite_type: 'group',
        invite_hash: 'deadbeef',
      })
    )
  })

  it('forwards invite/redirect query params through the "Create account" link', () => {
    const wrapper = mountLogin({
      invite_code: 'abc123',
      invite_type: 'group',
      invite_hash: 'deadbeef',
      redirect: '/group/invite/abc123',
    })

    expect(wrapper.find('[data-testid="login-register-link"]').attributes('href')).toBe(
      '/user/register?invite_code=abc123&invite_type=group&invite_hash=deadbeef&redirect=%2Fgroup%2Finvite%2Fabc123'
    )
  })

  it('links to plain /user/register when there is no invite/redirect query', () => {
    const wrapper = mountLogin()

    expect(wrapper.find('[data-testid="login-register-link"]').attributes('href')).toBe(
      '/user/register'
    )
  })

  it('renders a 422 field error against the email field', async () => {
    const authStore = useAuthStore()
    authStore.login = vi.fn().mockRejectedValue({
      status: 422,
      data: { message: 'Validation failed', errors: { email: ['The email field is required.'] } },
    })

    const wrapper = mountLogin()

    await wrapper.find('[data-testid="login-password"]').setValue('passw0rd')
    await wrapper.find('[data-testid="login-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="login-email-error"]').text()).toBe(
      'The email field is required.'
    )
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  it('does not call the API when the honeypot field is filled in', async () => {
    const authStore = useAuthStore()
    authStore.login = vi.fn()

    const wrapper = mountLogin()

    await wrapper.find('[data-testid="login-honeypot"]').setValue('I am a bot')
    await wrapper.find('[data-testid="login-email"]').setValue('jane@bloggs.net')
    await wrapper.find('[data-testid="login-password"]').setValue('passw0rd')
    await wrapper.find('[data-testid="login-form"]').trigger('submit')
    await Promise.resolve()

    expect(authStore.login).not.toHaveBeenCalled()
  })
})
