import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventEditPage from '../../../app/pages/party/edit/[id].vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
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
  let groupsStore
  let authStore
  let mockApi

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useRoute', () => ({ params: { id: '5' }, query: {}, fullPath: '/party/edit/5' }))
    vi.stubGlobal('navigateTo', vi.fn())

    // The Event log tab calls $api.event.audits(id) lazily on first open.
    mockApi = { event: { audits: vi.fn().mockResolvedValue({ data: [] }) } }
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi }))

    eventsStore = useEventsStore()
    eventsStore.fetchEvent = vi.fn().mockResolvedValue(BASE_EVENT)
    eventsStore.uploadEventImage = vi.fn().mockResolvedValue({ image_url: '/uploads/mid_x.png' })

    // Host status now comes from the UNCAPPED memberships list
    // (GET /api/v2/users/me/groups via groupsStore), not the capped dashboard
    // your_groups. Default: no memberships.
    groupsStore = useGroupsStore()
    groupsStore.memberships = []
    groupsStore.fetchMemberships = vi.fn(function () {
      return Promise.resolve(this.memberships)
    })

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
    groupsStore.memberships = [{ id: 1, name: 'Other', role: 3, archived: false }]
    eventsStore.current.data = BASE_EVENT

    const wrapper = mountPage()

    expect(wrapper.find('[data-testid="event-edit-forbidden"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="stub-updated"]').exists()).toBe(false)
  })

  it('renders the form when the user hosts the event group (role 3 in memberships)', () => {
    authStore.user = { role_name: 'Host' }
    groupsStore.memberships = [{ id: 9, name: 'Hosted', role: 3, archived: false }]
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
    groupsStore.memberships = []
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
  // edit.blade.php:30-42 - Details / Photos tabs above the panel. The page used
  // to render the form bare with Photos as a section below it, i.e. no tab
  // structure at all. develop's third tab (Event log) is gated on
  // `$audits && Administrator` and has no API endpoint yet, so it is absent
  // here by necessity rather than by choice.
  describe('tabs', () => {
    function mountEditable() {
      authStore.user = { role_name: 'Administrator' }
      eventsStore.current.data = BASE_EVENT
      return mountPage()
    }

    it('shows Details and Photos tabs, with Details active and Photos hidden', () => {
      const wrapper = mountEditable()

      expect(wrapper.find('[data-testid="event-edit-tab-details"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-edit-tab-photos"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-edit-tab-details"]').classes()).toContain('active')
      expect(wrapper.find('[data-testid="event-edit-pane-photos"]').attributes('style')).toContain('display: none')
    })

    // edit.blade.php:40 gates the Event log tab on Administrator. It fetches
    // lazily on first open - it is an admin-only panel most page loads never
    // show - so the endpoint must not be called just by landing on the page.
    it('hides the Event log tab from a non-administrator', () => {
      authStore.user = { role_name: 'Host' }
      groupsStore.memberships = [{ id: 9, name: 'Hosted', role: 3, archived: false }]
      eventsStore.current.data = BASE_EVENT
      const wrapper = mountPage()

      expect(wrapper.find('[data-testid="event-edit-tab-log"]').exists()).toBe(false)
    })

    it('shows the Event log tab for an administrator and fetches only on open', async () => {
      const wrapper = mountEditable()

      expect(wrapper.find('[data-testid="event-edit-tab-log"]').exists()).toBe(true)
      expect(mockApi.event.audits).not.toHaveBeenCalled()

      await wrapper.find('[data-testid="event-edit-tab-log"]').trigger('click')
      await flushPromises()

      expect(mockApi.event.audits).toHaveBeenCalledWith(5)
      expect(wrapper.find('[data-testid="event-edit-pane-log"]').attributes('style') || '').not.toContain('display: none')
    })

    it('switches to the Photos pane on click', async () => {
      const wrapper = mountEditable()

      await wrapper.find('[data-testid="event-edit-tab-photos"]').trigger('click')

      expect(wrapper.find('[data-testid="event-edit-pane-photos"]').attributes('style') || '').not.toContain('display: none')
      expect(wrapper.find('[data-testid="event-edit-pane-details"]').attributes('style')).toContain('display: none')
    })
  })


  // events/edit.blade.php lists the event's existing photos with a "Remove
  // file" link on each. The API has supported deletion all along
  // (DELETE /api/v2/events/{id}/images/{idimages}); nothing called it.
  describe('existing photos', () => {
    const WITH_IMAGES = { ...BASE_EVENT, images: [{ idimages: 11, path: 'a.jpg' }, { idimages: 12, path: 'b.jpg' }] }

    it('renders each existing photo with a remove control', async () => {
      eventsStore.fetchEvent = vi.fn().mockResolvedValue(WITH_IMAGES)
      eventsStore.current.data = WITH_IMAGES

      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.findAll('[data-testid="event-edit-photo"]')).toHaveLength(2)
      expect(wrapper.find('[data-testid="event-edit-photo-remove-11"]').exists()).toBe(true)
    })

    it('deletes the photo it was clicked on', async () => {
      eventsStore.fetchEvent = vi.fn().mockResolvedValue(WITH_IMAGES)
      eventsStore.current.data = WITH_IMAGES
      eventsStore.deleteEventImage = vi.fn().mockResolvedValue({})

      const wrapper = mountPage()
      await flushPromises()

      await wrapper.find('[data-testid="event-edit-photo-remove-12"]').trigger('click')
      await flushPromises()

      expect(eventsStore.deleteEventImage).toHaveBeenCalledWith(5, 12)
    })

    it('shows an error when deletion fails', async () => {
      eventsStore.fetchEvent = vi.fn().mockResolvedValue(WITH_IMAGES)
      eventsStore.current.data = WITH_IMAGES
      eventsStore.deleteEventImage = vi.fn().mockRejectedValue(new Error('nope'))

      const wrapper = mountPage()
      await flushPromises()

      await wrapper.find('[data-testid="event-edit-photo-remove-11"]').trigger('click')
      await flushPromises()

      expect(wrapper.find('[data-testid="event-edit-image-error"]').exists()).toBe(true)
    })

    it('shows no photo grid when the event has none', async () => {
      const wrapper = mountPage()
      await flushPromises()

      expect(wrapper.find('[data-testid="event-edit-photo"]').exists()).toBe(false)
    })
  })
})
