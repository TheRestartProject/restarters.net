import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import NetworkGroupsModerationTable from '../../../app/components/networks/NetworkGroupsModerationTable.vue'
import { useModerationStore } from '../../../app/stores/moderation.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BBadgeStub = { template: '<span v-bind="$attrs"><slot /></span>' }

// lang/en/networks.php gained `moderation.group_requires_moderation`
// alongside this Nuxt work (parity-v2/networks.md gap #2) but
// client/i18n/locales/en.json is a generated, checked-in artifact this
// change intentionally leaves untouched - overlay the new key here.
function mountComponent({ slots, ...props } = {}) {
  const messages = {
    en: {
      ...en,
      ...clientEn,
      networks: {
        ...en.networks,
        moderation: { group_requires_moderation: 'Group requires moderation' },
      },
    },
  }
  const i18n = createI18n({ legacy: false, locale: 'en', messages })

  return mount(NetworkGroupsModerationTable, {
    props: { networkId: 1, ...props },
    slots: slots || {},
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub, GroupsTable: GroupsTableStub } },
  })
}

// This component delegates its whole rendering to GroupsTable in `approve`
// mode, exactly as develop's GroupsRequiringModeration does. These tests
// therefore assert what is handed over, not how GroupsTable draws it -
// GroupsTable's own spec covers the columns, sorting and the amber cell.
const GroupsTableStub = {
  // Typed, not an array: Vue only boolean-casts a bare `approve`
  // attribute when the prop declares Boolean, otherwise it arrives as ''.
  props: { groups: Array, approve: Boolean, showJoin: Boolean, showFilters: Boolean },
  template: '<div data-testid="stub-groups-table" :data-approve="String(approve)" :data-ids="groups.map(g => g.id).join(\',\')" />',
}

describe('components/networks/NetworkGroupsModerationTable', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useModerationStore()
  })

  it('fetches the moderation groups queue on mount', () => {
    store.fetchGroups = vi.fn(async () => {})
    mountComponent()
    expect(store.fetchGroups).toHaveBeenCalled()
  })

  // The empty placeholder is the caller's, matching develop's split:
  // GroupsRequiringModeration.vue renders nothing when empty and NetworkPage
  // supplies its own "None" under the section heading. /group/all reuses this
  // component with no heading, where an unslotted "None" read as a stray word
  // above the page title.
  it('renders the empty slot, and nothing of its own, when there are no groups', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = []
    })

    const wrapper = mountComponent({ slots: { empty: '<p data-testid="none-slot">None</p>' } })
    await flushPromises()

    expect(wrapper.get('[data-testid="none-slot"]').text()).toBe('None')
    expect(wrapper.find('.table-section').exists()).toBe(false)
  })

  it('renders nothing when empty and the caller supplies no placeholder', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = []
    })

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.get('[data-testid="network-groups-moderation-table"]').text().trim()).toBe('')
  })

  // Replaces two tests that asserted this component's own photo/name/location/
  // hosts/restarters/next-event/moderation-link columns. It no longer draws
  // any of them - GroupsTable does, and its spec covers them. Asserting them
  // here would only re-test GroupsTable through a second component.
  it('hands the scoped groups to GroupsTable in approve mode', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = [{ id: 3, name: 'Camden Restarters', networks: [{ id: 1 }] }]
    })

    const wrapper = mountComponent({ networkId: 1 })
    await flushPromises()

    const table = wrapper.find('[data-testid="stub-groups-table"]')
    expect(table.exists()).toBe(true)
    expect(table.attributes('data-approve')).toBe('true')
  })

  it('scopes to groups whose networks array includes this network id', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = [
        { id: 3, name: 'Camden Restarters', networks: [{ id: 1 }] },
        { id: 4, name: 'Other City Restarters', networks: [{ id: 99 }] },
      ]
    })

    const wrapper = mountComponent({ networkId: 1 })
    await flushPromises()

    expect(wrapper.find('[data-testid="stub-groups-table"]').attributes('data-ids')).toBe('3')
  })

  
  })
