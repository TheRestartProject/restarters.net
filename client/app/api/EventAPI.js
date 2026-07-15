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
}
