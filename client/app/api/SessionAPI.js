import BaseAPI from './BaseAPI.js'

/**
 * The single client-bootstrap call (design.md §4.4 / §6): user, config and
 * feature flags in one shape.
 */
export default class SessionAPI extends BaseAPI {
  fetch() {
    return this.$get('/api/v2/session')
  }

  save({ locale }) {
    return this.$patch('/api/v2/session', { locale })
  }
}
