import { describe, expect, it, vi } from 'vitest'
import { mergePlaceSearchResults, buildPlaceSearchGeocoder } from '../../app/utils/placeSearchGeocoder.js'

// User feedback: searching "Haringey" (a borough) found nothing while
// "Muswell Hill" (a suburb) worked. Photon never ranks administrative
// boundaries into a plain query's results, so the search runs twice - once
// filtered to place layers (districts/cities/counties/states), once
// unfiltered - and shows the places first.
describe('utils/placeSearchGeocoder', () => {
  const r = (name) => ({ name, center: { lat: 0, lng: 0 } })

  describe('mergePlaceSearchResults', () => {
    it('puts place-layer results ahead of general ones', () => {
      const merged = mergePlaceSearchResults([r('London Borough of Haringey')], [r('Haringey Park'), r('Haringey Road')])
      expect(merged.map((x) => x.name)).toEqual(['London Borough of Haringey', 'Haringey Park', 'Haringey Road'])
    })

    it('drops duplicates by name, keeping the place-layer copy', () => {
      const place = r('Muswell Hill')
      const merged = mergePlaceSearchResults([place], [r('Muswell Hill'), r('Muswell Hill Library')])
      expect(merged.map((x) => x.name)).toEqual(['Muswell Hill', 'Muswell Hill Library'])
      expect(merged[0]).toBe(place)
    })

    it('caps the merged list at 10', () => {
      const many = Array.from({ length: 8 }, (_, i) => r(`Place ${i}`))
      const more = Array.from({ length: 8 }, (_, i) => r(`General ${i}`))
      expect(mergePlaceSearchResults(many, more)).toHaveLength(10)
    })
  })

  describe('buildPlaceSearchGeocoder', () => {
    it('queries both geocoders and merges, places first', async () => {
      const places = { geocode: vi.fn().mockResolvedValue([r('The Borough')]) }
      const general = { geocode: vi.fn().mockResolvedValue([r('A Street')]) }

      const merged = await buildPlaceSearchGeocoder({ places, general }).geocode('borough', { some: 'context' })

      expect(places.geocode).toHaveBeenCalledWith('borough', { some: 'context' })
      expect(general.geocode).toHaveBeenCalledWith('borough', { some: 'context' })
      expect(merged.map((x) => x.name)).toEqual(['The Borough', 'A Street'])
    })

    it('still returns the other list when one query fails', async () => {
      const places = { geocode: vi.fn().mockRejectedValue(new Error('boom')) }
      const general = { geocode: vi.fn().mockResolvedValue([r('A Street')]) }

      const merged = await buildPlaceSearchGeocoder({ places, general }).geocode('q')

      expect(merged.map((x) => x.name)).toEqual(['A Street'])
    })

    it('suggest() is the same search', async () => {
      const places = { geocode: vi.fn().mockResolvedValue([]) }
      const general = { geocode: vi.fn().mockResolvedValue([r('X')]) }

      const merged = await buildPlaceSearchGeocoder({ places, general }).suggest('q')

      expect(merged.map((x) => x.name)).toEqual(['X'])
    })
  })
})
