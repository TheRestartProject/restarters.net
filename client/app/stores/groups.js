import { defineStore } from 'pinia'
import { useToastStore } from './toast.js'

/**
 * Backs /group (mine), /group/all and /group/nearby (design.md §6.2 B4;
 * resources/js/store/groups.js + GroupsPage/GroupsTable.vue are the
 * functional spec).
 *
 * The v1 Vuex store held one big list with `following`/`nearby`/distance
 * baked onto every row by the Blade controller. The v2 API doesn't have an
 * equivalent of that yet:
 *
 *  - GET /api/v2/groups/names only returns {id, name, archived_at} today
 *    (api-contracts-phase-b.md client notes: the location/hosts/restarters/
 *    tags/next_event summary fields land with PR #887). `names` is the base
 *    list for the All Groups tab; `fetchDetails(id)` hydrates one group's
 *    full record (GET /api/v2/groups/{id}) on demand, e.g. only for the
 *    rows currently visible on a page, rather than N+1-ing every group.
 *  - There is no "list of groups I belong to" endpoint at all. `mine` reuses
 *    GET /api/v2/dashboard's `your_groups` (max 5, per B1) - the best
 *    available source of {id, name, role, archived} for groups the user
 *    follows. See docs/nuxt-migration/api-gaps.md for the requested proper
 *    endpoint.
 *  - `nearby` calls the dedicated GET /api/v2/groups/nearby (B2 - documented
 *    but not yet implemented server-side).
 *  - `memberIds` is an optimistic, session-local set of "groups the current
 *    user is a member of", seeded from `mine` and updated by join()/leave().
 *    The names index carries no membership flag, but the single-group
 *    endpoint does (Group.is_member), so pages holding a full group prefer
 *    that; memberIds is the fallback for rows not yet hydrated.
 *
 * B5 (/group/view/[id]) adds `current`/`stats`/`events`/`volunteers`, all
 * keyed by the single group being viewed (not by id - only one group view
 * is ever on screen at a time, so a page-scoped {data,loading,error} shape
 * keeps the actions simple; see fetchCurrent/fetchStats/fetchEvents/
 * fetchVolunteers). Two more B5-specific gaps (docs/nuxt-migration/
 * api-gaps.md B5):
 *  - GET /api/v2/groups/{id} carries is_member, the equivalent of the Blade
 *    page's server-computed $in_group; the page prefers it and falls back to
 *    `memberIds` only until the group has loaded.
 *  - GET /api/v2/groups/{id}/stats (group_stats/device_stats/cluster_stats/
 *    top_devices) doesn't exist yet - fetchStats() is written against the
 *    documented shape and fails gracefully (stats.error set, rest of the
 *    page unaffected).
 *
 * B6 (/group/create, /group/edit/[id]) adds createGroup/updateGroup (both
 * already implemented server-side, POST/PATCH /api/v2/groups[/{id}] -
 * unusually for the v2 API these return a bare {id: ...}, not
 * {data: {id: ...}}), fetchTags (GET /api/v2/groups/tags, already
 * implemented, role-filtered server-side) and uploadGroupImage (POST
 * /api/v2/groups/{id}/images - a B2 gap, see docs/nuxt-migration/
 * api-gaps.md B6).
 *
 * B7 (/group/map) lands PR #887: `names` now also carries `lat`/`lng`/
 * `country`/`network_ids`/`tag_ids` (previously just {id, name,
 * archived_at} - see the comment above and all.vue's own note, both now
 * stale) - that's the whole index the map draws markers from and filters
 * client-side, same as the legacy GroupMapAndList's `groups/list`. Visible
 * rows are then hydrated via `fetchSummaries`, the batched counterpart to
 * `fetchDetails` above (GET /api/v2/groups/summary?ids=..., not
 * GET /api/v2/groups/{id} N times) - mirroring the legacy Vuex store's
 * `hydrate` action, chunked at the API's 200-id cap.
 */
export const useGroupsStore = defineStore('groups', {
  state: () => ({
    names: [],
    namesLoading: false,
    namesError: null,

    // ALL of the current user's group memberships with pivot role -
    // GET /api/v2/users/me/groups (uncapped, unlike the dashboard's
    // your_groups which is limited to 5 and only fetched on /dashboard).
    // null = never fetched.
    memberships: null,
    membershipsLoading: false,

    // id -> full Group resource (GET /api/v2/groups/{id})
    details: {},
    // id -> boolean, true while a hydration request is in flight
    detailsLoading: {},

    mine: { data: [], loading: false, error: null },
    nearby: { data: [], loading: false, error: null },

    // ids of groups the current user is known to be a member of.
    memberIds: [],

    // /group/view/[id] state - one group at a time.
    current: { data: null, loading: false, error: null },
    stats: { data: null, loading: false, error: null },
    events: { data: [], loading: false, error: null },
    volunteers: { data: [], loading: false, error: null },

    // /group/create, /group/edit/[id] - role-filtered tag list
    // (GET /api/v2/groups/tags, already implemented server-side).
    tags: { data: [], loading: false, error: null },

    // /group/map - id -> GroupSummary, hydrated on demand for whichever
    // rows are currently visible (see fetchSummaries below).
    summaryByIds: {},
    // id -> boolean, true while that id's summary fetch is in flight - the
    // in-flight half of fetchSummaries' "don't refetch" guard (the other
    // half is just checking summaryByIds already has the id).
    summaryLoading: {},
  }),

  getters: {
    isMember: (state) => (id) => state.memberIds.includes(id),
  },

  actions: {
    async fetchMemberships(force = false) {
      if (this.membershipsLoading || (this.memberships !== null && !force)) {
        return this.memberships
      }
      this.membershipsLoading = true
      try {
        const { $api } = useNuxtApp()
        const { data } = await $api.user.myGroups()
        this.memberships = data
        return data
      } finally {
        this.membershipsLoading = false
      }
    },

    async fetchNames(params) {
      const { $api } = useNuxtApp()

      this.namesLoading = true
      this.namesError = null

      try {
        const { data } = await $api.group.names(params)
        this.names = data
        return data
      } catch (error) {
        this.namesError = error
        throw error
      } finally {
        this.namesLoading = false
      }
    },

    // Hydrates one group's full record. Cached: repeat calls for the same
    // id are a no-op unless `force` is set (e.g. after an edit elsewhere).
    async fetchDetails(id, { force = false } = {}) {
      if (!force && this.details[id]) {
        return this.details[id]
      }

      const { $api } = useNuxtApp()

      this.detailsLoading = { ...this.detailsLoading, [id]: true }

      try {
        const { data } = await $api.group.get(id)
        this.details = { ...this.details, [id]: data }
        return data
      } catch {
        // Leave the row without details rather than failing the whole page -
        // the table falls back to name-only for that row.
        return null
      } finally {
        this.detailsLoading = { ...this.detailsLoading, [id]: false }
      }
    },

    // GET /api/v2/users/me/groups (UserController::getMyGroupsv2). Uncapped,
    // unlike the dashboard endpoint's your_groups (capped at 5 alphabetically),
    // and carries location/hosts/restarters/next_event for develop's /group
    // table.
    async fetchMine() {
      const { $api } = useNuxtApp()

      this.mine.loading = true
      this.mine.error = null

      try {
        const { data } = await $api.user.myGroups()
        this.mine.data = (data || []).map((group) => ({
          ...group,
          // The dashboard payload named this `archived`; keep both shapes
          // working for existing consumers of `mine`.
          archived: group.archived,
        }))
        this.memberIds = [...new Set([...this.memberIds, ...this.mine.data.map((g) => g.id)])]
        return this.mine.data
      } catch (error) {
        this.mine.error = error
        throw error
      } finally {
        this.mine.loading = false
      }
    },

    async fetchNearby() {
      const { $api } = useNuxtApp()

      this.nearby.loading = true
      this.nearby.error = null

      try {
        const { data } = await $api.group.nearby()
        this.nearby.data = data
        return data
      } catch (error) {
        this.nearby.error = error
        throw error
      } finally {
        this.nearby.loading = false
      }
    },

    // Optimistic self-join: flips memberIds immediately, reverts + toasts on
    // failure (design.md §6.2 task brief for GroupJoinButton.vue).
    async join(id) {
      const wasMember = this.memberIds.includes(id)
      if (!wasMember) {
        this.memberIds = [...this.memberIds, id]
      }
      // /group/view/[id] reads `group.is_member ?? isMember(id)`, and
      // GET /api/v2/groups/{id} always sends is_member for a logged-in
      // caller - so a defined `false` wins and flipping memberIds alone
      // leaves the Join/Leave item stuck until the page is reloaded.
      const restoreIsMember = this._setCurrentIsMember(id, true)

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.group.join(id)
        return data
      } catch (error) {
        if (!wasMember) {
          this.memberIds = this.memberIds.filter((mid) => mid !== id)
        }
        restoreIsMember()
        useToastStore().error(error)
        throw error
      }
    },

    // Mirrors an optimistic membership flip onto the currently-loaded group,
    // returning the undo for the revert paths above.
    _setCurrentIsMember(id, value) {
      const current = this.current.data
      if (!current || current.id !== id) {
        return () => {}
      }
      const previous = current.is_member
      current.is_member = value
      return () => {
        current.is_member = previous
      }
    },

    // Optimistic self-leave: flips memberIds and (if present) removes the
    // row from `mine` immediately, reverts both + toasts on failure.
    async leave(id) {
      const wasMember = this.memberIds.includes(id)
      const mineIndex = this.mine.data.findIndex((g) => g.id === id)
      const mineEntry = mineIndex !== -1 ? this.mine.data[mineIndex] : null

      this.memberIds = this.memberIds.filter((mid) => mid !== id)
      if (mineEntry) {
        this.mine.data = this.mine.data.filter((g) => g.id !== id)
      }
      const restoreIsMember = this._setCurrentIsMember(id, false)

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.group.leave(id)
        return data
      } catch (error) {
        if (wasMember) {
          this.memberIds = [...this.memberIds, id]
        }
        restoreIsMember()
        if (mineEntry) {
          const restored = [...this.mine.data]
          restored.splice(mineIndex, 0, mineEntry)
          this.mine.data = restored
        }
        useToastStore().error(error)
        throw error
      }
    },

    // Fetches the single group shown by /group/view/[id]. The response does
    // carry is_member (see the class doc comment), but memberIds is what the
    // group lists elsewhere read, so this also seeds it from the dashboard's
    // your_groups (best-effort, capped at 5) unless we already know
    // something about this id.
    async fetchCurrent(id) {
      const { $api } = useNuxtApp()

      this.current.loading = true
      this.current.error = null

      try {
        const { data } = await $api.group.get(id)
        this.current.data = data
        this.details = { ...this.details, [id]: data }

        if (!this.mine.data.length && !this.memberIds.length) {
          await this.fetchMine().catch(() => {})
        }

        return data
      } catch (error) {
        this.current.error = error
        throw error
      } finally {
        this.current.loading = false
      }
    },

    // GET /api/v2/groups/{id}/stats (not yet implemented server-side - see
    // the class doc comment). Deliberately does not rethrow: a stats
    // failure shouldn't block the rest of the group view page.
    async fetchStats(id) {
      const { $api } = useNuxtApp()

      this.stats.loading = true
      this.stats.error = null

      try {
        const { data } = await $api.group.stats(id)
        this.stats.data = data
        return data
      } catch (error) {
        this.stats.error = error
        return null
      } finally {
        this.stats.loading = false
      }
    },

    async fetchEvents(id, params) {
      const { $api } = useNuxtApp()

      this.events.loading = true
      this.events.error = null

      try {
        const { data } = await $api.group.events(id, params)
        this.events.data = data
        return data
      } catch (error) {
        this.events.error = error
        throw error
      } finally {
        this.events.loading = false
      }
    },

    // Group members offered by the event invite modal's picker. Returns the
    // list rather than storing it: `volunteers` backs the group view page's
    // own list, and a modal opened from elsewhere must not overwrite it.
    // exclude_event drops members already confirmed at that event.
    async fetchMembersForInvite(id, excludeEvent) {
      const { $api } = useNuxtApp()
      const { data } = await $api.group.volunteers(id, { exclude_event: excludeEvent })
      return data || []
    },

    async fetchVolunteers(id) {
      const { $api } = useNuxtApp()

      this.volunteers.loading = true
      this.volunteers.error = null

      try {
        const { data } = await $api.group.volunteers(id)
        this.volunteers.data = data
        return data
      } catch (error) {
        this.volunteers.error = error
        throw error
      } finally {
        this.volunteers.loading = false
      }
    },

    // POST /api/v2/groups/{id}/invites (api-contracts-phase-b.md B2, not
    // yet implemented server-side). Returns {invites_sent, invalid} for the
    // modal to render; errors are left for the caller (GroupInviteModal.vue)
    // to show inline rather than as a toast, since this is a form submit.
    async inviteVolunteers(id, payload) {
      const { $api } = useNuxtApp()
      const { data } = await $api.group.invite(id, payload)
      return data
    },

    // DELETE /api/v2/groups/{id} - archive semantics. Only ever called when
    // permissions.can_perform_archive is true (enforced server-side too).
    async deleteGroup(id) {
      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.group.del(id)
        if (this.current.data && this.current.data.id === id) {
          this.current.data = { ...this.current.data, archived_at: new Date().toISOString() }
        }
        return data
      } catch (error) {
        useToastStore().error(error)
        throw error
      }
    },

    // DELETE /api/v2/groups/{id}/permanent - the hard delete GroupActions.vue
    // offers Administrators, removing the group and its events for good
    // rather than archiving. Drops the group from local state, since unlike
    // archive there is nothing left to view.
    async deleteGroupPermanently(id) {
      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.group.deletePermanently(id)

        this.mine.data = this.mine.data.filter((g) => g.id !== id)
        this.memberIds = this.memberIds.filter((mid) => mid !== id)
        if (this.current.data && this.current.data.id === id) {
          this.current.data = null
        }

        return data
      } catch (error) {
        useToastStore().error(error)
        throw error
      }
    },

    // Optimistically removes a volunteer row; reverts + toasts on failure.
    async removeVolunteer(id, iduser) {
      const previous = this.volunteers.data
      this.volunteers.data = previous.filter((v) => v.user !== iduser)

      const { $api } = useNuxtApp()

      try {
        await $api.group.removeVolunteer(id, iduser)
      } catch (error) {
        this.volunteers.data = previous
        useToastStore().error(error)
        throw error
      }
    },

    // Optimistically flips a volunteer's host flag; reverts + toasts on
    // failure.
    async setVolunteerHost(id, iduser, host) {
      const previous = this.volunteers.data
      this.volunteers.data = previous.map((v) => (v.user === iduser ? { ...v, host } : v))

      const { $api } = useNuxtApp()

      try {
        await $api.group.setVolunteerHost(id, iduser, host)
      } catch (error) {
        this.volunteers.data = previous
        useToastStore().error(error)
        throw error
      }
    },

    // POST /api/v2/groups (already implemented server-side -
    // API\GroupController::createGroupv2). Unlike almost every other v2
    // endpoint this returns a bare {id: ...}, not {data: {id: ...}} - see
    // GroupCreateTest.php. Errors (422 field validation, incl. geocode
    // failures - groups.geocode_failed) are left for GroupForm.vue to render
    // inline rather than toasted, since this is a form submit.
    async createGroup(payload) {
      const { $api } = useNuxtApp()
      const { id } = await $api.group.create(payload)
      return id
    },

    // PATCH /api/v2/groups/{id} (already implemented server-side -
    // API\GroupController::updateGroupv2). Same bare {id: ...} response
    // shape as createGroup. Invalidates the cached details/current entry so
    // other pages (group view, all-groups table) re-fetch fresh data next
    // time they're visited.
    async updateGroup(id, payload) {
      const { $api } = useNuxtApp()
      const { id: returnedId } = await $api.group.update(id, payload)
      delete this.details[id]
      if (this.current.data && this.current.data.id === id) {
        this.current.data = null
      }
      return returnedId
    },

    // GET /api/v2/groups/tags (already implemented server-side -
    // API\GroupController::listTagsv2). Role-filtered server-side.
    async fetchTags() {
      const { $api } = useNuxtApp()

      this.tags.loading = true
      this.tags.error = null

      try {
        const { data } = await $api.group.tags()
        this.tags.data = data
        return data
      } catch (error) {
        this.tags.error = error
        throw error
      } finally {
        this.tags.loading = false
      }
    },

    // GET /api/v2/groups/summary?ids=... (already implemented server-side -
    // API\GroupController::listSummaryv2, PR #887). Batched counterpart to
    // fetchDetails: hydrates every id in one call (chunked at the API's
    // 200-id cap) rather than one request per row - /group/map calls this
    // with whatever ids are currently visible on the map/list, same as the
    // legacy Vuex store's `hydrate` action.
    //
    // Skips ids already in summaryByIds (unless force) AND ids with a fetch
    // already in flight - the second guard matters here specifically
    // because panning the map can call this again before the previous
    // batch has returned, and without it the same ids would be requested
    // twice concurrently.
    async fetchSummaries(ids, { force = false } = {}) {
      const idsToFetch = [...new Set(ids)].filter(
        (id) => (force || !this.summaryByIds[id]) && !this.summaryLoading[id]
      )

      if (!idsToFetch.length) {
        return []
      }

      const loading = { ...this.summaryLoading }
      idsToFetch.forEach((id) => {
        loading[id] = true
      })
      this.summaryLoading = loading

      const { $api } = useNuxtApp()

      try {
        const results = []

        // The API caps ids at 200 per call (GroupController::listSummaryv2's
        // validation rule) - chunk rather than assume the caller already did.
        for (let i = 0; i < idsToFetch.length; i += 200) {
          const chunk = idsToFetch.slice(i, i + 200)
          const { data } = await $api.group.summary({
            ids: chunk.join(','),
            includeCounts: 'true',
            includeNextEvent: 'true',
          })
          results.push(...data)
        }

        const merged = { ...this.summaryByIds }
        results.forEach((summary) => {
          merged[summary.id] = summary
        })
        this.summaryByIds = merged

        return results
      } finally {
        const doneLoading = { ...this.summaryLoading }
        idsToFetch.forEach((id) => {
          delete doneLoading[id]
        })
        this.summaryLoading = doneLoading
      }
    },

    // POST /api/v2/groups/{id}/images (api-contracts-phase-b.md B2, not yet
    // implemented server-side - see docs/nuxt-migration/api-gaps.md B6).
    // Called with the upload_key emitted by TusImageUpload.vue once a tus
    // upload completes.
    async uploadGroupImage(id, uploadKey) {
      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.group.uploadImage(id, uploadKey)
        if (this.current.data && this.current.data.id === id && data?.image_url) {
          this.current.data = { ...this.current.data, image: data.image_url }
        }
        return data
      } catch (error) {
        useToastStore().error(error)
        throw error
      }
    },
  },
})
