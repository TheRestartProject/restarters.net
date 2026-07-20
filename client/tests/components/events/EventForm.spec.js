import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventForm from '../../../app/components/events/EventForm.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// RichTextEditor/LocationPicker/vue-datepicker-next's DatePicker are unit
// tested (or vendored) on their own - stub them here to plain inputs so
// EventForm's own logic (payload shape, validation, group/timezone
// defaulting, 422 rendering) is what's under test, same convention as
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
    props: ['value'],
    emits: ['update:value'],
    template:
      '<input data-testid="event-form-date-stub" :value="value" @input="$emit(\'update:value\', $event.target.value)" />',
  },
}))

const BFormStub = {
  emits: ['submit'],
  template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>',
}
const BFormGroupStub = { template: '<div><slot /></div>' }
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

async function fillRequiredFields(wrapper, { group = true } = {}) {
  await wrapper.find('[data-testid="event-form-venue"]').setValue('Repair Café')
  await wrapper.find('[data-testid="event-form-description"]').setValue('<p>Bring your broken things</p>')
  await wrapper.find('[data-testid="location-picker-input"]').setValue('Town Hall')
  if (group) {
    await wrapper.find('[data-testid="event-form-group"]').setValue('9')
  }
  await wrapper.find('[data-testid="event-form-date-stub"]').setValue('2026-08-20')
  await wrapper.find('[data-testid="event-form-start"]').setValue('10:00')
  await wrapper.find('[data-testid="event-form-end"]').setValue('12:00')
}

describe('components/events/EventForm', () => {
  let groupsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('useNuxtApp', () => ({
      $api: {
        config: { timezones: vi.fn().mockResolvedValue([{ name: 'Europe/London' }]) },
      },
    }))
    groupsStore = useGroupsStore()
    groupsStore.fetchDetails = vi.fn().mockResolvedValue(null)
  })

  describe('create mode', () => {
    it('shows validation errors and does not submit when required fields are empty', async () => {
      const store = useEventsStore()
      store.createEvent = vi.fn()

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await wrapper.find('[data-testid="event-form"]').trigger('submit')

      expect(wrapper.find('[data-testid="event-form-venue-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-description-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-group-error"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-date-error"]').exists()).toBe(true)
      expect(store.createEvent).not.toHaveBeenCalled()
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
      await wrapper.find('[data-testid="event-form-group"]').setValue('9')
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

      expect(wrapper.find('[data-testid="event-form-group"]').element.value).toBe('9')
    })

    it('defaults the timezone from the selected group and lets it be overridden (admin only - see the admin-only card test below)', async () => {
      groupsStore.fetchDetails = vi.fn().mockResolvedValue({ timezone: 'Europe/Paris', location: { location: 'Paris HQ' } })

      const wrapper = mountForm({ isAdmin: true, groups: [{ id: 9, name: 'Acme Restarters' }, { id: 10, name: 'Other Group' }] })
      await wrapper.find('[data-testid="event-form-group"]').setValue('9')
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="event-form-timezone"]').element.value).toBe('Europe/Paris')

      // A manual edit is never clobbered by a later group change.
      await wrapper.find('[data-testid="event-form-timezone"]').setValue('Europe/London')
      groupsStore.fetchDetails = vi.fn().mockResolvedValue({ timezone: 'Europe/Paris', location: null })
      await wrapper.find('[data-testid="event-form-group"]').setValue('10')
      await wrapper.vm.$nextTick()
      await wrapper.vm.$nextTick()

      expect(wrapper.find('[data-testid="event-form-timezone"]').element.value).toBe('Europe/London')
    })

    it('shows a "use group location" shortcut that fills the location field, hidden when online', async () => {
      groupsStore.fetchDetails = vi.fn(async (id) => {
        const detail = { timezone: 'Europe/London', location: { location: 'Group HQ, London' } }
        groupsStore.details[id] = detail
        return detail
      })

      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }] })
      await wrapper.find('[data-testid="event-form-group"]').setValue('9')
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

    it('shows the admin-only card (network_data editor + timezone override) for an admin, even while creating', () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: true })

      expect(wrapper.find('[data-testid="event-form-admin"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-timezone"]').exists()).toBe(true)
      expect(wrapper.findComponent(GroupNetworkDataStub).exists()).toBe(true)
    })

    it('hides the admin-only card and the timezone override for a non-admin', () => {
      const wrapper = mountForm({ groups: [{ id: 9, name: 'Acme Restarters' }], isAdmin: false })

      expect(wrapper.find('[data-testid="event-form-admin"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-form-timezone"]').exists()).toBe(false)
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

    it('shows the group as static text, not an editable select', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      expect(wrapper.find('[data-testid="event-form-group"]').exists()).toBe(false)
      expect(wrapper.text()).toContain('Acme Restarters')
    })

    it('shows the admin-only card for an admin while editing too, prefilled with the event\'s network_data', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT, isAdmin: true })

      expect(wrapper.find('[data-testid="event-form-admin"]').exists()).toBe(true)
      expect(wrapper.findComponent(GroupNetworkDataStub).props('modelValue')).toEqual({ dummy: 'value' })
    })

    it('does not show the "before submit" notice while editing (create-only, per develop)', () => {
      const wrapper = mountForm({ eventId: 5, initialEvent: EVENT })

      expect(wrapper.find('[data-testid="event-form-notice"]').exists()).toBe(false)
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

    it('prefills every field except the date, and keeps the group select editable', () => {
      const wrapper = mountForm({
        initialEvent: SOURCE_EVENT,
        isDuplicate: true,
        groups: [{ id: 9, name: 'Acme Restarters' }],
      })

      expect(wrapper.find('[data-testid="event-form-venue"]').element.value).toBe('Repair Café')
      expect(wrapper.find('[data-testid="event-form-start"]').element.value).toBe('10:00')
      expect(wrapper.find('[data-testid="event-form-date-stub"]').element.value).toBe('')
      expect(wrapper.find('[data-testid="event-form-group"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-form-group"]').element.value).toBe('9')
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
})
