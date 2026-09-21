// Stub for the leaflet-control-geocoder CommonJS subpath imports used by
// GroupMap.vue. The real package is a runtime-only dependency; we never
// exercise its behaviour from Jest, so any plain class is fine.
class Stub {
  constructor() {}
  on() { return this }
  addTo() { return this }
  setQuery() {}
}
module.exports = { Geocoder: Stub, Photon: Stub, default: Stub }
