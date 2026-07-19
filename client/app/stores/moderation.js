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
    async fetchEvents({ force = false } = {}) {
      if (this.events.loading || (this.events.loaded && !force)) {
        return this.events.data
      }
      this.events.loading = true
      this.events.error = null
      try {
        const { $api } = useNuxtApp()
        const { data } = await $api.moderation.events()
        this.events.data = data || []
        this.events.loaded = true
        return this.events.data
      } catch (error) {
        this.events.error = error
        throw error
      } finally {
        this.events.loading = false
      }
    },

    async fetchGroups({ force = false } = {}) {
      if (this.groups.loading || (this.groups.loaded && !force)) {
        return this.groups.data
      }
      this.groups.loading = true
      this.groups.error = null
      try {
        const { $api } = useNuxtApp()
        const { data } = await $api.moderation.groups()
        this.groups.data = data || []
        this.groups.loaded = true
        return this.groups.data
      } catch (error) {
        this.groups.error = error
        throw error
      } finally {
        this.groups.loading = false
      }
    },
  },
})
