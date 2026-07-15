import BaseAPI from './BaseAPI.js'

/**
 * Thin, hardcoded-path client for the network endpoint family. Full surface
 * lands with the Networks migration slice (design.md §6.2); this is the
 * scaffold-stage shape.
 */
export default class NetworkAPI extends BaseAPI {
  list(params) {
    return this.$get('/api/v2/networks', params)
  }

  get(id) {
    return this.$get(`/api/v2/networks/${id}`)
  }
}
