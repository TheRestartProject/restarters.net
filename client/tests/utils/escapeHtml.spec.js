import { describe, expect, it } from 'vitest'
import { escapeHtml } from '../../app/utils/escapeHtml.js'

describe('escapeHtml', () => {
  it('escapes markup characters', () => {
    expect(escapeHtml('<a href="x">&\'</a>')).toBe('&lt;a href=&quot;x&quot;&gt;&amp;&#39;&lt;/a&gt;')
  })
  it('turns null and undefined into an empty string and stringifies numbers', () => {
    expect(escapeHtml(null)).toBe('')
    expect(escapeHtml(undefined)).toBe('')
    expect(escapeHtml(5)).toBe('5')
  })
})
