import { hasBakedFootnote, footnoteLayout, footnoteKey } from './statsFootnote'
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
