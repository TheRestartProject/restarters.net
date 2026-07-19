import BaseAPI from './BaseAPI.js'

// GET/POST /api/v2/admin/preview-deploys (API\PreviewDeployController,
// Administrator-only). Moved off the Blade admin/preview-deploy web route so
// the bearer-authenticated SPA admin can drive it (the Blade page needed a web
// session the post-cutover SPA user doesn't have).
export default class PreviewDeployAPI extends BaseAPI {
  list() {
    return this.$get('/api/v2/admin/preview-deploys')
  }

  deploy(branch) {
    return this.$post('/api/v2/admin/preview-deploys', { branch })
  }
}
