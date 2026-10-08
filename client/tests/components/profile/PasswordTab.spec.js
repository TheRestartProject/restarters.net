import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PasswordTab from '../../../app/components/profile/PasswordTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BFormInputStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs" />',
}
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(PasswordTab, {
    props,
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BFormGroup: BFormGroupStub, BFormInput: BFormInputStub, BButton: BButtonStub, BForm: BFormStub },
    },
  })
}

describe('components/profile/PasswordTab', () => {
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    profileStore = useProfileStore()
  })

  it('rejects a mismatched confirmation without calling the API', async () => {
    profileStore.updatePassword = vi.fn()

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="password-current"]').setValue('oldpass')
    await wrapper.find('[data-testid="password-new"]').setValue('newpass1')
    await wrapper.find('[data-testid="password-new-repeat"]').setValue('newpass2')
    await wrapper.find('[data-testid="password-form"]').trigger('submit')

    expect(profileStore.updatePassword).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="password-new-repeat-error"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('New Passwords do not match!')
  })

  it('submits the exact legacy payload shape when the confirmation matches', async () => {
    profileStore.updatePassword = vi.fn().mockResolvedValue({ success: true })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="password-current"]').setValue('oldpass')
    await wrapper.find('[data-testid="password-new"]').setValue('newpass1')
    await wrapper.find('[data-testid="password-new-repeat"]').setValue('newpass1')
    await wrapper.find('[data-testid="password-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.updatePassword).toHaveBeenCalledWith({
      current_password: 'oldpass',
      new_password: 'newpass1',
      new_password_confirmation: 'newpass1',
    })
    expect(wrapper.text()).toContain('User Password Updated!')
  })

  it('clears the fields after a successful save', async () => {
    profileStore.updatePassword = vi.fn().mockResolvedValue({ success: true })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="password-current"]').setValue('oldpass')
    await wrapper.find('[data-testid="password-new"]').setValue('newpass1')
    await wrapper.find('[data-testid="password-new-repeat"]').setValue('newpass1')
    await wrapper.find('[data-testid="password-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="password-current"]').element.value).toBe('')
    expect(wrapper.find('[data-testid="password-new"]').element.value).toBe('')
    expect(wrapper.find('[data-testid="password-new-repeat"]').element.value).toBe('')
  })

  it('renders a 422 current_password field error from the server', async () => {
    profileStore.updatePassword = vi.fn().mockRejectedValue({
      status: 422,
      data: { errors: { current_password: ['Current Password does not match!'] } },
    })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="password-current"]').setValue('wrong')
    await wrapper.find('[data-testid="password-new"]').setValue('newpass1')
    await wrapper.find('[data-testid="password-new-repeat"]').setValue('newpass1')
    await wrapper.find('[data-testid="password-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="password-current-error"]').text()).toBe('Current Password does not match!')
  })

  describe('editing someone else\'s profile (isOwnProfile: false)', () => {
    it('hides the Current Password field', () => {
      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false })
      expect(wrapper.find('[data-testid="password-current"]').exists()).toBe(false)
    })

    it('calls updateUserPassword(targetId, payload) instead of updatePassword, omitting current_password', async () => {
      profileStore.updateUserPassword = vi.fn().mockResolvedValue({ success: true })

      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false })
      await wrapper.find('[data-testid="password-new"]').setValue('newpass1')
      await wrapper.find('[data-testid="password-new-repeat"]').setValue('newpass1')
      await wrapper.find('[data-testid="password-form"]').trigger('submit')
      await Promise.resolve()
      await Promise.resolve()

      // No current_password key at all - UserController::applyPasswordUpdate
      // only requires/checks it when the acting user IS the target.
      expect(profileStore.updateUserPassword).toHaveBeenCalledWith(42, {
        new_password: 'newpass1',
        new_password_confirmation: 'newpass1',
      })
    })
  })
})
