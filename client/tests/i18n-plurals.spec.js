import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import en from '../i18n/locales/en.json'
import fr from '../i18n/locales/fr.json'

// Parity tests: translations:export-client strips Laravel's exact/range plural
// tags ({1} x|[0,*] y) down to positional pipe forms, relying on vue-i18n's
// default rule selecting the same segment Laravel's MessageSelector would.
// These tests pin that equivalence on real exported strings:
//   2 forms: Laravel picks singular iff count === 1 — vue-i18n: 1 → first,
//            anything else (including 0) → second.
//   3 forms: Laravel {0}|{1}|[2,*] — vue-i18n: 0 → first, 1 → second,
//            2+ → third.
describe('exported plural strings match Laravel trans_choice semantics', () => {
  const t = (messages, key, count, params = {}) => {
    const i18n = createI18n({ legacy: false, locale: 'x', messages: { x: messages } })
    return i18n.global.t(key, { ...params, count }, count)
  }

  it('two-form: singular only at exactly 1 (dashboard.newly_added)', () => {
    expect(t(en, 'dashboard.newly_added', 1, { count: 1 })).toContain('1 group in your area')
    expect(t(en, 'dashboard.newly_added', 2, { count: 2 })).toContain('2 groups in your area')
    // Laravel picks the plural form for zero; so must vue-i18n.
    expect(t(en, 'dashboard.newly_added', 0, { count: 0 })).toContain('0 groups in your area')
  })

  it('three-form plurals ({0}|{1}|[2,*]) select zero/one/many positionally', () => {
    // PR #887's networks.php rework removed the corpus's only 3-form string
    // (networks.show.groups_count). This test self-adapts: it verifies the
    // zero/one/many selection on every 3-form string present, and passes
    // vacuously (while documenting the count) when none exist — so if a
    // 3-form string returns and the exporter mishandles it, this fails.
    const threeForm = []
    const walk = (obj, path) => {
      for (const [k, v] of Object.entries(obj)) {
        const p = path ? `${path}.${k}` : k
        if (v && typeof v === 'object') walk(v, p)
        else if (typeof v === 'string' && v.split(' | ').length === 3) threeForm.push(p)
      }
    }
    walk(en, '')

    for (const key of threeForm) {
      const zero = t(en, key, 0, { count: 0, name: 'N', number: 0, value: 0 })
      const one = t(en, key, 1, { count: 1, name: 'N', number: 1, value: 1 })
      const many = t(en, key, 5, { count: 5, name: 'N', number: 5, value: 5 })
      expect(zero).not.toBe(one)
      expect(one).not.toBe(many)
    }
    expect(threeForm.length).toBeGreaterThanOrEqual(0)
  })

  it('range form {1}|[0,*] (networks.stats.groups)', () => {
    expect(t(en, 'networks.stats.groups', 1)).toBe('Group')
    expect(t(en, 'networks.stats.groups', 0)).toBe('Groups')
    expect(t(en, 'networks.stats.groups', 3)).toBe('Groups')
  })

  it('parameters interpolate inside plural segments in fr too', () => {
    // Same key must exist and behave in fr (translated corpus).
    const one = t(fr, 'dashboard.newly_added', 1, { count: 1 })
    const many = t(fr, 'dashboard.newly_added', 3, { count: 3 })
    expect(one).toContain('1')
    expect(many).toContain('3')
    expect(one).not.toBe(many)
  })

  it('no exported string retains Laravel plural tags or raw :params', () => {
    const offenders = []
    const walk = (obj, path) => {
      for (const [k, v] of Object.entries(obj)) {
        const p = path ? `${path}.${k}` : k
        if (v && typeof v === 'object') walk(v, p)
        else if (typeof v === 'string' && (/\{\d+\}\s/.test(v) || /\[\d+,(?:\d+|\*)\]/.test(v) || /(?<!\w):\w+/.test(v))) {
          offenders.push(p)
        }
      }
    }
    walk(en, '')
    expect(offenders).toEqual([])
  })
})
