import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DeleteAccountTab from '../../../app/components/profile/DeleteAccountTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// Note: BModal's own data-testid="stub-modal" gets overridden by the real
// component's `data-testid="delete-account-modal"` attribute (Vue's
// fallthrough-attrs merge: the parent's binding on a component tag wins
// over the stub's own static template attribute of the same name) - so
// the modal is found by that testid, not the stub's.
const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue" data-testid="stub-modal"><slot /></div>',
}
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountComponent() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DeleteAccountTab, {
    global: {
      plugins: [i18n],
      stubs: { BModal: BModalStub, BAlert: BAlertStub, BButton: BButtonStub },
    },
  })
}

describe('components/profile/DeleteAccountTab', () => {
  let profileStore
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
    profileStore = useProfileStore()
  })

  it('does not show the confirmation modal until the delete button is clicked', () => {
    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="delete-account-modal"]').exists()).toBe(false)
  })

  it('opens the confirmation modal on delete-account-button click', async () => {
    const wrapper = mountComponent()
    await wrapper.find('[data-testid="delete-account-button"]').trigger('click')
    expect(wrapper.find('[data-testid="delete-account-modal"]').exists()).toBe(true)
  })

  it('does not call the store until the modal is confirmed', async () => {
    profileStore.deleteAccount = vi.fn()
    const wrapper = mountComponent()
    await wrapper.find('[data-testid="delete-account-button"]').trigger('click')
    expect(profileStore.deleteAccount).not.toHaveBeenCalled()
  })

  it('calls deleteAccount and navigates to /login on confirm', async () => {
    profileStore.deleteAccount = vi.fn().mockResolvedValue({ success: true })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="delete-account-button"]').trigger('click')
    await wrapper.find('[data-testid="delete-account-confirm"]').trigger('click')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.deleteAccount).toHaveBeenCalledTimes(1)
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  it('shows an error and keeps the account when the delete call fails', async () => {
    profileStore.deleteAccount = vi.fn().mockRejectedValue({ data: { message: 'Nope' } })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="delete-account-button"]').trigger('click')
    await wrapper.find('[data-testid="delete-account-confirm"]').trigger('click')
    await Promise.resolve()
    await Promise.resolve()

    expect(navigateToMock).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Nope')
    // The modal closes back to the initial "are you sure" prompt state.
    expect(wrapper.find('[data-testid="delete-account-modal"]').exists()).toBe(false)
  })
})
