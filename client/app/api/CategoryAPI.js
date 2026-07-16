import BaseAPI from './BaseAPI.js'

/**
 * /api/v2/categories + /api/v2/category-clusters (API\CategoryController,
 * already implemented server-side - design.md §6.2 Phase D task D4, PR
 * #863's CategoriesPage.vue is the functional spec). Unlike its siblings
 * (brands/skills/group-tags) this resource is list+update ONLY - no
 * create/delete route exists server-side (confirmed by reading
 * routes/api.php: only GET '/', GET '{id}' and PUT '{id}' under
 * /categories), matching the legacy CategoriesPage.vue's
 * `:allow-create="false" :allow-delete="false"`. List/get/clusters are
 * public; update requires Administrator.
 */
export default class CategoryAPI extends BaseAPI {
  list() {
    return this.$get('/api/v2/categories')
  }

  get(id) {
    return this.$get(`/api/v2/categories/${id}`)
  }

  update(id, payload) {
    return this.$put(`/api/v2/categories/${id}`, payload)
  }

  // GET /api/v2/category-clusters - {id, name}[], populates the cluster
  // dropdown on the edit form.
  clusters() {
    return this.$get('/api/v2/category-clusters')
  }
}
