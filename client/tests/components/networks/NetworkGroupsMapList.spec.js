import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkGroupsMapList from '../../../app/components/networks/NetworkGroupsMapList.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'

const GroupMapStub = {
  props: ['groups', 'network', 'initialBounds', 'frameRequest'],
  emits: ['update:groupIdsInBounds', 'select'],
  template: '<div data-testid="stub-map" :data-ids="groups.map(g => g.id).join(\',\')" :data-network="network" />',
}
const GroupsTableStub = {
  props: { groups: Array, showFilters: Boolean, showTags: Boolean },
  emits: ['update:filters'],
  template: '<div data-testid="stub-table" :data-ids="groups.map(g => g.id).join(\',\')" :data-filters="String(showFilters)" :data-tags="String(showTags)" />',
}

function mountIt(props = {}) {
  return mount(NetworkGroupsMapList, {
    props: { networkId: 5, ...props },
    global: {
      plugins: [createI18n({ legacy: false, locale: 'en', messages: { en: {} }, missingWarn: false })],
      stubs: { GroupMap: GroupMapStub, GroupsTable: GroupsTableStub, GroupInfoModal: true, BAlert: true },
    },
  })
}

describe('NetworkGroupsMapList', () => {
  let store
  beforeEach(() => {
    setActivePinia(createPinia())
    store = useGroupsStore()
    store.fetchNames = vi.fn().mockResolvedValue([])
    store.fetchMine = vi.fn().mockResolvedValue([])
    store.fetchSummaries = vi.fn()
    store.names = [
      { id: 1, name: 'In', network_ids: [5], tag_ids: [3], archived_at: null },
      { id: 2, name: 'Elsewhere', network_ids: [6], tag_ids: [], archived_at: null },
      { id: 3, name: 'Archived', network_ids: [5], tag_ids: [], archived_at: '2024-01-01' },
    ]
  })

  it('maps and lists only the unarchived groups of this network', () => {
    const w = mountIt()
    expect(w.find('[data-testid="stub-map"]').attributes('data-ids')).toBe('1')
    expect(w.find('[data-testid="stub-table"]').attributes('data-ids')).toBe('1')
    expect(w.find('[data-testid="stub-map"]').attributes('data-network')).toBe('5')
  })

  it('follows the map viewport for the list, including an empty one', async () => {
    const w = mountIt()
    await w.findComponent(GroupMapStub).vm.$emit('update:groupIdsInBounds', [])
    expect(w.find('[data-testid="stub-table"]').attributes('data-ids')).toBe('')
  })

  it('narrows the pins to the filter bar and asks the map to reframe once typing stops', async () => {
    vi.useFakeTimers()
    try {
      store.names = [
        { id: 1, name: 'Alpha', network_ids: [5], tag_ids: [3], archived_at: null },
        { id: 4, name: 'Beta', network_ids: [5], tag_ids: [], archived_at: null },
      ]
      const w = mountIt({ canManageTags: true })
      expect(w.find('[data-testid="stub-map"]').attributes('data-ids')).toBe('1,4')

      await w.findComponent(GroupsTableStub).vm.$emit('update:filters', { name: 'bet', tags: [] })
      expect(w.find('[data-testid="stub-map"]').attributes('data-ids')).toBe('4')
      expect(w.findComponent(GroupMapStub).props('frameRequest')).toBe(0)

      await vi.advanceTimersByTimeAsync(600)
      expect(w.findComponent(GroupMapStub).props('frameRequest')).toBe(1)

      await w.findComponent(GroupsTableStub).vm.$emit('update:filters', { name: '', tags: [3] })
      expect(w.find('[data-testid="stub-map"]').attributes('data-ids')).toBe('1')
    } finally {
      vi.useRealTimers()
    }
  })

  it('shows the filter bar and passes the tag gate through', () => {
    const w = mountIt({ canManageTags: true })
    expect(w.find('[data-testid="stub-table"]').attributes('data-filters')).toBe('true')
    expect(w.find('[data-testid="stub-table"]').attributes('data-tags')).toBe('true')
  })
})
