import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkShowPage from '../../../app/pages/networks/[id]/index.vue'
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
  props: ['stats', 'groupsCount', 'loading'],
  template: '<div data-testid="stub-network-stats" :data-groups-count="groupsCount" :data-parties="stats && stats.parties" :data-loading="String(loading)" />',
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
const NetworkGroupsMapListStub = {
  props: ['networkId', 'canManageTags'],
  template: '<div data-testid="stub-network-groups-map-list" :data-network-id="networkId" :data-can-manage-tags="String(canManageTags)" />',
}
const NetworkEventsStub = {
  props: ['networkId'],
  template: '<div data-testid="stub-network-events" :data-network-id="networkId" />',
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
  NetworkGroupsMapList: NetworkGroupsMapListStub,
  NetworkEvents: NetworkEventsStub,
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
  stats: { groups: 7, parties: 3, co2_total: 100, waste_total: 50, participants: 10, hours_volunteered: 20, fixed_powered: 1, fixed_unpowered: 1 },
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
      expect(wrapper.find('[data-testid="stub-network-stats"]').attributes('data-groups-count')).toBe('7')
    })

    it('falls back to the non-archived groups in the list when stats carry no group tally', async () => {
      networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
        networksStore.current.data = { ...NETWORK, stats: { parties: 3 } }
      })
      networksStore.fetchGroups = vi.fn().mockImplementation(async () => {
        networksStore.groups.data = [
          { id: 9, name: 'Live', archived_at: null },
          { id: 10, name: 'Gone', archived_at: '2024-01-01T00:00:00+00:00' },
        ]
      })
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="stub-network-stats"]').attributes('data-groups-count')).toBe('1')
    })

    // develop's NetworkPage.vue embeds a map and filterable list of the
    // network's groups (it dropped the count sentence and "View groups").
    it('renders the network groups map and list, with the tag filter for managers', async () => {
      const wrapper = mountPage()
      await flushPromises()

      const embed = wrapper.find('[data-testid="stub-network-groups-map-list"]')
      expect(embed.attributes('data-network-id')).toBe('1')
      expect(embed.attributes('data-can-manage-tags')).toBe('true')
      expect(wrapper.find('[data-testid="network-show-groups-info"]').exists()).toBe(false)
    })

    it('has no View groups item in the actions menu', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-view-groups"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="network-show-add-groups"]').exists()).toBe(true)
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

    // Network page rework: the order of the sections is part of the design.
    it('lays the page out as moderation, impact, about, coordinators, events, groups, tags', async () => {
      networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
        networksStore.current.data = { ...NETWORK, coordinators: [{ id: 7, name: 'Coord', avatar_url: null }] }
      })
      const wrapper = mountPage()
      await flushPromises()

      const html = wrapper.html()
      const order = [
        'network-show-name',
        'network-groups-moderation"',
        'network-events-moderation"',
        'network-show-impact',
        'network-show-coordinators"',
        'network-show-events',
        'network-groups-map-list',
        'tags-management',
      ].map((marker) => [marker, html.indexOf(marker)])

      order.forEach(([marker, at]) => expect(at, marker).toBeGreaterThan(-1))
      expect(order.map(([, at]) => at)).toEqual([...order.map(([, at]) => at)].sort((a, b) => a - b))
      expect(html.indexOf('About')).toBeGreaterThan(html.indexOf('network-show-impact'))
      expect(html.indexOf('About')).toBeLessThan(html.indexOf('network-show-coordinators"'))
    })

    it('renders the network events section for the network', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.get('[data-testid="network-show-events"]').text()).toContain('Upcoming events')
      expect(wrapper.get('[data-testid="stub-network-events"]').attributes('data-network-id')).toBe('1')
    })

    it('uses the full width for tags (no half-width column)', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('.col-lg-6').exists()).toBe(false)
    })

    describe('impact period', () => {
      it('defaults to all time, then fetches and shows the last 12 months with the months covered', async () => {
        networksStore.fetchStats = vi.fn().mockResolvedValue({
          parties: 2,
          period: { start: '2025-10-01', end: '2026-09-30' },
        })
        const wrapper = mountPage()
        await flushPromises()

        expect(wrapper.find('[data-testid="period-all"]').classes()).toContain('active')
        expect(wrapper.get('[data-testid="stub-network-stats"]').attributes('data-parties')).toBe('3')
        expect(wrapper.find('[data-testid="network-show-period-label"]').exists()).toBe(false)

        await wrapper.get('[data-testid="period-last12"]').trigger('click')
        await flushPromises()

        expect(networksStore.fetchStats).toHaveBeenCalledWith(1, { period: 'last_12_months' })
        expect(wrapper.get('[data-testid="stub-network-stats"]').attributes('data-parties')).toBe('2')
        expect(wrapper.get('[data-testid="network-show-period-label"]').text()).toBe('October 2025 to September 2026')
        // The group tally is the current one whichever period is shown.
        expect(wrapper.get('[data-testid="stub-network-stats"]').attributes('data-groups-count')).toBe('7')
      })

      it('goes back to all time without refetching, and remembers last 12 months once fetched', async () => {
        networksStore.fetchStats = vi.fn().mockResolvedValue({ parties: 2, period: { start: '2025-10-01', end: '2026-09-30' } })
        const wrapper = mountPage()
        await flushPromises()

        await wrapper.get('[data-testid="period-last12"]').trigger('click')
        await flushPromises()
        await wrapper.get('[data-testid="period-all"]').trigger('click')
        await wrapper.get('[data-testid="period-last12"]').trigger('click')
        await flushPromises()

        expect(networksStore.fetchStats).toHaveBeenCalledTimes(1)
        expect(wrapper.get('[data-testid="stub-network-stats"]').attributes('data-parties')).toBe('2')
      })

      it('says so when the last 12 months cannot be loaded', async () => {
        networksStore.fetchStats = vi.fn().mockRejectedValue({ status: 500 })
        const wrapper = mountPage()
        await flushPromises()

        await wrapper.get('[data-testid="period-last12"]').trigger('click')
        await flushPromises()

        expect(wrapper.find('[data-testid="network-show-period-error"]').exists()).toBe(true)
      })
    })

    describe('coordinators row', () => {
      function withCoordinators(n) {
        networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
          networksStore.current.data = {
            ...NETWORK,
            coordinators: Array.from({ length: n }, (_, i) => ({ id: i + 1, name: `Coord ${i + 1}`, avatar_url: null })),
          }
        })
      }

      it('shows 4 coordinators and a Show N more control for the rest', async () => {
        withCoordinators(7)
        const wrapper = mountPage()
        await flushPromises()

        expect(wrapper.findAll('[data-testid^="network-coordinator-"]')).toHaveLength(4)
        const toggle = wrapper.get('[data-testid="network-show-coordinators-toggle"]')
        expect(toggle.text()).toBe('Show 3 more')

        await toggle.trigger('click')
        expect(wrapper.findAll('[data-testid^="network-coordinator-"]')).toHaveLength(7)
        expect(toggle.text()).toBe('Show fewer')
      })

      it('has no toggle when they all fit in the row', async () => {
        withCoordinators(4)
        const wrapper = mountPage()
        await flushPromises()

        expect(wrapper.find('[data-testid="network-show-coordinators-toggle"]').exists()).toBe(false)
      })
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

    describe('reports link (develop #919)', () => {
      function withReportingUrl(reportingUrl) {
        networksStore.fetchCurrent = vi.fn().mockImplementation(async () => {
          networksStore.current.data = { ...NETWORK, reporting_url: reportingUrl }
        })
      }

      it('links to the network reports from the actions menu and from under the impact figures', async () => {
        withReportingUrl('https://reports.example.org/dashboard/7')
        const wrapper = mountPage()
        await flushPromises()

        const menu = wrapper.find('[data-testid="network-show-reports"]')
        expect(menu.attributes('href')).toBe('https://reports.example.org/dashboard/7')
        expect(menu.attributes('target')).toBe('_blank')

        const inline = wrapper.find('[data-testid="network-show-reports-link"]')
        expect(inline.attributes('href')).toBe('https://reports.example.org/dashboard/7')
        expect(inline.attributes('target')).toBe('_blank')
        expect(inline.attributes('rel')).toContain('noopener')
      })

      it('has no reports link when the network has no dashboard', async () => {
        withReportingUrl(null)
        const wrapper = mountPage()
        await flushPromises()

        expect(wrapper.find('[data-testid="network-show-reports"]').exists()).toBe(false)
        expect(wrapper.find('[data-testid="network-show-reports-link"]').exists()).toBe(false)
      })
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
