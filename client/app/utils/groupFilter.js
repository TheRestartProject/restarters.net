// The groups filter bar's meaning, shared by the list and the map so the two
// can't disagree: the list shows what the map shows, so if they differed you
// would get a count that doesn't match the pins, or pins for groups that
// aren't listed. (resources/js/misc/groupFilter.js in develop.)
//
// `group` needs only `name` and `tagIds`; `filters` is the bar's
// {name, tags} where tags is an array of tag ids. No filters, or an empty
// bar, matches everything.
export function matchesGroupFilters(group, filters) {
  if (!filters) return true

  if (filters.name) {
    const needle = String(filters.name).toLowerCase()
    if (!group.name || !String(group.name).toLowerCase().includes(needle)) return false
  }

  if (filters.tags && filters.tags.length) {
    const have = (group.tagIds || []).map(String)
    // Every selected tag must be present, so choosing more tags narrows the
    // results rather than widening them.
    return filters.tags.every((tag) => have.includes(String(tag)))
  }

  return true
}
