import BaseAPI from './BaseAPI.js'

/**
 * Restarters Talk (Discourse) read surface.
 *
 * GET /api/talk/topics/{tag?} (App\Http\Controllers\API\DiscourseController::
 * discussionTopics) - public, unauthenticated, cached 60s server-side. Returns
 * { success, topics } where topics are the top ~5 Discourse /latest.json topic
 * objects (title / slug / posts_count / created_at, each with an attached
 * `category`). Returns an empty list when the discourse_integration feature
 * flag is off or Discourse is unreachable, so callers must tolerate [].
 */
export default class TalkAPI extends BaseAPI {
  topics(tag = null) {
    return this.$get(tag ? `/api/talk/topics/${tag}` : '/api/talk/topics')
  }
}
