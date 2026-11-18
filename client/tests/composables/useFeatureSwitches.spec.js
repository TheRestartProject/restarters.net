import { describe, expect, it } from 'vitest'
import { fieldLabel } from '../../app/composables/useFeatureSwitches.js'

describe('composables/useFeatureSwitches fieldLabel', () => {
  it('adds a colon to a plain label', () => {
    expect(fieldLabel('Town')).toBe('Town:')
    expect(fieldLabel('Date (approx)')).toBe('Date (approx):')
  })

  it('adds no colon after a label that ends in punctuation', () => {
    expect(fieldLabel('First visit?')).toBe('First visit?')
    expect(fieldLabel('Pourquoi ?')).toBe('Pourquoi ?')
    expect(fieldLabel('Notes…')).toBe('Notes…')
    expect(fieldLabel('来店？')).toBe('来店？')
    expect(fieldLabel('Ville：')).toBe('Ville：')
  })

  it('trims, and copes with no label', () => {
    expect(fieldLabel('  Town  ')).toBe('Town:')
    expect(fieldLabel('Pourquoi ? ')).toBe('Pourquoi ?')
    expect(fieldLabel(undefined)).toBe('')
  })
})
