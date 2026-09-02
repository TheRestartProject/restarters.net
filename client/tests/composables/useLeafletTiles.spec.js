import { describe, expect, it, vi } from 'vitest'
import { useLeafletTiles } from '../../app/composables/useLeafletTiles.js'
import { LEAFLET_TILES } from '../../app/utils/mapConstants.js'

const withKey = (cartoApiKey) =>
  vi.stubGlobal('useRuntimeConfig', () => ({ public: { cartoApiKey } }))

describe('composables/useLeafletTiles', () => {
  it('appends the CARTO key when one is configured', () => {
    withKey('cb1_test_key')

    expect(useLeafletTiles()).toBe(`${LEAFLET_TILES}?key=cb1_test_key`)
  })

  it('returns the bare URL when no key is configured, so tiles still render', () => {
    withKey('')

    expect(useLeafletTiles()).toBe(LEAFLET_TILES)
  })

  it('returns the bare URL when the key is missing entirely', () => {
    vi.stubGlobal('useRuntimeConfig', () => ({ public: {} }))

    expect(useLeafletTiles()).toBe(LEAFLET_TILES)
  })

  it('leaves Leaflet\'s placeholders intact so tiles still resolve', () => {
    withKey('cb1_test_key')

    const url = useLeafletTiles()

    for (const placeholder of ['{s}', '{z}', '{x}', '{y}', '{r}']) {
      expect(url).toContain(placeholder)
    }
  })
})
