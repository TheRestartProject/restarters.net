import { computed, toValue } from 'vue'

// Client-side port of PartyController::generateAddToCalendarLinks(), which
// wraps spatie/calendar-links (vendor/spatie/calendar-links/src/Generators/
// {Google,Yahoo,BaseOutlook,Ics}.php). There is no API endpoint for this
// (design.md §9 only freezes the unrelated /calendar/** iCal *subscription*
// feeds) - google/yahoo/webOutlook are pure query-string URLs to external
// calendar sites and ics is a self-contained `data:` URI, so all four can be
// built purely from fields already on the v2 Event resource
// (title/start/end/description/location/online) with a plain href, no call.
//
// config/app.php pins the Laravel app timezone to 'UTC', so
// `new DateTime($event->event_start_utc)` (no offset in the string) is
// always interpreted as UTC server-side - matching the v2 resource's
// start/end, which are already UTC ISO8601 strings. That lets every
// generator below skip timezone conversion entirely.
//
// One deliberate deviation: Ics::generateEventUid() PHP-md5-hashes
// from+to+title+address. There's no MD5 in the browser (Web Crypto only
// offers SHA-*) and this UID is never surfaced to a user or asserted by any
// other system - it only helps a calendar app de-duplicate a re-added
// event - so this port uses a simple, still-deterministic
// `event-{id}-{start}` id instead of porting an MD5 implementation for a
// value nothing depends on being byte-identical.

function stripTags(html) {
  if (!html) return ''
  return html.replace(/<[^>]*>/g, '')
}

function pad(n) {
  return String(n).padStart(2, '0')
}

// 'YYYYMMDDTHHmmss', optionally with a trailing literal Z - matches
// Spatie's Ymd\THis / Ymd\THis\Z formats applied to a UTC DateTime.
function compactUtc(iso, withZ = false) {
  const d = new Date(iso)
  const s = `${d.getUTCFullYear()}${pad(d.getUTCMonth() + 1)}${pad(d.getUTCDate())}T${pad(d.getUTCHours())}${pad(d.getUTCMinutes())}${pad(d.getUTCSeconds())}`
  return withZ ? `${s}Z` : s
}

// 'YYYY-MM-DDTHH:mm:ssZ' - matches BaseOutlook's Y-m-d\TH:i:s\Z format.
function isoCompactUtc(iso) {
  const d = new Date(iso)
  return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())}T${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())}Z`
}

function base64Utf8(str) {
  const bytes = new TextEncoder().encode(str)
  let binary = ''
  bytes.forEach((b) => {
    binary += String.fromCharCode(b)
  })
  return btoa(binary)
}

// Matches Ics::escapeString() (addcslashes($field, "\r\n,;")) - only CR/LF,
// comma and semicolon are backslash-escaped, nothing else.
function icsEscape(str) {
  return String(str)
    .replace(/\r\n|\r|\n/g, '\\n')
    .replace(/,/g, '\\,')
    .replace(/;/g, '\\;')
}

function buildIcs({ id, title, description, address, start, end }) {
  const lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:Restarters',
    'BEGIN:VEVENT',
    `UID:event-${id}-${compactUtc(start)}@restarters.net`,
    `SUMMARY:${icsEscape(title)}`,
    `DTSTAMP:${compactUtc(start, true)}`,
    `DTSTART:${compactUtc(start, true)}`,
    `DTEND:${compactUtc(end, true)}`,
  ]

  if (description) lines.push(`DESCRIPTION:${icsEscape(description)}`)
  if (address) lines.push(`LOCATION:${icsEscape(address)}`)

  lines.push('END:VEVENT', 'END:VCALENDAR')

  return `data:text/calendar;charset=utf8;base64,${base64Utf8(lines.join('\r\n'))}`
}

/**
 * @param eventSource a ref/getter/plain object resolving to the event (or
 *   null).
 * @returns a computed ref of {google, yahoo, webOutlook, ics} hrefs, or
 *   null when the event has no start/end yet (mirrors
 *   generateAddToCalendarLinks() catching the "not a valid DateTime" case).
 */
export function useCalendarLinks(eventSource) {
  return computed(() => {
    const event = toValue(eventSource)
    if (!event || !event.start || !event.end) return null

    const title = event.title || ''
    const description = stripTags(event.description)
    const address = event.online ? '' : event.location || ''

    const fromCompact = compactUtc(event.start)
    const toCompact = compactUtc(event.end)
    const fromCompactZ = compactUtc(event.start, true)
    const toCompactZ = compactUtc(event.end, true)

    let google = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
    google += `&dates=${fromCompact}/${toCompact}`
    google += '&ctz=UTC'
    google += `&text=${encodeURIComponent(title)}`
    if (description) google += `&details=${encodeURIComponent(description)}`
    if (address) google += `&location=${encodeURIComponent(address)}`

    let yahoo = 'https://calendar.yahoo.com/?v=60&view=d&type=20'
    yahoo += `&ST=${fromCompactZ}`
    yahoo += `&ET=${toCompactZ}`
    yahoo += `&TITLE=${encodeURIComponent(title)}`
    if (description) yahoo += `&DESC=${encodeURIComponent(description)}`
    if (address) yahoo += `&in_loc=${encodeURIComponent(address)}`

    let webOutlook = 'https://outlook.live.com/calendar/action/compose?path=/calendar/action/compose&rru=addevent'
    webOutlook += `&startdt=${isoCompactUtc(event.start)}`
    webOutlook += `&enddt=${isoCompactUtc(event.end)}`
    webOutlook += `&subject=${encodeURIComponent(title)}`
    if (description) webOutlook += `&body=${encodeURIComponent(description)}`
    if (address) webOutlook += `&location=${encodeURIComponent(address)}`

    const ics = buildIcs({ id: event.id, title, description, address, start: event.start, end: event.end })

    return { google, yahoo, webOutlook, ics }
  })
}
