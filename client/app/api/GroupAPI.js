import BaseAPI from './BaseAPI.js'

/**
 * Thin, hardcoded-path client for the group endpoint family. Full surface
 * (invites, images, stats...) lands with later Groups migration slices
 * (design.md §5.3, §6.2); this slice (B4 - group list pages) adds the
 * names index + membership + nearby methods used by stores/groups.js.
 */
export default class GroupAPI extends BaseAPI {
  list(params) {
    return this.$get('/api/v2/groups', params)
  }

  get(id) {
    return this.$get(`/api/v2/groups/${id}`)
  }

  create(payload) {
    return this.$post('/api/v2/groups', payload)
  }

  update(id, payload) {
    return this.$patch(`/api/v2/groups/${id}`, payload)
  }

  // GET /api/v2/groups/names - {id, name, archived_at} for every group.
  // Currently the only field set (api-contracts-phase-b.md client notes);
  // the location/hosts/restarters/tags/next_event summary fields land with
  // PR #887 - see docs/nuxt-migration/api-gaps.md.
  names(params) {
    return this.$get('/api/v2/groups/names', params)
  }

  // POST /api/v2/groups/{id}/members/me (api-contracts-phase-b.md B2, not
  // yet implemented server-side) - self-join.
  join(id) {
    return this.$post(`/api/v2/groups/${id}/members/me`)
  }

  // DELETE /api/v2/groups/{id}/members/me (api-contracts-phase-b.md B2, not
  // yet implemented server-side) - self-leave.
  leave(id) {
    return this.$del(`/api/v2/groups/${id}/members/me`)
  }

  // GET /api/v2/groups/nearby (api-contracts-phase-b.md B2, not yet
  // implemented server-side) - nearby_groups shape, for the current user.
  nearby(params) {
    return this.$get('/api/v2/groups/nearby', params)
  }

  // GET /api/v2/groups/{id}/stats (api-contracts-phase-b.md B2, not yet
  // implemented server-side) - {group_stats, device_stats, cluster_stats,
  // top_devices}, per the group/view.blade.php Blade props it replaces. See
  // docs/nuxt-migration/api-gaps.md B5.
  stats(id) {
    return this.$get(`/api/v2/groups/${id}/stats`)
  }

  // GET /api/v2/groups/{id}/events - already implemented server-side
  // (API\GroupController::getEventsForGroupv2) - EventSummary[] for the
  // group, unfiltered; the client splits upcoming/past itself.
  events(id, params) {
    return this.$get(`/api/v2/groups/${id}/events`, params)
  }

  // GET /api/v2/groups/{id}/volunteers - already implemented server-side
  // (API\GroupController::getVolunteersForGroupv2) - Volunteer[].
  volunteers(id, params) {
    return this.$get(`/api/v2/groups/${id}/volunteers`, params)
  }

  // POST /api/v2/groups/{id}/invites (api-contracts-phase-b.md B2, not yet
  // implemented server-side) - {emails:[...], message?} ->
  // {invites_sent, invalid:[...]}.
  invite(id, payload) {
    return this.$post(`/api/v2/groups/${id}/invites`, payload)
  }

  // DELETE /api/v2/groups/{id} (api-contracts-phase-b.md B2, not yet
  // implemented server-side) - archive semantics, gated client-side on the
  // can_perform_delete permission flag (#892).
  del(id) {
    return this.$del(`/api/v2/groups/${id}`)
  }

  // DELETE /api/v2/groups/{id}/volunteers/{iduser} - already implemented
  // server-side (API\GroupController::deleteVolunteerForGroupv2).
  removeVolunteer(id, iduser) {
    return this.$del(`/api/v2/groups/${id}/volunteers/${iduser}`)
  }

  // PATCH /api/v2/groups/{id}/volunteers/{iduser} {host:bool} - already
  // implemented server-side (API\GroupController::patchVolunteerForGroupv2).
  setVolunteerHost(id, iduser, host) {
    return this.$patch(`/api/v2/groups/${id}/volunteers/${iduser}`, { host })
  }

  // GET /api/v2/groups/tags - already implemented server-side
  // (API\GroupController::listTagsv2). Role-filtered server-side: admins see
  // every tag, network coordinators only tags from networks they coordinate,
  // everyone else gets [].
  tags(params) {
    return this.$get('/api/v2/groups/tags', params)
  }

  // POST /api/v2/groups/{id}/images {upload_key} (api-contracts-phase-b.md
  // B2, not yet implemented server-side) - attaches a completed tus upload
  // (see TusImageUpload.vue) as the group's image. See
  // docs/nuxt-migration/api-gaps.md B6.
  uploadImage(id, uploadKey) {
    return this.$post(`/api/v2/groups/${id}/images`, { upload_key: uploadKey })
  }

  // DELETE /api/v2/groups/{id}/images/{idimages} (api-contracts-phase-b.md
  // B2, not yet implemented server-side).
  deleteImage(id, idimages) {
    return this.$del(`/api/v2/groups/${id}/images/${idimages}`)
  }
}
