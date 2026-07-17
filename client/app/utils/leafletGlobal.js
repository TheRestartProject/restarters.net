import L from 'leaflet'

// leaflet.markercluster and leaflet-control-geocoder's Photon backend both
// read the bare `L` identifier as a global rather than importing it (the
// same assumption resources/js/app.js makes when it loads Leaflet from a
// CDN: `global.L = L`). Bundled builds of those two packages resolve that
// identifier via `window.L`/`globalThis.L`, so it must exist before either
// is imported - see GroupMap.vue, which imports this module first for its
// side effect, then imports 'leaflet.markercluster'.
//
// This also has to be the SAME Leaflet instance vue-leaflet's <LMap> uses,
// otherwise `instanceof` checks (and Leaflet's shared default-icon state)
// see two unrelated copies of the library. <LMap use-global-leaflet> makes
// vue-leaflet read window.L instead of dynamically importing its own copy,
// which only works once this has run.
if (typeof window !== 'undefined' && !window.L) {
  window.L = L
}

export default L
