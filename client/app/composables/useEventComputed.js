import { computed, toValue } from 'vue'
import moment from 'moment-timezone'

/**
 * Port of resources/js/mixins/event.js's computed vocabulary
 * (attending/upcoming/finished/inprogress/startingsoon/dayofmonth/month/
 * date/start/end/timezone), re-provided as a composable per design.md §5.4/
 * §6 ("The mixin surface ... is re-provided as composables"). The mixin
 * read these off the Vuex event object, where the Blade controller's
 * `expandEvent()` had already computed them server-side; api-contracts-
 * phase-c.md C1a is explicit that the v2 event resource does NOT carry
 * those fields - "derivable client-side from start/end/timezone ... port
 * into useEventComputed(event), no new fields" - so this module does that
 * derivation.
 *
 * `upcoming`/`finished`/`inprogress` mirror Party::isUpcoming()/
 * hasFinished()/isInProgress() exactly: plain instant comparisons against
 * `now`, timezone-irrelevant (start/end are absolute ISO8601 instants).
 * `startingSoon` mirrors Party::isStartingSoon(), which additionally needs
 * "is today" - the server used Carbon::isCurrentDay() (app/server default
 * timezone, ambiguous for a global site); this port instead uses the
 * event's own `timezone` field, since "today" for a specific repair event
 * should mean "today where the event is happening" - a deliberate,
 * documented deviation, not a bug.
 *
 * Every function here is a plain, non-reactive function operating on a
 * plain event object (or null) - so pages can also use them directly for
 * bucketing/sorting an *array* of events (e.g. splitting /party's mine list
 * into upcoming/past), which isn't something a composable can be called for
 * inside a .filter()/.sort() callback. `useEventComputed(event)` just wraps
 * them as computed refs for single-event consumers (EventCard.vue).
 */

function eventTimezone(event) {
  return (event && event.timezone) || moment.tz.guess()
}

export function eventIsAttending(event) {
  return !!(event && event.attending)
}

export function eventIsApproved(event) {
  return !!(event && event.approved)
}

export function eventIsUpcoming(event, now = moment()) {
  if (!event || !event.start) return false
  return moment(event.start).isAfter(now)
}

export function eventIsFinished(event, now = moment()) {
  if (!event || !event.end) return false
  return moment(event.end).isBefore(now)
}

export function eventIsInProgress(event, now = moment()) {
  if (!event || !event.start || !event.end) return false
  const n = moment(now)
  return n.isSameOrAfter(moment(event.start)) && n.isSameOrBefore(moment(event.end))
}

export function eventIsStartingSoon(event, now = moment()) {
  if (!event || !event.start) return false
  if (eventIsInProgress(event, now) || eventIsFinished(event, now)) return false

  const zone = eventTimezone(event)
  return moment.tz(event.start, zone).isSame(moment.tz(now, zone), 'day')
}

export function eventDayOfMonth(event) {
  if (!event || !event.start) return null
  return moment.tz(event.start, eventTimezone(event)).format('DD')
}

export function eventMonth(event) {
  if (!event || !event.start) return null
  return moment.tz(event.start, eventTimezone(event)).format('MMM').toUpperCase()
}

// Matches the legacy mixin's DATE_FORMAT ('ddd Do MMM Y').
export function eventDateLabel(event) {
  if (!event || !event.start) return null
  return moment.tz(event.start, eventTimezone(event)).format('ddd Do MMM YYYY')
}

export function eventStartLocal(event) {
  if (!event || !event.start) return null
  return moment.tz(event.start, eventTimezone(event)).format('HH:mm')
}

export function eventEndLocal(event) {
  if (!event || !event.end) return null
  return moment.tz(event.end, eventTimezone(event)).format('HH:mm')
}

/**
 * @param eventSource a ref/getter/plain object resolving to the event (or
 *   null) - EventCard.vue passes `() => props.event` or a computed ref.
 */
export function useEventComputed(eventSource) {
  const event = computed(() => toValue(eventSource))

  return {
    event,
    attending: computed(() => eventIsAttending(event.value)),
    approved: computed(() => eventIsApproved(event.value)),
    upcoming: computed(() => eventIsUpcoming(event.value)),
    finished: computed(() => eventIsFinished(event.value)),
    inProgress: computed(() => eventIsInProgress(event.value)),
    startingSoon: computed(() => eventIsStartingSoon(event.value)),
    dayOfMonth: computed(() => eventDayOfMonth(event.value)),
    month: computed(() => eventMonth(event.value)),
    date: computed(() => eventDateLabel(event.value)),
    start: computed(() => eventStartLocal(event.value)),
    end: computed(() => eventEndLocal(event.value)),
    timezone: computed(() => (event.value ? event.value.timezone : null)),
  }
}
