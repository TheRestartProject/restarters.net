import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it } from 'vitest'
import ProfileTabs from '../../../app/components/profile/ProfileTabs.vue'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const stub = (testid) => ({ template: `<div data-testid="${testid}" />` })
const idStub = (testid) => ({ props: ['targetId'], template: `<div data-testid="${testid}" :data-target-id="targetId" />` })

const GLOBAL_STUBS = {
  ProfileInfoTab: stub('stub-profile-info'),
  SkillsTab: stub('stub-skills'),
  ProfilePhotoTab: stub('stub-photo'),
  PasswordTab: stub('stub-password'),
  LanguageTab: stub('stub-language'),
  AdminSettingsTab: idStub('stub-admin-settings'),
  DeleteAccountTab: stub('stub-delete-account'),
  EmailPreferencesTab: stub('stub-email-preferences'),
  CalendarsTab: stub('stub-calendars'),
  RepairDirectoryTab: idStub('stub-repair-directory'),
  NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' },
}

function mountComponent(props) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(ProfileTabs, {
    props: { targetId: 5, isOwnProfile: false, isAdmin: false, ...props },
    global: { plugins: [i18n], stubs: GLOBAL_STUBS },
  })
}

function isPaneVisible(wrapper, testid) {
  const el = wrapper.find(`[data-testid="${testid}"]`)
  return el.exists() && el.element.style.display !== 'none'
}

describe('components/profile/ProfileTabs', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('editing your own profile (isOwnProfile, not an Administrator)', () => {
    it('shows Profile/Account/Email/Calendars/Notifications, and the account panel has no AdminSettingsTab', () => {
      const wrapper = mountComponent({ isOwnProfile: true, isAdmin: false })

      expect(wrapper.find('[data-testid="profile-tab-nav-profile"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="profile-tab-nav-account"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="profile-tab-nav-email"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="profile-tab-nav-calendars"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="profile-tab-nav-notifications"]').exists()).toBe(true)

      expect(wrapper.find('[data-testid="stub-password"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="stub-language"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="stub-delete-account"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="stub-admin-settings"]').exists()).toBe(false)
    })

    it('defaults the active tab to Profile and shows its panel, hiding the others', () => {
      const wrapper = mountComponent({ isOwnProfile: true, isAdmin: false })

      expect(isPaneVisible(wrapper, 'profile-tab-panel-profile')).toBe(true)
      expect(isPaneVisible(wrapper, 'profile-tab-panel-account')).toBe(false)
    })

    it('switches the visible panel on nav click without unmounting the other panels', async () => {
      const wrapper = mountComponent({ isOwnProfile: true, isAdmin: false })

      await wrapper.find('[data-testid="profile-tab-nav-account"]').trigger('click')

      expect(isPaneVisible(wrapper, 'profile-tab-panel-profile')).toBe(false)
      expect(isPaneVisible(wrapper, 'profile-tab-panel-account')).toBe(true)
      // Still in the DOM (v-show, not v-if) - switching back doesn't refetch.
      expect(wrapper.find('[data-testid="profile-tab-panel-profile"]').exists()).toBe(true)
    })
  })

  describe('an Administrator editing someone else\'s profile', () => {
    it('shows only the Account tab, containing AdminSettingsTab scoped to targetId - no Profile/Email/Calendars/Notifications', () => {
      const wrapper = mountComponent({ targetId: 42, isOwnProfile: false, isAdmin: true })

      expect(wrapper.find('[data-testid="profile-tab-nav-profile"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="profile-tab-nav-email"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="profile-tab-nav-calendars"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="profile-tab-nav-notifications"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="profile-tab-nav-account"]').exists()).toBe(true)

      // Deliberate deviation from the legacy Blade page - see
      // stores/profile.js's class doc comment: these three are "me"-only
      // endpoints and must never render while editing someone else.
      expect(wrapper.find('[data-testid="stub-password"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="stub-language"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="stub-delete-account"]').exists()).toBe(false)

      const adminSettings = wrapper.find('[data-testid="stub-admin-settings"]')
      expect(adminSettings.exists()).toBe(true)
      expect(adminSettings.attributes('data-target-id')).toBe('42')
    })
  })

  describe('Repair Directory tab visibility (proxies Policy::viewRepairDirectorySettings)', () => {
    it('is hidden while repairDirectoryVisible is false, even for the profile owner', () => {
      const wrapper = mountComponent({ isOwnProfile: true, isAdmin: false })
      expect(wrapper.find('[data-testid="profile-tab-nav-repair-directory"]').exists()).toBe(false)
    })

    it('appears once the store resolves it visible, scoped to targetId, and becomes the active tab when nothing else is visible', async () => {
      const wrapper = mountComponent({ targetId: 7, isOwnProfile: false, isAdmin: false })

      // Neither self nor Administrator and repair directory unresolved: no
      // tabs at all yet.
      expect(wrapper.find('[data-testid="profile-tabs-nav"]').findAll('button, a')).toHaveLength(0)

      const profileStore = useProfileStore()
      profileStore.repairDirectory.data = {
        current: 0,
        options: [{ value: 1, disabled: false }],
      }
      await wrapper.vm.$nextTick()

      const nav = wrapper.find('[data-testid="profile-tab-nav-repair-directory"]')
      expect(nav.exists()).toBe(true)
      expect(isPaneVisible(wrapper, 'profile-tab-panel-repair-directory')).toBe(true)

      const tab = wrapper.find('[data-testid="stub-repair-directory"]')
      expect(tab.attributes('data-target-id')).toBe('7')
    })
  })
})
