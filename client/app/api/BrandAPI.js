import BaseAPI from './BaseAPI.js'

/**
 * /api/v2/brands (API\BrandController, already implemented server-side -
 * design.md §6.2 Phase D task D4, PR #863's BrandsPage.vue is the
 * functional spec). List/get are public; create/update/delete require
 * Administrator (403 JSON otherwise) - enforced server-side, the
 * Administrator-only page gating is UX-level only (design.md §4.4).
 */
export default class BrandAPI extends BaseAPI {
  list() {
    return this.$get('/api/v2/brands')
  }

  get(id) {
    return this.$get(`/api/v2/brands/${id}`)
  }

  create(payload) {
    return this.$post('/api/v2/brands', payload)
  }

  update(id, payload) {
    return this.$put(`/api/v2/brands/${id}`, payload)
  }

  del(id) {
    return this.$del(`/api/v2/brands/${id}`)
  }
}
