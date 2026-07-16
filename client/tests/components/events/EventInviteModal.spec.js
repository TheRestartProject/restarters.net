import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import EventInviteModal from '../../../app/components/events/EventInviteModal.vue'
import { useEventsStore } from '../../../app/stores/events.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const BModalStub = {
  props: ['modelValue'],
  emits: ['hide'],
  template: '<div v-if="modelValue"><slot /></div>',
}
const BAlertStub = { template: '<div><slot /></div>' }
const BFormStub = { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' }
const BFormGroupStub = { template: '<div><slot /></div>' }
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

describe('components/events/EventInviteModal', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
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
    await wrapper.find('[data-testid="event-invite-emails"]').setValue('not-an-email, also bad')
    await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')

    expect(wrapper.find('[data-testid="event-invite-emails-error"]').exists()).toBe(true)
    expect(store.inviteVolunteers).not.toHaveBeenCalled()
  })

  it('calls store.inviteVolunteers with parsed emails and shows a success message', async () => {
    const store = useEventsStore()
    store.inviteVolunteers = vi.fn().mockResolvedValue({ invites_sent: 2, invalid: [] })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="event-invite-emails"]').setValue('a@example.com, b@example.com')
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
    await wrapper.find('[data-testid="event-invite-emails"]').setValue('a@example.com')
    await wrapper.find('[data-testid="event-invite-form"]').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="event-invite-success"]').text()).toContain('bad@example.com')
  })

  it('shows a general error when the store action rejects', async () => {
    const store = useEventsStore()
    store.inviteVolunteers = vi.fn().mockRejectedValue({ status: 500, message: 'Nope' })

    const wrapper = mountComponent()
    await wrapper.find('[data-testid="event-invite-emails"]').setValue('a@example.com')
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
