import BaseAPI from './BaseAPI.js'

/**
 * /api/v2/networks family (App\Http\Controllers\API\NetworkController,
 * already implemented server-side - design.md §6.2 Phase E task E1;
 * resources/js/components/NetworkPage.vue + resources/views/networks/*
 * are the functional spec). List/get/groups/events/tags/stats are public
 * (no auth:sanctum middleware - routes/api.php); tag create/update/delete
 * require auth (Administrator or network-coordinator-for-this-network,
 * enforced server-side - stores/networks.js's page-level gate mirrors it
 * for UX only, design.md §4.4).
 */
export default class NetworkAPI extends BaseAPI {
  list(params) {
    return this.$get('/api/v2/networks', params)
  }

  get(id) {
    return this.$get(`/api/v2/networks/${id}`)
  }

  // includeNextEvent/includeDetails/includeStats/includeArchived/group_tag
  // query params (getNetworkGroupsv2). Also honours includeCounts (read
  // directly off the request by GroupSummary#toArray, not documented in
  // getNetworkGroupsv2's own OA annotation but present on every other
  // GroupSummary-returning endpoint - see docs/nuxt-migration/
  // api-gaps.md Phase E) to populate hosts/restarters counts for the
  // network's groups table.
  groups(id, params) {
    return this.$get(`/api/v2/networks/${id}/groups`, params)
  }

  events(id, params) {
    return this.$get(`/api/v2/networks/${id}/events`, params)
  }

  tags(id, params) {
    return this.$get(`/api/v2/networks/${id}/tags`, params)
  }

  createTag(id, payload) {
    return this.$post(`/api/v2/networks/${id}/tags`, payload)
  }

  updateTag(id, tagId, payload) {
    return this.$put(`/api/v2/networks/${id}/tags/${tagId}`, payload)
  }

  deleteTag(id, tagId) {
    return this.$del(`/api/v2/networks/${id}/tags/${tagId}`)
  }

  // group_tag query param filters stats to groups carrying that tag
  // (getNetworkStatsv2). The plain GET /api/v2/networks/{id} response
  // already embeds unfiltered stats (App\Http\Resources\Network#stats) -
  // this dedicated call is only needed for the tag-filtered case.
  stats(id, params) {
    return this.$get(`/api/v2/networks/${id}/stats`, params)
  }

  // POST /api/v2/networks/{id}/groups - documented but NOT yet implemented
  // server-side (the only existing route, networks.associate-group in
  // routes/web.php, is a session+CSRF form POST with a redirect response,
  // unusable from the SPA). See docs/nuxt-migration/api-gaps.md Phase E.
  associateGroups(id, groupIds) {
    return this.$post(`/api/v2/networks/${id}/groups`, { groups: groupIds })
  }
}
