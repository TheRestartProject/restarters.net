// A group's networks arrive in two shapes: the names index that draws the map
// sends plain ids, while the summary API sends objects. Understanding only one
// of them leaves the network pages with an empty map and an empty list.
export function inNetwork(group, networkId) {
  if (!networkId) {
    return true
  }

  return (group.networks || []).some(n => (n && n.id !== undefined ? n.id : n) === networkId)
}

// The map and the list must agree on what a filter means: the list shows what
// the map shows, so if they disagree you get a count that doesn't match the
// pins, or pins for groups that aren't listed.
export function matchesFilters(group, filters) {
  if (!filters) {
    return true
  }

  if (filters.name) {
    const name = filters.name.toLowerCase()

    if (!group.name || !group.name.toLowerCase().includes(name)) {
      return false
    }
  }

  if (filters.tags && filters.tags.length) {
    const groupTags = group.group_tags_full || []

    // Every selected tag must be present, so choosing more tags narrows the
    // results rather than widening them.
    return filters.tags.every(t => groupTags.some(gt => gt.id === t.id))
  }

  return true
}
