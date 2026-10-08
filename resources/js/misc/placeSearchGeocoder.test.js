import { mergePlaceSearchResults, buildPlaceSearchGeocoder } from './placeSearchGeocoder'

// User feedback: searching "Haringey" (a borough) found nothing while
// "Muswell Hill" (a suburb) worked. Photon never ranks administrative
// boundaries into a plain query's results, so the search runs twice - once
// filtered to place layers, once unfiltered - and shows the places first.
describe('placeSearchGeocoder', () => {
  const r = (name) => ({ name, center: { lat: 0, lng: 0 } })

  describe('mergePlaceSearchResults', () => {
    test('puts place-layer results ahead of general ones', () => {
      const merged = mergePlaceSearchResults([r('London Borough of Haringey')], [r('Haringey Park')])
      expect(merged.map((x) => x.name)).toEqual(['London Borough of Haringey', 'Haringey Park'])
    })

    test('drops duplicates by name, keeping the place-layer copy', () => {
      const place = r('Muswell Hill')
      const merged = mergePlaceSearchResults([place], [r('Muswell Hill'), r('Muswell Hill Library')])
      expect(merged.map((x) => x.name)).toEqual(['Muswell Hill', 'Muswell Hill Library'])
      expect(merged[0]).toBe(place)
    })

    test('caps the merged list at 10', () => {
      const many = Array.from({ length: 8 }, (_, i) => r(`P${i}`))
      const more = Array.from({ length: 8 }, (_, i) => r(`G${i}`))
      expect(mergePlaceSearchResults(many, more)).toHaveLength(10)
    })
  })

  describe('buildPlaceSearchGeocoder (v1 callback API)', () => {
    const cbGeocoder = (results) => ({
      geocode: jest.fn((query, cb, context) => cb.call(context, results)),
    })

    test('queries both geocoders and calls back with places first', (done) => {
      const places = cbGeocoder([r('The Borough')])
      const general = cbGeocoder([r('A Street')])

      buildPlaceSearchGeocoder({ places, general }).geocode('borough', function (results) {
        expect(results.map((x) => x.name)).toEqual(['The Borough', 'A Street'])
        done()
      })
    })

    test('still returns the other list when one side throws', (done) => {
      const places = { geocode: jest.fn(() => { throw new Error('boom') }) }
      const general = cbGeocoder([r('A Street')])

      buildPlaceSearchGeocoder({ places, general }).geocode('q', function (results) {
        expect(results.map((x) => x.name)).toEqual(['A Street'])
        done()
      })
    })

    test('suggest() is the same search', (done) => {
      const places = cbGeocoder([])
      const general = cbGeocoder([r('X')])

      buildPlaceSearchGeocoder({ places, general }).suggest('q', function (results) {
        expect(results.map((x) => x.name)).toEqual(['X'])
        done()
      })
    })
  })
})
