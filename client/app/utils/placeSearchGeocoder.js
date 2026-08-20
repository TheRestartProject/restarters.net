// The map's place-search geocoder. Photon never ranks administrative
// boundaries into a plain query's results - searching "Haringey" (a London
// borough) returned only streets and bus stops while "Muswell Hill" (a
// suburb, which Photon treats as a place) worked (user feedback, 2026-08-20).
// So the search runs twice: once filtered to Photon's place layers
// (district/city/county/state - boroughs are `layer=district`), once
// unfiltered, and the dropdown lists the places first.

// Places first, then everything else, deduped by display name (the
// place-layer copy wins - it carries the boundary's full extent, so
// selecting it frames the whole area). Capped at 10 like Photon's own
// default result list.
export function mergePlaceSearchResults(places, general) {
  const seen = new Set()

  return [...places, ...general]
    .filter((result) => {
      if (seen.has(result.name)) return false
      seen.add(result.name)
      return true
    })
    .slice(0, 10)
}

// Wraps two IGeocoder instances (leaflet-control-geocoder v3 promise API)
// into one. A failed query on either side degrades to the other's results
// rather than failing the search.
export function buildPlaceSearchGeocoder({ places, general }) {
  return {
    async geocode(query, context) {
      const [p, g] = await Promise.all([
        places.geocode(query, context).catch(() => []),
        general.geocode(query, context).catch(() => []),
      ])
      return mergePlaceSearchResults(p, g)
    },
    suggest(query, context) {
      return this.geocode(query, context)
    },
  }
}

// Photon's layers that represent named places rather than addresses or
// venues. `district` covers both suburbs and London-borough-style
// administrative areas.
export const PLACE_LAYERS = ['district', 'city', 'county', 'state']
