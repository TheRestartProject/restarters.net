import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupNearbyPage from '../../../app/pages/group/nearby.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import { useProfileStore } from '../../../app/stores/profile.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useModerationStore } from '../../../app/stores/moderation.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

// groups.nearest_groups/nearest_groups_change/create_groups_mobile2 are new
// keys (lang/en/groups.php) not yet re-exported to en.json - injected here
// so these specs exercise the real copy rather than the untranslated key
// fallback.
function mountPage() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        groups: {
          ...en.groups,
          nearest_groups: 'These are the groups that are within 50 km of {location}',
          nearest_groups_change: '(change)',
          create_groups_mobile2: 'Add new',
        },
      },
    },
  })

  return mount(GroupNearbyPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub },
    },
  })
}

function setLoggedInUser(user) {
  useAuthStore().user = user
}

describe('pages/group/nearby', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchNearby = vi.fn().mockResolvedValue([])
    useProfileStore().fetchProfileInfo = vi.fn().mockResolvedValue(null)
    useModerationStore().fetchGroups = vi.fn().mockResolvedValue([])
  })

  it('calls groupsStore.fetchNearby() on mount', () => {
    mountPage()
    expect(groupsStore.fetchNearby).toHaveBeenCalledTimes(1)
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.nearby.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-nearby-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-nearby-list"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetchNearby again', async () => {
    groupsStore.nearby.error = { status: 500 }

    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="group-nearby-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-nearby-retry"]').trigger('click')
    expect(groupsStore.fetchNearby).toHaveBeenCalledTimes(2)
  })

  it('shows the no-groups-nearby empty state when there are no nearby groups', () => {
    groupsStore.nearby.data = []

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-nearby-empty"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-nearby-no-location"]').exists()).toBe(false)
  })

  it('shows the set-a-location prompt (not the plain empty state) when the user has no town set', () => {
    // Legacy's "Other groups nearby" tab distinguishes "no location set" from
    // "no groups nearby"; hasLocation comes from the dashboard's has_location.
    const dashboardStore = useDashboardStore()
    dashboardStore.data = { has_location: false }
    groupsStore.nearby.data = []

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-nearby-no-location"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-nearby-empty"]').exists()).toBe(false)
  })

  it('renders a row per nearby group in the shared GroupsTable (gap #5)', () => {
    groupsStore.nearby.data = [{ id: 5, name: 'Riverside Fixers', distance: 2.1, location: 'Riverside', image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-row-5"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-join-5"]').exists()).toBe(true)
  })

  it('reflects membership from the store on the join button', () => {
    groupsStore.nearby.data = [{ id: 5, name: 'Riverside Fixers', distance: 2.1, location: 'Riverside', image_url: null }]
    groupsStore.memberIds = [5]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-leave-5"]').exists()).toBe(true)
  })

  it('shows the "within 50 km of :location (change)" heading above the table (gap #12)', () => {
    groupsStore.nearby.data = [{ id: 5, name: 'Riverside Fixers', distance: 2.1, location: 'Riverside', image_url: null }]
    useProfileStore().info.data = { location: 'Bristol' }

    const wrapper = mountPage()

    const heading = wrapper.find('[data-testid="group-nearby-heading"]')
    expect(heading.text()).toContain('These are the groups that are within 50 km of Bristol')
    expect(wrapper.find('[data-testid="group-nearby-location-change"]').attributes('href')).toBe('/profile/edit')
  })

  it('shows the mobile-length create-group label alongside the full label', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add new')
    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add a new group')
  })

  it('shows the groups moderation queue for an Administrator', () => {
    setLoggedInUser({ id: 1, role_name: 'Administrator' })
    useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="moderation-queue-groups"]').exists()).toBe(true)
  })

  // Legacy shows GroupsRequiringModeration on every /group tab, unscoped by
  // which tab is active - it's rendered once, above the shared tabs, not
  // per-tab content. ModerationQueue here is wired the same way (a plain
  // v-if="showModeration" with no dependency on groupsStore.nearby at all),
  // but that's worth pinning explicitly: it must still show even when this
  // particular tab's own content is in an empty/no-location state, proving
  // it isn't accidentally coupled to this tab's data.
  it('shows the moderation queue regardless of this tab\'s own empty/no-location state', () => {
    setLoggedInUser({ id: 1, role_name: 'Administrator' })
    useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]
    useDashboardStore().data = { has_location: false }
    groupsStore.nearby.data = []

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-nearby-no-location"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="moderation-queue-groups"]').exists()).toBe(true)
  })

  // Legacy's group.index.blade.php renders GroupsRequiringModeration
  // BEFORE GroupsPage, whose own template opens with the "Groups" h1 -
  // order, not just presence (a prior version had it after the h1, caught
  // only by a mobile screenshot pass where the reordering was obvious;
  // desktop's wider layout made it easy to miss).
  it('renders before the page heading, not after it', () => {
    setLoggedInUser({ id: 1, role_name: 'Administrator' })
    useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]

    const wrapper = mountPage()
    const html = wrapper.html()

    expect(html.indexOf('moderation-queue-groups')).toBeLessThan(html.indexOf('group-create-link'))
  })

  // gap #1/#2: legacy's shared tab bar only ever has three tabs (no "Map"),
  // and its .ourtabs border/shadow box wraps the tab-content (here: the
  // heading + table) as well as the nav itself.
  it('has no Map tab, and renders the table inside the shared tabs panel box', () => {
    groupsStore.nearby.data = [{ id: 5, name: 'Riverside Fixers', distance: 2.1, location: 'Riverside', image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="groups-tab-map"]').exists()).toBe(false)
    expect(
      wrapper.find('[data-testid="groups-tabs-panel"] [data-testid="group-nearby-list"]').exists()
    ).toBe(true)
  })
})
