<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useSsoBridge } from '~/composables/useSsoBridge.js'
import DashboardTalkTopic from '~/components/dashboard/DashboardTalkTopic.vue'
import CollapsibleSection from '~/components/CollapsibleSection.vue'

// Legacy source: resources/js/components/DiscourseDiscussion.vue - lists the top
// Talk topics from GET /api/talk/topics (DiscourseController::discussionTopics,
// public, cached 60s server-side), "see all" linking to `${DISCOURSE_URL}/latest`.
// The endpoint returns [] when the discourse_integration flag is off or Discourse
// has no cached topics, in which case only the header/divider/see-all show - the
// same empty state the legacy page had.
const { t } = useI18n()
const sessionStore = useSessionStore()
const { goTo } = useSsoBridge()

const topics = ref([])
const discourseBaseUrl = computed(() => sessionStore.config?.discourse_url || '')

onMounted(async () => {
  try {
    const { $api } = useNuxtApp()
    const res = await $api.talk.topics()
    if (res?.success) {
      topics.value = res.topics || []
    }
  } catch {
    // Leave topics empty - the section degrades to the header/divider/see-all
    // state, exactly as legacy did when Discourse was unavailable.
  }
})

function seeAll() {
  const base = sessionStore.config?.discourse_url
  goTo(base ? `${base}/latest` : null)
}
</script>

<template>
  <CollapsibleSection data-testid="dashboard-whats-happening">
    <template #title>
      <div class="d-flex align-items-center">
        <h2 class="mb-0">{{ t('dashboard.whats_happening_heading') }}</h2>
        <!-- Desktop-only heading doodle (d-none d-md-block), matching the other
             dashboard section headings (Your Groups' group_doodle_ico). -->
        <img src="/images/talk_doodle.svg" alt="" class="talk-doodle ms-3 d-none d-md-block">
      </div>
    </template>

    <div class="content-divider">
      <table class="table talk-topics" data-testid="whats-happening-topics">
        <thead>
          <tr>
            <th />
            <th class="d-none d-md-table-cell text-center">
              <img src="/images/speech_bubble.svg" class="talk-topics__icon" :alt="t('discourse.number_of_comments')" :title="t('discourse.number_of_comments')">
            </th>
            <th class="d-none d-md-table-cell text-center">
              <img src="/images/clock.svg" class="talk-topics__icon" :alt="t('discourse.topic_created_at')" :title="t('discourse.topic_created_at')">
            </th>
          </tr>
        </thead>
        <tbody>
          <DashboardTalkTopic
            v-for="topic in topics"
            :key="topic.id"
            :topic="topic"
            :discourse-base-url="discourseBaseUrl"
          />
        </tbody>
      </table>

      <div class="d-flex justify-content-end">
        <a href="#" data-testid="whats-happening-see-all" @click.prevent="seeAll">
          {{ t('dashboard.whats_happening_see_all') }}
        </a>
      </div>
    </div>
  </CollapsibleSection>
</template>

<style scoped>
h2 {
  font-size: 1.1rem;
  font-weight: bold;
}

.talk-doodle {
  height: 40px;
}

.talk-topics {
  margin-bottom: 0.5rem;
}

.talk-topics__icon {
  height: 28px;
}
</style>
