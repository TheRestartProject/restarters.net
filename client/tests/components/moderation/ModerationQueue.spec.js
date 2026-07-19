import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ModerationQueue from '../../../app/components/moderation/ModerationQueue.vue'
import { useModerationStore } from '../../../app/stores/moderation.js'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

function mountQueue(type) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...clientEn } } })
  return mount(ModerationQueue, {
    props: { type },
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
})
