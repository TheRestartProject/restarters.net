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
    const { data } = await request()
    section.data = data || []
    section.loaded = true
    return section.data
  } catch (error) {
    section.error = error
    throw error
  } finally {
    section.loading = false
  }
}
