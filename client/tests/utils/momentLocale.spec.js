import { describe, expect, it, afterEach } from 'vitest'
import moment from 'moment'
import momentTz from 'moment-timezone'
import { applyMomentLocale } from '../../app/utils/momentLocale.js'

describe('applyMomentLocale', () => {
  afterEach(() => applyMomentLocale('en'))

  it('formats in English by default', () => {
    expect(moment('2026-01-05T10:00:00Z').format('MMMM')).toBe('January')
  })

  it('formats in French once fr is applied, for both moment and moment-timezone', () => {
    applyMomentLocale('fr')
    expect(moment('2026-01-05T10:00:00Z').format('MMMM')).toBe('janvier')
    expect(momentTz.tz('2026-01-05T10:00:00Z', 'Europe/Paris').format('dddd')).toBe('lundi')
  })

  it('treats fr-BE as French', () => {
    expect(applyMomentLocale('fr-BE')).toBe('fr')
    expect(moment('2026-01-05T10:00:00Z').format('MMMM')).toBe('janvier')
  })

  it('switches back to English', () => {
    applyMomentLocale('fr')
    applyMomentLocale('en')
    expect(moment('2026-01-05T10:00:00Z').format('MMMM')).toBe('January')
  })
})
