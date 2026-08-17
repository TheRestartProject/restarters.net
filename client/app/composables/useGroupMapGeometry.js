/**
 * Pure geometry helpers behind /group/map's GroupMap.vue, split out so they
 * can be unit-tested without mounting a real Leaflet map (this repo favours
 * testing observable behaviour/composable outputs over reaching into
 * component internals - see e.g. useCategorySuggestion.js).
 *
 * Ported from resources/js/components/GroupMap.vue's allGroups/
 * mappableGroups/hasLocation/zoomToGroups computed properties and methods,
 * and GroupMarker.vue's icon className logic. `groups` throughout is the
 * names-index shape (GroupController::listNamesv2, PR #887):
 * {id, name, lat, lng, country, network_ids, tag_ids, archived_at}.
 */

// A group with no geocode has null lat/lng; leaving it in would put a
// marker at null island (0,0) - same guard as legacy's mappableGroups.
export function filterMappableGroups(groups, network = null) {
  const byNetwork = network ? (groups || []).filter((g) => (g.network_ids || []).includes(network)) : groups || []

  return byNetwork.filter((g) => g.lat != null && g.lng != null && !isNaN(+g.lat) && !isNaN(+g.lng))
}

// The groups page sends the inverted whole-world box [[90,180],[-90,-180]]
// when the user has no location set; a real bounding box always has
// min_lat <= max_lat. Also false for null/malformed input (this branch has
// no source for the legacy bounding box at all - see GroupMap.vue's own
// doc comment).
export function hasLocation(bounds) {
  if (!Array.isArray(bounds) || bounds.length < 2 || !Array.isArray(bounds[0]) || !Array.isArray(bounds[1])) {
    return false
  }
  return +bounds[0][0] <= +bounds[1][0]
}

// ids of mappable groups whose [lat,lng] fall within `bounds`. `bounds` just
// needs a `.contains([lat,lng])` method - a real Leaflet LatLngBounds
// satisfies that, and so does a plain test double, keeping this decoupled
// from the Leaflet runtime.
export function idsInBounds(mappableGroups, bounds) {
  if (!bounds) return []
  return mappableGroups.filter((g) => bounds.contains([g.lat, g.lng])).map((g) => g.id)
}

// The `count` groups closest to `center` ({lat, lng}) - legacy: "frame the 5
// groups closest to the centre" when the user has a location.
export function nearestGroups(mappableGroups, center, count = 5) {
  return [...mappableGroups]
    .map((group) => ({
      group,
      distance: Math.sqrt((group.lat - center.lat) ** 2 + (group.lng - center.lng) ** 2),
    }))
    .sort((a, b) => a.distance - b.distance)
    .slice(0, count)
    .map((entry) => entry.group)
}

// Plain {minLat, maxLat, minLng, maxLng} bounding box for a set of groups -
// deliberately not an L.LatLngBounds, so this file never has to import
// Leaflet itself. GroupMap.vue converts the result with `L.latLngBounds(...)`
// before calling fitBounds. Returns null for an empty set (nothing to frame).
export function boundingBoxFor(mappableGroups) {
  if (!mappableGroups.length) return null

  let minLat = Infinity
  let maxLat = -Infinity
  let minLng = Infinity
  let maxLng = -Infinity

  mappableGroups.forEach((g) => {
    minLat = Math.min(minLat, g.lat)
    maxLat = Math.max(maxLat, g.lat)
    minLng = Math.min(minLng, g.lng)
    maxLng = Math.max(maxLng, g.lng)
  })

  return { minLat, maxLat, minLng, maxLng }
}

// Groups at the exact same coordinates would render as one unclickable
// stack even at max zoom. Ported from Freegle's ClusterMarker.vue: nudge
// each subsequent duplicate at a location by a small fixed offset, into NEW
// objects - mutating the input would corrupt the store's coordinates and
// accumulate on every recompute (a bug Freegle actually hit, per its own
// comment). Coordinates are coerced to numbers first: the names index can
// carry them as strings, and "51.5" + 0.00015 would concatenate.
//
// The offset is much smaller than Freegle's 0.003: that put pins streets
// apart, which read as two genuinely different locations (user feedback).
// 0.00015 degrees is ~28px lng / ~45px lat at max zoom (z18, UK latitudes) -
// pins only visibly split right at max zoom, where GroupMap.vue also
// disables clustering so the split is actually shown.
export function separateIdenticalLocations(mappableGroups) {
  const seen = {}

  return mappableGroups.map((group) => {
    const lat = +group.lat
    const lng = +group.lng
    const key = `${lat}|${lng}`
    const already = seen[key] || 0
    seen[key] = already + 1

    return { ...group, lat: lat + already * 0.00015, lng: lng + already * 0.00015 }
  })
}

// GroupMarker.vue's icon computed, ported: hover wins over "yours".
export function markerClassName(groupId, { hoveredId = null, yourGroupIds = [] } = {}) {
  if (hoveredId === groupId) return 'group-marker-hover'
  if (yourGroupIds.includes(groupId)) return 'group-marker-yours'
  return ''
}
