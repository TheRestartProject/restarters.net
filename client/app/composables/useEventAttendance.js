import { computed, toValue } from 'vue'
import { useEventsStore } from '../stores/events.js'

// Role ints per app/Role.php (api-contracts-phase-c.md C1b): HOST=3,
// RESTARTER=4, GUEST=5 (guest = plain attendee/"participant"). Pinned here
// rather than a shared constants file since this is the only place the
// client currently needs the RESTARTER/GUEST values (useGroupRole.js pins
// the *user* role ints, a different table - app/Role.php's group roles are
// 1/2/3/4/6, event-attendance roles are 3/4/5).
export const EVENT_ROLE_HOST = 3
export const EVENT_ROLE_RESTARTER = 4
export const EVENT_ROLE_GUEST = 5

/**
 * Backs the event view page's attendance section (design.md §5.4:
 * "useEventAttendance(id) backed by the attendees endpoint"). Wraps
 * stores/events.js's `attendees` state ({confirmed, invited} per
 * api-contracts-phase-c.md C1b, GET /api/v2/events/{id}/attendees).
 *
 * `hosts` replaces the Blade view's separate `$hosts` array - per the
 * contract doc, "the event-view 'hosts' block is just
 * confirmed.filter(a => a.role === 3) - no separate array needed".
 *
 * `isAttendingUser(userId)` replaces Party::isVolunteer()'s looser
 * confirmed-or-null-status test (contract: "derive client-side as
 * attendees.confirmed.some(a => a.user === me.id)" - now that this
 * endpoint exists, no separate `isVolunteer` field is needed).
 *
 * @param idSource a ref/getter/plain value resolving to the event id.
 */
export function useEventAttendance(idSource) {
  const store = useEventsStore()
  const id = computed(() => toValue(idSource))

  const confirmed = computed(() => store.attendees.data.confirmed || [])
  const invited = computed(() => store.attendees.data.invited || [])
  const hosts = computed(() => confirmed.value.filter((a) => a.role === EVENT_ROLE_HOST))
  const loading = computed(() => store.attendees.loading)
  const error = computed(() => store.attendees.error)

  function fetch() {
    return store.fetchAttendees(id.value)
  }

  function isAttendingUser(userId) {
    return confirmed.value.some((a) => a.user === userId)
  }

  function remove(idEventsUsers) {
    return store.removeAttendee(id.value, idEventsUsers)
  }

  return { confirmed, invited, hosts, loading, error, fetch, isAttendingUser, remove }
}
