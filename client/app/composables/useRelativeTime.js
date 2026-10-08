import { useI18n } from 'vue-i18n'

// Bucket thresholds for relativeTime() below - the standard
// Intl.RelativeTimeFormat "walk the divisions" algorithm (see MDN's own
// example): each entry's `amount` is how many of the *previous* unit make
// one of this unit, so dividing the running duration by each in turn walks
// it down from seconds to years until it fits inside a single bucket.
const DIVISIONS = [
  { amount: 60, unit: 'second' },
  { amount: 60, unit: 'minute' },
  { amount: 24, unit: 'hour' },
  { amount: 7, unit: 'day' },
  { amount: 4.34524, unit: 'week' },
  { amount: 12, unit: 'month' },
  { amount: Number.POSITIVE_INFINITY, unit: 'year' },
]

/**
 * Relative + absolute date/time formatting for notification timestamps
 * (pages/notifications.vue), replacing Carbon's diffForHumans() +
 * toDayDateTimeString() (partials.notification.blade.php) with the
 * Intl formatters vue-i18n already pulls in - no new dependency.
 *
 * Both functions take the active i18n locale live (via useI18n(), so a
 * locale switch is picked up automatically) and accept an optional
 * reference `now` (defaults to `new Date()`) so callers - and tests - can
 * pin "now" for a deterministic result.
 */
export function useRelativeTime() {
  const { locale } = useI18n()

  // e.g. "3 days ago" / "in 3 days". numeric: 'always' matches Carbon's
  // diffForHumans(), which never shortens to "yesterday"/"tomorrow".
  function relativeTime(date, now = new Date()) {
    const rtf = new Intl.RelativeTimeFormat(locale.value, { numeric: 'always' })
    let duration = (new Date(date).getTime() - new Date(now).getTime()) / 1000

    for (const division of DIVISIONS) {
      if (Math.abs(duration) < division.amount) {
        return rtf.format(Math.round(duration), division.unit)
      }
      duration /= division.amount
    }
  }

  // The full localized date/time, for a `title` tooltip - mirrors Carbon's
  // toDayDateTimeString() (weekday + date + time).
  function absoluteDateTime(date) {
    return new Intl.DateTimeFormat(locale.value, { dateStyle: 'full', timeStyle: 'short' }).format(new Date(date))
  }

  return { relativeTime, absoluteDateTime }
}
