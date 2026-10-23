import { hasBakedFootnote, footnoteLayout, footnoteKey, hasBakedBranding, brandingLayout } from './statsFootnote'
import fs from 'fs'
import path from 'path'

function value(loc, key) {
  const f = fs.readFileSync(path.join(__dirname, '../../../lang', loc, 'partials.php'), 'utf8')
  const m = new RegExp("'" + key + "' => \"([^\"]*)\"").exec(f)
  return m ? m[1] : null
}

describe('stats share footnote', () => {
  test('only ranges 1-5 have a baked footnote', () => {
    expect(hasBakedFootnote('ImpactRange1Landscape-1.png')).toBe(true)
    expect(hasBakedFootnote('ImpactRange5Square-3.png')).toBe(true)
    expect(hasBakedFootnote('ImpactRange6Landscape-40.png')).toBe(false)
    expect(hasBakedFootnote(null)).toBe(false)
  })

  test('range 5 uses the hectares footnote, others the urban trees one', () => {
    expect(footnoteKey('ImpactRange5Landscape-3.png')).toBe('partials.share_modal_footnote_hectares')
    expect(footnoteKey('ImpactRange5Square-3.png')).toBe('partials.share_modal_footnote_hectares')
    for (const r of [1, 2, 3, 4]) {
      expect(footnoteKey('ImpactRange' + r + 'Square-1.png')).toBe('partials.share_modal_footnote')
    }
  })

  test('landscape cover leaves the right-hand branding alone (range 5 branding starts at 0.717)', () => {
    const l = footnoteLayout(false)
    expect(l.x + l.w).toBeLessThan(0.717)
    expect(l.textX + l.maxW).toBeLessThan(l.x + l.w)
  })

  test('landscape cover starts above the baked footnote text (0.909)', () => {
    expect(footnoteLayout(false).y).toBeLessThan(0.909)
  })

  test('every range has the baked branding, range 6 included', () => {
    for (const r of [1, 2, 3, 4, 5, 6]) {
      expect(hasBakedBranding('ImpactRange' + r + 'Landscape-1.png')).toBe(true)
      expect(hasBakedBranding('ImpactRange' + r + 'Square-1.png')).toBe(true)
    }
    expect(hasBakedBranding(null)).toBe(false)
  })

  // Measured from the PNGs: square branding spans x 0.333-0.667, y 0.948-0.980; landscape spans x 0.717-0.985,
  // y 0.909-0.953, with the dashed line ending at ~0.89.
  test('square branding cover sits below the footnote cover and covers the baked text', () => {
    const b = brandingLayout(true)
    const f = footnoteLayout(true)
    expect(b.y).toBeGreaterThanOrEqual(f.y + f.h - 1e-9)
    expect(b.y).toBeLessThan(0.948)
    expect(b.y + b.h).toBeGreaterThan(0.980)
    expect(b.centre).toBe(true)
  })

  test('landscape branding cover is right of the footnote cover and below the dashed line', () => {
    const b = brandingLayout(false)
    const f = footnoteLayout(false)
    expect(b.x).toBeGreaterThanOrEqual(f.x + f.w - 1e-9)
    expect(b.x).toBeLessThan(0.717)
    expect(b.x + b.w).toBeGreaterThan(0.985)
    expect(b.y).toBeGreaterThan(0.89)
    expect(b.y).toBeLessThan(0.909)
    expect(b.y + b.h).toBeGreaterThan(0.953)
  })

  test('the branding is translated but restarters.net is not', () => {
    for (const loc of ['en', 'fr', 'fr-BE']) {
      const v = value(loc, 'share_modal_generated_by')
      expect(v).toContain(':site')
      expect(v).not.toMatch(/restarters/i)
    }
    expect(value('fr', 'share_modal_generated_by')).not.toBe(value('en', 'share_modal_generated_by'))
  })

  test('footnotes are translated in en, fr and fr-BE', () => {
    for (const key of ['share_modal_footnote', 'share_modal_footnote_hectares']) {
      const en = value('en', key)
      expect(en).toBeTruthy()
      for (const loc of ['fr', 'fr-BE']) {
        const v = value(loc, key)
        expect(v).toBeTruthy()
        expect(v).not.toBe(en)
      }
    }
  })
})
