import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventEditPage from '../../../app/pages/party/edit/[id].vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useDashboardStore } from '../../../app/stores/dashboard.js'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const EventFormStub = {
  props: ['eventId', 'initialEvent', 'isAdmin'],
  emits: ['updated'],
  template: '<button data-testid="stub-updated" @click="$emit(\'updated\', eventId)" />',
}
const TusImageUploadStub = {
  emits: ['uploaded', 'upload-error'],
  template:
    '<div><button data-testid="stub-upload-ok" @click="$emit(\'uploaded\', { uploadKey: \'key123\' })" /><button data-testid="stub-upload-fail" @click="$emit(\'upload-error\', \'boom\')" /></div>',
}

const GLOBAL_STUBS = {
  NuxtLink: NuxtLinkStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  EventForm: EventFormStub,
  TusImageUpload: TusImageUploadStub,
}

const BASE_EVENT = {
  id: 5,
  title: 'Repair Café',
  group: { id: 9, name: 'Acme Restarters' },
}

function mountPage() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventEditPage, {
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

describe('pages/party/edit/[id]', () => {
  let eventsStore
  let dashboardStore
  let authStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/party/edit/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    eventsStore = useEventsStore()
    eventsStore.fetchEvent = vi.fn().mockResolvedValue(BASE_EVENT)
    eventsStore.uploadEventImage = vi.fn().mockResolvedValue({ image_url: '/uploads/mid_x.png' })

    dashboardStore = useDashboardStore()
    dashboardStore.fetch = vi.fn().mockResolvedValue({ your_groups: [] })

    authStore = useAuthStore()
    authStore.user = { role_name: 'Administrator' }
  })

  it('fetches the event for the routed id on mount', () => {
    mountPage()
    expect(eventsStore.fetchEvent).toHaveBeenCalledWith(5)
  })

  it('shows a loading skeleton while loading', () => {
    eventsStore.current.loading = true
    const wrapper = mountPage()
    expect(wrapper.find('[data-testid="event-edit-loading"]').exists()).toBe(true)
  })

  it('shows an error state with a retry button', async () => {
    eventsStore.current.error = { status: 404 }
    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-edit-error"]').exists()).toBe(true)
    await wrapper.find('[data-testid="event-edit-retry"]').trigger('click')
    expect(eventsStore.fetchEvent).toHaveBeenCalledTimes(2)
  })

  it('shows a forbidden message instead of the form when the user neither administers nor hosts the group', () => {
    authStore.user = { role_name: 'Host' }
    dashboardStore.data = { your_groups: [{ id: 1, role: 3 }] }
    eventsStore.current.data = BASE_EVENT

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-edit-forbidden"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-updated"]').exists()).toBe(false)
  })

  it('renders the form when the user hosts the event group (role===3 in your_groups)', () => {
    authStore.user = { role_name: 'Host' }
    dashboardStore.data = { your_groups: [{ id: 9, role: 3 }] }
    eventsStore.current.data = BASE_EVENT

    const wrapper = mountPage()

    const form = wrapper.findComponent(EventFormStub)
    expect(form.exists()).toBe(true)
    expect(form.props('eventId')).toBe(5)
    expect(form.props('initialEvent')).toEqual(BASE_EVENT)
    expect(form.props('isAdmin')).toBe(false)
  })

  it('renders the form for an Administrator regardless of hosted groups', () => {
    authStore.user = { role_name: 'Administrator' }
    dashboardStore.data = { your_groups: [] }
    eventsStore.current.data = BASE_EVENT

    const wrapper = mountPage()

    expect(wrapper.findComponent(EventFormStub).props('isAdmin')).toBe(true)
    expect(wrapper.find('[data-testid="event-edit-forbidden"]').exists()).toBe(false)
  })

  it('shows a success message and re-fetches when EventForm emits updated', async () => {
    eventsStore.current.data = BASE_EVENT
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-updated"]').trigger('click')

    expect(wrapper.find('[data-testid="event-edit-success"]').exists()).toBe(true)
    expect(eventsStore.fetchEvent).toHaveBeenCalledTimes(2)
  })

  it('calls uploadEventImage with the emitted upload key', async () => {
    eventsStore.current.data = BASE_EVENT
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')

    expect(eventsStore.uploadEventImage).toHaveBeenCalledWith(5, 'key123')
    expect(wrapper.find('[data-testid="event-edit-image-success"]').exists()).toBe(true)
  })

  it('shows an image upload error message from TusImageUpload', async () => {
    eventsStore.current.data = BASE_EVENT
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-upload-fail"]').trigger('click')

    expect(wrapper.find('[data-testid="event-edit-image-error"]').text()).toBe('boom')
  })

  it('shows an image upload error message when uploadEventImage itself rejects', async () => {
    eventsStore.current.data = BASE_EVENT
    eventsStore.uploadEventImage = vi.fn().mockRejectedValue(new Error('server error'))
    const wrapper = mountPage()

    await wrapper.find('[data-testid="stub-upload-ok"]').trigger('click')

    expect(wrapper.find('[data-testid="event-edit-image-error"]').exists()).toBe(true)
  })
})
