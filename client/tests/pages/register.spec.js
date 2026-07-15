import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RegisterPage from '../../app/pages/user/register.vue'
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
  BFormSelect: {
    props: ['modelValue'],
    template:
      '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs"><slot /></select>',
  },
  BFormCheckbox: {
    props: ['modelValue', 'value'],
    template:
      '<input type="checkbox" v-bind="$attrs" @change="$emit(\'update:modelValue\', $event.target.checked)" /><slot />',
  },
  BButton: { template: '<button v-bind="$attrs"><slot /></button>' },
  BAlert: { template: '<div><slot /></div>' },
}

function mountRegister(query = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })
  vi.stubGlobal('useRoute', () => ({ query, params: {}, fullPath: '/user/register' }))

  return mount(RegisterPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, ...bvnStubs },
    },
  })
}

async function fillRequiredFields(wrapper) {
  await wrapper.find('[data-testid="register-name"]').setValue('Bob Fixer')
  await wrapper.find('[data-testid="register-email"]').setValue('bob@bloggs.net')
  await wrapper.find('[data-testid="register-password"]').setValue('passw0rd')
  await wrapper.find('[data-testid="register-password-confirmation"]').setValue('passw0rd')
  await wrapper.find('[data-testid="register-age"]').setValue('1990')
  await wrapper.find('[data-testid="register-country"]').setValue('GB')
}

describe('pages/user/register', () => {
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
  })

  it('does not submit when the GDPR/future-data consents are unchecked', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn()

    const wrapper = mountRegister()
    await fillRequiredFields(wrapper)
    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()

    expect(authStore.register).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="register-consent-gdpr-error"]').exists()).toBe(true)
  })

  it('submits the full payload once both consents are checked', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn().mockResolvedValue({ token: 'tok-1', user: { id: 1 } })

    const wrapper = mountRegister({ invite_code: 'abc123', invite_type: 'group' })
    await fillRequiredFields(wrapper)
    await wrapper.find('[data-testid="register-consent-gdpr"]').setValue(true)
    await wrapper.find('[data-testid="register-consent-future-data"]').setValue(true)
    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(authStore.register).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Bob Fixer',
        email: 'bob@bloggs.net',
        password: 'passw0rd',
        password_confirmation: 'passw0rd',
        consent_gdpr: true,
        consent_future_data: true,
        invite_code: 'abc123',
        invite_type: 'group',
      })
    )
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('does not call the API when the honeypot field is filled in', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn()

    const wrapper = mountRegister()
    await fillRequiredFields(wrapper)
    await wrapper.find('[data-testid="register-honeypot"]').setValue('I am a bot')
    await wrapper.find('[data-testid="register-consent-gdpr"]').setValue(true)
    await wrapper.find('[data-testid="register-consent-future-data"]').setValue(true)
    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()

    expect(authStore.register).not.toHaveBeenCalled()
  })

  it('renders 422 field errors', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn().mockRejectedValue({
      status: 422,
      data: { message: 'Validation failed', errors: { email: ['The email has already been taken.'] } },
    })

    const wrapper = mountRegister()
    await fillRequiredFields(wrapper)
    await wrapper.find('[data-testid="register-consent-gdpr"]').setValue(true)
    await wrapper.find('[data-testid="register-consent-future-data"]').setValue(true)
    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="register-email-error"]').text()).toBe(
      'The email has already been taken.'
    )
  })
})
