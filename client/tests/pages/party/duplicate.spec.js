import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventDuplicatePage from '../../../app/pages/party/duplicate/[id].vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const EventFormStub = {
  props: ['groups', 'isAdmin', 'initialEvent', 'isDuplicate'],
  emits: ['created'],
  template: '<button data-testid="stub-create" @click="$emit(\'created\', 88)" />',
}

const GLOBAL_STUBS = {
  BAlert: BAlertStub,
  BButton: BButtonStub,
  EventForm: EventFormStub,
}

const SOURCE_EVENT = {
  id: 5,
  title: 'Repair Café',
  group: { id: 9, name: 'Acme Restarters' },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventDuplicatePage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/party/duplicate/[id]', () => {
  let eventsStore
  let dashboardStore
  let groupsStore
  let authStore
  let myGroups

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/party/duplicate/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    eventsStore = useEventsStore()
    eventsStore.fetchEvent = vi.fn().mockResolvedValue(SOURCE_EVENT)

    dashboardStore = useDashboardStore()
    dashboardStore.fetch = vi.fn().mockResolvedValue({ your_groups: [] })

    groupsStore = useGroupsStore()
    groupsStore.fetchNames = vi.fn().mockResolvedValue([])

    myGroups = vi.fn().mockResolvedValue({ data: [{ id: 9, name: 'Acme Restarters', role: 3, archived: false }] })
    vi.stubGlobal('useNuxtApp', () => ({ $api: { user: { myGroups } } }))

    authStore = useAuthStore()
    authStore.user = { role_name: 'Administrator' }
  })

  it('fetches the source event for the routed id on mount', () => {
    mountPage()
    expect(eventsStore.fetchEvent).toHaveBeenCalledWith(5)
  })

  it('shows a loading skeleton while loading', () => {
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="party-duplicate-loading"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button when the source event fails to load', async () => {
    eventsStore.fetchEvent = vi.fn().mockRejectedValue(new Error('boom'))
    eventsStore.current.error = { status: 404 }

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="party-duplicate-error"]').exists()).toBe(true)
    await wrapper.find('[data-testid="party-duplicate-retry"]').trigger('click')
    expect(eventsStore.fetchEvent).toHaveBeenCalledTimes(2)
  })

  it('shows a forbidden message when the user neither administers nor hosts the source group', async () => {
    authStore.user = { role_name: 'Host' }
    dashboardStore.data = { your_groups: [{ id: 1, role: 3 }] }
    eventsStore.current.data = SOURCE_EVENT

    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('[data-testid="party-duplicate-forbidden"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-create"]').exists()).toBe(false)
  })

  it('renders EventForm with isDuplicate=true and the source event as initialEvent, for an admin', async () => {
    eventsStore.current.data = SOURCE_EVENT

    const wrapper = mountPage()
    await flushPromises()

    const form = wrapper.findComponent(EventFormStub)
    expect(form.exists()).toBe(true)
    expect(form.props('initialEvent')).toEqual(SOURCE_EVENT)
    expect(form.props('isDuplicate')).toBe(true)
    expect(form.props('isAdmin')).toBe(true)
  })

  it('renders EventForm for a host of the source group, sourced from users/me/groups', async () => {
    authStore.user = { role_name: 'Host' }
    dashboardStore.data = { your_groups: [{ id: 9, role: 3 }] }
    eventsStore.current.data = SOURCE_EVENT

    const wrapper = mountPage()
    await flushPromises()

    const form = wrapper.findComponent(EventFormStub)
    expect(form.exists()).toBe(true)
    expect(form.props('groups')).toEqual([{ id: 9, name: 'Acme Restarters' }])
    expect(groupsStore.fetchNames).not.toHaveBeenCalled()
  })

  it('navigates to /party/edit/{id} when EventForm emits created', async () => {
    eventsStore.current.data = SOURCE_EVENT
    const navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)

    const wrapper = mountPage()
    await flushPromises()

    await wrapper.find('[data-testid="stub-create"]').trigger('click')

    expect(navigateToMock).toHaveBeenCalledWith('/party/edit/88')
  })
})
