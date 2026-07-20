import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EmailPreferencesTab from '../../../app/components/profile/EmailPreferencesTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import { useSessionStore } from '../../../app/stores/session.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
// Single-root: the real usage's data-testid="email-preferences-invites"
// attribute falls through onto this root <input> (matching the BModal
// convention documented in DeleteAccountTab.spec.js) - it is not
// hardcoded here.
const BFormCheckboxStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
}
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EmailPreferencesTab, {
    props,
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BFormCheckbox: BFormCheckboxStub, BButton: BButtonStub, BForm: BFormStub },
    },
  })
}

describe('components/profile/EmailPreferencesTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
    useSessionStore().config = { discourse_url: 'https://talk.example' }
    useSessionStore().user = { username: 'jane' }
  })

  it('fetches GET /users/me/preferences on mount and pre-fills the checkbox', async () => {
    profileStore.fetchEmailPreferences = vi.fn().mockResolvedValue({ invites: true })

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.fetchEmailPreferences).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[data-testid="email-preferences-invites"]').element.checked).toBe(true)
  })

  it('saves {invites: boolean} on submit', async () => {
    profileStore.fetchEmailPreferences = vi.fn().mockResolvedValue({ invites: false })
    profileStore.updateEmailPreferences = vi.fn().mockResolvedValue({ invites: true })

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.find('[data-testid="email-preferences-invites"]').setValue(true)
    await wrapper.find('[data-testid="email-preferences-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.updateEmailPreferences).toHaveBeenCalledWith({ invites: true })
    expect(wrapper.text()).toContain('User Preferences Updated!')
  })

  it('builds the platform preferences link from the session discourse_url and username', async () => {
    profileStore.fetchEmailPreferences = vi.fn().mockResolvedValue({ invites: false })

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    const link = wrapper.find('a.btn-preferences')

    expect(link.attributes('href')).toBe('https://talk.example/u/jane/preferences/emails')
  })

  describe('editing someone else\'s profile (isOwnProfile: false)', () => {
    it('fetches via fetchUserEmailPreferences(targetId) and saves via updateUserEmailPreferences(targetId, payload), not the me/* actions', async () => {
      profileStore.fetchUserEmailPreferences = vi.fn().mockResolvedValue({ invites: true })
      profileStore.updateUserEmailPreferences = vi.fn().mockResolvedValue({ invites: false })
      profileStore.fetchEmailPreferences = vi.fn()
      profileStore.updateEmailPreferences = vi.fn()

      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false })
      await Promise.resolve()
      await Promise.resolve()

      expect(profileStore.fetchUserEmailPreferences).toHaveBeenCalledWith(42)
      expect(profileStore.fetchEmailPreferences).not.toHaveBeenCalled()
      expect(wrapper.find('[data-testid="email-preferences-invites"]').element.checked).toBe(true)

      await wrapper.find('[data-testid="email-preferences-invites"]').setValue(false)
      await wrapper.find('[data-testid="email-preferences-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()

      expect(profileStore.updateUserEmailPreferences).toHaveBeenCalledWith(42, { invites: false })
      expect(profileStore.updateEmailPreferences).not.toHaveBeenCalled()
    })
  })
})
