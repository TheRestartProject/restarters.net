import BaseAPI from './BaseAPI.js'

/**
 * /api/v2/skills (API\SkillController, already implemented server-side -
 * design.md §6.2 Phase D task D4, PR #863's SkillsPage.vue is the
 * functional spec). List/get are public; create/update/delete require
 * Administrator. `category` is an integer (1 = Organising, 2 = Technical -
 * App\Helpers\Fixometer::skillCategories()).
 */
export default class SkillAPI extends BaseAPI {
  list() {
    return this.$get('/api/v2/skills')
  }

  get(id) {
    return this.$get(`/api/v2/skills/${id}`)
  }

  create(payload) {
    return this.$post('/api/v2/skills', payload)
  }

  update(id, payload) {
    return this.$put(`/api/v2/skills/${id}`, payload)
  }

  del(id) {
    return this.$del(`/api/v2/skills/${id}`)
  }
}
