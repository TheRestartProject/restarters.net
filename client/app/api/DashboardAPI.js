import BaseAPI from './BaseAPI.js'

/**
 * The dashboard's single bootstrap call (api-contracts-phase-b.md B1):
 * your groups, nearby groups, newly-added nearby groups and upcoming events
 * in one shape. Endpoint does not exist server-side yet (B1 is a separate
 * plan row) - client code is built against the documented contract.
 */
export default class DashboardAPI extends BaseAPI {
  fetch() {
    return this.$get('/api/v2/dashboard')
  }
}
