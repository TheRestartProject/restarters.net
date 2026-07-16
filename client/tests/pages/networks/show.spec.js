import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkShowPage from '../../../app/pages/networks/[id].vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import { useSessionStore } from '../../../app/stores/session.js'
import { useNetworksStore } from '../../../app/stores/networks.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
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
const AdminCrudTableStub = {
  props: ['items', 'displayKey', 'tableFields', 'formFields', 'labels', 'testidPrefix', 'editId', 'fetchItems', 'createItem', 'updateItem', 'deleteItem'],
  template: '<div data-testid="stub-admin-crud-table" :data-item-count="items.length" />',
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
  NetworkStats: NetworkStatsStub,
  AssociateGroupsModal: AssociateGroupsModalStub,
  AdminCrudTable: AdminCrudTableStub,
  GroupsTable: GroupsTableStub,
}

function setLoggedInUser(user) {
  useSessionStore().user = user
  useAuthStore().user = user
  useAuthStore().token = 'tok-1'
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

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

    it('renders the network name, stats and groups table', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-name"]').text()).toBe('Test London')
      expect(wrapper.find('[data-testid="stub-network-stats"]').attributes('data-groups-count')).toBe('1')
      expect(wrapper.find('[data-testid="stub-groups-table"]').attributes('data-row-count')).toBe('1')
    })

    it('always renders tags management (view access implies manage access)', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="tags-management"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="stub-admin-crud-table"]').attributes('data-item-count')).toBe('1')
    })

    it('shows the "Add groups" action', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="network-show-add-groups"]').exists()).toBe(true)
    })

    it('opens the associate-groups modal with candidates excluding groups already in the network', async () => {
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
      // 9 is already in the network, 11 is archived - only 10 is a candidate.
      expect(modal.attributes('data-candidate-count')).toBe('1')
    })

    it('re-fetches groups filtered by tag when the tag filter changes', async () => {
      const wrapper = mountPage()
      await flushPromises()
      networksStore.fetchGroups.mockClear()

      await wrapper.find('[data-testid="network-show-tag-filter"]').setValue('1')
      await flushPromises()

      expect(networksStore.fetchGroups).toHaveBeenCalledWith(1, { group_tag: 1 })
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
