import moment from 'moment'
import { describe, expect, it } from 'vitest'
import { applyMomentLocale } from '../app/utils/momentLocale.js'

describe('applyMomentLocale', () => {
  it.each([
    ['en', 'en', 'Monday'],
    ['de', 'de', 'Montag'],
    ['es', 'es', 'lunes'],
    ['fr', 'fr', 'lundi'],
    ['fr-BE', 'fr', 'lundi'],
    ['it', 'it', 'lunedì'],
    ['nl', 'nl', 'maandag'],
    ['nl-BE', 'nl-be', 'maandag'],
  ])('%s -> moment %s', (code, expected, monday) => {
    expect(applyMomentLocale(code)).toBe(expected)
    expect(moment('2026-11-02').format('dddd')).toBe(monday)
  })
})
