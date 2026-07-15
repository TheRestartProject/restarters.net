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

  it('three-form from {0}|{1}|[2,*] (networks.show.groups_count)', () => {
    const zero = t(en, 'networks.show.groups_count', 0, { name: 'N', count: 0 })
    const one = t(en, 'networks.show.groups_count', 1, { name: 'N', count: 1 })
    const many = t(en, 'networks.show.groups_count', 5, { name: 'N', count: 5 })

    expect(zero).toContain('currently no groups')
    expect(one).toContain('1 group in')
    expect(many).toContain('5 groups in')
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
