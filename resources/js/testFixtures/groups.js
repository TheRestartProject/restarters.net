// One definition of what a group looks like once it is in the store, so that
// component tests exercise the shape the store actually produces rather than
// one someone imagined while writing the test.
//
// Inventing a group inline in a component test is what let the network pages
// ship empty: those fixtures said networks were [{id}] objects, while the map
// is drawn from the names index, which sends plain ids.
//
// INDEX_API_ENTRY and INDEX_STORE_ENTRY are a matched pair - the response the
// names index sends, and exactly what the store turns it into.
// store/groups.test.js feeds the first in and asserts the whole of the second
// comes out, with nothing overridden, so a change to the mapping fails there
// and this file has to be updated with it.

export const INDEX_API_ENTRY = {
  id: 7,
  name: 'G',
  lat: 51,
  lng: 0,
  country: 'United Kingdom',
  network_ids: [3],
  tag_ids: [9],
  archived_at: null,
}

export const INDEX_STORE_ENTRY = {
  id: 7,
  name: 'G',
  // The index puts coordinates at the top level and inside location.
  lat: 51,
  lng: 0,
  location: {
    location: null,
    country: 'United Kingdom',
    lat: 51,
    lng: 0,
  },
  // Plain ids, not objects.
  networks: [3],
  // Tag ids wrapped as objects; the names arrive with hydration.
  group_tags_full: [{ id: 9 }],
  archived_at: null,
}

// A group as the names index leaves it: enough to draw the map and run the
// client-side filters, with no image, location text, counts or next event.
export function indexGroup(overrides = {}) {
  return { ...INDEX_STORE_ENTRY, ...overrides }
}

// A group after hydrate() has filled in the rest from the summary API. Note
// that the summary API sends networks as objects, unlike the index - both
// shapes are live at once, because the table hydrates only its visible rows.
export function hydratedGroup(overrides = {}) {
  return {
    ...INDEX_STORE_ENTRY,
    image: null,
    location: {
      location: 'Townsville',
      country: 'United Kingdom',
      lat: 51,
      lng: 0,
      distance: null,
    },
    networks: [{ id: 3 }],
    next_event: null,
    summary: true,
    ...overrides,
  }
}
