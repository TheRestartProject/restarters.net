import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { useCalendarLinks } from '../../app/composables/useCalendarLinks.js'

function decodeIcs(href) {
  const base64 = href.replace('data:text/calendar;charset=utf8;base64,', '')
  return Buffer.from(base64, 'base64').toString('utf8')
}

describe('composables/useCalendarLinks', () => {
  it('returns null when the event has no start/end', () => {
    expect(useCalendarLinks(() => null).value).toBeNull()
    expect(useCalendarLinks(() => ({ title: 'No dates' })).value).toBeNull()
  })

  it('builds a Google Calendar link with UTC dates, title, description and location', () => {
    const links = useCalendarLinks(() => ({
      id: 42,
      title: 'Repair Café',
      description: '<p>Come <b>fix</b> stuff</p>',
      location: 'Town Hall',
      online: false,
      start: '2026-08-01T10:30:00+00:00',
      end: '2026-08-01T12:00:00+00:00',
    })).value

    expect(links.google).toContain('https://calendar.google.com/calendar/render?action=TEMPLATE')
    expect(links.google).toContain('dates=20260801T103000/20260801T120000')
    expect(links.google).toContain('ctz=UTC')
    expect(links.google).toContain('text=Repair%20Caf%C3%A9')
    // Tags stripped from the description.
    expect(links.google).toContain('details=Come%20fix%20stuff')
    expect(links.google).toContain('location=Town%20Hall')
  })

  it('omits location from every link when the event is online', () => {
    const links = useCalendarLinks(() => ({
      id: 1,
      title: 'Online session',
      location: 'Zoom (should not appear)',
      online: true,
      start: '2026-08-01T10:00:00+00:00',
      end: '2026-08-01T11:00:00+00:00',
    })).value

    expect(links.google).not.toContain('location=')
    expect(links.yahoo).not.toContain('in_loc=')
    expect(links.webOutlook).not.toContain('location=')
  })

  it('builds a Yahoo link with ST/ET in compact UTC-Z format', () => {
    const links = useCalendarLinks(() => ({
      id: 2,
      title: 'Fixit',
      start: '2026-08-01T10:00:00+00:00',
      end: '2026-08-01T11:00:00+00:00',
    })).value

    expect(links.yahoo).toContain('ST=20260801T100000Z')
    expect(links.yahoo).toContain('ET=20260801T110000Z')
    expect(links.yahoo).toContain('TITLE=Fixit')
  })

  it('builds a WebOutlook link with ISO startdt/enddt', () => {
    const links = useCalendarLinks(() => ({
      id: 3,
      title: 'Fixit',
      start: '2026-08-01T10:00:00+00:00',
      end: '2026-08-01T11:00:00+00:00',
    })).value

    expect(links.webOutlook).toContain('startdt=2026-08-01T10:00:00Z')
    expect(links.webOutlook).toContain('enddt=2026-08-01T11:00:00Z')
    expect(links.webOutlook).toContain('outlook.live.com')
  })

  it('builds an ics data URI whose decoded body has the expected VEVENT fields', () => {
    const links = useCalendarLinks(() => ({
      id: 42,
      title: 'Repair Café',
      description: 'Bring your broken toaster',
      location: 'Town Hall',
      online: false,
      start: '2026-08-01T10:00:00+00:00',
      end: '2026-08-01T11:00:00+00:00',
    })).value

    expect(links.ics).toMatch(/^data:text\/calendar;charset=utf8;base64,/)

    const body = decodeIcs(links.ics)
    expect(body).toContain('BEGIN:VCALENDAR')
    expect(body).toContain('SUMMARY:Repair Café')
    expect(body).toContain('DTSTART:20260801T100000Z')
    expect(body).toContain('DTEND:20260801T110000Z')
    expect(body).toContain('DESCRIPTION:Bring your broken toaster')
    expect(body).toContain('LOCATION:Town Hall')
    expect(body).toContain('UID:event-42-20260801T100000@restarters.net')
    expect(body).toContain('END:VEVENT')
  })

  it('is reactive to the underlying event ref', () => {
    const event = ref(null)
    const links = useCalendarLinks(event)
    expect(links.value).toBeNull()

    event.value = { id: 1, title: 'Now set', start: '2026-08-01T10:00:00+00:00', end: '2026-08-01T11:00:00+00:00' }
    expect(links.value).not.toBeNull()
    expect(links.value.google).toContain('text=Now%20set')
  })
})
