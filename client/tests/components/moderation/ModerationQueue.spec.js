import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ModerationQueue from '../../../app/components/moderation/ModerationQueue.vue'
import { useModerationStore } from '../../../app/stores/moderation.js'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountQueue(type, props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...clientEn } } })
  return mount(ModerationQueue, {
    props: { type, ...props },
    global: { plugins: [i18n], stubs: { NuxtLink: NuxtLinkStub } },
  })
}

describe('components/moderation/ModerationQueue', () => {
  let store

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useModerationStore()
  })

  it('renders events awaiting moderation, each linking to its event page', async () => {
    store.fetchEvents = vi.fn(async () => {
      store.events.data = [{ id: 7, title: 'Repair Café' }, { id: 8, title: 'Fixfest' }]
    })

    const wrapper = mountQueue('events')
    await flushPromises()

    expect(wrapper.get('[data-testid="moderation-queue-events"]').text()).toContain('Events requiring moderation')
    expect(wrapper.get('[data-testid="moderation-queue-events-item-7"]').attributes('href')).toBe('/party/view/7')
    expect(wrapper.text()).toContain('Repair Café')
    expect(wrapper.text()).toContain('Fixfest')
  })

  it('renders groups awaiting moderation, each linking to its group page', async () => {
    store.fetchGroups = vi.fn(async () => {
      store.groups.data = [{ id: 3, name: 'Camden Restarters' }]
    })

    const wrapper = mountQueue('groups')
    await flushPromises()

    expect(wrapper.get('[data-testid="moderation-queue-groups"]').text()).toContain('Groups requiring moderation')
    expect(wrapper.get('[data-testid="moderation-queue-groups-item-3"]').attributes('href')).toBe('/group/view/3')
    expect(wrapper.text()).toContain('Camden Restarters')
  })

  it('renders nothing when the queue is empty (matches legacy - no empty panel)', async () => {
    store.fetchEvents = vi.fn(async () => {
      store.events.data = []
    })

    const wrapper = mountQueue('events')
    await flushPromises()

    expect(wrapper.find('[data-testid="moderation-queue-events"]').exists()).toBe(false)
  })

  it('fetches the right queue on mount by type', () => {
    store.fetchEvents = vi.fn(async () => {})
    store.fetchGroups = vi.fn(async () => {})

    mountQueue('groups')
    expect(store.fetchGroups).toHaveBeenCalled()
    expect(store.fetchEvents).not.toHaveBeenCalled()
  })

  describe('network scoping (:networks prop)', () => {
    it('scopes groups to items whose networks array includes one of the given ids', async () => {
      store.fetchGroups = vi.fn(async () => {
        store.groups.data = [
          { id: 3, name: 'Camden Restarters', networks: [{ id: 33, name: 'Test Network' }] },
          { id: 4, name: 'Other City Restarters', networks: [{ id: 99, name: 'Other Network' }] },
          { id: 5, name: 'No Network Group', networks: [] },
        ]
      })

      const wrapper = mountQueue('groups', { networks: [33] })
      await flushPromises()

      expect(wrapper.find('[data-testid="moderation-queue-groups-item-3"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="moderation-queue-groups-item-4"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="moderation-queue-groups-item-5"]').exists()).toBe(false)
    })

    it('scopes events via their nested group.networks array', async () => {
      store.fetchEvents = vi.fn(async () => {
        store.events.data = [
          { id: 7, title: 'Repair Café', group: { networks: [{ id: 33 }] } },
          { id: 8, title: 'Fixfest', group: { networks: [{ id: 99 }] } },
          { id: 9, title: 'No Group Networks', group: { networks: [] } },
        ]
      })

      const wrapper = mountQueue('events', { networks: [33] })
      await flushPromises()

      expect(wrapper.find('[data-testid="moderation-queue-events-item-7"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="moderation-queue-events-item-8"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="moderation-queue-events-item-9"]').exists()).toBe(false)
    })

    it('does not filter when no networks prop is given (the /party and /group/map usage)', async () => {
      store.fetchGroups = vi.fn(async () => {
        store.groups.data = [{ id: 3, name: 'Camden Restarters', networks: [{ id: 99 }] }]
      })

      const wrapper = mountQueue('groups')
      await flushPromises()

      expect(wrapper.find('[data-testid="moderation-queue-groups-item-3"]').exists()).toBe(true)
    })
  })

  // A failed fetch used to be swallowed entirely (.catch(() => {})), which
  // to an Administrator looked identical to "nothing is awaiting
  // moderation" - the exact symptom the parity investigation traced this
  // to. It must now surface visibly, and even when alwaysShow is false
  // (the /group/all, /group/nearby, /party, /group/map usage), since that's
  // precisely the case where "hidden on error" and "hidden because empty"
  // were indistinguishable.
  describe('fetch failure', () => {
    it('surfaces an error state instead of silently rendering nothing, even with alwaysShow false', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      store.fetchGroups = vi.fn(async () => {
        store.groups.error = { status: 500 }
        throw new Error('boom')
      })

      const wrapper = mountQueue('groups')
      await flushPromises()

      const panel = wrapper.get('[data-testid="moderation-queue-groups"]')
      expect(panel.text()).toContain('Groups requiring moderation')
      expect(wrapper.get('[data-testid="moderation-queue-groups-error"]').text()).toContain(
        "Sorry, we couldn't load the moderation queue."
      )
      expect(wrapper.find('[data-testid="moderation-queue-groups-empty"]').exists()).toBe(false)
      expect(consoleError).toHaveBeenCalled()

      consoleError.mockRestore()
    })

    it('applies the same error handling to the events queue', async () => {
      const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
      store.fetchEvents = vi.fn(async () => {
        store.events.error = { status: 500 }
        throw new Error('boom')
      })

      const wrapper = mountQueue('events')
      await flushPromises()

      expect(wrapper.get('[data-testid="moderation-queue-events-error"]').exists()).toBe(true)
      expect(consoleError).toHaveBeenCalled()

      consoleError.mockRestore()
    })

    it('retries the fetch when the retry button is clicked', async () => {
      vi.spyOn(console, 'error').mockImplementation(() => {})
      store.fetchGroups = vi.fn(async () => {
        store.groups.error = { status: 500 }
        throw new Error('boom')
      })

      const wrapper = mountQueue('groups')
      await flushPromises()
      expect(store.fetchGroups).toHaveBeenCalledTimes(1)

      await wrapper.get('[data-testid="moderation-queue-groups-retry"]').trigger('click')
      await flushPromises()
      expect(store.fetchGroups).toHaveBeenCalledTimes(2)
    })
  })

  describe('alwaysShow', () => {
    it('renders the heading with a "None" message on an empty (possibly filtered) queue', async () => {
      store.fetchGroups = vi.fn(async () => {
        store.groups.data = []
      })

      const wrapper = mountQueue('groups', { alwaysShow: true })
      await flushPromises()

      const panel = wrapper.get('[data-testid="moderation-queue-groups"]')
      expect(panel.text()).toContain('Groups requiring moderation')
      expect(wrapper.get('[data-testid="moderation-queue-groups-empty"]').text()).toBe('Nothing is awaiting moderation.')
    })

    it('still renders the item list (not the "None" message) when there are items', async () => {
      store.fetchGroups = vi.fn(async () => {
        store.groups.data = [{ id: 3, name: 'Camden Restarters', networks: [] }]
      })

      const wrapper = mountQueue('groups', { alwaysShow: true })
      await flushPromises()

      expect(wrapper.find('[data-testid="moderation-queue-groups-empty"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="moderation-queue-groups-item-3"]').exists()).toBe(true)
    })
  })
})
