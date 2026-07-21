import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventAddVolunteerModal from '../../../app/components/events/EventAddVolunteerModal.vue'
import { useGroupsStore } from '../../../app/stores/groups.js'
import { useEventsStore } from '../../../app/stores/events.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue"><slot /><slot name="footer" /></div>',
}
const BAlertStub = { template: '<div><slot /></div>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountModal(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventAddVolunteerModal, {
    props: { eventId: 5, groupId: 9, ...props },
    global: {
      plugins: [i18n],
      stubs: { BModal: BModalStub, BAlert: BAlertStub, BFormGroup: BFormGroupStub, BButton: BButtonStub },
    },
  })
}

// Ports EventAddVolunteerModal.vue (gap 14) - two ways to add a volunteer
// to a finished/in-progress event: an existing group member, or a manually
// entered name/email for someone not registered.
describe('components/events/EventAddVolunteerModal', () => {
  let groupsStore
  let eventsStore

  beforeEach(() => {
    setActivePinia(createPinia())
    groupsStore = useGroupsStore()
    groupsStore.fetchVolunteers = vi.fn().mockResolvedValue([])
    eventsStore = useEventsStore()
    eventsStore.addVolunteer = vi.fn().mockResolvedValue({ success: 'success' })
  })

  it('fetches the hosting group\'s volunteers when shown', async () => {
    // Real usage always mounts with show:false first (the page renders the
    // modal unconditionally, toggling `show` later) - starting there so
    // the show:false -> true transition genuinely fires the watcher, same
    // as the real app.
    const wrapper = mountModal({ show: false, groupId: 9 })
    await wrapper.setProps({ show: true })

    expect(groupsStore.fetchVolunteers).toHaveBeenCalledWith(9)
  })

  it('lists group members plus a "not registered" option in the dropdown', () => {
    groupsStore.volunteers.data = [
      { id: 1, user: 20, name: 'Jo Host' },
      { id: 2, user: 21, name: 'Sam Guest' },
    ]
    const wrapper = mountModal({ show: true })

    const options = wrapper.find('[data-testid="event-add-volunteer-user"]').findAll('option')
    expect(options.map((o) => o.text())).toEqual(['-- Select --', 'Jo Host', 'Sam Guest', 'Not registered on here'])
  })

  it('disables submit until a group member is picked', async () => {
    groupsStore.volunteers.data = [{ id: 1, user: 20, name: 'Jo Host' }]
    const wrapper = mountModal({ show: true })

    expect(wrapper.find('[data-testid="event-add-volunteer-submit"]').attributes('disabled')).toBeDefined()

    await wrapper.find('[data-testid="event-add-volunteer-user"]').setValue('20')
    expect(wrapper.find('[data-testid="event-add-volunteer-submit"]').attributes('disabled')).toBeUndefined()
  })

  it('shows name/email fields only for "not registered", and requires at least one of them', async () => {
    const wrapper = mountModal({ show: true })

    expect(wrapper.find('[data-testid="event-add-volunteer-name"]').exists()).toBe(false)

    await wrapper.find('[data-testid="event-add-volunteer-user"]').setValue('not-registered')
    expect(wrapper.find('[data-testid="event-add-volunteer-name"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="event-add-volunteer-submit"]').attributes('disabled')).toBeDefined()

    await wrapper.find('[data-testid="event-add-volunteer-email"]').setValue('sam@example.com')
    expect(wrapper.find('[data-testid="event-add-volunteer-submit"]').attributes('disabled')).toBeUndefined()
  })

  it('submits the picked group member and closes on success', async () => {
    groupsStore.volunteers.data = [{ id: 1, user: 20, name: 'Jo Host' }]
    const wrapper = mountModal({ show: true })

    await wrapper.find('[data-testid="event-add-volunteer-user"]').setValue('20')
    await wrapper.find('[data-testid="event-add-volunteer-submit"]').trigger('click')
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(eventsStore.addVolunteer).toHaveBeenCalledWith(5, { user: 20, full_name: null, volunteer_email_address: null })
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('submits a not-registered volunteer with name/email', async () => {
    const wrapper = mountModal({ show: true })

    await wrapper.find('[data-testid="event-add-volunteer-user"]').setValue('not-registered')
    await wrapper.find('[data-testid="event-add-volunteer-name"]').setValue('Anon Volunteer')
    await wrapper.find('[data-testid="event-add-volunteer-email"]').setValue('anon@example.com')
    await wrapper.find('[data-testid="event-add-volunteer-submit"]').trigger('click')
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(eventsStore.addVolunteer).toHaveBeenCalledWith(5, {
      user: 'not-registered',
      full_name: 'Anon Volunteer',
      volunteer_email_address: 'anon@example.com',
    })
  })

  it('shows an error and stays open when the save fails', async () => {
    groupsStore.volunteers.data = [{ id: 1, user: 20, name: 'Jo Host' }]
    eventsStore.addVolunteer = vi.fn().mockRejectedValue(new Error('nope'))
    const wrapper = mountModal({ show: true })

    await wrapper.find('[data-testid="event-add-volunteer-user"]').setValue('20')
    await wrapper.find('[data-testid="event-add-volunteer-submit"]').trigger('click')
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="event-add-volunteer-error"]').exists()).toBe(true)
    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('resets the form when closed and reopened', async () => {
    const wrapper = mountModal({ show: true })
    await wrapper.find('[data-testid="event-add-volunteer-user"]').setValue('not-registered')
    await wrapper.find('[data-testid="event-add-volunteer-name"]').setValue('Anon Volunteer')

    await wrapper.setProps({ show: false })
    await wrapper.setProps({ show: true })

    expect(wrapper.find('[data-testid="event-add-volunteer-user"]').element.value).toBe('')
    expect(wrapper.find('[data-testid="event-add-volunteer-name"]').exists()).toBe(false)
  })
})
