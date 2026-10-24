import { bucketByDay, monthGrid, localDay, shiftMonth } from './networkEventsUtil'

const ev = (id, group, start, tz = 'Europe/London', extra = {}) => ({
  id, group: { id: group }, start, end: start, timezone: tz, ...extra
})

describe('networkEventsUtil', () => {
  test('localDay uses the event timezone', () => {
    // 22:00 UTC on 5 March is already 6 March in Auckland (NZDT).
    expect(localDay(ev(1, 1, '2025-03-05T22:00:00Z', 'Pacific/Auckland'))).toBe('2025-03-06')
    expect(localDay(ev(1, 1, '2025-03-05T22:00:00Z', 'Europe/London'))).toBe('2025-03-05')
  })

  test('localDay handles the DST boundary', () => {
    // 30 March 2025 23:30 UTC is 31 March 00:30 in London (BST has started).
    expect(localDay(ev(1, 1, '2025-03-30T23:30:00Z'))).toBe('2025-03-31')
  })

  test('bucketByDay groups and sorts by start', () => {
    const b = bucketByDay([
      ev(2, 1, '2025-03-05T15:00:00Z'),
      ev(1, 2, '2025-03-05T10:00:00Z'),
      ev(3, 1, '2025-03-06T10:00:00Z')
    ])
    expect(Object.keys(b)).toEqual(['2025-03-05', '2025-03-06'])
    expect(b['2025-03-05'].map(e => e.id)).toEqual([1, 2])
  })

  test('monthGrid is Monday-first and padded to whole weeks', () => {
    const weeks = monthGrid('2025-03', 1)
    expect(weeks.every(w => w.length === 7)).toBe(true)
    // 1 March 2025 is a Saturday: Monday-first means 5 leading days from February.
    expect(weeks[0][0].date).toBe('2025-02-24')
    expect(weeks[0][5].date).toBe('2025-03-01')
    expect(weeks[0][5].inMonth).toBe(true)
    expect(weeks[0][0].inMonth).toBe(false)
    expect(weeks[weeks.length - 1][6].date).toBe('2025-04-06')
    expect(weeks.length).toBe(6)
  })

  test('monthGrid Sunday-first, month starting on first day', () => {
    // 1 June 2025 is a Sunday.
    const weeks = monthGrid('2025-06', 0)
    expect(weeks[0][0].date).toBe('2025-06-01')
    expect(weeks[weeks.length - 1][6].date).toBe('2025-07-05')
  })

  test('shiftMonth crosses year boundaries', () => {
    expect(shiftMonth('2025-12', 1)).toBe('2026-01')
    expect(shiftMonth('2025-01', -1)).toBe('2024-12')
  })
})
