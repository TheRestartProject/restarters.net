import { computed, nextTick, ref } from 'vue'
import { describe, expect, it } from 'vitest'
import {
  eventDayOfMonth,
  eventDateLabel,
  eventEndLocal,
  eventIsApproved,
  eventIsAttending,
  eventIsFinished,
  eventIsInProgress,
  eventIsStartingSoon,
  eventIsUpcoming,
  eventMonth,
  eventStartLocal,
  useEventComputed,
} from '../../app/composables/useEventComputed.js'

// Fixed reference instant used across the table-driven cases below, so
// upcoming/finished/inprogress/startingSoon are deterministic regardless of
// when the suite runs.
const NOW = '2026-06-15T12:00:00Z'

function event(overrides = {}) {
  return {
    start: '2026-06-15T10:00:00Z',
    end: '2026-06-15T14:00:00Z',
    timezone: 'Europe/London',
    attending: false,
    approved: true,
    ...overrides,
  }
}

describe('composables/useEventComputed', () => {
  describe('eventIsAttending', () => {
    it.each([
      ['null event', null, false],
      ['attending true', event({ attending: true }), true],
      ['attending false', event({ attending: false }), false],
      ['attending omitted (logged out)', { start: NOW, end: NOW }, false],
    ])('%s', (_label, input, expected) => {
      expect(eventIsAttending(input)).toBe(expected)
    })
  })

  describe('eventIsApproved', () => {
    it.each([
      ['null event', null, false],
      ['approved true', event({ approved: true }), true],
      ['approved false', event({ approved: false }), false],
    ])('%s', (_label, input, expected) => {
      expect(eventIsApproved(input)).toBe(expected)
    })
  })

  // Mirrors Party::isUpcoming()/hasFinished()/isInProgress() - plain
  // instant comparisons against `now`, timezone-irrelevant.
  describe('eventIsUpcoming / eventIsFinished / eventIsInProgress', () => {
    it.each([
      ['future event', event({ start: '2026-06-16T10:00:00Z', end: '2026-06-16T14:00:00Z' }), true, false, false],
      ['past event', event({ start: '2026-06-14T10:00:00Z', end: '2026-06-14T14:00:00Z' }), false, true, false],
      ['event in progress', event({ start: '2026-06-15T10:00:00Z', end: '2026-06-15T14:00:00Z' }), false, false, true],
      ['event starting exactly now', event({ start: NOW, end: '2026-06-15T14:00:00Z' }), false, false, true],
      ['event ending exactly now', event({ start: '2026-06-15T08:00:00Z', end: NOW }), false, false, true],
      ['null event', null, false, false, false],
    ])('%s', (_label, input, upcoming, finished, inProgress) => {
      expect(eventIsUpcoming(input, NOW)).toBe(upcoming)
      expect(eventIsFinished(input, NOW)).toBe(finished)
      expect(eventIsInProgress(input, NOW)).toBe(inProgress)
    })
  })

  describe('eventIsStartingSoon', () => {
    it.each([
      ['in progress - not starting soon', event({ start: '2026-06-15T10:00:00Z', end: '2026-06-15T14:00:00Z' }), false],
      ['finished - not starting soon', event({ start: '2026-06-14T10:00:00Z', end: '2026-06-14T14:00:00Z' }), false],
      [
        'later today, same local day as now - starting soon',
        event({ start: '2026-06-15T20:00:00Z', end: '2026-06-15T22:00:00Z', timezone: 'UTC' }),
        true,
      ],
      [
        'tomorrow - not starting soon',
        event({ start: '2026-06-16T08:00:00Z', end: '2026-06-16T10:00:00Z', timezone: 'UTC' }),
        false,
      ],
      ['null event', null, false],
    ])('%s', (_label, input, expected) => {
      expect(eventIsStartingSoon(input, NOW)).toBe(expected)
    })
  })

  // dayofmonth/month port resources/js/mixins/event.js's computeds of the
  // same name - formatted in the event's own timezone, not the browser's.
  describe('eventDayOfMonth / eventMonth / eventDateLabel', () => {
    it.each([
      ['London event', event({ start: '2026-06-15T10:00:00Z', timezone: 'Europe/London' }), '15', 'JUN'],
      // 23:30 UTC on the 15th is already the 16th in Auckland.
      ['Auckland event past midnight locally', event({ start: '2026-06-15T23:30:00Z', timezone: 'Pacific/Auckland' }), '16', 'JUN'],
      ['no timezone falls back to guessed zone without throwing', event({ start: '2026-06-15T10:00:00Z', timezone: undefined }), null, null],
    ])('%s', (label, input, day, month) => {
      if (label.startsWith('no timezone')) {
        // Falls back to moment.tz.guess() - just assert it doesn't throw and
        // returns *some* two-digit day/three-letter month.
        expect(eventDayOfMonth(input)).toMatch(/^\d{2}$/)
        expect(eventMonth(input)).toMatch(/^[A-Z]{3}$/)
        return
      }
      expect(eventDayOfMonth(input)).toBe(day)
      expect(eventMonth(input)).toBe(month)
    })

    it('returns null for an event with no start', () => {
      expect(eventDayOfMonth(null)).toBeNull()
      expect(eventMonth(null)).toBeNull()
      expect(eventDateLabel(null)).toBeNull()
    })

    it('formats the full date label using the legacy DATE_FORMAT shape', () => {
      expect(eventDateLabel(event({ start: '2026-06-15T10:00:00Z', timezone: 'Europe/London' }))).toBe(
        'Mon 15th Jun 2026'
      )
    })
  })

  describe('eventStartLocal / eventEndLocal', () => {
    it('formats start/end as HH:mm in the event timezone', () => {
      const e = event({ start: '2026-06-15T10:00:00Z', end: '2026-06-15T14:00:00Z', timezone: 'Europe/London' })
      // BST (UTC+1) in June.
      expect(eventStartLocal(e)).toBe('11:00')
      expect(eventEndLocal(e)).toBe('15:00')
    })

    it('returns null when start/end is missing', () => {
      expect(eventStartLocal({})).toBeNull()
      expect(eventEndLocal({})).toBeNull()
    })
  })

  describe('useEventComputed', () => {
    it('accepts a getter and reacts to the underlying value changing', async () => {
      const current = ref(event({ attending: false, start: '2026-06-16T10:00:00Z', end: '2026-06-16T14:00:00Z' }))
      const { attending, dayOfMonth, month, timezone } = useEventComputed(() => current.value)

      expect(attending.value).toBe(false)
      expect(dayOfMonth.value).toBe('16')
      expect(month.value).toBe('JUN')
      expect(timezone.value).toBe('Europe/London')

      current.value = event({ attending: true, start: '2026-06-16T10:00:00Z', end: '2026-06-16T14:00:00Z' })
      await nextTick()

      expect(attending.value).toBe(true)
    })

    it('accepts a plain computed ref', () => {
      const source = computed(() => event({ attending: true }))
      const { attending, approved } = useEventComputed(source)

      expect(attending.value).toBe(true)
      expect(approved.value).toBe(true)
    })

    it('handles a null event without throwing', () => {
      const { attending, upcoming, dayOfMonth, timezone } = useEventComputed(() => null)

      expect(attending.value).toBe(false)
      expect(upcoming.value).toBe(false)
      expect(dayOfMonth.value).toBeNull()
      expect(timezone.value).toBeNull()
    })
  })
})
