import BaseAPI from './BaseAPI.js'

/**
 * Thin client for small, unauthenticated, non-v2 config endpoints that
 * don't fit the resource-per-model classes. `timezones()` backs
 * GroupForm.vue's timezone field (GroupTimeZone.vue is the functional
 * spec) - GET /api/timezones already exists (ApiController::timezones) and
 * predates the v2 API/{data:...} envelope, so it returns a bare array.
 */
export default class ConfigAPI extends BaseAPI {
  timezones() {
    return this.$get('/api/timezones')
  }
}
