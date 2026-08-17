import { describe, expect, it } from 'vitest'
import {
  boundingBoxFor,
  filterMappableGroups,
  hasLocation,
  idsInBounds,
  markerClassName,
  nearestGroups,
  separateIdenticalLocations,
} from '../../app/composables/useGroupMapGeometry.js'

// The groups page sends this inverted whole-world box when the user has no
// location set (min_lat 90 > max_lat -90) - see GroupMap.vue's doc comment.
const WORLD = [
  [90, 180],
  [-90, -180],
]

describe('composables/useGroupMapGeometry', () => {
  describe('filterMappableGroups', () => {
    const groups = [
      { id: 1, lat: 51.5, lng: -0.1, network_ids: [5] },
      { id: 2, lat: null, lng: null, network_ids: [5] },
      { id: 3, lat: 53.4, lng: -2.2, network_ids: [6] },
      { id: 4 },
    ]

    it('only keeps groups with numeric coordinates', () => {
      expect(filterMappableGroups(groups).map((g) => g.id)).toEqual([1, 3])
    })

    it('filters by network when given', () => {
      expect(filterMappableGroups(groups, 6).map((g) => g.id)).toEqual([3])
    })

    it('handles a missing/empty groups array', () => {
      expect(filterMappableGroups(null)).toEqual([])
      expect(filterMappableGroups(undefined, 5)).toEqual([])
    })
  })

  describe('hasLocation', () => {
    it('is false for the inverted whole-world box (no user location)', () => {
      expect(hasLocation(WORLD)).toBe(false)
    })

    it('is true for a real bounding box', () => {
      expect(hasLocation([[51.0, -0.8], [51.8, 0.4]])).toBe(true)
    })

    it('is false for null (this branch has no source for the legacy box)', () => {
      expect(hasLocation(null)).toBe(false)
    })

    it('is false for malformed input', () => {
      expect(hasLocation([])).toBe(false)
      expect(hasLocation([[1, 2]])).toBe(false)
      expect(hasLocation('not an array')).toBe(false)
    })
  })

  describe('idsInBounds', () => {
    const groups = [
      { id: 1, lat: 51.5, lng: -0.1 },
      { id: 2, lat: 60, lng: 10 },
    ]

    it('returns ids whose coordinates the bounds contains', () => {
      const bounds = { contains: ([lat]) => lat < 55 }
      expect(idsInBounds(groups, bounds)).toEqual([1])
    })

    // Regression (ported from GroupMapAndList.test.js): an empty result is a
    // real answer, not "no bounds yet" - callers must not conflate the two.
    it('returns an empty array, not the full list, when nothing matches', () => {
      const bounds = { contains: () => false }
      expect(idsInBounds(groups, bounds)).toEqual([])
    })

    it('returns an empty array when there is no bounds at all', () => {
      expect(idsInBounds(groups, null)).toEqual([])
    })
  })

  describe('nearestGroups', () => {
    it('returns the closest `count` groups to the centre, nearest first', () => {
      const groups = [
        { id: 'far', lat: 10, lng: 10 },
        { id: 'near', lat: 0.1, lng: 0.1 },
        { id: 'mid', lat: 1, lng: 1 },
      ]

      expect(nearestGroups(groups, { lat: 0, lng: 0 }, 2).map((g) => g.id)).toEqual(['near', 'mid'])
    })

    it('does not mutate the input array', () => {
      const groups = [
        { id: 1, lat: 5, lng: 5 },
        { id: 2, lat: 0, lng: 0 },
      ]
      const copy = [...groups]

      nearestGroups(groups, { lat: 0, lng: 0 }, 1)

      expect(groups).toEqual(copy)
    })
  })

  describe('boundingBoxFor', () => {
    it('returns null for an empty set', () => {
      expect(boundingBoxFor([])).toBeNull()
    })

    it('returns the min/max lat/lng spanning every group', () => {
      const groups = [
        { lat: 51.5, lng: -0.1 },
        { lat: 55.9, lng: -3.2 },
        { lat: 53.4, lng: 1.0 },
      ]

      expect(boundingBoxFor(groups)).toEqual({ minLat: 51.5, maxLat: 55.9, minLng: -3.2, maxLng: 1.0 })
    })
  })

  // Freegle's ClusterMarker.vue approach for pins at the exact same
  // coordinates: each subsequent duplicate is nudged by a small fixed offset
  // so both pins are visible/clickable when zoomed right in.
  describe('separateIdenticalLocations', () => {
    it('leaves groups at distinct locations untouched', () => {
      const groups = [
        { id: 1, name: 'A', lat: 51.5, lng: -0.1 },
        { id: 2, name: 'B', lat: 53.4, lng: -2.2 },
      ]

      expect(separateIdenticalLocations(groups)).toEqual(groups)
    })

    it('nudges each subsequent duplicate at a location by 0.003 degrees', () => {
      const groups = [
        { id: 1, lat: 51.5, lng: -0.1 },
        { id: 2, lat: 51.5, lng: -0.1 },
        { id: 3, lat: 51.5, lng: -0.1 },
      ]

      const [first, second, third] = separateIdenticalLocations(groups)
      expect(first.lat).toBe(51.5)
      expect(first.lng).toBe(-0.1)
      expect(second.lat).toBeCloseTo(51.503, 10)
      expect(second.lng).toBeCloseTo(-0.097, 10)
      expect(third.lat).toBeCloseTo(51.506, 10)
      expect(third.lng).toBeCloseTo(-0.094, 10)
    })

    it('never mutates the input groups (Freegle regression: offsets would accumulate)', () => {
      const groups = [
        { id: 1, lat: 51.5, lng: -0.1 },
        { id: 2, lat: 51.5, lng: -0.1 },
      ]

      separateIdenticalLocations(groups)
      separateIdenticalLocations(groups)

      expect(groups[1]).toEqual({ id: 2, lat: 51.5, lng: -0.1 })
    })

    it('coerces string coordinates so the nudge is arithmetic, not concatenation', () => {
      const groups = [
        { id: 1, lat: '51.5', lng: '-0.1' },
        { id: 2, lat: '51.5', lng: '-0.1' },
      ]

      const [first, second] = separateIdenticalLocations(groups)
      expect(first.lat).toBe(51.5)
      expect(second.lat).toBeCloseTo(51.503, 10)
      expect(second.lng).toBeCloseTo(-0.097, 10)
    })

    it('preserves the other names-index fields on nudged entries', () => {
      const groups = [
        { id: 1, name: 'A', lat: 51.5, lng: -0.1, network_ids: [1] },
        { id: 2, name: 'B', lat: 51.5, lng: -0.1, network_ids: [2] },
      ]

      const [, second] = separateIdenticalLocations(groups)
      expect(second.id).toBe(2)
      expect(second.name).toBe('B')
      expect(second.network_ids).toEqual([2])
    })
  })

  describe('markerClassName', () => {
    it('is plain by default', () => {
      expect(markerClassName(1)).toBe('')
    })

    it('is the "yours" class for a group the user belongs to', () => {
      expect(markerClassName(1, { yourGroupIds: [1, 2] })).toBe('group-marker-yours')
    })

    it('hover wins over "yours", matching GroupMarker.vue\'s priority', () => {
      expect(markerClassName(1, { hoveredId: 1, yourGroupIds: [1] })).toBe('group-marker-hover')
    })
  })
})
