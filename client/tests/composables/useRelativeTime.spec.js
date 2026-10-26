import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import { useRelativeTime } from '../../app/composables/useRelativeTime.js'

// useRelativeTime() calls useI18n(), which needs an active component
// instance - mount a throwaway host component to get one, same technique as
// tests/composables/useCo2Equivalent.spec.js.
function withI18n(locale, callback) {
  let result
  const i18n = createI18n({ legacy: false, locale, messages: { [locale]: {} } })
  const app = createApp({
    setup() {
      result = callback()
      return () => null
    },
  })
  app.use(i18n)
  const el = document.createElement('div')
  app.mount(el)
  app.unmount()
  return result
}

const NOW = new Date('2026-07-17T12:00:00Z')

describe('composables/useRelativeTime', () => {
  it('formats a few seconds ago', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2026-07-17T11:59:30Z'), NOW)).toBe('30 seconds ago')
  })

  it('formats minutes ago', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2026-07-17T11:55:00Z'), NOW)).toBe('5 minutes ago')
  })

  it('formats hours ago', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2026-07-17T09:00:00Z'), NOW)).toBe('3 hours ago')
  })

  it('formats days ago', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2026-07-14T12:00:00Z'), NOW)).toBe('3 days ago')
  })

  it('formats weeks ago', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2026-06-26T12:00:00Z'), NOW)).toBe('3 weeks ago')
  })

  it('formats months ago', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2026-03-17T12:00:00Z'), NOW)).toBe('4 months ago')
  })

  it('formats years ago', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2024-07-17T12:00:00Z'), NOW)).toBe('2 years ago')
  })

  it('formats a future date as "in X days"', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    expect(relativeTime(new Date('2026-07-20T12:00:00Z'), NOW)).toBe('in 3 days')
  })

  it('defaults "now" to the current time when not given', () => {
    const { relativeTime } = withI18n('en', () => useRelativeTime())
    // `now` ticks on between the two `new Date()` calls, so the sign isn't
    // pinned - just confirm it lands in the seconds bucket either way.
    expect(relativeTime(new Date())).toMatch(/^(in )?0 seconds( ago)?$/)
  })

  it('uses the active i18n locale', () => {
    const { relativeTime } = withI18n('fr', () => useRelativeTime())
    expect(relativeTime(new Date('2026-07-14T12:00:00Z'), NOW)).toBe('il y a 3 jours')
  })

  it('formats the absolute date/time for a title tooltip', () => {
    const { absoluteDateTime } = withI18n('en', () => useRelativeTime())
    expect(absoluteDateTime(new Date('2026-07-17T10:30:00Z'))).toBe(
      new Intl.DateTimeFormat('en', { dateStyle: 'full', timeStyle: 'short' }).format(new Date('2026-07-17T10:30:00Z'))
    )
  })

  it('absoluteDateTime follows the active locale too', () => {
    const { absoluteDateTime } = withI18n('fr', () => useRelativeTime())
    expect(absoluteDateTime(new Date('2026-07-17T10:30:00Z'))).toBe(
      new Intl.DateTimeFormat('fr', { dateStyle: 'full', timeStyle: 'short' }).format(new Date('2026-07-17T10:30:00Z'))
    )
  })
})
