import BaseAPI from './BaseAPI.js'

/**
 * Admin-configured informational alerts, shown as dismissible banners above
 * the events list (design.md's Nuxt gap-closure pass).
 *
 * GET /api/v2/alerts (App\Http\Controllers\API\AlertController::listAlertsv2)
 * - public, unauthenticated, cached server-side for 2h. Returns
 * { data: [...] } of currently-active alerts (the server already filters to
 * start <= now <= end); each alert is
 * {id, title, html, ctatitle, ctalink, start, end, variant}.
 *
 * There is no per-user dismiss endpoint: PUT/PATCH on this resource
 * (addAlertv2/updateAlertv2) are Administrator-only "create/edit this alert"
 * actions, not a dismiss. The legacy client (resources/js/components/
 * AlertBanner.vue) dismissed purely client-side via localStorage, which
 * components/alerts/AlertsBanner.vue reproduces - so this wrapper only needs
 * the read side.
 */
export default class AlertsAPI extends BaseAPI {
  list() {
    return this.$get('/api/v2/alerts')
  }
}
