import { existsSync, readdirSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'
import { STATS_SHARE_PLATFORMS, useStatsShareImage } from '../../app/composables/useStatsShareImage.js'

// These filenames are no longer served from the Nuxt client's own public/
// (that would have meant shipping the entire ~224MB image set in the client
// bundle/repo) - they're proxied from Laravel's public/images/stats/ via
// GET /api/v2/stats/share-image/{filename} (StatsShareImageController),
// which serves this exact directory with an allowlist check. Reading it
// directly here (one level up from client/) still lets this test assert
// every filename the lookup table can produce corresponds to a real file,
// without the client needing a copy of its own.
const statsImagesDir = join(process.cwd(), '../public/images/stats/')

describe('composables/useStatsShareImage', () => {
  describe('rangeIndex/getImage - the CO2e -> background-image lookup table', () => {
    it('resolves the lowest bracket for a count below the table minimum (30kg)', () => {
      const { getImage } = useStatsShareImage()
      expect(getImage(0, 'Facebook')).toBe('ImpactRange1Landscape-1.png')
      expect(getImage(29, 'Facebook')).toBe('ImpactRange1Landscape-1.png')
    })

    it('walks level 1 (single-seedling) boundaries', () => {
      const { getImage } = useStatsShareImage()
      expect(getImage(89.99, 'Facebook')).toBe('ImpactRange1Landscape-1.png')
      expect(getImage(90, 'Facebook')).toBe('ImpactRange1Landscape-2.png')
      expect(getImage(209.99, 'Facebook')).toBe('ImpactRange1Landscape-3.png')
    })

    it('moves to level 2 (more seedlings) just above the level-1 ceiling', () => {
      const { getImage } = useStatsShareImage()
      expect(getImage(210, 'Facebook')).toBe('ImpactRange2Landscape-4.png')
      expect(getImage(3629.99, 'Facebook')).toBe('ImpactRange2Landscape-60.png')
    })

    it('moves to level 3 (square of seedlings) just above the level-2 ceiling - starts at increment 2, not 1', () => {
      const { getImage } = useStatsShareImage()
      expect(getImage(3630, 'Facebook')).toBe('ImpactRange3Landscape-2.png')
    })

    it('moves to level 5 (hectares) once the square-of-seedlings visualisation tops out', () => {
      const { getImage } = useStatsShareImage()
      expect(getImage(14250, 'Facebook')).toBe('ImpactRange5Landscape-1.png')
    })

    it('reaches the top of the table at the highest CO2e boundary', () => {
      const { getImage } = useStatsShareImage()
      expect(getImage(774000, 'Facebook')).toBe('ImpactRange6Landscape-64.png')
    })

    it('clamps rather than overflowing the table for CO2e above the top boundary (develop crashes here)', () => {
      const { getImage, rangeIndex } = useStatsShareImage()
      expect(() => getImage(10000000, 'Facebook')).not.toThrow()
      expect(getImage(10000000, 'Facebook')).toBe('ImpactRange6Landscape-64.png')
      expect(rangeIndex(10000000)).toBe(rangeIndex(774000))
    })

    it('uses the Square filename variant for the portrait (Instagram) target', () => {
      const { getImage } = useStatsShareImage()
      expect(getImage(600, 'Instagram')).toBe('ImpactRange2Square-10.png')
    })

    it('every filename getImage() can produce actually exists in the Laravel-side public/images/stats', () => {
      const { getImage } = useStatsShareImage()
      const seen = new Set()
      // Sample every whole-kg value across the full table range plus a
      // margin past the top boundary, both orientations.
      for (let co2 = 0; co2 <= 780000; co2 += 30) {
        for (const target of ['Facebook', 'Instagram']) {
          seen.add(getImage(co2, target))
        }
      }
      expect(seen.size).toBeGreaterThan(100)
      for (const filename of seen) {
        expect(existsSync(statsImagesDir + filename), `missing asset: ${filename}`).toBe(true)
      }
    })
  })

  describe('getCount - the number shown in "growing about X seedlings" / "planting around X hectares"', () => {
    it('returns the increment number directly for the Seedling visualisation', () => {
      const { getCount } = useStatsShareImage()
      expect(getCount(600)).toBe(10) // level 2, increment 10 (lowerBoundary 570)
    })

    it('returns the seedling count (co2/60), not the square number, for Square of seedlings', () => {
      const { getCount } = useStatsShareImage()
      // 3630kg is the first "square of seedlings" row (level 3, increment 2)
      expect(getCount(3630)).toBe(Math.round(3630 / 60))
    })

    it('returns the increment number directly for the Hectare visualisation', () => {
      const { getCount } = useStatsShareImage()
      expect(getCount(14250)).toBe(1) // level 5, increment 1
      expect(getCount(774000)).toBe(64) // level 6, increment 64
    })
  })

  describe('dimensions/initialX/initialY - per-platform canvas geometry', () => {
    it('has fixed pixel dimensions for every supported platform', () => {
      const { dimensions } = useStatsShareImage()
      expect(dimensions('Instagram')).toEqual({ width: 1080, height: 1080 })
      expect(dimensions('Facebook')).toEqual({ width: 1200, height: 630 })
      expect(dimensions('Twitter')).toEqual({ width: 1600, height: 900 })
      expect(dimensions('LinkedIn')).toEqual({ width: 1200, height: 627 })
    })

    it('centres text horizontally only for the portrait (Instagram) target', () => {
      const { initialX } = useStatsShareImage()
      expect(initialX('Instagram')).toBe(0)
      expect(initialX('Facebook')).toBe(1200 / 20)
      expect(initialX('Twitter')).toBe(1600 / 20)
      expect(initialX('LinkedIn')).toBe(1200 / 20)
    })

    it('starts text lower on Instagram (fixed 100px) than the fifth-of-height used elsewhere', () => {
      const { initialY } = useStatsShareImage()
      expect(initialY('Instagram')).toBe(100)
      expect(initialY('Facebook')).toBe(630 / 5)
      expect(initialY('Twitter')).toBe(900 / 5)
      expect(initialY('LinkedIn')).toBe(627 / 5)
    })
  })

  // develop's fontSize computed() is a switch with no `break`, so it falls
  // through to the LinkedIn case regardless of the selected target - see the
  // composable's own comment. These values are what actually renders in
  // production; they are NOT what a reader would predict from the
  // per-platform numbers written in the switch cases.
  describe('fontSize/smallerFontSize - reproduces develop\'s switch-fallthrough bug for visual parity', () => {
    it('renders every English/French locale at the same size on Instagram (the only portrait target)', () => {
      const { fontSize } = useStatsShareImage()
      expect(fontSize('Instagram', 'en')).toBe(47)
      expect(fontSize('Instagram', 'fr')).toBe(47)
      expect(fontSize('Instagram', 'fr-BE')).toBe(47)
    })

    it('renders Facebook and LinkedIn identically in English (both fall through to the LinkedIn case)', () => {
      const { fontSize } = useStatsShareImage()
      expect(fontSize('Facebook', 'en')).toBe(45)
      expect(fontSize('LinkedIn', 'en')).toBe(45)
    })

    it('shrinks Facebook/LinkedIn in French but grows Twitter in French', () => {
      const { fontSize } = useStatsShareImage()
      expect(fontSize('Facebook', 'fr')).toBe(39)
      expect(fontSize('LinkedIn', 'fr-BE')).toBe(39)
      expect(fontSize('Twitter', 'fr')).toBe(53)
    })

    it('grows Twitter the most in English', () => {
      const { fontSize } = useStatsShareImage()
      expect(fontSize('Twitter', 'en')).toBe(60)
    })

    it('derives the smaller (secondary line) font size as 4/5 of the main size', () => {
      const { fontSize, smallerFontSize } = useStatsShareImage()
      expect(smallerFontSize('Facebook', 'en')).toBe(Math.round((fontSize('Facebook', 'en') * 4) / 5))
      expect(smallerFontSize('Instagram', 'fr')).toBe(Math.round((fontSize('Instagram', 'fr') * 4) / 5))
    })
  })

  it('STATS_SHARE_PLATFORMS lists the four toggle buttons in the order the modal renders them', () => {
    expect(STATS_SHARE_PLATFORMS).toEqual(['Instagram', 'Facebook', 'Twitter', 'LinkedIn'])
  })

  it('sanity check: the images directory this test reads exists and is non-empty', () => {
    expect(existsSync(statsImagesDir)).toBe(true)
    expect(readdirSync(statsImagesDir).length).toBeGreaterThan(0)
  })
})
