import moment from 'moment-timezone'

// Pure helpers for the network events list/calendar. Events are the
// PartySummary objects returned by GET /api/v2/networks/{id}/events.

// The YYYY-MM-DD on which an event starts, in the event's own timezone (so a
// late evening event in Auckland lands on its local day, not the UTC one).
export function localDay(event) {
  const m = event.timezone ? moment.tz(event.start, event.timezone) : moment.utc(event.start)
  return m.format('YYYY-MM-DD')
}

// Group events into { 'YYYY-MM-DD': [events sorted by start] }.
export function bucketByDay(events) {
  const buckets = {}

  ;(events || []).forEach((e) => {
    const day = localDay(e)
    if (!buckets[day]) {
      buckets[day] = []
    }
    buckets[day].push(e)
  })

  Object.keys(buckets).forEach((day) => {
    buckets[day].sort((a, b) => new Date(a.start) - new Date(b.start))
  })

  return buckets
}

// Weeks of days for a month ('YYYY-MM'), padded to whole weeks. Each day is
// { date: 'YYYY-MM-DD', inMonth: bool, day: number }. firstDay: 0 = Sunday,
// 1 = Monday.
export function monthGrid(month, firstDay = 1) {
  const first = moment.utc(month + '-01', 'YYYY-MM-DD')
  const lead = (first.day() - firstDay + 7) % 7
  const weekCount = Math.ceil((lead + first.daysInMonth()) / 7)
  const cursor = first.clone().subtract(lead, 'days')
  const weeks = []

  for (let w = 0; w < weekCount; w++) {
    const week = []

    for (let d = 0; d < 7; d++) {
      week.push({
        date: cursor.format('YYYY-MM-DD'),
        inMonth: cursor.format('YYYY-MM') === month,
        day: cursor.date(),
      })
      cursor.add(1, 'days')
    }

    weeks.push(week)
  }

  return weeks
}

// 'YYYY-MM' shifted by n months.
export function shiftMonth(month, n) {
  return moment.utc(month + '-01', 'YYYY-MM-DD').add(n, 'months').format('YYYY-MM')
}

// Colours for group chips. Every entry has a contrast ratio of at least 4.5:1
// against white, so white text on the fill, and the colour as text/border on
// a white (dashed) chip, are both readable.
export const GROUP_COLOURS = [
  '#1b6e7a',
  '#7b3f98',
  '#b0451b',
  '#2f6b2f',
  '#1f4e9c',
  '#a12a4d',
  '#6b5a1a',
  '#44448f',
]

// A stable colour per group: the same group always gets the same one, on
// every page load and for every viewer.
export function groupColour(groupId) {
  const n = Math.abs(Number.parseInt(groupId, 10) || 0)
  // 3 is coprime with the palette size, so multiplying first spreads
  // neighbouring ids (groups are often created in a batch) across the palette.
  return GROUP_COLOURS[(n * 3) % GROUP_COLOURS.length]
}
