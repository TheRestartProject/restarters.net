import { describe, expect, it } from 'vitest'
import { sortAriaValue, sortHintKey } from '../../app/composables/useSortAria.js'

describe('composables/useSortAria', () => {
  it('reports the current direction only for the active column', () => {
    expect(sortAriaValue('name', 'name', false)).toBe('ascending')
    expect(sortAriaValue('name', 'name', true)).toBe('descending')
    expect(sortAriaValue('name', 'other', false)).toBe('none')
    expect(sortAriaValue(null, null, false)).toBe('none')
  })

  // The hint says what a click DOES, which is the opposite of the current
  // direction once a column is active - not the state aria-sort already
  // carries. Getting these the same way round would announce the column
  // twice and offer nothing.
  it('offers the direction a click would switch to', () => {
    expect(sortHintKey('name', 'name', false)).toBe('client.common.sort_descending')
    expect(sortHintKey('name', 'name', true)).toBe('client.common.sort_ascending')
    expect(sortHintKey('name', 'other', false)).toBe('client.common.sort_ascending')
  })
})
