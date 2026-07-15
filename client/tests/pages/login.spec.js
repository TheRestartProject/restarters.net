import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LoginPage from '../../app/pages/login.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import en from '../../i18n/locales/en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

const bvnStubs = {
  BForm: { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' },
  BFormGroup: { template: '<div><slot /></div>' },
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
