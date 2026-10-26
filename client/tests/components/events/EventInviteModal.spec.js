import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventInviteModal from '../../../app/components/events/EventInviteModal.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import { useGroupsStore } from '../../../app/stores/groups.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue"><slot /></div>',
}
const BAlertStub = { template: '<div><slot /></div>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot name="label" /><slot /></div>' }
const BButtonStub = { template: '<button v-bind="$attrs"><slot /></button>' }

function mountComponent(props = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(EventInviteModal, {
    props: { show: true, eventId: 5, ...props },
    global: {
      plugins: [i18n],
      stubs: { BModal: BModalStub, BAlert: BAlertStub, BForm: BFormStub, BFormGroup: BFormGroupStub, BButton: BButtonStub },
    },
  })
}

// Both pickers are TagMultiselect now (develop uses vue-multiselect for each),
// so tests set their value through the component rather than through native
// <select>/<textarea> APIs.
function setPicker(wrapper, testid, values) {
  const picker = wrapper.findAllComponents({ name: 'TagMultiselect' })
    .find((c) => c.attributes('data-testid') === testid)
  return picker.vm.$emit('update:modelValue', values)
}

function pickerOptions(wrapper, testid) {
  const picker = wrapper.findAllComponents({ name: 'TagMultiselect' })
    .find((c) => c.attributes('data-testid') === testid)
  return picker ? picker.props('options') : null
}

describe('components/events/EventInviteModal', () => {
  // The Blade modal's chain-link header toggle swaps the email form for a
  // box holding the shareable join link. Both halves exist; they are never
  // shown at once.
  describe('shareable link panel', () => {
    it('swaps the email form for the link box when toggled', async () => {
      const wrapper = mountComponent({ shareableLink: 'https://example.test/party/invite/abc123' })

      expect(wrapper.find('[data-testid="event-invite-form"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-invite-link-panel"]').exists()).toBe(false)

      await wrapper.find('[data-testid="event-invite-toggle"]').trigger('click')

      expect(wrapper.find('[data-testid="event-invite-form"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-invite-link-panel"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-invite-link"]').element.value)
        .toBe('https://example.test/party/invite/abc123')
    })

    it('toggles back to the email form', async () => {
      const wrapper = mountComponent()

      await wrapper.find('[data-testid="event-invite-toggle"]').trigger('click')
      await wrapper.find('[data-testid="event-invite-toggle"]').trigger('click')

      expect(wrapper.find('[data-testid="event-invite-form"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-invite-link-panel"]').exists()).toBe(false)
    })

    it('closes from the link panel', async () => {
      const wrapper = mountComponent()

      await wrapper.find('[data-testid="event-invite-toggle"]').trigger('click')
      await wrapper.find('[data-testid="event-invite-link-done"]').trigger('click')

      expect(wrapper.emitted('close')).toBeTruthy()
    })
  })

  beforeEach(() => {
    setActivePinia(createPinia())
  })

  // EventInviteModal.vue lets a host pick group members instead of retyping
  // their addresses, with a tickbox that selects everyone at once. Members
  // already confirmed at the event are excluded server-side.
  describe('group member picker', () => {
    const MEMBERS = [
      { id: 1, name: 'Ada', email: 'ada@example.test' },
      { id: 2, name: 'Grace', email: 'grace@example.test' },
      { id: 3, name: 'NoEmail', email: null },
    ]

    function mountWithMembers(members = MEMBERS, props = {}) {
      const groups = useGroupsStore()
      groups.fetchMembersForInvite = vi.fn().mockResolvedValue(members)
      return { wrapper: mountComponent({ groupId: 9, groupName: 'Fixers', ...props }), groups }
    }

    it('offers only members who have an address', async () => {
      const { wrapper, groups } = mountWithMembers()
      await flushPromises()

      expect(groups.fetchMembersForInvite).toHaveBeenCalledWith(9, 5)
      const options = pickerOptions(wrapper, 'event-invite-members')
      expect(options).toHaveLength(2)
      expect(options.map((o) => o.name)).toEqual(['Ada', 'Grace'])
    })

    it('is absent when the group has no invitable members', async () => {
      const { wrapper } = mountWithMembers([])
      await flushPromises()

      expect(pickerOptions(wrapper, 'event-invite-members')).toBeNull()
    })

    it('sends picked members alongside typed addresses, deduped', async () => {
      const events = useEventsStore()
      events.inviteVolunteers = vi.fn().mockResolvedValue({ invites_sent: 3, invalid: [] })

      const { wrapper } = mountWithMembers()
      await flushPromises()

      await setPicker(wrapper, 'event-invite-members', ['ada@example.test'])
      await setPicker(wrapper, 'event-invite-emails', ['ada@example.test', 'new@example.test'])
      await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')
      await flushPromises()

      expect(events.inviteVolunteers).toHaveBeenCalledWith(5, {
        emails: ['ada@example.test', 'new@example.test'],
        message: undefined,
      })
    })

    it('the tickbox selects every member, and clears them again', async () => {
      const { wrapper } = mountWithMembers()
      await flushPromises()

      const tickbox = wrapper.find('[data-testid="event-invite-all-members"]')
      await tickbox.setValue(true)
      const picked = () => wrapper.findAllComponents({ name: 'TagMultiselect' })
        .find((c) => c.attributes('data-testid') === 'event-invite-members')
        .props('modelValue')
      expect(picked()).toHaveLength(2)

      await tickbox.setValue(false)
      expect(picked()).toHaveLength(0)
    })

    it('does not blame the user for a member address it fetched', async () => {
      const events = useEventsStore()
      events.inviteVolunteers = vi.fn().mockResolvedValue({ invites_sent: 1, invalid: [] })

      const { wrapper } = mountWithMembers([{ id: 1, name: 'Odd', email: 'not-an-email' }])
      await flushPromises()

      await setPicker(wrapper, 'event-invite-members', ['not-an-email'])
      await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')
      await flushPromises()

      expect(wrapper.find('[data-testid="event-invite-emails-error"]').exists()).toBe(false)
      expect(events.inviteVolunteers).toHaveBeenCalled()
    })
  })

  it('does not render when show is false', () => {
    const wrapper = mountComponent({ show: false })
    expect(wrapper.find('[data-testid="event-invite-form"]').exists()).toBe(false)
  })

  it('shows a validation error and does not call the store when no emails are entered', async () => {
    const store = useEventsStore()
    store.inviteVolunteers = vi.fn()

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')

    expect(wrapper.find('[data-testid="event-invite-emails-error"]').exists()).toBe(true)
    expect(store.inviteVolunteers).not.toHaveBeenCalled()
  })

  it('shows a validation error for malformed email addresses', async () => {
    const store = useEventsStore()
    store.inviteVolunteers = vi.fn()

    const wrapper = mountComponent()
    await setPicker(wrapper, 'event-invite-emails', ['not-an-email', 'also bad'])
    await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')

    expect(wrapper.find('[data-testid="event-invite-emails-error"]').exists()).toBe(true)
    expect(store.inviteVolunteers).not.toHaveBeenCalled()
  })

  it('calls store.inviteVolunteers with parsed emails and shows a success message', async () => {
    const store = useEventsStore()
    store.inviteVolunteers = vi.fn().mockResolvedValue({ invites_sent: 2, invalid: [] })

    const wrapper = mountComponent()
    await setPicker(wrapper, 'event-invite-emails', ['a@example.com', 'b@example.com'])
    await wrapper.find('[data-testid="event-invite-message"]').setValue('Come join us')
    await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(store.inviteVolunteers).toHaveBeenCalledWith(5, {
      emails: ['a@example.com', 'b@example.com'],
      message: 'Come join us',
    })
    expect(wrapper.find('[data-testid="event-invite-success"]').exists()).toBe(true)
  })

  it('shows the apart-from message when the API reports invalid addresses', async () => {
    const store = useEventsStore()
    store.inviteVolunteers = vi.fn().mockResolvedValue({ invites_sent: 1, invalid: ['bad@example.com'] })

    const wrapper = mountComponent()
    await setPicker(wrapper, 'event-invite-emails', ['a@example.com'])
    await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="event-invite-success"]').text()).toContain('bad@example.com')
  })

  it('shows a general error when the store action rejects', async () => {
    const store = useEventsStore()
    store.inviteVolunteers = vi.fn().mockRejectedValue({ status: 500, message: 'Nope' })

    const wrapper = mountComponent()
    await setPicker(wrapper, 'event-invite-emails', ['a@example.com'])
    await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="event-invite-error"]').exists()).toBe(true)
  })

  it('emits close when cancel is clicked', async () => {
    const wrapper = mountComponent()
    await wrapper.find('[data-testid="event-invite-cancel"]').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
