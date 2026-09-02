// Ported from resources/js/constants.js: CARTO Voyager raster tiles and the
// zoom range used by the legacy GroupMap.vue. CARTO now require an API key -
// without one the tiles still load but carry an "API KEY REQUIRED" watermark -
// so read the URL through useLeafletTiles() rather than using LEAFLET_TILES
// directly.
export const LEAFLET_TILES = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager_labels_under/{z}/{x}/{y}{r}.png'
export const LEAFLET_ATTRIBUTION =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="https://carto.com/attribution">CARTO</a>'
export const MIN_MAP_ZOOM = 1
// Legacy capped this at 14, which user feedback found too shallow to read
// street names or tell close-together pins apart; CARTO's raster tiles
// serve up to z18.
export const MAX_MAP_ZOOM = 18
// leaflet.markercluster's maxClusterRadius (px). The default 80 left too
// many small cluster bubbles on screen at once (user feedback); wider
// merges them into fewer, larger clusters.
export const MAP_CLUSTER_RADIUS = 120
