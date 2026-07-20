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
function mountComponent(props = {}) {
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
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub, BBadge: BBadgeStub } },
  })
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

  it('shows the "None" message when there are no groups awaiting moderation for this network', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = []
    })

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.get('[data-testid="network-groups-moderation-empty"]').text()).toBe('None')
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

    expect(wrapper.find('[data-testid="network-groups-moderation-row-3"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="network-groups-moderation-row-4"]').exists()).toBe(false)
  })

  it('renders the full column set: photo, name+archived+tags, location, hosts, restarters, next event, moderation flag link', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = [
        {
          id: 3,
          name: 'Camden Restarters',
          networks: [{ id: 1 }],
          image: null,
          archived_at: '2020-01-01T00:00:00Z',
          tags: [{ id: 1, name: 'Scotland' }],
          location: { location: 'Camden', country: 'UK' },
          hosts: 2,
          restarters: 5,
          next_event: { start: '2026-08-01T10:00:00Z' },
        },
      ]
    })

    const wrapper = mountComponent()
    await flushPromises()

    const row = wrapper.get('[data-testid="network-groups-moderation-row-3"]')
    expect(row.text()).toContain('Camden Restarters')
    expect(row.text()).toContain('Archived')
    expect(row.text()).toContain('Scotland')
    expect(row.text()).toContain('Camden')
    expect(row.text()).toContain('UK')
    expect(row.text()).toContain('2')
    expect(row.text()).toContain('5')

    const link = wrapper.get('[data-testid="network-groups-moderation-link-3"]')
    expect(link.attributes('href')).toBe('/group/view/3')

    const flag = wrapper.get('[data-testid="network-groups-moderation-flag-3"]')
    expect(flag.attributes('href')).toBe('/group/edit/3')
    expect(flag.text()).toBe('Group requires moderation')
  })

  it('shows "None planned" when a group has no next event', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = [{ id: 3, name: 'Camden Restarters', networks: [{ id: 1 }], next_event: null }]
    })

    const wrapper = mountComponent()
    await flushPromises()

    expect(wrapper.get('[data-testid="network-groups-moderation-row-3"]').text()).toContain('None planned')
  })
})
