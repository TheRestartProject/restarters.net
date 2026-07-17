import { defineStore } from 'pinia'
import { useToastStore } from './toast.js'

// Fields the read (Device resource) and write (validateDeviceParams)
// shapes agree on scalar-for-scalar - safe to optimistically splat straight
// from the submitted payload onto the cached row while a create/update
// request is in flight. `category` is deliberately excluded: the payload
// carries a bare category id, but the read shape carries a full
// {id, name, powered, ...} Category object (Http\Resources\Category) - a
// bad optimistic value there would show the wrong category name/tick
// column until the real response lands, so `category` is left untouched
// until the server responds.
const OPTIMISTIC_FIELDS = [
  'item_type',
  'brand',
  'model',
  'age',
  'estimate',
  'problem',
  'notes',
  'repair_status',
  'next_steps',
  'spare_parts',
  'barrier',
]

function optimisticPatch(payload) {
  const patch = {}
  for (const field of OPTIMISTIC_FIELDS) {
    if (field in payload) patch[field] = payload[field]
  }
  return patch
}

// Groups the flat GET /api/v2/categories rows (each carrying `cluster`
// id + `cluster_name`) under the GET /api/v2/category-clusters headers -
// api-contracts-phase-c.md C5: "no endpoint returns that [nested] shape ...
// group client-side rather than adding a third shape for the same data."
// Matches the {cluster: [...] } nesting components/devices/DeviceForm.vue's
// category <select> (and the legacy DeviceCategorySelect.vue/
// useDeviceCategorySuggestion.js it's ported from) expects.
function buildClusters(categories, clusterHeaders) {
  const order = clusterHeaders.length ? clusterHeaders : []
  const byId = new Map()

  for (const header of order) {
    byId.set(header.id, { id: header.id, name: header.name, categories: [] })
  }

  for (const cat of categories) {
    if (cat.cluster === null || cat.cluster === undefined) continue

    if (!byId.has(cat.cluster)) {
      // A category references a cluster id that GET /api/v2/category-clusters
      // didn't return (shouldn't normally happen) - fall back to the
      // joined cluster_name so the category isn't silently dropped.
      byId.set(cat.cluster, { id: cat.cluster, name: cat.cluster_name || '', categories: [] })
    }

    byId.get(cat.cluster).categories.push({
      idcategories: cat.id,
      name: cat.name,
      powered: cat.powered,
    })
  }

  return order.length
    ? order.map((header) => byId.get(header.id)).filter((cluster) => cluster.categories.length)
    : [...byId.values()].filter((cluster) => cluster.categories.length)
}

/**
 * Device CRUD (api-contracts-phase-c.md C5; design.md §6.2 C5 task brief).
 * Functional spec: resources/js/store/devices.js +
 * resources/js/components/EventDevice.vue/EventDeviceSummary.vue/
 * EventDeviceList.vue/EventDevices.vue.
 *
 * `byEvent[eventId]` is a {data, loading, error} device list, fetched via
 * the same GET /api/v2/events/{id}/devices endpoint stores/events.js's
 * `devices`/`fetchDevices` (C3) already wraps - this store is the one the
 * *editable* device section (components/devices/EventDevicesPanel.vue) and
 * the party/view/[id] page now use; stores/events.js's `devices` field is
 * left in place (still covered by its own tests) rather than deleted, but
 * the page no longer calls `fetchDevices` since that would be a second,
 * redundant fetch of the same list against a second copy of the state.
 *
 * `addDevice`/`updateDevice`/`deleteDevice` optimistically patch/remove the
 * cached row (see OPTIMISTIC_FIELDS above), reverting + surfacing a toast
 * on failure - same pattern as stores/groups.js#join()/leave() and
 * stores/events.js#attend()/unattend().
 *
 * Item types, categories/clusters, brands and the barrier/spare_parts/
 * next_steps option enums (api-contracts-phase-c.md C5) rarely change, so
 * each is fetched once and cached (`loaded` flag) - mirrors the legacy
 * items.js Vuex module's "don't refetch if we already have them" guard.
 */
export const useDevicesStore = defineStore('devices', {
  state: () => ({
    byEvent: {},

    itemTypes: { data: [], loading: false, loaded: false, error: null },
    categories: { data: [], loading: false, loaded: false, error: null },
    clusterHeaders: { data: [], loading: false, loaded: false, error: null },
    brands: { data: [], loading: false, loaded: false, error: null },
    options: { data: { barriers: [], spare_parts: [], next_steps: [] }, loading: false, loaded: false, error: null },

    // /device/search (C6a) - a single page of GET /api/v2/devices results,
    // not keyed by anything (only one search is ever on screen at a time,
    // same reasoning as stores/groups.js's `current`/`stats`).
    searchResults: { data: [], count: 0, loading: false, error: null },
  }),

  getters: {
    list: (state) => (eventId) => state.byEvent[eventId]?.data || [],
    listLoading: (state) => (eventId) => !!state.byEvent[eventId]?.loading,
    listError: (state) => (eventId) => state.byEvent[eventId]?.error || null,

    clusters: (state) => buildClusters(state.categories.data, state.clusterHeaders.data),
  },

  actions: {
    async fetchForEvent(eventId, { force = false } = {}) {
      if (!force && this.byEvent[eventId]?.loaded) {
        return this.byEvent[eventId].data
      }

      // Assign the fallback into state FIRST, then read it back: the
      // read-back is the reactive proxy, whereas mutating the plain object
      // after assignment bypasses subscribers entirely (the event page's
      // devices panel then never left its loading skeleton - see the
      // "notifies reactive subscribers" spec).
      if (!this.byEvent[eventId]) {
        this.byEvent[eventId] = { data: [], loading: false, error: null, loaded: false }
      }
      const entry = this.byEvent[eventId]
      entry.loading = true
      entry.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.event.devices(eventId)
        entry.data = data
        entry.loaded = true
        return data
      } catch (error) {
        entry.error = error
        throw error
      } finally {
        entry.loading = false
      }
    },

    // POST /api/v2/devices. `payload` is the full device payload (see
    // DeviceForm.vue's buildPayload()) minus `eventid`, which this action
    // adds. Pushes an optimistic placeholder row (negative id, like the
    // legacy add flow's draft ids) immediately, then reconciles with the
    // server's real row on success or drops it on failure.
    async addDevice(eventId, payload) {
      // Same assign-then-read-back as fetchForEvent: `entry` must be the
      // reactive proxy, not the plain fallback object.
      if (!this.byEvent[eventId]) {
        this.byEvent[eventId] = { data: [], loading: false, error: null, loaded: true }
      }
      const entry = this.byEvent[eventId]

      const tempId = -(Date.now() + Math.floor(Math.random() * 1000))
      const optimisticDevice = {
        id: tempId,
        eventid: eventId,
        category: { id: payload.category, name: '', powered: null },
        images: [],
        ...optimisticPatch(payload),
      }
      entry.data = [...entry.data, optimisticDevice]

      const { $api } = useNuxtApp()

      try {
        const full = { ...payload, eventid: eventId }
        const { device, stats } = await $api.device.create(full)
        entry.data = entry.data.map((d) => (d.id === tempId ? device : d))
        return { device, stats }
      } catch (error) {
        entry.data = entry.data.filter((d) => d.id !== tempId)
        useToastStore().error(error)
        throw error
      }
    },

    // PATCH /api/v2/devices/{id}.
    async updateDevice(eventId, deviceId, payload) {
      const entry = this.byEvent[eventId]
      const previous = entry ? entry.data.find((d) => d.id === deviceId) : null

      if (entry && previous) {
        const patch = optimisticPatch(payload)
        entry.data = entry.data.map((d) => (d.id === deviceId ? { ...d, ...patch } : d))
      }

      const { $api } = useNuxtApp()

      try {
        const full = { ...payload, eventid: eventId }
        const { device, stats } = await $api.device.update(deviceId, full)
        if (entry) {
          entry.data = entry.data.map((d) => (d.id === deviceId ? device : d))
        }
        return { device, stats }
      } catch (error) {
        if (entry && previous) {
          entry.data = entry.data.map((d) => (d.id === deviceId ? previous : d))
        }
        useToastStore().error(error)
        throw error
      }
    },

    // DELETE /api/v2/devices/{id}.
    async deleteDevice(eventId, deviceId) {
      const entry = this.byEvent[eventId]
      const index = entry ? entry.data.findIndex((d) => d.id === deviceId) : -1
      const previous = index !== -1 ? entry.data[index] : null

      if (entry && index !== -1) {
        entry.data = entry.data.filter((d) => d.id !== deviceId)
      }

      const { $api } = useNuxtApp()

      try {
        const { stats } = await $api.device.del(deviceId)
        return stats
      } catch (error) {
        if (entry && previous) {
          const restored = [...entry.data]
          restored.splice(index, 0, previous)
          entry.data = restored
        }
        useToastStore().error(error)
        throw error
      }
    },

    // POST /api/v2/devices/{id}/images. The response only carries
    // {image_url}, not the new image's id/idxref (needed to delete it
    // again) - api-gaps.md Phase C - so this force-refetches the event's
    // device list afterwards rather than trying to optimistically patch a
    // single device's `images` array.
    async uploadDeviceImage(eventId, deviceId, uploadKey) {
      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.uploadImage(deviceId, uploadKey)
        await this.fetchForEvent(eventId, { force: true })
        return data
      } catch (error) {
        useToastStore().error(error)
        throw error
      }
    },

    // DELETE /api/v2/devices/{id}/images/{idxref}.
    async deleteDeviceImage(eventId, deviceId, idxref) {
      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.deleteImage(deviceId, idxref)
        await this.fetchForEvent(eventId, { force: true })
        return data
      } catch (error) {
        useToastStore().error(error)
        throw error
      }
    },

    // GET /api/v2/devices (C6a). `params` is passed straight through to the
    // API (page/size/sortBy/sortDesc/powered/category/brand/model/
    // item_type/status/comments/wiki/group/from_date/to_date) - see
    // components/fixometer/DevicesSearchTable.vue for how the filter form
    // builds it. Unlike byEvent's fetchForEvent(), this always refetches
    // (no `loaded` guard) since every call represents a new search/page.
    async searchDevices(params) {
      this.searchResults.loading = true
      this.searchResults.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.search(params)
        this.searchResults.data = data.items
        this.searchResults.count = data.count
        return data
      } catch (error) {
        this.searchResults.error = error
        throw error
      } finally {
        this.searchResults.loading = false
      }
    },

    async fetchItemTypes({ force = false } = {}) {
      if (!force && this.itemTypes.loaded) return this.itemTypes.data

      this.itemTypes.loading = true
      this.itemTypes.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.itemTypes()
        this.itemTypes.data = data
        this.itemTypes.loaded = true
        return data
      } catch (error) {
        this.itemTypes.error = error
        throw error
      } finally {
        this.itemTypes.loading = false
      }
    },

    async fetchCategories({ force = false } = {}) {
      if (!force && this.categories.loaded) return this.categories.data

      this.categories.loading = true
      this.categories.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.categories()
        this.categories.data = data
        this.categories.loaded = true
        return data
      } catch (error) {
        this.categories.error = error
        throw error
      } finally {
        this.categories.loading = false
      }
    },

    async fetchClusterHeaders({ force = false } = {}) {
      if (!force && this.clusterHeaders.loaded) return this.clusterHeaders.data

      this.clusterHeaders.loading = true
      this.clusterHeaders.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.categoryClusters()
        this.clusterHeaders.data = data
        this.clusterHeaders.loaded = true
        return data
      } catch (error) {
        this.clusterHeaders.error = error
        throw error
      } finally {
        this.clusterHeaders.loading = false
      }
    },

    async fetchBrands({ force = false } = {}) {
      if (!force && this.brands.loaded) return this.brands.data

      this.brands.loading = true
      this.brands.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.brands()
        this.brands.data = data
        this.brands.loaded = true
        return data
      } catch (error) {
        this.brands.error = error
        throw error
      } finally {
        this.brands.loading = false
      }
    },

    async fetchOptions({ force = false } = {}) {
      if (!force && this.options.loaded) return this.options.data

      this.options.loading = true
      this.options.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.options()
        this.options.data = data
        this.options.loaded = true
        return data
      } catch (error) {
        this.options.error = error
        throw error
      } finally {
        this.options.loading = false
      }
    },

    // Loads every cached metadata list DeviceForm.vue needs, in parallel.
    // Individual failures don't block the others - DeviceForm degrades to
    // an empty options list rather than a hard error (item type/category/
    // brand fields still work as plain text entry either way).
    async ensureMetaLoaded() {
      const results = await Promise.allSettled([
        this.fetchItemTypes(),
        this.fetchCategories(),
        this.fetchClusterHeaders(),
        this.fetchBrands(),
        this.fetchOptions(),
      ])
      return results
    },
  },
})
