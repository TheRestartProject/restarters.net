import { describe, expect, it } from 'vitest'
import { findDuplicateEvents } from '../../app/utils/duplicateEvents.js'

// Groups repeatedly end up with the same repair event posted twice - a second
// host adds it not knowing the first did, or someone re-submits after fixing a
// typo instead of editing. The checks below run against the group's existing
// events before a create is sent, so the host can be shown what already exists
// and choose to edit it instead.
describe('utils/duplicateEvents', () => {
  const LONDON = 'Brixton Library, London'

  const existing = (over = {}) => ({
    id: 10,
    title: 'Repair Cafe',
    location: LONDON,
    online: false,
    start: '2026-10-10T13:00:00+00:00',
    end: '2026-10-10T16:00:00+00:00',
    timezone: 'Europe/London',
    updated_at: '2026-09-01T09:00:00+00:00',
    ...over,
  })

  const candidate = (over = {}) => ({
    title: 'Repair Cafe',
    location: LONDON,
    online: false,
    start: '2026-10-10T13:00:00+00:00',
    end: '2026-10-10T16:00:00+00:00',
    timezone: 'Europe/London',
    ...over,
  })

  const NOW = new Date('2026-09-01T09:05:00+00:00')
  const find = (c, list, opts = {}) => findDuplicateEvents(c, list, { now: NOW, ...opts })

  describe('certain', () => {
    it('flags the same place at the same instant', () => {
      const [match] = find(candidate(), [existing()])

      expect(match.confidence).toBe('certain')
      expect(match.event.id).toBe(10)
    })

    it('ignores case and surrounding whitespace in the location', () => {
      const [match] = find(candidate({ location: '  brixton   library, LONDON ' }), [existing()])

      expect(match.confidence).toBe('certain')
    })

    it('treats two online events at the same instant as the same place', () => {
      const [match] = find(
        candidate({ online: true, location: '' }),
        [existing({ online: true, location: '' })],
      )

      expect(match.confidence).toBe('certain')
    })
  })

  describe('likely', () => {
    it('flags the same place on the same day within an hour', () => {
      const [match] = find(candidate({ start: '2026-10-10T13:45:00+00:00' }), [existing()])

      expect(match.confidence).toBe('likely')
    })

    it('flags the same title on the same day even when the time moved a lot', () => {
      const [match] = find(
        candidate({ start: '2026-10-10T19:00:00+00:00', location: 'Somewhere else entirely' }),
        [existing()],
      )

      expect(match.confidence).toBe('likely')
    })

    it('flags a same-titled event this group posted moments ago', () => {
      // The classic double submit: the first create succeeded but the host did
      // not see it, so `updated_at` is seconds old.
      const [match] = find(
        candidate({ start: '2026-11-20T13:00:00+00:00' }),
        [existing({ updated_at: '2026-09-01T09:04:30+00:00' })],
      )

      expect(match.confidence).toBe('likely')
      expect(match.reasons).toContain('just-posted')
    })
  })

  describe('possible', () => {
    it('flags the same place later the same day', () => {
      const [match] = find(
        candidate({ start: '2026-10-10T19:00:00+00:00', title: 'Evening session' }),
        [existing()],
      )

      expect(match.confidence).toBe('possible')
    })

    it('flags the same instant at a different place', () => {
      const [match] = find(
        candidate({ location: 'Peckham Library', title: 'Another thing' }),
        [existing()],
      )

      expect(match.confidence).toBe('possible')
    })
  })

  describe('no match', () => {
    it('says nothing for a different day at a different place', () => {
      expect(find(candidate({ start: '2026-12-01T13:00:00+00:00', title: 'Winter fix' , location: 'Elsewhere' }), [existing()])).toEqual([])
    })

    it('does not compare an event being edited against itself', () => {
      expect(find({ ...candidate(), id: 10 }, [existing()])).toEqual([])
    })

    it('ignores an online candidate against an in-person event at the same time', () => {
      const matches = find(candidate({ online: true, location: '', title: 'Online clinic' }), [existing()])

      expect(matches).toEqual([])
    })

    it('copes with events that have no location or times', () => {
      expect(() => find(candidate(), [existing({ location: null, start: null, end: null })])).not.toThrow()
    })

    it('returns nothing when the group has no events yet', () => {
      expect(find(candidate(), [])).toEqual([])
    })
  })

  describe('ordering', () => {
    it('puts the strongest match first', () => {
      const matches = find(candidate(), [
        existing({ id: 1, start: '2026-10-10T19:00:00+00:00', title: 'Evening session' }),
        existing({ id: 2 }),
      ])

      expect(matches.map((m) => m.event.id)).toEqual([2, 1])
      expect(matches[0].confidence).toBe('certain')
    })
  })

  describe('day boundaries', () => {
    it('uses the event timezone, not UTC, to decide "same day"', () => {
      // 23:30 in Auckland on the 10th is 10:30 UTC on the 10th; an event at
      // 00:30 Auckland on the 11th is still 11:30 UTC on the 10th, and must
      // not count as the same day.
      const nz = { timezone: 'Pacific/Auckland', location: LONDON, title: 'Fix it', online: false }
      const matches = find(
        { ...nz, start: '2026-10-10T10:30:00+00:00' },
        [{ ...existing(), ...nz, id: 5, start: '2026-10-10T11:30:00+00:00', title: 'Different' }],
      )

      expect(matches).toEqual([])
    })
  })
})
