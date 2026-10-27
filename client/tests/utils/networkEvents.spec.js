import { describe, expect, it } from 'vitest'
import { GROUP_COLOURS, bucketByDay, groupColour, localDay, monthGrid, shiftMonth } from '../../app/utils/networkEvents.js'

describe('utils/networkEvents', () => {
  it('puts an event on its own timezone day, not the UTC one', () => {
    // 23:00 UTC on 31 Jan is already 12:00 on 1 Feb in Auckland (UTC+13).
    expect(localDay({ start: '2038-01-31T23:00:00Z', timezone: 'Pacific/Auckland' })).toBe('2038-02-01')
    expect(localDay({ start: '2038-01-31T23:00:00Z', timezone: 'Europe/London' })).toBe('2038-01-31')
    expect(localDay({ start: '2038-01-31T23:00:00Z' })).toBe('2038-01-31')
  })

  it('buckets events by day, earliest first', () => {
    const buckets = bucketByDay([
      { id: 2, start: '2038-01-05T15:00:00Z' },
      { id: 1, start: '2038-01-05T09:00:00Z' },
      { id: 3, start: '2038-01-06T09:00:00Z' },
    ])

    expect(buckets['2038-01-05'].map((e) => e.id)).toEqual([1, 2])
    expect(buckets['2038-01-06'].map((e) => e.id)).toEqual([3])
    expect(bucketByDay(undefined)).toEqual({})
  })

  it('builds Monday-first whole weeks for a month', () => {
    // February 2038 starts on a Monday and has 28 days: exactly four weeks.
    const feb = monthGrid('2038-02', 1)
    expect(feb).toHaveLength(4)
    expect(feb[0][0]).toEqual({ date: '2038-02-01', inMonth: true, day: 1 })

    // January 2038 starts on a Friday, so the first row leads with Dec days.
    const jan = monthGrid('2038-01', 1)
    expect(jan[0][0]).toEqual({ date: '2037-12-28', inMonth: false, day: 28 })
    expect(jan[0][4]).toEqual({ date: '2038-01-01', inMonth: true, day: 1 })
    jan.forEach((week) => expect(week).toHaveLength(7))
  })

  it('shifts months across year boundaries', () => {
    expect(shiftMonth('2038-01', -1)).toBe('2037-12')
    expect(shiftMonth('2038-12', 1)).toBe('2039-01')
  })

  describe('groupColour', () => {
    it('is stable for a group and always from the palette', () => {
      expect(groupColour(42)).toBe(groupColour(42))
      expect(groupColour('42')).toBe(groupColour(42))
      for (let id = 0; id < 50; id++) expect(GROUP_COLOURS).toContain(groupColour(id))
    })

    it('gives consecutive group ids different colours', () => {
      for (let id = 1; id < 40; id++) expect(groupColour(id)).not.toBe(groupColour(id + 1))
    })

    it('has a palette of accessible fills: 4.5:1 against white text', () => {
      const luminance = (hex) => {
        const [r, g, b] = [1, 3, 5].map((i) => Number.parseInt(hex.slice(i, i + 2), 16) / 255)
        const lin = (c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4)
        return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b)
      }

      GROUP_COLOURS.forEach((c) => expect(1.05 / (luminance(c) + 0.05), c).toBeGreaterThanOrEqual(4.5))
    })

    it('copes with a missing id', () => {
      expect(GROUP_COLOURS).toContain(groupColour(undefined))
    })
  })
})
