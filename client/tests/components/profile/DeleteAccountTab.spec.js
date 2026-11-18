import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import DeleteAccountTab from '../../../app/components/profile/DeleteAccountTab.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(DeleteAccountTab, {
    props,
    global: {
      plugins: [i18n],
      stubs: { BAlert: BAlertStub, BButton: BButtonStub },
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

  // findings/parity-v2/profile-notifs-auth.md gap 8 was diffed against
  // origin/develop's older, since-superseded Blade (which happens to leave
  // this tab unwrapped) rather than this branch's own pre-Phase-F Vue
  // component (git show 07e6abd7cc^:resources/js/components/
  // DeleteAccountTab.vue), which DOES wrap in .edit-panel like every other
  // profile tab - pins that so it can't silently regress again.
  it('wraps its root in .edit-panel, matching every other profile tab', () => {
    const wrapper = mountComponent()
    expect(wrapper.find('[data-testid="delete-account-tab"]').classes()).toContain('edit-panel')
  })

  // develop's form (resources/views/user/profile/account.blade.php) submits
  // the soft-delete request straight from the button click - no "are you
  // sure?" confirmation step - matched verbatim rather than adding one.
  it('calls deleteAccount and navigates to /login on delete-account-button click, with no confirmation step', async () => {
    profileStore.deleteAccount = vi.fn().mockResolvedValue({ success: true })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="delete-account-button"]').trigger('click')
    await Promise.resolve()
    await Promise.resolve()

    expect(profileStore.deleteAccount).toHaveBeenCalledTimes(1)
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  it('shows an error and keeps the account when the delete call fails', async () => {
    profileStore.deleteAccount = vi.fn().mockRejectedValue({ data: { message: 'Nope' } })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="delete-account-button"]').trigger('click')
    await Promise.resolve()
    await Promise.resolve()

    expect(navigateToMock).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Nope')
  })

  describe('editing someone else\'s profile (isOwnProfile: false)', () => {
    it('calls deleteUser(targetId) and navigates to /user/all, not deleteAccount/login', async () => {
      profileStore.deleteUser = vi.fn().mockResolvedValue({ success: true })

      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false })
      await wrapper.find('[data-testid="delete-account-button"]').trigger('click')
      await Promise.resolve()
      await Promise.resolve()

      expect(profileStore.deleteUser).toHaveBeenCalledWith(42)
      expect(navigateToMock).toHaveBeenCalledWith('/user/all')
    })
  })
})
