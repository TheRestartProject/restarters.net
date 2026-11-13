import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventForm from '../../../app/components/events/EventForm.vue'
import EventDuplicateModal from '../../../app/components/events/EventDuplicateModal.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// RichTextEditor/LocationPicker/vue-datepicker-next's DatePicker are unit
// tested (or vendored) on their own - stub them here to plain inputs so
// EventForm's own logic (payload shape, validation, group timezone
// inheritance, 422 rendering) is what's under test, same convention as
// tests/components/groups/GroupForm.spec.js.
const RichTextEditorStub = {
  props: ['modelValue', 'hasError', 'testid'],
  emits: ['update:modelValue'],
  template: '<textarea :data-testid="testid" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
}
const LocationPickerStub = {
  props: ['location', 'hasError', 'label', 'placeholder', 'helpText', 'errorText', 'showPostcode'],
  emits: ['update:location'],
  template:
    '<input data-testid="location-picker-input" :value="location" @input="$emit(\'update:location\', $event.target.value)" />',
}

// vue-datepicker-next's default export minifies to an internal component
// name ("gt" as of the pinned version) rather than "DatePicker", so
// global.stubs (which matches by resolved component name) can't target it -
// mock the module itself instead, which works regardless of that internal
// name.
vi.mock('vue-datepicker-next', () => ({
  default: {
    props: ['value', 'placeholder'],
    emits: ['update:value'],
    template:
      '<input data-testid="event-form-date-stub" :value="value" :placeholder="placeholder" @input="$emit(\'update:value\', $event.target.value)" />',
  },
}))

// EventForm pulls in EventVenueMap.vue for the venue preview, which uses
// @vue-leaflet/vue-leaflet - that package dynamic-import()s Leaflet's marker
// PNGs to patch L.Icon.Default, which needs a bundler rather than Node's
// module loader. Mock the package rather than mount it, same approach
// tests/pages/party/view.spec.js and GroupMap.spec.js take.
const { LMapStub, LTileLayerStub, LMarkerStub } = vi.hoisted(() => ({
  LMapStub: { name: 'LMap', props: ['zoom', 'center', 'options', 'useGlobalLeaflet'], template: '<div class="stub-lmap"><slot /></div>' },
  LTileLayerStub: { name: 'LTileLayer', props: ['url', 'attribution'], template: '<div class="stub-ltilelayer" />' },
  LMarkerStub: { name: 'LMarker', props: ['latLng', 'icon', 'interactive'], template: '<div class="stub-lmarker" />' },
}))

vi.mock('@vue-leaflet/vue-leaflet', () => ({ LMap: LMapStub, LTileLayer: LTileLayerStub, LMarker: LMarkerStub }))

const BFormStub = {
  emits: ['submit'],
  template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>',
}
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BAlertStub = { template: '<div><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }
const BFormCheckboxStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template:
    '<label><input type="checkbox" :checked="modelValue" v-bind="$attrs" @change="$emit(\'update:modelValue\', $event.target.checked)" /><slot /></label>',
}
const BCardStub = { template: '<div><slot /></div>' }
const BCardHeaderStub = { template: '<div><slot /></div>' }
const BCardBodyStub = { template: '<div><slot /></div>' }
// Stands in for GroupNetworkData.vue (unit-tested on its own, and reused
// as-is here rather than duplicated - see EventForm.vue's import comment) -
// same stub shape tests/components/groups/GroupForm.spec.js uses for it.
const GroupNetworkDataStub = {
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<div data-testid="stub-group-network-data" />',
}

const GLOBAL_STUBS = {
  RichTextEditor: RichTextEditorStub,
  LocationPicker: LocationPickerStub,
  BForm: BFormStub,
  BFormGroup: BFormGroupStub,
  BAlert: BAlertStub,
  BButton: BButtonStub,
  BFormCheckbox: BFormCheckboxStub,
  BCard: BCardStub,
  BCardHeader: BCardHeaderStub,
  BCardBody: BCardBodyStub,
  GroupNetworkData: GroupNetworkDataStub,
}

function mountForm(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventForm, {
    props,
    global: {
      plugins: [i18n],
      stubs: GLOBAL_STUBS,
    },
  })
}

// EventGroup.vue's group picker is components/forms/TagMultiselect.vue now
// (develop uses vue-multiselect) - set its value through the component
// rather than through a native <select> API.
function setGroupField(wrapper, value) {
  return wrapper.findComponent('[data-testid="event-form-group"]').vm.$emit('update:modelValue', value)
}

async function fillRequiredFields(wrapper, { group = true } = {}) {
  await wrapper.find('[data-testid="event-form-venue"]').setValue('Repair Café')
  await wrapper.find('[data-testid="event-form-description"]').setValue('<p>Bring your broken things</p>')
  await wrapper.find('[data-testid="location-picker-input"]').setValue('Town Hall')
  if (group) {
    await setGroupField(wrapper, 9)
  }
  await wrapper.find('[data-testid="event-form-date-stub"]').setValue('2026-08-20')
  await wrapper.find('[data-testid="event-form-start"]').setValue('10:00')
  await wrapper.find('[data-testid="event-form-end"]').setValue('12:00')
}

describe('components/events/EventForm', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchDetails = vi.fn().mockResolvedValue(null)
  })

  describe('create mode', () => {
    // Rendered-parity fix: develop's date field shows "No date selected"
    // placeholder text (bootstrap-vue's own untranslated library default,
    // ported here as a real translated key since there's no develop copy
    // to reuse) rather than rendering empty.
    it('shows a "No date selected" placeholder on the date field', () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      expect(wrapper.find('[data-testid="event-form-date-stub"]').attributes('placeholder')).toBe('No date selected')
    })

    it('shows validation errors and does not submit when required fields are empty', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn()

      // Two groups, not one - a single-group list now auto-selects
      // synchronously on mount (onMounted has no more awaited timezone
      // fetch to delay it past this synchronous submit), which would
      // leave the group field validly populated and defeat this case.
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }, { id: 10, name: 'Other Group' }] })
      await wrapper.find('[data-testid="event-form"]').trigger('submit')

      expect(wrapper.find('[data-testid="event-form-venue-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-description-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-group-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-date-error"]').exists()).toBe(true)
      expect(store.createEvent).not.toHaveBeenCalled()

      // EventGroup.vue's hasError styling (a red border, ported via
      // EventForm.vue's own scoped style over the shared .multiselect
      // rules) is driven by this class.
      expect(wrapper.findComponent('[data-testid="event-form-group"]').classes()).toContain('hasError')
    })

    it('submits the exact payload field names createEventv2 expects and emits created', async () => {
      groupsStore.fetchDetails = vi.fn().mockResolvedValue({ timezone: 'Europe/London', location: null })

      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.createEvent).toHaveBeenCalledWith({
        start: '2026-08-20T09:00:00Z',
        end: '2026-08-20T11:00:00Z',
        title: 'Repair Café',
        description: '<p>Bring your broken things</p>',
        location: 'Town Hall',
        online: false,
        link: null,
        timezone: 'Europe/London',
        network_data: '{}',
        groupid: 9,
      })
      expect(wrapper.emitted('created')).toEqual([[42]])
    })

    it('does not require a location when online is ticked', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await wrapper.find('[data-testid="event-form-venue"]').setValue('Online Repair Café')
      await wrapper.find('[data-testid="event-form-description"]').setValue('<p>Bring your broken things</p>')
      await wrapper.find('[data-testid="event-form-online"] input').setValue(true)
      await setGroupField(wrapper, 9)
      await wrapper.find('[data-testid="event-form-date-stub"]').setValue('2026-08-20')
      await wrapper.find('[data-testid="event-form-start"]').setValue('10:00')
      await wrapper.find('[data-testid="event-form-end"]').setValue('12:00')
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="event-form-location-error"]').exists()).toBe(false)
      expect(store.createEvent).toHaveBeenCalled()
    })

    it('rejects an end time that is not after the start time', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn()

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form-start"]').setValue('14:00')
      await wrapper.find('[data-testid="event-form-end"]').setValue('12:00')
      await wrapper.find('[data-testid="event-form"]').trigger('submit')

      expect(wrapper.find('[data-testid="event-form-end-error"]').text()).toContain('after the start time')
      expect(store.createEvent).not.toHaveBeenCalled()
    })

    it('rejects an invalid link without hitting the store', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn()

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form-link"]').setValue('not-a-url')
      await wrapper.find('[data-testid="event-form"]').trigger('submit')

      expect(wrapper.find('[data-testid="event-form-link-error"]').exists()).toBe(true)
      expect(store.createEvent).not.toHaveBeenCalled()
    })

    it('renders server-side 422 field errors and a general error banner', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn().mockRejectedValue({
        status: 422,
        data: { errors: { title: ['That title is too long.'] } },
      })

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="event-form-venue-error"]').text()).toContain('too long')
      expect(wrapper.find('[data-testid="event-form-error"]').exists()).toBe(true)
    })

    it('auto-selects the group when there is only one option', async () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      expect(wrapper.findComponent('[data-testid="event-form-group"]').props('modelValue')).toBe(9)
    })

    it('inherits the timezone from the selected group (no visible per-event control, matching develop) and re-inherits on a later group change', async () => {
      groupsStore.fetchDetails = vi.fn().mockResolvedValue({ timezone: 'Europe/Paris', location: { location: 'Paris HQ' } })

      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }, { id: 10, name: 'Other Group' }] })
      await setGroupField(wrapper, 9)
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      await fillRequiredFields(wrapper, { group: false })
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()
      expect(store.createEvent.mock.calls[0][0].timezone).toBe('Europe/Paris')

      // A later group change re-inherits the new group's timezone - there's
      // no per-event override to protect (develop has none either).
      groupsStore.fetchDetails = vi.fn().mockResolvedValue({ timezone: 'Europe/London', location: null })
      await setGroupField(wrapper, 10)
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()
      expect(store.createEvent.mock.calls[1][0].timezone).toBe('Europe/London')
    })

    // EventAddEdit.vue:99/258 - the notice tells the host what submitting will
    // do, and that differs entirely when the group's networks auto-approve.
    // This branch used to be unreachable: the component hardcoded the
    // "a coordinator will confirm it" copy on the grounds that auto_approve
    // wasn't available client-side, when GET /api/v2/groups/{id} had been
    // returning it all along.
    it('switches the "before submit" notice to the auto-approved copy for an auto-approving group', async () => {
      groupsStore.fetchDetails = vi.fn(async (id) => {
        const detail = { timezone: 'Europe/London', location: null, auto_approve: true }
        groupsStore.details[id] = detail
        return detail
      })

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      const notice = () => wrapper.find('[data-testid="event-form-notice"]').text()

      expect(notice()).toBe('Once confirmed by a coordinator, your event will be made public.')

      await setGroupField(wrapper, 9)
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      expect(notice()).toBe('When you create or save this event, it will be made public.')
    })

    it('shows a "use group location" shortcut that fills the location field, hidden when online', async () => {
      groupsStore.fetchDetails = vi.fn(async (id) => {
        const detail = { timezone: 'Europe/London', location: { location: 'Group HQ, London' } }
        groupsStore.details[id] = detail
        return detail
      })

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await setGroupField(wrapper, 9)
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="event-form-use-group-location"]').exists()).toBe(true)
      await wrapper.find('[data-testid="event-form-use-group-location"]').trigger('click')
      expect(wrapper.find('[data-testid="location-picker-input"]').element.value).toBe('Group HQ, London')

      await wrapper.find('[data-testid="event-form-online"] input').setValue(true)
      expect(wrapper.find('[data-testid="event-form-use-group-location"]').exists()).toBe(false)
    })

    it('never shows the moderation approve select while creating', () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: true })
      expect(wrapper.find('[data-testid="event-approve"]').exists()).toBe(false)
    })

    it('shows the admin-only card (network_data editor) for an admin, even while creating', () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: true })

      expect(wrapper.find('[data-testid="event-form-admin"]').exists()).toBe(true)
      expect(wrapper.findComponent(GroupNetworkDataStub).exists()).toBe(true)
    })

    it('hides the admin-only card for a non-admin', () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: false })

      expect(wrapper.find('[data-testid="event-form-admin"]').exists()).toBe(false)
    })

    it('never renders a per-event timezone control, for an admin or otherwise (develop has none)', () => {
      expect(mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: true }).find('[data-testid="event-form-timezone"]').exists()).toBe(false)
      expect(mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: false }).find('[data-testid="event-form-timezone"]').exists()).toBe(false)
    })

    it('submits network_data edited via the admin-only card', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: true })
      await fillRequiredFields(wrapper)
      await wrapper.findComponent(GroupNetworkDataStub).vm.$emit('update:modelValue', { widgets: '3' })
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.createEvent.mock.calls[0][0].network_data).toBe(JSON.stringify({ widgets: '3' }))
    })

    it('shows the "before submit" notice beside the create button', () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })

      expect(wrapper.find('[data-testid="event-form-notice"]').text()).toContain('made public')
    })
  })

  describe('edit mode', () => {
    const EVENT = {
      id: 5,
      title: 'Repair Café',
      link: 'https://example.com',
      description: '<p>Bring your broken things</p>',
      online: false,
      location: 'Town Hall',
      timezone: 'Europe/London',
      start: '2026-08-20T09:00:00Z',
      end: '2026-08-20T11:00:00Z',
      approved: true,
      group: { id: 9, name: 'Acme Restarters' },
      network_data: { dummy: 'value' },
    }

    it('prefills fields from initialEvent, including the local start/end times', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      expect(wrapper.find('[data-testid="event-form-venue"]').element.value).toBe('Repair Café')
      expect(wrapper.find('[data-testid="location-picker-input"]').element.value).toBe('Town Hall')
      expect(wrapper.find('[data-testid="event-form-start"]').element.value).toBe('10:00')
      expect(wrapper.find('[data-testid="event-form-end"]').element.value).toBe('12:00')
      expect(wrapper.find('[data-testid="event-form-date-stub"]').element.value).toBe('2026-08-20')
    })

    // EventAddEdit.vue:17-22 keeps the SAME EventGroup control while editing,
    // just `:disabled="!creating"`. This previously asserted static text,
    // which is what the form actually rendered - so the assertion was pinning
    // the divergence rather than develop.
    // VenueAddress.vue:31-40 shows a map beside the address, gated on
    // `!online && lat !== null`. Coordinates are only known for an event that
    // already has them (edit / duplicate-from-source) because this client
    // leaves geocoding to the server.
    it('shows the venue map when the event has coordinates, and hides it when online', async () => {
      const wrapper = mountForm({
        eventId: 5,
        initialEvent: { ...EVENT, lat: 51.5, lng: -0.12 },
      })

      expect(wrapper.find('[data-testid="event-form-venue-map"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-form-online"] input').setValue(true)
      expect(wrapper.find('[data-testid="event-form-venue-map"]').exists()).toBe(false)
    })

    it('shows no venue map when the event has no coordinates', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      expect(wrapper.find('[data-testid="event-form-venue-map"]').exists()).toBe(false)
    })

    // LocationPicker.vue's lat/lng are exposed as v-model props for exactly
    // this - see its own class doc comment - wired up so picking an
    // autocomplete suggestion shows the map preview live, not just from
    // pre-existing initialEvent coordinates.
    it('shows the venue map live once LocationPicker emits coordinates for a new address', async () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      expect(wrapper.find('[data-testid="event-form-venue-map"]').exists()).toBe(false)

      const picker = wrapper.findComponent(LocationPickerStub)
      await picker.vm.$emit('update:lat', 51.5)
      await picker.vm.$emit('update:lng', -0.12)

      expect(wrapper.find('[data-testid="event-form-venue-map"]').exists()).toBe(true)
    })

    it('keeps the group picker, disabled, while editing', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      const picker = wrapper.findComponent('[data-testid="event-form-group"]')
      expect(picker.exists()).toBe(true)
      expect(picker.props('disabled')).toBe(true)
      expect(picker.text()).toContain('Acme Restarters')
    })

    it('shows the admin-only card for an admin while editing too, prefilled with the event\'s network_data', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT, isAdmin: true })

      expect(wrapper.find('[data-testid="event-form-admin"]').exists()).toBe(true)
      expect(wrapper.findComponent(GroupNetworkDataStub).props('modelValue')).toEqual({ dummy: 'value' })
    })

    // This previously asserted the notice was create-only "per develop". It is
    // not: EventAddEdit.vue:116-125 renders it while editing too, hidden only
    // once the event is approved-or-being-approved, or immediately after a
    // create. The assertion was documenting the gap rather than develop.
    it('shows the "before submit" notice while editing, for a non-approver', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      expect(wrapper.find('[data-testid="event-form-notice"]').exists()).toBe(true)
    })

    // EventAddEdit.vue:121's `!canApprove` half: someone who can approve the
    // event themselves is not waiting on a coordinator, so the notice would be
    // telling them something untrue.
    it('hides the "before submit" notice while editing for someone who can approve', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT, isAdmin: true })

      expect(wrapper.find('[data-testid="event-form-notice"]').exists()).toBe(false)
    })

    it('shows the creation confirmation instead of the notice when just created', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT, justCreated: true })

      expect(wrapper.find('[data-testid="event-form-created"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-notice"]').exists()).toBe(false)
    })

    it('offers Duplicate from the edit form (EventAddEdit.vue:127)', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      expect(wrapper.find('[data-testid="event-form-duplicate"]').exists()).toBe(true)
    })

    it('submits without groupid, round-tripping network_data', async () => {
      const store = useEventsStore()
      store.updateEvent = vi.fn().mockResolvedValue(5)

      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.updateEvent).toHaveBeenCalledWith(5, {
        start: '2026-08-20T09:00:00Z',
        end: '2026-08-20T11:00:00Z',
        title: 'Repair Café',
        description: '<p>Bring your broken things</p>',
        location: 'Town Hall',
        online: false,
        link: 'https://example.com',
        timezone: 'Europe/London',
        network_data: JSON.stringify({ dummy: 'value' }),
      })
      expect(wrapper.emitted('updated')).toEqual([[5]])
    })

    it('does not show the moderation select once the event is already approved', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT, isAdmin: true })
      expect(wrapper.find('[data-testid="event-approve"]').exists()).toBe(false)
    })

    it('shows the moderation select for an admin when the event is unapproved, and submits moderate=approve', async () => {
      const store = useEventsStore()
      store.updateEvent = vi.fn().mockResolvedValue(5)

      const unapproved = { ...EVENT, approved: false }
      const wrapper = mountForm({ eventId: 5, initialEvent: unapproved, isAdmin: true })

      expect(wrapper.find('[data-testid="event-approve"]').exists()).toBe(true)

      await wrapper.find('[data-testid="event-approve"]').setValue('approve')
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      const payload = store.updateEvent.mock.calls[0][1]
      expect(payload.moderate).toBe('approve')
    })

    it('does not show the moderation select for a non-admin, even when unapproved', () => {
      const unapproved = { ...EVENT, approved: false }
      const wrapper = mountForm({ eventId: 5, initialEvent: unapproved, isAdmin: false })
      expect(wrapper.find('[data-testid="event-approve"]').exists()).toBe(false)
    })

    // EventAddEdit.vue:76 - the cog SVG immediately before the "Approve
    // Event:" label, dropped from an earlier version of this form.
    it('shows the cog icon before the Approve Event label', () => {
      const unapproved = { ...EVENT, approved: false }
      const wrapper = mountForm({ eventId: 5, initialEvent: unapproved, isAdmin: true })

      const approveGroup = wrapper.find('.event-form-approve')
      expect(approveGroup.find('svg').exists()).toBe(true)
      expect(approveGroup.text()).toContain('Approve Event')
    })
  })

  describe('duplicate mode (create mode with a prefill source)', () => {
    const SOURCE_EVENT = {
      id: 5,
      title: 'Repair Café',
      link: '',
      description: '<p>Bring your broken things</p>',
      online: false,
      location: 'Town Hall',
      timezone: 'Europe/London',
      start: '2026-08-20T09:00:00Z',
      end: '2026-08-20T11:00:00Z',
      approved: true,
      group: { id: 9, name: 'Acme Restarters' },
      network_data: {},
    }

    it('prefills every field except the date, and keeps the group picker editable', () => {
      const wrapper = mountForm({
        initialEvent: SOURCE_EVENT,
        isDuplicate: true,
        groups: [{ id: 9, name: 'Acme Restarters' }],
      })

      expect(wrapper.find('[data-testid="event-form-venue"]').element.value).toBe('Repair Café')
      expect(wrapper.find('[data-testid="event-form-start"]').element.value).toBe('10:00')
      expect(wrapper.find('[data-testid="event-form-date-stub"]').element.value).toBe('')

      const picker = wrapper.findComponent('[data-testid="event-form-group"]')
      expect(picker.exists()).toBe(true)
      expect(picker.props('disabled')).toBe(false)
      expect(picker.props('modelValue')).toBe(9)
    })

    it('submits as a create (groupid, no moderate) once the date is filled in, round-tripping the source event\'s network_data', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(99)

      const wrapper = mountForm({
        initialEvent: { ...SOURCE_EVENT, network_data: { widgets: '3' } },
        isDuplicate: true,
        groups: [{ id: 9, name: 'Acme Restarters' }],
      })
      await wrapper.find('[data-testid="event-form-date-stub"]').setValue('2026-09-01')
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await wrapper.vm.$nextTick()

      expect(store.createEvent).toHaveBeenCalledWith(
        expect.objectContaining({ groupid: 9, start: '2026-09-01T09:00:00Z', network_data: JSON.stringify({ widgets: '3' }) })
      )
      expect(store.createEvent.mock.calls[0][0]).not.toHaveProperty('moderate')
      expect(wrapper.emitted('created')).toEqual([[99]])
    })
  })
  // Groups kept posting the same event twice - a second host adding one that
  // is already there, or a re-submit after fixing a typo. Before a create the
  // form asks the API what the group already has around that date and, if
  // anything looks like a match, shows it rather than posting.
  describe('duplicate check before creating', () => {
    const alreadyThere = (over = {}) => ({
      id: 77,
      title: 'Repair Café',
      location: 'Town Hall',
      online: false,
      start: '2026-08-20T09:00:00Z',
      end: '2026-08-20T11:00:00Z',
      timezone: 'Europe/London',
      updated_at: '2020-01-01T00:00:00Z',
      ...over,
    })

    const withGroupEvents = (events) =>
      vi.stubGlobal('useNuxtApp', () => ({
        $api: { group: { events: vi.fn().mockResolvedValue({ data: events }) } },
      }))

    it('shows what already exists instead of posting a second copy', async () => {
      withGroupEvents([alreadyThere()])
      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await flushPromises()

      expect(store.createEvent).not.toHaveBeenCalled()
      expect(wrapper.findComponent(EventDuplicateModal).props('show')).toBe(true)
      expect(wrapper.findComponent(EventDuplicateModal).props('matches')[0].event.id).toBe(77)
    })

    it('posts once the host says it is a different event', async () => {
      withGroupEvents([alreadyThere()])
      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await flushPromises()

      wrapper.findComponent(EventDuplicateModal).vm.$emit('post-anyway')
      await flushPromises()

      expect(store.createEvent).toHaveBeenCalledTimes(1)
      expect(wrapper.emitted('created')).toEqual([[42]])
    })

    it('stays on the form, without posting, when the host backs out', async () => {
      withGroupEvents([alreadyThere()])
      const store = useEventsStore()
      store.createEvent = vi.fn()

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await flushPromises()

      wrapper.findComponent(EventDuplicateModal).vm.$emit('close')
      await flushPromises()

      expect(store.createEvent).not.toHaveBeenCalled()
      expect(wrapper.findComponent(EventDuplicateModal).props('show')).toBe(false)
    })

    it('posts as normal when the group has nothing like it', async () => {
      withGroupEvents([alreadyThere({ id: 5, title: 'Something else', location: 'Elsewhere', start: '2026-12-25T09:00:00Z' })])
      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await flushPromises()

      expect(store.createEvent).toHaveBeenCalledTimes(1)
    })

    it('never blocks a post because the check itself failed', async () => {
      vi.stubGlobal('useNuxtApp', () => ({
        $api: { group: { events: vi.fn().mockRejectedValue(new Error('offline')) } },
      }))
      const store = useEventsStore()
      store.createEvent = vi.fn().mockResolvedValue(42)

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await fillRequiredFields(wrapper)
      await wrapper.find('[data-testid="event-form"]').trigger('submit')
      await flushPromises()

      expect(store.createEvent).toHaveBeenCalledTimes(1)
    })
  })
})
