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

// lang/en/profile.php gained `other_profile` alongside this Nuxt work (RES
// gap-closure pass, gap 7) but client/i18n/locales/en.json is a generated,
// checked-in artifact this change intentionally leaves untouched (php
// artisan translations:export-client) - overlay the new key here so the
// spec doesn't depend on regenerating it. Same convention as
// PublicProfileView.spec.js's users.view_profile_on_talk/not_on_talk overlay.
const messages = {
  en: {
    ...en,
    ...clientEn,
    profile: {
      ...en.profile,
      other_profile: "{name}'s profile",
    },
  },
}

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(ProfileInfoTab, {
    props,
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

  it('shows the own-profile <h3> heading', async () => {
    profileStore.fetchProfileInfo = vi.fn().mockResolvedValue(PROFILE_RESPONSE)
    profileStore.info.data = PROFILE_RESPONSE

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    const heading = wrapper.find('[data-testid="profile-info-heading"]')
    expect(heading.element.tagName).toBe('H3')
    expect(heading.text()).toBe('Your profile')
  })

  describe('editing someone else\'s profile (isOwnProfile: false)', () => {
    it('shows an <h4> "X\'s profile" heading instead of the own-profile <h3>, matching the legacy conditional', async () => {
      profileStore.fetchUserProfile = vi.fn().mockResolvedValue(PROFILE_RESPONSE)
      profileStore.userProfile.data = PROFILE_RESPONSE

      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false })
      await Promise.resolve()
      await Promise.resolve()

      const heading = wrapper.find('[data-testid="profile-info-heading"]')
      expect(heading.element.tagName).toBe('H4')
      expect(heading.text()).toBe("Jane's profile")
    })

    it('fetches via fetchUserProfile(targetId) and saves via updateUserProfile(targetId, payload), not the me/* actions', async () => {
      profileStore.fetchUserProfile = vi.fn().mockResolvedValue(PROFILE_RESPONSE)
      profileStore.userProfile.data = PROFILE_RESPONSE
      profileStore.updateUserProfile = vi.fn().mockResolvedValue(PROFILE_RESPONSE)
      profileStore.fetchProfileInfo = vi.fn()
      profileStore.updateProfileInfo = vi.fn()

      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false })
      await Promise.resolve()
      await Promise.resolve()

      expect(profileStore.fetchUserProfile).toHaveBeenCalledWith(42)
      expect(profileStore.fetchProfileInfo).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="profile-name"]').element.value).toBe('Jane')

      await wrapper.find('[data-testid="profile-name"]').setValue('Jane Doe')
      await wrapper.find('[data-testid="profile-info-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()

      expect(profileStore.updateUserProfile).toHaveBeenCalledWith(42, {
        name: 'Jane Doe',
        email: 'jane@example.com',
        country: 'GB',
        townCity: 'London',
        age: '1990',
        gender: 'F',
        biography: 'Fixer',
      })
      expect(profileStore.updateProfileInfo).not.toHaveBeenCalled()
    })
  })
})
