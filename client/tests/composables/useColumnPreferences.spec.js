import { beforeEach, describe, expect, it } from 'vitest'
import { useColumnPreferences } from '../../app/composables/useColumnPreferences.js'

describe('composables/useColumnPreferences', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('starts with the given defaults when nothing is stored', () => {
    const { columns } = useColumnPreferences('test-key', { a: true, b: false })

    expect(columns.value).toEqual({ a: true, b: false })
  })

  it('toggle() flips a column and persists it', () => {
    const { columns, toggle } = useColumnPreferences('test-key', { a: true, b: false })

    toggle('a')

    expect(columns.value).toEqual({ a: false, b: false })
    expect(JSON.parse(localStorage.getItem('restarters.columnPreferences.test-key'))).toEqual({
      a: false,
      b: false,
    })
  })

  it('loads a previously persisted preference on next use', () => {
    localStorage.setItem('restarters.columnPreferences.test-key', JSON.stringify({ a: false, b: true }))

    const { columns } = useColumnPreferences('test-key', { a: true, b: false })

    expect(columns.value).toEqual({ a: false, b: true })
  })

  it('merges stored values over defaults so new columns still appear', () => {
    localStorage.setItem('restarters.columnPreferences.test-key', JSON.stringify({ a: false }))

    const { columns } = useColumnPreferences('test-key', { a: true, b: true })

    expect(columns.value).toEqual({ a: false, b: true })
  })

  it('falls back to defaults when stored JSON is corrupt', () => {
    localStorage.setItem('restarters.columnPreferences.test-key', '{not json')

    const { columns } = useColumnPreferences('test-key', { a: true })

    expect(columns.value).toEqual({ a: true })
  })

  it('keys preferences independently per storage key', () => {
    const { toggle } = useColumnPreferences('key-one', { a: true })
    toggle('a')

    const { columns } = useColumnPreferences('key-two', { a: true })

    expect(columns.value).toEqual({ a: true })
  })
})
