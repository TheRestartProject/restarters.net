import BaseAPI from './BaseAPI.js'

/**
 * Thin client for the maps proxy family (design.md §6.2 B6 task brief;
 * api-contracts-phase-b.md B6). The session-auth Blade routes
 * (MapsProxyController: GET /maps/autocomplete, GET /maps/place-details)
 * already exist and keep the Google API key server-side, but the
 * bearer-token v2 equivalents documented here
 * (`GET /api/v2/maps/autocomplete`, `GET /api/v2/maps/place-details`) are
 * not implemented yet - "moves in this phase" per the contract doc, but not
 * landed on this branch. See docs/nuxt-migration/api-gaps.md B6.
 *
 * Both return the raw Google Places JSON shape (predictions[]/result),
 * unlike the rest of the v2 API's {data: ...} envelope - matching what
 * MapsProxyController passes straight through today.
 */
export default class MapsAPI extends BaseAPI {
  autocomplete(input, types = 'geocode') {
    return this.$get('/api/v2/maps/autocomplete', { input, types })
  }

  placeDetails(placeId) {
    return this.$get('/api/v2/maps/place-details', { place_id: placeId })
  }
}
