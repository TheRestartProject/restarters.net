import { defineStore } from 'pinia'

/**
 * Backs /networks and /networks/{id} (design.md §6.2 Phase E task E1;
 * resources/js/components/NetworkPage.vue + resources/views/networks/*
 * are the functional spec). API\NetworkController is already implemented
 * server-side for everything except associating groups (see
 * associateGroups() below and docs/nuxt-migration/api-gaps.md Phase E).
 *
 * Both /networks and /networks/{id} are Administrator/NetworkCoordinator-
 * only pages (NetworkController@index aborts 403 for anyone else;
 * NetworkPolicy@view only grants Administrators and the network's own
 * coordinators) - the pages themselves do the role gate (design.md §4.4),
 * this store makes no assumption about who's calling it.
 *
 * `current.data.stats` (from GET /api/v2/networks/{id} - App\Http\
 * Resources\Network already embeds unfiltered stats) covers the page's
 * default stats block; `fetchStats(id, {group_tag})` is only needed for
 * the tag-filtered view (GetNetworkStatsv2).
 *
 * `groups.data` holds the raw GroupSummary array from GET /api/v2/
 * networks/{id}/groups?includeCounts=true&includeNextEvent=true - the page
 * maps it into the {id, name, archivedAt, location, hosts, restarters,
 * nextEvent} shape GroupsTable.vue expects (same mapping as
 * pages/group/all.vue's `rows` computed).
 *
 * `tags.data` (GET /api/v2/networks/{id}/tags) already carries
 * `groups_count` per tag (App\Http\Resources\Tag) - no extra call needed
 * to power the delete-confirmation's in-use warning, same as the global
 * /tags admin page (stores/adminRefdata.js#groupTags).
 */
export const useNetworksStore = defineStore('networks', {
  state: () => ({
    list: { data: [], loading: false, error: null },
    current: { data: null, loading: false, error: null },
    groups: { data: [], loading: false, error: null },
    tags: { data: [], loading: false, error: null },
  }),

  actions: {
    async fetchList(params) {
      const { $api } = useNuxtApp()

      this.list.loading = true
      this.list.error = null

      try {
        const { data } = await $api.network.list(params)
        this.list.data = data
        return data
      } catch (error) {
        this.list.error = error
        throw error
      } finally {
        this.list.loading = false
      }
    },

    async fetchCurrent(id) {
      const { $api } = useNuxtApp()

      this.current.loading = true
      this.current.error = null

      try {
        const { data } = await $api.network.get(id)
        this.current.data = data
        return data
      } catch (error) {
        this.current.error = error
        throw error
      } finally {
        this.current.loading = false
      }
    },

    // Set the network logo from a completed tus upload, then reflect the new
    // logo url on the loaded network so the preview updates immediately.
    async uploadLogo(id, uploadKey) {
      const { $api } = useNuxtApp()
      const { data } = await $api.network.uploadLogo(id, uploadKey)
      if (this.current.data && this.current.data.id === id) {
        this.current.data = { ...this.current.data, logo: data.logo }
      }
      return data
    },

    async fetchGroups(id, params) {
      const { $api } = useNuxtApp()

      this.groups.loading = true
      this.groups.error = null

      try {
        const { data } = await $api.network.groups(id, { includeCounts: true, includeNextEvent: true, ...params })
        this.groups.data = data
        return data
      } catch (error) {
        this.groups.error = error
        throw error
      } finally {
        this.groups.loading = false
      }
    },

    async fetchStats(id, params) {
      const { $api } = useNuxtApp()
      return $api.network.stats(id, params)
    },

    async fetchTags(id) {
      const { $api } = useNuxtApp()

      this.tags.loading = true
      this.tags.error = null

      try {
        const { data } = await $api.network.tags(id)
        this.tags.data = sortByName(data)
        return this.tags.data
      } catch (error) {
        this.tags.error = error
        throw error
      } finally {
        this.tags.loading = false
      }
    },

    async createTag(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.network.createTag(id, payload)
      this.tags.data = sortByName([...this.tags.data, data])
      return data
    },

    async updateTag(id, tagId, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.network.updateTag(id, tagId, payload)
      this.tags.data = sortByName(this.tags.data.map((tag) => (tag.id === tagId ? data : tag)))
      return data
    },

    async deleteTag(id, tagId) {
      const { $api } = useNuxtApp()
      await $api.network.deleteTag(id, tagId)
      this.tags.data = this.tags.data.filter((tag) => tag.id !== tagId)
    },

    // POST /api/v2/networks/{id}/groups. On success, re-fetches the
    // network's groups so the just-added rows appear without a page reload.
    async associateGroups(id, groupIds) {
      const { $api } = useNuxtApp()
      const result = await $api.network.associateGroups(id, groupIds)
      await this.fetchGroups(id)
      return result
    },
  },
})

function sortByName(tags) {
  return [...tags].sort((a, b) => String(a.name).localeCompare(String(b.name)))
}
