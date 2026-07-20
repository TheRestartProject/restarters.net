import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkShowPage from '../../../app/pages/networks/[id].vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useSessionStore } from '../../../app/stores/session.js'
import { useNetworksStore } from '../../../app/stores/networks.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { BDropdownItemStub, BDropdownStub } from '../../helpers/stubs.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BModalStub = {
  props: ['modelValue', 'title'],
  emits: ['hide'],
  template: '<div v-if="modelValue" :data-title="title"><slot /></div>',
}
const NetworkStatsStub = {
  props: ['stats', 'groupsCount'],
  template: '<div data-testid="stub-network-stats" :data-groups-count="groupsCount" />',
}
const AssociateGroupsModalStub = {
  props: ['show', 'networkId', 'networkName', 'candidates'],
  emits: ['close'],
  template: '<div v-if="show" data-testid="stub-associate-modal" :data-candidate-count="candidates.length" />',
}
const NetworkTagsManagerStub = {
  props: ['networkId'],
  template: '<div data-testid="stub-network-tags-manager" :data-network-id="networkId" />',
}
const NetworkGroupsModerationTableStub = {
  props: ['networkId'],
  template: '<div data-testid="stub-network-groups-moderation-table" :data-network-id="networkId" />',
}
const NetworkEventsModerationTableStub = {
  props: ['networkId'],
  template: '<div data-testid="stub-network-events-moderation-table" :data-network-id="networkId" />',
}
const GroupsTableStub = {
  props: ['groups', 'showJoin'],
  template: '<div data-testid="stub-groups-table" :data-row-count="groups.length" />',
}

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BModal: BModalStub,
  BDropdown: BDropdownStub,
  BDropdownItem: BDropdownItemStub,
  NetworkStats: NetworkStatsStub,
  AssociateGroupsModal: AssociateGroupsModalStub,
  NetworkTagsManager: NetworkTagsManagerStub,
  NetworkGroupsModerationTable: NetworkGroupsModerationTableStub,
  NetworkEventsModerationTable: NetworkEventsModerationTableStub,
  GroupsTable: GroupsTableStub,
}

function setLoggedInUser(user) {
  useSessionStore().user = user
  useAuthStore().user = user
  useAuthStore().token = 'tok-1'
}

// lang/en/networks.php gained `general.coordinators`/`general.actions`/
// `moderation.*` alongside this Nuxt work (RES gap-closure pass) but
// client/i18n/locales/en.json is a generated, checked-in artifact this
// change intentionally leaves untouched (php artisan
// translations:export-client) - overlay the new keys here so the spec
// doesn't depend on regenerating it.
const messages = {
  en: {
    ...en,
    ...clientEn,
    networks: {
      ...en.networks,
      general: { ...en.networks.general, coordinators: 'Network Coordinators', actions: 'Network Actions' },
      moderation: {
        groups_title: 'Groups to moderate',
        events_title: 'Events to moderate',
        group_requires_moderation: 'Group requires moderation',
      },
    },
  },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(NetworkShowPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

const NETWORK = {
  id: 1,
  name: 'Test London',
  logo: null,
  website: 'https://example.com',
  description: '<p>A description.</p>',
  stats: { parties: 3, co2_total: 100, waste_total: 50, participants: 10, hours_volunteered: 20, fixed_powered: 1, fixed_unpowered: 1 },
}

async function flushPromises() {
  await Promise.resolve()
  await Promise.resolve()
}

describe('pages/networks/[id]', () => {
  let networksStore
  let groupsStore
  let navigateToSpy

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '1' }, query: {}, fullPath: '/networks/1' }))
    navigateToSpy = vi.fn()
    vi.stubGlobal('navigateTo', navigateToSpy)

    networksStore = useNetworksStore()
    networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
      networksStore.current.data = NETWORK
    })
    networksStore.fetchTags = vi.fn().mockImplementation(async () => {
      networksStore.tags.data = [{ id: 1, name: 'Existing Tag', groups_count: 0 }]
    })
    networksStore.fetchGroups = vi.fn().mockImplementation(async () => {
      networksStore.groups.data = [{ id: 9, name: 'Tag Test Group', archived_at: null, location: null, hosts: 1, restarters: 2, next_event: null }]
    })

    groupsStore = useGroupsStore()
    groupsStore.fetchNames = vi.fn().mockResolvedValue([])
  })

  describe('permission gate', () => {
    it('redirects Hosts to /forbidden without fetching the network', () => {
      setLoggedInUser({ id: 1, role_name: 'Host', networks: [] })

      mountPage()

      expect(navigateToSpy).toHaveBeenCalledWith('/forbidden')
      expect(networksStore.fetchCurrent).not.toHaveBeenCalled()
    })

    it('redirects a NetworkCoordinator who does not coordinate THIS network', () => {
      setLoggedInUser({ id: 1, role_name: 'NetworkCoordinator', networks: [{ id: 99, name: 'Other Network' }] })

      mountPage()

      expect(navigateToSpy).toHaveBeenCalledWith('/forbidden')
      expect(networksStore.fetchCurrent).not.toHaveBeenCalled()
    })

    it('lets in the coordinator of this network', async () => {
      setLoggedInUser({ id: 1, role_name: 'NetworkCoordinator', networks: [{ id: 1, name: 'Test London' }] })

      mountPage()
      await flushPromises()

      expect(navigateToSpy).not.toHaveBeenCalled()
      expect(networksStore.fetchCurrent).toHaveBeenCalledWith(1)
    })

    it('lets in an Administrator regardless of their own coordinated networks', async () => {
      setLoggedInUser({ id: 1, role_name: 'Administrator', networks: [] })

      mountPage()
      await flushPromises()

      expect(navigateToSpy).not.toHaveBeenCalled()
      expect(networksStore.fetchCurrent).toHaveBeenCalledWith(1)
    })
  })

  describe('once past the gate', () => {
    beforeEach(() => {
      setLoggedInUser({ id: 1, role_name: 'Administrator', networks: [] })
    })

    it('renders the network name and stats', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-name"]').text()).toBe('Test London')
      expect(wrapper.find('[data-testid="stub-network-stats"]').attributes('data-groups-count')).toBe('1')
    })

    // NetworkPage.vue:80-86 - a count sentence and a link to the groups list
    // filtered to this network, NOT an inline groups table. The table this
    // used to assert was a divergence from develop.
    it('renders a group-count sentence linking to the filtered groups list', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-groups-info"]').text()).toContain('Test London')
      expect(wrapper.find('[data-testid="network-show-groups-link"]').attributes('href')).toBe('/group/all?network=1')
      expect(wrapper.find('[data-testid="stub-groups-table"]').exists()).toBe(false)
    })

    it('does not render the coordinators section when the network has none', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-coordinators"]').exists()).toBe(false)
    })

    it('renders a card per coordinator with avatar, name and profile link', async () => {
      networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
        networksStore.current.data = {
          ...NETWORK,
          coordinators: [
            { id: 7, name: 'Coord Inator', avatar_url: 'https://example.com/coord.png' },
            { id: 8, name: 'No Avatar Person', avatar_url: null },
          ],
        }
      })

      const wrapper = mountPage()
      await flushPromises()

      const section = wrapper.get('[data-testid="network-show-coordinators"]')
      expect(section.text()).toContain('Network Coordinators')

      const card7 = wrapper.get('[data-testid="network-coordinator-7"]')
      expect(card7.text()).toContain('Coord Inator')
      expect(card7.attributes('href')).toBe('/profile/7')
      expect(card7.get('img').attributes('src')).toBe('https://example.com/coord.png')

      const card8 = wrapper.get('[data-testid="network-coordinator-8"]')
      expect(card8.get('img').attributes('src')).toBe('/images/placeholder-avatar.webp')
    })

    it('renders the network-scoped groups/events moderation tables, always, with the legacy heading copy', async () => {
      const wrapper = mountPage()
      await flushPromises()

      const groupsSection = wrapper.get('[data-testid="network-groups-moderation"]')
      expect(groupsSection.text()).toContain('Groups to moderate')
      expect(groupsSection.get('[data-testid="stub-network-groups-moderation-table"]').attributes('data-network-id')).toBe('1')

      const eventsSection = wrapper.get('[data-testid="network-events-moderation"]')
      expect(eventsSection.text()).toContain('Events to moderate')
      expect(eventsSection.get('[data-testid="stub-network-events-moderation-table"]').attributes('data-network-id')).toBe('1')
    })

    it('always renders tags management (view access implies manage access)', async () => {
      const wrapper = mountPage()
      await flushPromises()

      const section = wrapper.get('[data-testid="tags-management"]')
      expect(section.get('[data-testid="stub-network-tags-manager"]').attributes('data-network-id')).toBe('1')
    })

    it('shows the "Add groups" action inside the Network Actions dropdown', async () => {
      const wrapper = mountPage()
      await flushPromises()

      const dropdown = wrapper.find('[data-testid="network-show-actions"]')
      expect(dropdown.exists()).toBe(true)
      expect(dropdown.text()).toContain('Network Actions')
      expect(wrapper.find('[data-testid="network-show-add-groups"]').exists()).toBe(true)
    })

    it('links "Download event list" to the public export route', async () => {
      const wrapper = mountPage()
      await flushPromises()

      const link = wrapper.find('[data-testid="network-show-export-events"]')
      expect(link.exists()).toBe(true)
      expect(link.attributes('href')).toBe('/export/networks/1/events')
    })

    it('opens the associate-groups modal with candidates excluding only groups already in the network (archived groups stay selectable, matching Network::groupsNotIn())', async () => {
      groupsStore.names = [
        { id: 9, name: 'Tag Test Group', archived_at: null },
        { id: 10, name: 'Fixers United', archived_at: null },
        { id: 11, name: 'Archived Group', archived_at: '2020-01-01T00:00:00Z' },
      ]

      const wrapper = mountPage()
      await flushPromises()

      await wrapper.find('[data-testid="network-show-add-groups"]').trigger('click')

      const modal = wrapper.find('[data-testid="stub-associate-modal"]')
      expect(modal.exists()).toBe(true)
      // 9 is already in the network - 10 and 11 (archived) are both candidates.
      expect(modal.attributes('data-candidate-count')).toBe('2')
    })

    // The per-tag group filter belonged to the inline groups browser, which
    // develop's network page does not have; it went with it.
    it('does not render a tag filter over the groups', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-tag-filter"]').exists()).toBe(false)
    })

    it('shows a not-found state for a 404', async () => {
      networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
        networksStore.current.error = { status: 404 }
      })

      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-not-found"]').exists()).toBe(true)
    })

    it('shows a generic load error with retry for other failures', async () => {
      networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
        networksStore.current.error = { status: 500 }
      })

      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-load-error"]').exists()).toBe(true)

      await wrapper.find('[data-testid="network-show-retry"]').trigger('click')
      expect(networksStore.fetchCurrent).toHaveBeenCalledTimes(2)
    })
  })
})
