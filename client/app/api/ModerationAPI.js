import BaseAPI from './BaseAPI.js'

/**
 * Moderation queues for Administrators and NetworkCoordinators:
 *   GET /api/v2/moderate/events - events awaiting approval, across the caller's
 *       networks (all networks for an Administrator). API\EventController::moderateEventsv2.
 *   GET /api/v2/moderate/groups - unapproved groups visible to the caller.
 *       API\GroupController::moderateGroupsv2.
 * Both are gated server-side (auth:sanctum,api + the role/ownership checks in the
 * controllers); this client surface just fetches them for the queue UI the
 * legacy EventsRequiringModeration / GroupsRequiringModeration components showed.
 */
export default class ModerationAPI extends BaseAPI {
  events() {
    return this.$get('/api/v2/moderate/events')
  }

  groups() {
    return this.$get('/api/v2/moderate/groups')
  }
}
