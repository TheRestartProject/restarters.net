import { matchesFilters } from './groupFilter'

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
