<script setup>
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useSsoBridge } from '~/composables/useSsoBridge.js'

// Legacy source: resources/js/components/DiscourseDiscussion.vue - fetches
// GET /api/talk/topics/{tag?} (App\Http\Controllers\API\DiscourseController::
// discussionTopics, routes/api.php, unauthenticated - still live) and lists
// the top Talk topics, "see all" linking to `${DISCOURSE_URL}/latest`. Wiring
// live topics needs a new API resource class + $api plugin registration
// (client/app/api/, client/app/plugins/), both outside this component's
// permitted scope for this pass - see the dashboard-rebuild report. This
// renders the section header, dashed divider and "see all" link faithfully;
// that's also exactly what the legacy page itself shows whenever Discourse
// has no cached topics (feature flag off, or a cold cache).
const { t } = useI18n()
const sessionStore = useSessionStore()
const { goTo } = useSsoBridge()

function seeAll() {
  const base = sessionStore.config?.discourse_url
  goTo(base ? `${base}/latest` : null)
}
</script>

<template>
  <div data-testid="dashboard-whats-happening">
    <div class="d-flex align-items-center">
      <h2 class="mb-0">{{ t('dashboard.whats_happening_heading') }}</h2>
      <!-- Desktop-only heading doodle, matching legacy DiscourseDiscussion.vue
           (d-none d-md-block) and the other dashboard section headings
           (Your Groups' group_doodle_ico). -->
      <img src="/images/talk_doodle.svg" alt="" class="talk-doodle ms-3 d-none d-md-block">
    </div>

    <div class="content-divider">
      <div class="d-flex justify-content-end">
        <a href="#" data-testid="whats-happening-see-all" @click.prevent="seeAll">
          {{ t('dashboard.whats_happening_see_all') }}
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
h2 {
  font-size: 1.1rem;
  font-weight: bold;
}

.talk-doodle {
  height: 40px;
}
</style>
