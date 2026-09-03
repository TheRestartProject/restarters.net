import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ProfileEditPage from '../../../../app/pages/profile/edit/[[id]].vue'
import { useAuthStore } from '../../../../app/stores/auth.js'
import { useProfileStore } from '../../../../app/stores/profile.js'
import en from '../../../../i18n/locales/en.json'
import clientEn from '../../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const ProfileTabsStub = {
  props: ['targetId', 'isOwnProfile', 'isAdmin'],
  template: '<div data-testid="stub-profile-tabs" />',
}

function stubRoute(params) {
  vi.stubGlobal('useRoute', () => ({ params, query: {}, fullPath: '/profile/edit' }))
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(ProfileEditPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, ProfileTabs: ProfileTabsStub },
    },
  })
}

describe('pages/profile/edit/[[id]]', () => {
  let authStore
  let profileStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('navigateTo', vi.fn())

    authStore = useAuthStore()
    profileStore = useProfileStore()
    profileStore.fetchRepairDirectoryOptions = vi.fn().mockResolvedValue({ current: 0, options: [] })
    profileStore.fetchTargetUser = vi.fn().mockResolvedValue(null)
  })

  describe('own profile (no id param)', () => {
    beforeEach(() => {
      stubRoute({})
      authStore.user = { id: 1, role_name: 'Restarter' }
    })

    it('resolves targetId to the session user id and renders ProfileTabs with isOwnProfile true', () => {
      const wrapper = mountPage()
      const tabs = wrapper.findComponent(ProfileTabsStub)

      expect(tabs.exists()).toBe(true)
      expect(tabs.props('targetId')).toBe(1)
      expect(tabs.props('isOwnProfile')).toBe(true)
      expect(tabs.props('isAdmin')).toBe(false)
    })

    it('grants access instantly with no checking-access spinner', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="profile-edit-checking-access"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="profile-edit-forbidden"]').exists()).toBe(false)
    })

    it('links "View profile" to /profile, not to /profile/{id}', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="profile-edit-view-link"]').attributes('href')).toBe('/profile')
    })

    it('does not fetch the target user (best-effort header) for one\'s own profile', () => {
      mountPage()
      expect(profileStore.fetchTargetUser).not.toHaveBeenCalled()
    })

    it('still fetches repair-directory-options - the tab is visible regardless of self/admin status', () => {
      mountPage()
      expect(profileStore.fetchRepairDirectoryOptions).toHaveBeenCalledWith(1)
    })
  })

  describe('an Administrator editing a specific id', () => {
    beforeEach(() => {
      stubRoute({ id: '42' })
      authStore.user = { id: 1, role_name: 'Administrator' }
    })

    it('grants access instantly (no checking-access spinner) and renders ProfileTabs scoped to the target', () => {
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="profile-edit-checking-access"]').exists()).toBe(false)

      const tabs = wrapper.findComponent(ProfileTabsStub)
      expect(tabs.props('targetId')).toBe(42)
      expect(tabs.props('isOwnProfile')).toBe(false)
      expect(tabs.props('isAdmin')).toBe(true)
    })

    it('links "View user profile" to /profile/{id}', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="profile-edit-view-link"]').attributes('href')).toBe('/profile/42')
    })

    it('fetches the best-effort target user for the heading', () => {
      mountPage()
      expect(profileStore.fetchTargetUser).toHaveBeenCalledWith(42)
    })

    it('shows a generic "Editing user #{id}" heading until the target user name resolves', () => {
      const wrapper = mountPage()
      expect(wrapper.find('[data-testid="profile-edit-heading"]').text()).toBe('Editing user #42')
    })

    it('shows "Editing: {name}" once the target user resolves', async () => {
      const wrapper = mountPage()
      profileStore.targetUser.data = { id: 42, name: 'Jane Fixit' }
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="profile-edit-heading"]').text()).toBe('Editing: Jane Fixit')
    })
  })

  describe('neither the profile owner nor an Administrator', () => {
    beforeEach(() => {
      stubRoute({ id: '42' })
      authStore.user = { id: 1, role_name: 'Restarter' }
    })

    it('shows a checking-access spinner while repair-directory-options is in flight', () => {
      profileStore.repairDirectory.loading = true
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="profile-edit-checking-access"]').exists()).toBe(true)
      expect(wrapper.findComponent(ProfileTabsStub).exists()).toBe(false)
    })

    it('shows the access-denied message once resolved with no enabled repair-directory options', () => {
      profileStore.repairDirectory.loading = false
      profileStore.repairDirectory.data = { current: 0, options: [{ value: 0, disabled: true }] }
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="profile-edit-forbidden"]').exists()).toBe(true)
      expect(wrapper.findComponent(ProfileTabsStub).exists()).toBe(false)
    })

    it('grants access once resolved with at least one enabled repair-directory option (Regional/Super Admin)', () => {
      profileStore.repairDirectory.loading = false
      profileStore.repairDirectory.data = { current: 0, options: [{ value: 1, disabled: false }] }
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="profile-edit-forbidden"]').exists()).toBe(false)
      const tabs = wrapper.findComponent(ProfileTabsStub)
      expect(tabs.exists()).toBe(true)
      expect(tabs.props('isOwnProfile')).toBe(false)
      expect(tabs.props('isAdmin')).toBe(false)
    })
  })

  describe('an invalid id', () => {
    it('shows a not-found message and does not fetch anything', () => {
      stubRoute({ id: 'not-a-number' })
      authStore.user = { id: 1, role_name: 'Restarter' }

      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="profile-edit-invalid"]').exists()).toBe(true)
      expect(profileStore.fetchRepairDirectoryOptions).not.toHaveBeenCalled()
    })
  })
})
