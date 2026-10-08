import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import PublicProfileView from '../../../app/components/profile/PublicProfileView.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useUsersStore } from '../../../app/stores/users.js'
import en from '../../../i18n/locales/en.json'
import fr from '../../../i18n/locales/fr.json'
import clientEn from '../../../i18n/locales/client-en.json'
import clientFr from '../../../i18n/locales/client-fr.json'

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

// lang/en/users.php gained `view_profile_on_talk` / `not_on_talk` alongside
// this Nuxt work (RES gap-closure pass) but client/i18n/locales/en.json is a
// generated, checked-in artifact this change intentionally leaves untouched
// (php artisan translations:export-client) - overlay the two new keys here
// so the spec doesn't depend on regenerating it.
const messages = {
  en: {
    ...en,
    ...clientEn,
    users: {
      ...en.users,
      view_profile_on_talk: 'View profile on Talk',
      not_on_talk: '[Not on Talk]',
    },
  },
}

function mountView(props) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(PublicProfileView, {
    props,
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub },
    },
  })
}

// French-locale mount, used only to prove a value actually round-trips
// through t() (most English translations of these keys are identity
// strings, e.g. "Electronics repair" -> "Electronics repair", so an
// English-only assertion can't tell a real t() call apart from a raw
// passthrough that happens to look the same).
function mountViewFr(props) {
  const i18n = createI18n({
    legacy: false,
    locale: 'fr',
    messages: { fr: { ...fr, ...clientFr } },
  })

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

    // PROFILE.role_name is 'Restarter' - legacy's @lang() translates the raw
    // roles.role DB value via lang/en.json (Restarter -> "Repairer"), not a
    // literal passthrough, so the rendered label is "Repairer", not
    // "Restarter".
    it('renders name, translated role, location, skills and biography', () => {
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-name"]').text()).toBe('Jane Fixit')
      expect(wrapper.find('[data-testid="profile-view-role-location"]').text()).toBe('Repairer, London')
      expect(wrapper.find('[data-testid="profile-view-skills"]').text()).toContain('Electronics repair')
      expect(wrapper.find('[data-testid="profile-view-bio"]').text()).toBe('I love fixing things.')
    })

    // Gap fix: legacy's `@lang(Fixometer::getRoleName($user->role))` prints
    // the raw roles.role column ("Administrator", "Restarter",
    // "NetworkCoordinator", ...) translated via Laravel's JSON translation
    // files (lang/en.json et al, not lang/en/*.php) - a prior version showed
    // the raw role_name untranslated for everything except a hand-mapped
    // "Administrator" -> "Admin" special case (also wrong: it hardcoded
    // "Admin" even in French, where develop shows "Administrateur"). Pin a
    // few different roles through the real i18n keys instead, so any role
    // silently reverting to the raw API string is caught.
    it.each([
      ['Administrator', 'Admin'],
      ['Restarter', 'Repairer'],
      ['NetworkCoordinator', 'Network Coordinator'],
      ['Host', 'Host'],
    ])('translates role_name %s to %s via i18n, not a hand-mapped special case', (role_name, expected) => {
      usersStore.publicProfile.data = { ...PROFILE, role_name }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-role-location"]').text()).toBe(`${expected}, London`)
    })

    // The exact regression this fix corrects: a prior version hardcoded
    // "Admin" for the Administrator role in every locale, including French,
    // where develop actually shows "Administrateur".
    it('translates role_name via i18n in French too, not the English-only hand-mapped label', () => {
      usersStore.publicProfile.data = { ...PROFILE, role_name: 'Administrator' }
      const wrapper = mountViewFr({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-role-location"]').text()).toBe('Administrateur, London')
    })

    // Skill names are translated the same way (see the "My skills" list) -
    // "Publicising events" is a real skill name in the exported 143-key set,
    // and its French translation actually differs from the raw string, so
    // this proves a real t() call rather than a passthrough that happens to
    // look identical in English.
    it('translates skill names via i18n too, not a raw passthrough', () => {
      usersStore.publicProfile.data = { ...PROFILE, skills: [{ id: 1, name: 'Publicising events' }] }
      const wrapper = mountViewFr({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-skills"]').text()).toContain('Promouvoir des événements')
    })

    it('does not render a Groups panel (no equivalent in the legacy public profile page)', () => {
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-groups"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="profile-view-no-groups"]').exists()).toBe(false)
    })

    it('falls back to the no-bio message when biography is null', () => {
      usersStore.publicProfile.data = { ...PROFILE, biography: null }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-bio"]').text()).toBe(
        'Jane Fixit has not yet entered a biography.',
      )
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

    it('shows a "View profile on Talk" link when on_talk is true', () => {
      usersStore.publicProfile.data = { ...PROFILE, on_talk: true, talk_profile_url: 'https://talk.restarters.net/u/janefixit' }
      const wrapper = mountView({ userId: 42 })

      const link = wrapper.get('[data-testid="profile-view-talk-link"] a')
      expect(link.text()).toBe('View profile on Talk')
      expect(link.attributes('href')).toBe('https://talk.restarters.net/u/janefixit')
      expect(link.attributes('target')).toBe('_blank')
      expect(link.attributes('rel')).toContain('noopener')
      expect(wrapper.find('[data-testid="profile-view-not-on-talk"]').exists()).toBe(false)
    })

    it('shows the admin-only "[Not on Talk]" indicator when the viewer is an Administrator and the profile has no Talk account', () => {
      authStore.user = { id: 1, role_name: 'Administrator' }
      usersStore.publicProfile.data = { ...PROFILE, on_talk: false, talk_profile_url: null }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-not-on-talk"]').text()).toBe('[Not on Talk]')
      expect(wrapper.find('[data-testid="profile-view-talk-link"]').exists()).toBe(false)
    })

    it('hides both the Talk link and the "not on Talk" indicator for a non-admin viewer when the profile has no Talk account', () => {
      authStore.user = { id: 1, role_name: 'Restarter' }
      usersStore.publicProfile.data = { ...PROFILE, on_talk: false, talk_profile_url: null }
      const wrapper = mountView({ userId: 42 })

      expect(wrapper.find('[data-testid="profile-view-talk-link"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="profile-view-not-on-talk"]').exists()).toBe(false)
    })
  })
})
