import moment from 'moment-timezone'

// Pure helpers for the network events list/calendar.  Events are as returned by /api/v2/networks/{id}/events.

// The YYYY-MM-DD on which an event starts, in the event's own timezone (so a late evening event in Auckland lands
// on its local day, not the UTC one).
export function localDay(event) {
  const m = event.timezone ? moment.tz(event.start, event.timezone) : moment.utc(event.start)
  return m.format('YYYY-MM-DD')
}

// Group events into { 'YYYY-MM-DD': [events sorted by start] }.
export function bucketByDay(events) {
  const buckets = {}

  ;(events || []).forEach(e => {
    const day = localDay(e)
    if (!buckets[day]) {
      buckets[day] = []
    }
    buckets[day].push(e)
  })

  Object.keys(buckets).forEach(day => {
    buckets[day].sort((a, b) => new Date(a.start) - new Date(b.start))
  })

  return buckets
}

// Weeks of days for a month ('YYYY-MM'), padded to whole weeks.  Each day is
// { date: 'YYYY-MM-DD', inMonth: bool, day: number }.  firstDay: 0 = Sunday, 1 = Monday.
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
        day: cursor.date()
      })
      cursor.add(1, 'days')
    }

    weeks.push(week)
  }

  return weeks
}

// Days on which two or more different groups have events (version 1 of clash detection).  Cancelled events don't
// count.  Returns { 'YYYY-MM-DD': number of events that day }.
export function clashDays(buckets) {
  const clashes = {}

  Object.keys(buckets).forEach(day => {
    const live = buckets[day].filter(e => !e.cancelled)
    const groups = new Set(live.map(e => e.group?.id))

    if (groups.size >= 2) {
      clashes[day] = live.length
    }
  })

  return clashes
}

// 'YYYY-MM' shifted by n months.
export function shiftMonth(month, n) {
  return moment.utc(month + '-01', 'YYYY-MM-DD').add(n, 'months').format('YYYY-MM')
}
