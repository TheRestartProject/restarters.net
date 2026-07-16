import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import LanguageTab from '../../../app/components/profile/LanguageTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BFormGroupStub = { template: '<div><slot /></div>' }
const BFormSelectStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs"><slot /></select>',
}
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }

const LANGUAGE_RESPONSE = { language: 'en', supported: [{ code: 'en', native: 'English' }, { code: 'fr', native: 'Français' }] }

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(LanguageTab, {
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BFormGroup: BFormGroupStub, BFormSelect: BFormSelectStub, BButton: BButtonStub, BForm: BFormStub },
    },
  })
}

describe('components/profile/LanguageTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
  })

  it('fetches GET /users/me/language on mount and renders the supported list', async () => {
    profileStore.fetchLanguage = vi.fn().mockResolvedValue(LANGUAGE_RESPONSE)
    profileStore.language.data = LANGUAGE_RESPONSE

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.fetchLanguage).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[data-testid="language-select"]').findAll('option')).toHaveLength(2)
  })

  it('disables save until a different language is chosen', async () => {
    profileStore.fetchLanguage = vi.fn().mockResolvedValue(LANGUAGE_RESPONSE)
    profileStore.language.data = LANGUAGE_RESPONSE

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="language-save"]').attributes('disabled')).toBeDefined()

    await wrapper.find('[data-testid="language-select"]').setValue('fr')
    expect(wrapper.find('[data-testid="language-save"]').attributes('disabled')).toBeUndefined()
  })

  it('saves {language} on submit', async () => {
    profileStore.fetchLanguage = vi.fn().mockResolvedValue(LANGUAGE_RESPONSE)
    profileStore.language.data = LANGUAGE_RESPONSE
    profileStore.updateLanguage = vi.fn().mockResolvedValue({ language: 'fr' })

    const wrapper = mountComponent()
    await Promise.resolve()
    await Promise.resolve()

    await wrapper.find('[data-testid="language-select"]').setValue('fr')
    await wrapper.find('[data-testid="language-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.updateLanguage).toHaveBeenCalledWith({ language: 'fr' })
  })
})
