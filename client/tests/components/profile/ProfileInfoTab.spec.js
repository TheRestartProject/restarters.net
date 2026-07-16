import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ProfileInfoTab from '../../../app/components/profile/ProfileInfoTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BFormGroupStub = { template: '<div><slot /></div>' }
const BFormInputStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
}
const BFormSelectStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
}
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }

const PROFILE_RESPONSE = {
  name: 'Jane',
  email: 'jane@example.com',
  country_code: 'GB',
  location: 'London',
  age: '1990',
  gender: 'F',
  biography: 'Fixer',
  countries: [{ code: 'GB', name: 'United Kingdom' }],
  ages: ['1990'],
}

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(ProfileInfoTab, {
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BFormGroup: BFormGroupStub, BFormInput: BFormInputStub, BFormSelect: BFormSelectStub, BButton: BButtonStub, BForm: BFormStub },
    },
  })
}

describe('components/profile/ProfileInfoTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
  })

  it('fetches GET /users/me/profile on mount and pre-fills the form', async () => {
    profileStore.fetchProfileInfo = vi.fn().mockResolvedValue(PROFILE_RESPONSE)
    profileStore.info.data = PROFILE_RESPONSE

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.fetchProfileInfo).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[data-testid="profile-name"]').element.value).toBe('Jane')
    expect(wrapper.find('[data-testid="profile-town-city"]').element.value).toBe('London')
  })

  it('sends the exact legacy field names (name/email/country/townCity/age/gender/biography) on save', async () => {
    profileStore.fetchProfileInfo = vi.fn().mockResolvedValue(PROFILE_RESPONSE)
    profileStore.info.data = PROFILE_RESPONSE
    profileStore.updateProfileInfo = vi.fn().mockResolvedValue(PROFILE_RESPONSE)

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.find('[data-testid="profile-name"]').setValue('Jane Doe')
    await wrapper.find('[data-testid="profile-info-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.updateProfileInfo).toHaveBeenCalledWith({
      name: 'Jane Doe',
      email: 'jane@example.com',
      country: 'GB',
      townCity: 'London',
      age: '1990',
      gender: 'F',
      biography: 'Fixer',
    })
  })

  it('renders 422 field errors returned by the server', async () => {
    profileStore.fetchProfileInfo = vi.fn().mockResolvedValue(PROFILE_RESPONSE)
    profileStore.info.data = PROFILE_RESPONSE
    profileStore.updateProfileInfo = vi.fn().mockRejectedValue({
      status: 422,
      data: { errors: { email: ['The email has already been taken.'] } },
    })

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.find('[data-testid="profile-info-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="profile-email-error"]').text()).toBe('The email has already been taken.')
  })
})
