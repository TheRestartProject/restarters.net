import { defineStore } from 'pinia'
import { useToastStore } from './toast.js'

/**
 * Backs /party (mine), /party/all and /party/all-past (design.md §6.2
 * section 4; api-contracts-phase-c.md C2/C3; resources/js/components/
 * GroupEvents.vue + mixins/event.js are the functional spec).
 *
 * There's a single source list, `myEvents`, from `GET
 * /api/v2/users/me/events` - the union PartyController::index() builds
 * today ("my" events, nearby upcoming events, other approved upcoming
 * events), each event tagged `nearby`/`all` per the contract doc. Pages
 * bucket this one list client-side (mine/nearby/all, upcoming/past) using
 * the pure helpers in composables/useEventComputed.js rather than the store
 * pre-splitting it, mirroring how GroupEventsList.vue buckets
 * GET /api/v2/groups/{id}/events client-side.
 *
 * `attend`/`unattend` (api-contracts-phase-c.md C1c, POST/DELETE
 * /api/v2/events/{id}/attendees/me) optimistically flip the matching row's
 * `attending` flag in `myEvents.data` *and* in `current.data` (if it's the
 * event being viewed), reverting + toasting on failure - same pattern as
 * stores/groups.js#join()/leave().
 *
 * C3 (/party/view/[id]) adds `current` (the single event being viewed),
 * `attendees` ({confirmed, invited} per api-contracts-phase-c.md C1b) and
 * `devices` (read-only list, C1e) - all page-scoped {data,loading,error}
 * shapes, same convention as stores/groups.js's current/stats/events/
 * volunteers (only one event view is ever on screen at a time).
 * `useEventAttendance(id)` (composables/useEventAttendance.js) is the thin
 * wrapper pages/components read these through.
 *
 * C4 (/party/create/[[group_id]], /party/edit/[id], /party/duplicate/[id])
 * adds `createEvent`/`updateEvent` (POST/PATCH /api/v2/events[/{id}], both
 * already implemented server-side - EventController::createEventv2/
 * updateEventv2 - and, like createGroup/updateGroup, return a bare
 * {id: ...}, not {data: {id: ...}}) and `uploadEventImage` (POST
 * /api/v2/events/{id}/images, C1f - see EventAPI.js).
 */
export const useEventsStore = defineStore('events', {
  state: () => ({
    // `loaded` distinguishes "not fetched yet" from "fetched and empty".
    // Without it the first paint has loading=false and data=[], so a page
    // renders its empty state ("no events") before the request is even sent,
    // then flips to a placeholder, then to content - a wrong answer on screen
    // plus a layout shift. Same shape stores/fixometer.js already uses.
    myEvents: { data: [], loading: false, loaded: false, error: null },
    current: { data: null, loading: false, error: null },
    attendees: { data: { confirmed: [], invited: [] }, loading: false, error: null },
    devices: { data: [], loading: false, error: null },
    // Id of an event created in this session that the user has just been
    // redirected to the edit form for, so that form can show develop's green
    // "Event created!" confirmation (EventAddEditPage.vue:104's justCreated).
    // develop never navigates - it keeps one component mounted and rewrites
    // the URL with history.replaceState - so the flag simply survives there.
    // This client performs a real route change, hence the hand-off. Holds an
    // id rather than a boolean so a later visit to a DIFFERENT event's edit
    // form can't inherit a stale "just created" banner; the edit page clears
    // it once consumed.
    justCreatedId: null,
  }),

  actions: {
    async fetchEvent(id) {
      const { $api } = useNuxtApp()

      this.current.loading = true
      this.current.error = null

      try {
        const { data } = await $api.event.get(id)
        this.current.data = data
        return data
      } catch (error) {
        this.current.error = error
        throw error
      } finally {
        this.current.loading = false
      }
    },

    async fetchAttendees(id) {
      const { $api } = useNuxtApp()

      this.attendees.loading = true
      this.attendees.error = null

      try {
        const { data } = await $api.event.attendees(id)
        this.attendees.data = data
        return data
      } catch (error) {
        this.attendees.error = error
        throw error
      } finally {
        this.attendees.loading = false
      }
    },

    async fetchDevices(id) {
      const { $api } = useNuxtApp()

      this.devices.loading = true
      this.devices.error = null

      try {
        const { data } = await $api.event.devices(id)
        this.devices.data = data
        return data
      } catch (error) {
        this.devices.error = error
        throw error
      } finally {
        this.devices.loading = false
      }
    },

    // POST /api/v2/events/{id}/invites (C1d). Errors are left for the
    // caller (EventInviteModal.vue) to show inline, same as
    // stores/groups.js#inviteVolunteers().
    async inviteVolunteers(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.event.invite(id, payload)
      return data
    },

    // PUT /api/events/{id}/volunteers (gap 14, v1 - see api/EventAPI.js's
    // addVolunteer doc comment). Errors are left for the caller
    // (EventAddVolunteerModal.vue) to show inline, same convention as
    // inviteVolunteers above. Doesn't refetch attendees itself - the page
    // does that once the modal closes, same as legacy's EventAttendance.vue
    // (`@hide="fetchVolunteers"` on its own EventAddVolunteerModal).
    async addVolunteer(id, payload) {
      const { $api } = useNuxtApp()
      return $api.event.addVolunteer(id, payload)
    },

    // POST /api/v2/events/{id}/request-review - ask confirmed attendees to
    // review the event's repairs. Host/coordinator/admin only.
    async requestReview(id) {
      const { $api } = useNuxtApp()
      const { data } = await $api.event.requestReview(id)
      return data
    },

    // DELETE /api/v2/events/{id} (C1g). Only ever called when the page's
    // client-side canDelete gate is true - see pages/party/view/[id].vue.
    async deleteEvent(id) {
      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.event.del(id)
        return data
      } catch (error) {
        useToastStore().error(error)
        throw error
      }
    },

    // Optimistically removes an attendee row (EventAttendees.vue's
    // "remove" action, keyed by the events_users row id per contract
    // judgment call 2 - not by user id, since manually-added volunteers
    // have none). No dedicated DELETE endpoint is documented for this in
    // api-contracts-phase-c.md (only the volunteer-management {iduser}/
    // {idevents_users} routes are proposed, not committed) - recorded as a
    // gap; wired against the proposed shape so it activates once it lands.
    async removeAttendee(eventId, idEventsUsers) {
      const previous = this.attendees.data
      this.attendees.data = {
        confirmed: previous.confirmed.filter((a) => a.id !== idEventsUsers),
        invited: previous.invited.filter((a) => a.id !== idEventsUsers),
      }

      const { $api } = useNuxtApp()

      try {
        await $api.event.removeVolunteer(eventId, idEventsUsers)
      } catch (error) {
        this.attendees.data = previous
        useToastStore().error(error)
        throw error
      }
    },

    async fetchMyEvents(params) {
      const { $api } = useNuxtApp()

      this.myEvents.loading = true
      this.myEvents.error = null

      try {
        const { data } = await $api.event.myEvents(params)
        this.myEvents.data = data
        this.myEvents.loaded = true
        return data
      } catch (error) {
        this.myEvents.error = error
        throw error
      } finally {
        this.myEvents.loading = false
      }
    },

    // Optimistic RSVP: flips `attending` on the matching myEvents row (if
    // present - the event may not be in the list yet, e.g. joining from a
    // group/view page) and on `current` (if it's the event being viewed)
    // immediately, reverts + toasts on failure.
    async attend(id) {
      const index = this.myEvents.data.findIndex((e) => e.id === id)
      const previous = index !== -1 ? this.myEvents.data[index] : null
      const previousCurrent = this.current.data && this.current.data.id === id ? this.current.data : null

      if (previous) {
        this.myEvents.data = this.myEvents.data.map((e) => (e.id === id ? { ...e, attending: true } : e))
      }
      if (previousCurrent) {
        this.current.data = { ...previousCurrent, attending: true }
      }

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.event.attend(id)
        return data
      } catch (error) {
        if (previous) {
          this.myEvents.data = this.myEvents.data.map((e) => (e.id === id ? previous : e))
        }
        if (previousCurrent) {
          this.current.data = previousCurrent
        }
        useToastStore().error(error)
        throw error
      }
    },

    async unattend(id) {
      const index = this.myEvents.data.findIndex((e) => e.id === id)
      const previous = index !== -1 ? this.myEvents.data[index] : null
      const previousCurrent = this.current.data && this.current.data.id === id ? this.current.data : null

      if (previous) {
        this.myEvents.data = this.myEvents.data.map((e) => (e.id === id ? { ...e, attending: false } : e))
      }
      if (previousCurrent) {
        this.current.data = { ...previousCurrent, attending: false }
      }

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.event.unattend(id)
        return data
      } catch (error) {
        if (previous) {
          this.myEvents.data = this.myEvents.data.map((e) => (e.id === id ? previous : e))
        }
        if (previousCurrent) {
          this.current.data = previousCurrent
        }
        useToastStore().error(error)
        throw error
      }
    },

    // POST /api/v2/events (C4). Errors (incl. 422) are left for
    // EventForm.vue to render inline, same as stores/groups.js#createGroup.
    async createEvent(payload) {
      const { $api } = useNuxtApp()
      const { id } = await $api.event.create(payload)
      return id
    },

    // PATCH /api/v2/events/{id} (C4). Invalidates `current` so the edit
    // page's next load re-fetches the freshly-saved event (same pattern as
    // stores/groups.js#updateGroup).
    async updateEvent(id, payload) {
      const { $api } = useNuxtApp()
      const { id: returnedId } = await $api.event.update(id, payload)
      if (this.current.data && this.current.data.id === id) {
        this.current.data = null
      }
      return returnedId
    },

    // PATCH /api/v2/events/{id}, participants/volunteers only (gap 13 -
    // unblocked, replaces legacy's POST /party/update-quantity +
    // update-volunteerquantity routes). Deliberately does NOT invalidate
    // `current` like updateEvent above: EventAttendanceCount.vue's +/-
    // stepper is a rapid, repeated interaction (matches legacy's own Vuex
    // mutation, `this.event.volunteers = val` - an instant in-place local
    // update, no reload) - invalidating+refetching on every click would
    // flash the whole page's loading skeleton for what should feel instant.
    async updateEventCount(id, payload, field, value) {
      const { $api } = useNuxtApp()
      await $api.event.update(id, payload)
      if (this.current.data?.id === id && this.current.data.stats) {
        this.current.data.stats[field] = value
      }
    },

    // POST /api/v2/events/{id}/images (C1f/C4). Write-only from the
    // client's point of view: no endpoint lists an event's existing photos
    // (docs/nuxt-migration/api-gaps.md Phase C item 3), so there's no local
    // state to update on success - the caller (party/edit/[id].vue) just
    // shows a confirmation/error.
    async uploadEventImage(id, uploadKey) {
      const { $api } = useNuxtApp()
      const { data } = await $api.event.uploadImage(id, uploadKey)
      return data
    },
  },
})
