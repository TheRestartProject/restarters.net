import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PublicProfileView from '../../../app/components/profile/PublicProfileView.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useUsersStore } from '../../../app/stores/users.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }

const PROFILE = {
  id: 42,
  name: 'Jane Fixit',
  avatar_url: null,
  role_name: 'Restarter',
  location: 'London',
  groups: [{ id: 3, name: 'Chiswick Fixers' }],
  skills: [{ id: 5, name: 'Electronics repair' }],
  biography: 'I love fixing things.',
}

function mountView(props) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(PublicProfileView, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub },
    },
  })
}

describe('components/profile/PublicProfileView', () => {
  let authStore
  let usersStore

  beforeEach(() => {
    setActivePinia(createPinia())
    authStore = useAuthStore()
    usersStore = useUsersStore()
    usersStore.fetchPublicProfile = vi.fn().mockResolvedValue(PROFILE)
    authStore.user = { id: 1, role_name: 'Restarter' }
  })

  it('shows a not-found message and fetches nothing for a null userId', () => {
    const wrapper = mountView({ userId: null })

    expect(wrapper.find('[data-testid="profile-view-invalid"]').exists()).toBe(true)
    expect(usersStore.fetchPublicProfile).not.toHaveBeenCalled()
  })

  it('fetches the profile for the given userId on mount', () => {
    mountView({ userId: 42 })
    expect(usersStore.fetchPublicProfile).toHaveBeenCalledWith(42)
  })

  it('re-fetches when userId changes', async () => {
    const wrapper = mountView({ userId: 42 })
    expect(usersStore.fetchPublicProfile).toHaveBeenCalledTimes(1)

    await wrapper.setProps({ userId: 43 })
    expect(usersStore.fetchPublicProfile).toHaveBeenCalledWith(43)
    expect(usersStore.fetchPublicProfile).toHaveBeenCalledTimes(2)
  })

  it('shows a loading skeleton while loading', () => {
    usersStore.publicProfile.loading = true

    const wrapper = mountView({ userId: 42 })

    expect(wrapper.find('[data-testid="profile-view-loading"]').exists()).toBe(true)
  })

  it('shows a not-found state on a 404', () => {
    usersStore.publicProfile.error = { status: 404 }

    const wrapper = mountView({ userId: 42 })

    expect(wrapper.find('[data-testid="profile-view-not-found"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="profile-view-error"]').exists()).toBe(false)
  })

  it('shows a generic error state on a non-404 failure', () => {
    usersStore.publicProfile.error = { status: 500 }

    const wrapper = mountView({ userId: 42 })

    expect(wrapper.find('[data-testid="profile-view-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="profile-view-not-found"]').exists()).toBe(false)
  })

  describe('with a loaded profile', () => {
    beforeEach(() => {
      usersStore.publicProfile.data = PROFILE
    })

    it('renders name, role, location, skills, groups and biography', () => {
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-name"]').text()).toBe('Jane Fixit')
      expect(wrapper.find('[data-testid="profile-view-role-location"]').text()).toBe('Restarter, London')
      expect(wrapper.find('[data-testid="profile-view-skills"]').text()).toContain('Electronics repair')
      expect(wrapper.find('[data-testid="profile-view-groups"]').text()).toContain('Chiswick Fixers')
      expect(wrapper.find('[data-testid="profile-view-bio"]').text()).toBe('I love fixing things.')
    })

    it('falls back to the no-bio message when biography is null', () => {
      usersStore.publicProfile.data = { ...PROFILE, biography: null }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-bio"]').text()).toBe(
        'Jane Fixit has not yet entered a biography.',
      )
    })

    it('shows a no-groups message when groups is empty', () => {
      usersStore.publicProfile.data = { ...PROFILE, groups: [] }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-no-groups"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="profile-view-groups"]').exists()).toBe(false)
    })

    it('shows the edit link when viewing your own profile', () => {
      authStore.user = { id: 42, role_name: 'Restarter' }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-edit-link"]').attributes('href')).toBe('/profile/edit/42')
    })

    it('shows the edit link for an Administrator viewing someone else', () => {
      authStore.user = { id: 1, role_name: 'Administrator' }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-edit-link"]').exists()).toBe(true)
    })

    it('hides the edit link for a stranger', () => {
      authStore.user = { id: 1, role_name: 'Restarter' }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-edit-link"]').exists()).toBe(false)
    })

    it('shows the placeholder avatar when avatar_url is null', () => {
      const wrapper = mountView({ userId: 42 })
      expect(wrapper.find('[data-testid="profile-view-avatar-placeholder"]').exists()).toBe(true)
    })
  })
})
