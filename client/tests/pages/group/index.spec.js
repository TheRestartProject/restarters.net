import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import GroupMinePage from '../../../app/pages/group/index.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useModerationStore } from '../../../app/stores/moderation.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

// groups.create_groups_mobile2 is a new key (lang/en/groups.php) not yet
// re-exported to en.json - injected here so the mobile-label test exercises
// the real copy rather than the untranslated key fallback.
function mountPage() {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: { en: { ...en, ...clientEn, groups: { ...en.groups, create_groups_mobile2: 'Add new' } } },
  })

  return mount(GroupMinePage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, BAlert: BAlertStub, BButton: BButtonStub, BBadge: BBadgeStub },
    },
  })
}

function setLoggedInUser(user) {
  useAuthStore().user = user
}

describe('pages/group/index (mine)', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchMine = vi.fn().mockResolvedValue([])
    useModerationStore().fetchGroups = vi.fn().mockResolvedValue([])
  })

  it('calls groupsStore.fetchMine() on mount', () => {
    mountPage()
    expect(groupsStore.fetchMine).toHaveBeenCalledTimes(1)
  })

  it('shows a loading skeleton while loading', () => {
    groupsStore.mine.loading = true

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-mine-loading"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-mine-table"]').exists()).toBe(false)
  })

  it('shows an error state with a retry button that calls fetchMine again', async () => {
    groupsStore.mine.error = { status: 500 }

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-mine-error"]').exists()).toBe(true)

    await wrapper.find('[data-testid="group-mine-retry"]').trigger('click')
    expect(groupsStore.fetchMine).toHaveBeenCalledTimes(2)
  })

  it('shows the empty state, with a link to /group/nearby, when there are no groups', () => {
    groupsStore.mine.data = []

    const wrapper = mountPage()

    const empty = wrapper.find('[data-testid="group-mine-empty"]')
    expect(empty.exists()).toBe(true)
    expect(empty.html()).toContain('/group/nearby')
  })

  it('renders a row per group with a leave button', () => {
    groupsStore.mine.data = [{ id: 1, name: 'Fixers United', role: 3, archived: false, image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-row-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="group-leave-1"]').exists()).toBe(true)
  })

  it('shows an archived badge for archived groups', () => {
    groupsStore.mine.data = [{ id: 2, name: 'Old Group', role: 4, archived: true, image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-row-archived-2"]').exists()).toBe(true)
  })

  it('shows the mobile-length create-group label alongside the full label', () => {
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add new')
    expect(wrapper.find('[data-testid="group-create-link"]').text()).toContain('Add a new group')
  })

  // gap #1/#2: legacy's shared tab bar only ever has three tabs (no "Map"),
  // and its .ourtabs border/shadow box wraps the tab-content (here: the
  // table) as well as the nav itself.
  it('has no Map tab, and renders the table inside the shared tabs panel box', () => {
    groupsStore.mine.data = [{ id: 1, name: 'Fixers United', role: 3, archived: false, image_url: null }]

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="groups-tab-map"]').exists()).toBe(false)
    expect(
      wrapper.find('[data-testid="groups-tabs-panel"] [data-testid="group-mine-table"]').exists()
    ).toBe(true)
  })

  describe('moderation queue', () => {
    it('is hidden for a plain Host', () => {
      setLoggedInUser({ id: 1, role_name: 'Host' })

      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="network-groups-moderation-table"]').exists()).toBe(false)
    })

    it('shows above the tabs for an Administrator', () => {
      setLoggedInUser({ id: 1, role_name: 'Administrator' })
      useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]

      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="network-groups-moderation-table"]').exists()).toBe(true)
    })

    // Legacy's group.index.blade.php renders GroupsRequiringModeration
    // BEFORE GroupsPage, whose own template opens with the "Groups" h1 -
    // order, not just presence (a prior version had it after the h1,
    // caught only by a mobile screenshot pass where the reordering was
    // obvious; desktop's wider layout made it easy to miss).
    it('renders before the page heading, not after it', () => {
      setLoggedInUser({ id: 1, role_name: 'Administrator' })
      useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]

      const wrapper = mountPage()
      const html = wrapper.html()

      expect(html.indexOf('network-groups-moderation-table')).toBeLessThan(html.indexOf('group-create-link'))
    })

    it('shows for a NetworkCoordinator', () => {
      setLoggedInUser({ id: 1, role_name: 'NetworkCoordinator' })
      useModerationStore().groups.data = [{ id: 9, name: 'Pending Fixers' }]

      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="network-groups-moderation-table"]').exists()).toBe(true)
    })
  })
})
