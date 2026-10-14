import { defineStore } from 'pinia'

// Admin / NetworkCoordinator moderation queues (GET /api/v2/moderate/events and
// /groups). Kept in a dedicated store rather than folded into events/groups so
// the queue widgets on /party, /group/map and /networks/{id} can fetch and cache
// independently. Read-only: the queue lists items and links to each item's page,
// where the existing approve/edit controls live (matching the legacy
// EventsRequiringModeration listing, which was navigational, not inline-approve).
export const useModerationStore = defineStore('moderation', {
  state: () => ({
    events: { data: [], loading: false, loaded: false, error: null },
    groups: { data: [], loading: false, loaded: false, error: null },
  }),

  actions: {
    // Thin wrappers over loadSection (below) - the only difference between the
    // two queues is which /moderate endpoint they call.
    fetchEvents({ force = false } = {}) {
      const { $api } = useNuxtApp()
      return loadSection(this, 'events', force, () => $api.moderation.events())
    },

    fetchGroups({ force = false } = {}) {
      const { $api } = useNuxtApp()
      return loadSection(this, 'groups', force, () => $api.moderation.groups())
    },
  },
})

// Cached-fetch for a queue section (events | groups): return early when already
// loaded (unless forced) or in flight, otherwise load once and record
// loading/error/data. Kept as a module helper (not an action) so it isn't part
// of the store's public surface.
async function loadSection(store, key, force, request) {
  const section = store[key]
  if (section.loading || (section.loaded && !force)) {
    return section.data
  }
  section.loading = true
  section.error = null
  try {
    // await inside try so a synchronous throw from request() (e.g. a missing
    // $api in a test) is caught here, same as the original explicit actions.
    //
    // GET /api/v2/moderate/{groups,events} are the odd ones out among v2
    // endpoints: moderateGroupsv2/moderateEventsv2 (API\GroupController,
    // API\EventController) both do `response()->json($resourceCollection)`,
    // which skips Laravel's normal Responsable auto-wrap and returns a bare
    // JSON array - not the `{"data": [...]}` envelope every other v2
    // endpoint uses (confirmed via a live capture: 200 OK, body is
    // `[{"id":1,...}]` directly). A plain `const { data } = ...` destructure
    // silently produced `undefined` for that shape, so the queue rendered
    // as "empty" with no error at all - a live, reproducible admin-facing
    // bug (the moderation panel invisibly failing to show a genuinely
    // unapproved group). Fixed here rather than server-side because
    // develop's own Vuex consumer (resources/js/store/groups.js's
    // getModerationRequired) already depends on the current bare-array
    // shape (axios's own single `.data` unwrap lands on it correctly) -
    // wrapping the response server-side would fix us and break develop,
    // which is still the live parity reference. Tolerate both shapes so
    // this keeps working if the envelope is ever normalised later.
    const res = await request()
    section.data = (Array.isArray(res) ? res : res?.data) || []
    section.loaded = true
    return section.data
  } catch (error) {
    section.error = error
    throw error
  } finally {
    section.loading = false
  }
}
