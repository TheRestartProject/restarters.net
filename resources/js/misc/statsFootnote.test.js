import { hasBakedFootnote, footnoteLayout } from './statsFootnote'
import fs from 'fs'
import path from 'path'

describe('stats share footnote', () => {
  test('only ranges 1-5 have a baked footnote', () => {
    expect(hasBakedFootnote('ImpactRange1Landscape-1.png')).toBe(true)
    expect(hasBakedFootnote('ImpactRange5Square-3.png')).toBe(true)
    expect(hasBakedFootnote('ImpactRange6Landscape-40.png')).toBe(false)
    expect(hasBakedFootnote(null)).toBe(false)
  })

  test('landscape cover leaves the right-hand branding alone', () => {
    const l = footnoteLayout(false)
    expect(l.x + l.w).toBeLessThan(0.73)
  })

  test('footnote translated in en, fr and fr-BE', () => {
    for (const loc of ['en', 'fr', 'fr-BE']) {
      const f = fs.readFileSync(path.join(__dirname, '../../../lang', loc, 'partials.php'), 'utf8')
      expect(f).toContain("'share_modal_footnote'")
    }
  })
})
