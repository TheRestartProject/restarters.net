import BaseAPI from './BaseAPI.js'

/**
 * Thin, hardcoded-path client for the event endpoint family. Full surface
 * (RSVP, volunteers, devices, images...) lands with the Events migration
 * slice (design.md §5.4, §6.2); this is the scaffold-stage shape.
 */
export default class EventAPI extends BaseAPI {
  list(params) {
    return this.$get('/api/v2/events', params)
  }

  get(id) {
    return this.$get(`/api/v2/events/${id}`)
  }

  create(payload) {
    return this.$post('/api/v2/events', payload)
  }

  update(id, payload) {
    return this.$patch(`/api/v2/events/${id}`, payload)
  }

  // GET /api/v2/users/me/events (api-contracts-phase-c.md C2/C3, NEW) - the
  // union PartyController::index() builds today: "my" events
  // (Party::forUser), nearby upcoming events if the user has a location,
  // and other approved upcoming events not already included - each tagged
  // `nearby`/`all` per event (undocumented beyond prose - see
  // docs/nuxt-migration/api-gaps.md Phase C for the exact-shape gap).
  myEvents(params) {
    return this.$get('/api/v2/users/me/events', params)
  }

  // POST /api/v2/events/{id}/attendees/me (api-contracts-phase-c.md C1c,
  // NEW) - self-RSVP. Mirrors getJoinEvent (incl. already-attending no-op)
  // and, per the doc's judgment call 1, also resolves a pending hash-invite
  // for the caller. {data:{attending, already_attending,
  // prompt_follow_group}}.
  attend(id) {
    return this.$post(`/api/v2/events/${id}/attendees/me`)
  }

  // DELETE /api/v2/events/{id}/attendees/me (api-contracts-phase-c.md C1c,
  // NEW) - cancel RSVP, mirrors cancelInvite. {data:{left:true}}.
  unattend(id) {
    return this.$del(`/api/v2/events/${id}/attendees/me`)
  }

  // GET /api/v2/events/{id}/attendees (api-contracts-phase-c.md C1b, NEW) -
  // {data:{confirmed:[...], invited:[...]}}. Replaces the Blade-only
  // attended/invited/hosts computation; "hosts" is just
  // confirmed.filter(a => a.role === HOST) client-side (no separate array).
  attendees(id) {
    return this.$get(`/api/v2/events/${id}/attendees`)
  }

  // GET /api/v2/events/{id}/devices (api-contracts-phase-c.md C1e, NEW) -
  // {data: Device[]}. Read-only device list for the event view page;
  // CRUD (add/edit/delete) lands with C5.
  devices(id) {
    return this.$get(`/api/v2/events/${id}/devices`)
  }

  // POST /api/v2/events/{id}/invites (api-contracts-phase-c.md C1d, NEW) -
  // {emails:[...], message?} -> {data:{invites_sent, invalid:[...]}}.
  // Replaces postSendInvite's hidden-form POST to /party/invite.
  invite(id, payload) {
    return this.$post(`/api/v2/events/${id}/invites`, payload)
  }

  // DELETE /api/v2/events/{id}/volunteers/{idevents_users}
  // (api-contracts-phase-c.md C1d, NEW). The contract flags this as a
  // deliberate naming deviation from the group volunteer-removal pattern
  // ({iduser}): the only removal path today (postRemoveVolunteer) keys off
  // the events_users row id because a manually-added volunteer may have no
  // `user`, so the route param here is that row id, not a user id.
  removeVolunteer(id, idEventsUsers) {
    return this.$del(`/api/v2/events/${id}/volunteers/${idEventsUsers}`)
  }

  // DELETE /api/v2/events/{id} (api-contracts-phase-c.md C1g, NEW) -
  // {data:{deleted:true}}. Permission + canDelete() (zero devices) are
  // enforced/approximated client-side only, matching today's behaviour
  // (contract judgment call 4).
  del(id) {
    return this.$del(`/api/v2/events/${id}`)
  }

  // POST /api/v2/events/{id}/images {upload_key} (api-contracts-phase-c.md
  // C1f - already implemented server-side, EventAttendanceController::
  // uploadImagev2, confirmed by reading routes/api.php directly) -
  // {data:{image_url}}. Attaches a completed tus upload (TusImageUpload.vue)
  // as one photo in the event's gallery - unlike a group/profile image this
  // is additive (no $clear), so repeated calls add more photos rather than
  // replacing one.
  uploadImage(id, uploadKey) {
    return this.$post(`/api/v2/events/${id}/images`, { upload_key: uploadKey })
  }

  // DELETE /api/v2/events/{id}/images/{idimages} (api-contracts-phase-c.md
  // C1f, already implemented server-side).
  deleteImage(id, idimages) {
    return this.$del(`/api/v2/events/${id}/images/${idimages}`)
  }

  // POST /api/v2/events/{id}/request-review - emails confirmed attendees
  // asking them to review the event's repairs (EventAttendanceController::
  // requestReviewv2). Host/coordinator/admin only.
  requestReview(id) {
    return this.$post(`/api/v2/events/${id}/request-review`)
  }
}
