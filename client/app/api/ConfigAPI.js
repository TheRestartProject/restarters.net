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

  // GET /api/homepage_data (api-contracts-phase-c.md C6b, EXISTS, v1,
  // unauthenticated, 12h server cache). Embedded as-is at
  // therestartproject.org, so its shape is frozen - no {data:...} envelope,
  // the body IS the payload: {participants, hours_volunteered, items_fixed,
  // waste_powered, waste_unpowered, waste_total, co2_powered, co2_unpowered,
  // co2_total, fixed_powered, fixed_unpowered, total_powered,
  // total_unpowered, ...legacy aliases}. Backs components/fixometer/
  // ImpactStats.vue (resources/js/components/FixometerGlobalImpact.vue is
  // the functional spec).
  homepageData() {
    return this.$get('/api/homepage_data')
  }
}
