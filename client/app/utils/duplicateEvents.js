// Groups keep ending up with the same repair event posted twice - a second
// host adds one not knowing the first did, or someone re-submits after fixing a
// typo instead of editing what is already there. EventForm runs these checks
// against the group's existing events before sending a create, so the host can
// be shown what already exists and pick: edit that one, or post anyway.
//
// Deliberately advisory. Everything here is a heuristic on user-entered text
// and times, so it warns and never blocks - two genuinely different events can
// share a day and a venue.

export const CERTAIN = 'certain'
export const LIKELY = 'likely'
export const POSSIBLE = 'possible'

const RANK = { [CERTAIN]: 3, [LIKELY]: 2, [POSSIBLE]: 1 }

// Times within this are "the same slot" rather than a second sitting.
const NEAR_MINUTES = 60

// An event this group saved seconds ago, with the same name, is almost always
// the first half of a double submit the host never saw land.
const JUST_POSTED_MINUTES = 15

function normalise(value) {
  return (value || '').trim().toLowerCase().replace(/\s+/g, ' ')
}

function time(value) {
  if (!value) return null
  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

// Calendar day in the event's own timezone: an evening event in Auckland and
// one just after midnight are different days locally even though they are an
// hour apart in UTC.
function localDay(value, timezone) {
  const parsed = time(value)
  if (!parsed) return null

  const format = (tz) =>
    new Intl.DateTimeFormat('en-CA', {
      timeZone: tz,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).format(parsed)

  try {
    return format(timezone || 'UTC')
  } catch {
    // An unknown timezone string shouldn't lose us the comparison entirely.
    return format('UTC')
  }
}

function minutesBetween(a, b) {
  const first = time(a)
  const second = time(b)
  if (!first || !second) return null
  return Math.abs(first.getTime() - second.getTime()) / 60000
}

function samePlace(candidate, other) {
  if (candidate.online || other.online) return Boolean(candidate.online && other.online)

  const here = normalise(candidate.location)
  return here !== '' && here === normalise(other.location)
}

function compare(candidate, other, now) {
  // An online event and one in a room are not the same event, whatever else
  // lines up - so don't nag about the clash.
  if (Boolean(candidate.online) !== Boolean(other.online)) return null

  const reasons = []
  let confidence = null
  const raise = (level, reason) => {
    reasons.push(reason)
    if (!confidence || RANK[level] > RANK[confidence]) confidence = level
  }

  const apart = minutesBetween(candidate.start, other.start)
  const place = samePlace(candidate, other)
  const day = localDay(candidate.start, candidate.timezone)
  const otherDay = localDay(other.start, other.timezone)
  const sameDay = day !== null && day === otherDay
  const title = normalise(candidate.title)
  const sameTitle = title !== '' && title === normalise(other.title)

  if (apart === 0 && place) raise(CERTAIN, 'same-time-and-place')
  else if (apart !== null && apart <= NEAR_MINUTES && place && sameDay) raise(LIKELY, 'same-place-near-time')
  else if (sameDay && sameTitle) raise(LIKELY, 'same-day-same-name')
  else if (sameDay && place) raise(POSSIBLE, 'same-day-same-place')
  else if (apart === 0) raise(POSSIBLE, 'same-time')

  const since = minutesBetween(other.updated_at, now)
  if (sameTitle && since !== null && since <= JUST_POSTED_MINUTES) raise(LIKELY, 'just-posted')

  return confidence ? { event: other, confidence, reasons } : null
}

/**
 * Existing events that look like the one about to be created.
 *
 * @param candidate  the event being created - start/end/location/title/online/
 *                   timezone, plus id when editing so it isn't matched to itself
 * @param events     the group's existing events (PartySummary shape)
 * @param options    `now` for the just-posted window; injected so tests don't
 *                   depend on the clock
 * @returns matches, strongest first
 */
export function findDuplicateEvents(candidate, events, options = {}) {
  if (!candidate || !Array.isArray(events)) return []

  const now = options.now || new Date()

  return events
    .filter((other) => other && (candidate.id == null || other.id !== candidate.id))
    .map((other) => compare(candidate, other, now))
    .filter(Boolean)
    .sort((a, b) => RANK[b.confidence] - RANK[a.confidence])
}
