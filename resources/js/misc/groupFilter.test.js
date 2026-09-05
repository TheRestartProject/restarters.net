import { matchesFilters, inNetwork } from './groupFilter'

// A group's networks arrive in two shapes: the names index that draws the map
// sends plain ids, while the summary API sends objects. Matching only one of
// them empties the network pages.
describe('inNetwork', () => {
  test('matches plain ids, as sent by the names index', () => {
    expect(inNetwork({ networks: [5, 6] }, 5)).toBe(true)
    expect(inNetwork({ networks: [6] }, 5)).toBe(false)
  })

  test('matches objects, as sent by the summary API', () => {
    expect(inNetwork({ networks: [{ id: 5 }, { id: 6 }] }, 5)).toBe(true)
    expect(inNetwork({ networks: [{ id: 6 }] }, 5)).toBe(false)
  })

  test('a group in no networks matches nothing', () => {
    expect(inNetwork({}, 5)).toBe(false)
    expect(inNetwork({ networks: [] }, 5)).toBe(false)
  })

  test('everything is in scope when no network is asked for', () => {
    expect(inNetwork({ networks: [] }, null)).toBe(true)
  })
})

const group = {
  id: 1,
  name: 'Hackney Fixing Factory',
  group_tags_full: [{ id: 1, name: 'Lille' }, { id: 2, name: 'Repair Cafe' }],
}

test('everything matches when nothing is filtered', () => {
  expect(matchesFilters(group, null)).toBe(true)
  expect(matchesFilters(group, {})).toBe(true)
})

test('matches part of a name, ignoring case', () => {
  expect(matchesFilters(group, { name: 'hackney' })).toBe(true)
  expect(matchesFilters(group, { name: 'Fixing' })).toBe(true)
  expect(matchesFilters(group, { name: 'Ulverston' })).toBe(false)
})

test('a group with no name matches nothing', () => {
  expect(matchesFilters({ id: 2 }, { name: 'anything' })).toBe(false)
})

// Choosing more tags should narrow the results, not widen them.
test('requires every selected tag, not just one of them', () => {
  expect(matchesFilters(group, { tags: [{ id: 1 }] })).toBe(true)
  expect(matchesFilters(group, { tags: [{ id: 1 }, { id: 2 }] })).toBe(true)
  expect(matchesFilters(group, { tags: [{ id: 1 }, { id: 99 }] })).toBe(false)
})

test('a group with no tags matches only an empty tag filter', () => {
  const untagged = { id: 3, name: 'Untagged' }
  expect(matchesFilters(untagged, { tags: [] })).toBe(true)
  expect(matchesFilters(untagged, { tags: [{ id: 1 }] })).toBe(false)
})

test('name and tags both have to match', () => {
  expect(matchesFilters(group, { name: 'hackney', tags: [{ id: 1 }] })).toBe(true)
  expect(matchesFilters(group, { name: 'ulverston', tags: [{ id: 1 }] })).toBe(false)
  expect(matchesFilters(group, { name: 'hackney', tags: [{ id: 99 }] })).toBe(false)
})
