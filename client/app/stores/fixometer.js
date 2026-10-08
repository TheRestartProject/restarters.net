import { defineStore } from 'pinia'

/**
 * Backs the /fixometer home page (api-contracts-phase-c.md C6b; design.md
 * §6.2 C6 task brief). Functional spec: resources/views/fixometer/
 * index.blade.php + FixometerPage/FixometerGlobalImpact/
 * FixometerLatestData.vue.
 *
 * `impactData` wraps GET /api/homepage_data (v1, unauthenticated, no
 * {data:...} envelope - the body IS the payload, see ConfigAPI#homepageData
 * doc comment). `latestRepaired` wraps GET /api/v2/stats/
 * latest-repaired-event (C6b, NEW, public) - a nullable {id,
 * waste_prevented, group} for the "X just fixed Y kg of waste" banner;
 * `data` is `null` both while unloaded and when the server genuinely has no
 * finished-event-with-repairs to report (components/fixometer/
 * LatestRepairs.vue hides the banner in either case, distinguished by
 * `loading`/`loaded`).
 *
 * Both are simple singleton {data, loading, loaded, error} entries (not
 * keyed by anything - only one fixometer home is ever on screen), fetched
 * once and cached, same `loaded` guard convention as this store's
 * itemTypes/categories/brands/options.
 */
export const useFixometerStore = defineStore('fixometer', {
  state: () => ({
    impactData: { data: null, loading: false, loaded: false, error: null },
    latestRepaired: { data: null, loading: false, loaded: false, error: null },
  }),

  actions: {
    async fetchImpactData({ force = false } = {}) {
      if (!force && this.impactData.loaded) return this.impactData.data

      this.impactData.loading = true
      this.impactData.error = null

      const { $api } = useNuxtApp()

      try {
        const data = await $api.config.homepageData()
        this.impactData.data = data
        this.impactData.loaded = true
        return data
      } catch (error) {
        this.impactData.error = error
        throw error
      } finally {
        this.impactData.loading = false
      }
    },

    async fetchLatestRepaired({ force = false } = {}) {
      if (!force && this.latestRepaired.loaded) return this.latestRepaired.data

      this.latestRepaired.loading = true
      this.latestRepaired.error = null

      const { $api } = useNuxtApp()

      try {
        const { data } = await $api.device.latestRepairedEvent()
        this.latestRepaired.data = data
        this.latestRepaired.loaded = true
        return data
      } catch (error) {
        this.latestRepaired.error = error
        throw error
      } finally {
        this.latestRepaired.loading = false
      }
    },
  },
})
