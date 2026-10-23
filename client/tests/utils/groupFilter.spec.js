import { describe, expect, it } from 'vitest'
import { matchesGroupFilters } from '../../app/utils/groupFilter.js'

// The list and the map must agree on what a filter means (develop's
// resources/js/misc/groupFilter.js): the list shows what the map shows.
describe('utils/groupFilter', () => {
  const group = { name: 'London Fixers', tagIds: [5, 6] }

  it('matches everything with no filters or an empty bar', () => {
    expect(matchesGroupFilters(group, null)).toBe(true)
    expect(matchesGroupFilters(group, { name: '', tags: [] })).toBe(true)
  })

  it('matches names case-insensitively, anywhere in the name', () => {
    expect(matchesGroupFilters(group, { name: 'fixers', tags: [] })).toBe(true)
    expect(matchesGroupFilters(group, { name: 'paris', tags: [] })).toBe(false)
  })

  it('does not match a group with no name when a name is typed', () => {
    expect(matchesGroupFilters({ tagIds: [] }, { name: 'x', tags: [] })).toBe(false)
  })

  it('requires every selected tag, so more tags narrow the results', () => {
    expect(matchesGroupFilters(group, { name: '', tags: [5] })).toBe(true)
    expect(matchesGroupFilters(group, { name: '', tags: [5, 6] })).toBe(true)
    expect(matchesGroupFilters(group, { name: '', tags: [5, 7] })).toBe(false)
  })

  it('compares tag ids whether they arrive as numbers or strings', () => {
    expect(matchesGroupFilters({ name: 'a', tagIds: ['5'] }, { name: '', tags: [5] })).toBe(true)
  })

  it('treats a group with no tags as matching no tag filter', () => {
    expect(matchesGroupFilters({ name: 'a' }, { name: '', tags: [5] })).toBe(false)
  })

  it('applies name and tags together', () => {
    expect(matchesGroupFilters(group, { name: 'london', tags: [7] })).toBe(false)
    expect(matchesGroupFilters(group, { name: 'london', tags: [6] })).toBe(true)
  })
})
