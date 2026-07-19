import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ResetPage from '../../../app/pages/user/reset.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// Legacy reset-password.blade.php validated the recovery token server-side
// on load ($valid_code) - a stale/expired/used link showed an error, not a
// fillable form - and pre-filled a disabled account-email input (fp_email)
// so the user can confirm which account they're resetting. These specs pin
// that behaviour for the GET /api/v2/auth/password/recovery/{token} port.

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

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

function mountReset(query = { recovery: 'tok-1' }, recoveryInfo = vi.fn()) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  vi.stubGlobal('useRoute', () => ({ query, params: {}, fullPath: '/user/reset' }))
  vi.stubGlobal('useNuxtApp', () => ({ $api: { auth: { recoveryInfo } } }))

  return mount(ResetPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, ...bvnStubs },
    },
  })
}

describe('pages/user/reset', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows a loading state while the recovery token is being validated', () => {
    const recoveryInfo = vi.fn(() => new Promise(() => {}))
    const wrapper = mountReset({ recovery: 'tok-1' }, recoveryInfo)

    expect(wrapper.find('[data-testid="reset-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="reset-form"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="reset-invalid-code"]').exists()).toBe(false)
  })

  it('shows the invalid-code state without calling the API when the recovery param is missing', async () => {
    const recoveryInfo = vi.fn()
    const wrapper = mountReset({}, recoveryInfo)
    await flushPromises()

    expect(recoveryInfo).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="reset-invalid-code"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="reset-form"]').exists()).toBe(false)
  })

  it('shows the invalid-code state when the endpoint reports the token invalid', async () => {
    const recoveryInfo = vi.fn().mockResolvedValue({ data: { valid: false, email: null } })
    const wrapper = mountReset({ recovery: 'tok-1' }, recoveryInfo)
    await flushPromises()

    expect(recoveryInfo).toHaveBeenCalledWith('tok-1')
    expect(wrapper.find('[data-testid="reset-invalid-code"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="reset-form"]').exists()).toBe(false)
  })

  it('shows the invalid-code state when the lookup errors', async () => {
    const recoveryInfo = vi.fn().mockRejectedValue({ status: 404 })
    const wrapper = mountReset({ recovery: 'tok-1' }, recoveryInfo)
    await flushPromises()

    expect(wrapper.find('[data-testid="reset-invalid-code"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="reset-form"]').exists()).toBe(false)
  })

  it('renders a disabled, pre-filled email field and the reset form when the token is valid', async () => {
    const recoveryInfo = vi.fn().mockResolvedValue({ data: { valid: true, email: 'jane@bloggs.net' } })
    const wrapper = mountReset({ recovery: 'tok-1' }, recoveryInfo)
    await flushPromises()

    expect(wrapper.find('[data-testid="reset-invalid-code"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="reset-form"]').exists()).toBe(true)

    const emailInput = wrapper.find('[data-testid="reset-email"]')
    expect(emailInput.element.value).toBe('jane@bloggs.net')
    expect(emailInput.attributes('disabled')).toBeDefined()
  })

  it('submits the new password via the auth store once the token has validated', async () => {
    const recoveryInfo = vi.fn().mockResolvedValue({ data: { valid: true, email: 'jane@bloggs.net' } })
    const wrapper = mountReset({ recovery: 'tok-1' }, recoveryInfo)
    await flushPromises()

    const authStore = useAuthStore()
    authStore.resetPassword = vi.fn().mockResolvedValue({})

    await wrapper.find('[data-testid="reset-password"]').setValue('newpass123')
    await wrapper.find('[data-testid="reset-password-confirmation"]').setValue('newpass123')
    await wrapper.find('[data-testid="reset-form"]').trigger('submit')
    await flushPromises()

    expect(authStore.resetPassword).toHaveBeenCalledWith({
      recovery: 'tok-1',
      password: 'newpass123',
      password_confirmation: 'newpass123',
    })
    expect(wrapper.find('[data-testid="reset-success"]').exists()).toBe(true)
  })
})
