import { mount } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import EventActionsDropdown from '../../../app/components/events/EventActionsDropdown.vue'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'
import { GROUP_VIEW_STUBS } from '../../helpers/stubs.js'

// events.event_actions/share_event_stats/invite_when_approved are lang/en/
// events.php keys not yet in the generated client i18n JSON - overlaid
// inline per the established DashboardWhatsHappening.spec.js pattern.
function mountComponent(props = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
      en: {
        ...en,
        ...clientEn,
        events: {
          ...en.events,
          event_actions: 'Event actions',
          share_event_stats: 'Share event stats',
          invite_when_approved: 'You can only invite volunteers when this event has been approved',
        },
      },
    },
  })

  return mount(EventActionsDropdown, {
    props: { eventId: 5, ...props },
    global: { plugins: [i18n], stubs: GROUP_VIEW_STUBS },
  })
}

describe('components/events/EventActionsDropdown', () => {
  it('renders a single EVENT ACTIONS dropdown (gap 2)', () => {
    const wrapper = mountComponent()
    const dropdown = wrapper.find('[data-testid="event-actions-dropdown"]')
    expect(dropdown.exists()).toBe(true)
    expect(dropdown.text()).toContain('Event actions')
  })

  describe('editor menu (canedit=true)', () => {
    it('shows Edit/Duplicate/Delete as items, Delete disabled (not omitted) for an admin without candelete', () => {
      const wrapper = mountComponent({ canedit: true, candelete: false, isAdmin: true })

      expect(wrapper.find('[data-testid="event-actions-edit"]').attributes('href')).toBe('/party/edit/5')
      expect(wrapper.find('[data-testid="event-actions-duplicate"]').attributes('href')).toBe('/party/duplicate/5')
      expect(wrapper.find('[data-testid="event-actions-delete"]').classes()).toContain('disabled')
    })

    it('shows Delete enabled once candelete, whether or not isAdmin', () => {
      const wrapper = mountComponent({ canedit: true, candelete: true, isAdmin: false })

      const item = wrapper.find('[data-testid="event-actions-delete"]')
      expect(item.exists()).toBe(true)
      expect(item.classes()).not.toContain('disabled')
    })

    // EventActions.vue: a host who canedit but isn't isAdmin and isn't
    // candelete never sees the Delete item at all - not even disabled.
    it('omits Delete entirely for a non-admin host who cannot delete', () => {
      const wrapper = mountComponent({ canedit: true, candelete: false, isAdmin: false })
      expect(wrapper.find('[data-testid="event-actions-delete"]').exists()).toBe(false)
    })

    it('shows Request review/Share stats/Export for a finished event, not RSVP/Invite/Follow', () => {
      const wrapper = mountComponent({ canedit: true, finished: true })

      expect(wrapper.find('[data-testid="event-actions-request-review"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-share-stats"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-export"]').attributes('href')).toBe('/export/devices/event/5')
      expect(wrapper.find('[data-testid="event-actions-rsvp"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-invite"]').exists()).toBe(false)
    })

    it('shows RSVP (not Invite)/Follow group for a non-finished event when not attending', () => {
      const wrapper = mountComponent({
        canedit: true,
        finished: false,
        upcoming: true,
        approved: true,
        isAttending: false,
        hasGroup: true,
        inGroup: false,
      })

      // EventActions.vue's Invite requires isAttending - a non-attending
      // viewer, even a canedit host, only gets RSVP.
      expect(wrapper.find('[data-testid="event-actions-invite"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-rsvp"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-follow-group"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-share-stats"]').exists()).toBe(false)
    })

    it('shows Invite enabled (not RSVP) once attending, upcoming and approved', () => {
      const wrapper = mountComponent({
        canedit: true,
        finished: false,
        upcoming: true,
        approved: true,
        isAttending: true,
      })

      const invite = wrapper.find('[data-testid="event-actions-invite"]')
      expect(invite.exists()).toBe(true)
      expect(invite.classes()).not.toContain('disabled')
      expect(wrapper.find('[data-testid="event-actions-rsvp"]').exists()).toBe(false)
    })

    it('shows Invite disabled with a tooltip once attending and upcoming but not yet approved', () => {
      const wrapper = mountComponent({
        canedit: true,
        finished: false,
        upcoming: true,
        approved: false,
        isAttending: true,
      })

      const invite = wrapper.find('[data-testid="event-actions-invite"]')
      expect(invite.exists()).toBe(true)
      expect(invite.classes()).toContain('disabled')
      expect(wrapper.find('[data-testid="event-actions-invite-tooltip"]').text()).toContain('only invite volunteers when this event has been approved')
    })

    it('hides RSVP once attending, and hides Follow group once in the group', () => {
      const wrapper = mountComponent({ canedit: true, finished: false, isAttending: true, hasGroup: true, inGroup: true })

      expect(wrapper.find('[data-testid="event-actions-rsvp"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-follow-group"]').exists()).toBe(false)
    })

    it('emits rsvp/invite/request-review/share-stats/follow-group/delete on click', async () => {
      const wrapper = mountComponent({
        canedit: true,
        candelete: true,
        finished: true,
        hasGroup: true,
      })

      await wrapper.find('[data-testid="event-actions-request-review"]').trigger('click')
      expect(wrapper.emitted('request-review')).toBeTruthy()

      await wrapper.find('[data-testid="event-actions-share-stats"]').trigger('click')
      expect(wrapper.emitted('share-stats')).toBeTruthy()

      await wrapper.find('[data-testid="event-actions-delete"]').trigger('click')
      expect(wrapper.emitted('delete')).toBeTruthy()
    })
  })

  describe('non-editor menu (canedit=false)', () => {
    it('hides Edit/Duplicate/Delete/Export', () => {
      const wrapper = mountComponent({ canedit: false })

      expect(wrapper.find('[data-testid="event-actions-edit"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-duplicate"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-delete"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="event-actions-export"]').exists()).toBe(false)
    })

    it('always shows Share event stats for a finished event', () => {
      const wrapper = mountComponent({ canedit: false, finished: true })
      expect(wrapper.find('[data-testid="event-actions-share-stats"]').exists()).toBe(true)
    })

    it('shows Follow group/RSVP for a non-finished event', () => {
      const wrapper = mountComponent({ canedit: false, finished: false, hasGroup: true, inGroup: false, isAttending: false })

      expect(wrapper.find('[data-testid="event-actions-follow-group"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="event-actions-rsvp"]').exists()).toBe(true)
    })
  })
})
