import { LEAFLET_TILES, leafletTiles } from '../constants'

afterEach(() => {
  delete window.restarters
})

test('returns the bare tile URL when no CARTO key has been injected', () => {
  expect(leafletTiles()).toBe(LEAFLET_TILES)
})

test('returns the bare tile URL when the key is present but empty', () => {
  window.restarters = { cartoApiKey: '' }

  expect(leafletTiles()).toBe(LEAFLET_TILES)
})

test('appends the CARTO key when one has been injected', () => {
  window.restarters = { cartoApiKey: 'cb1_test_key' }

  expect(leafletTiles()).toBe(LEAFLET_TILES + '?key=cb1_test_key')
})

test('leaves the Leaflet placeholders intact so tiles still resolve', () => {
  window.restarters = { cartoApiKey: 'cb1_test_key' }

  const url = leafletTiles()

  for (const placeholder of ['{s}', '{z}', '{x}', '{y}', '{r}']) {
    expect(url).toContain(placeholder)
  }
})
