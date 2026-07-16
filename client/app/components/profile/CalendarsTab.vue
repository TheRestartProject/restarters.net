<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'
import { useSessionStore } from '~/stores/session.js'

// GET /api/v2/users/me/calendars. Functional spec:
// resources/js/components/CalendarsTab.vue +
// resources/views/user/profile/calendars.blade.php. Read-only (no save
// action) - always operates on Auth::user(), see stores/profile.js's class
// doc comment for why this tab is only ever shown while editing one's own
// profile.
const { t } = useI18n()
const profileStore = useProfileStore()
const sessionStore = useSessionStore()

const loading = ref(true)
const selectedArea = ref('')

// The legacy find-out-more link deep-links into Discourse SSO with a
// return_path back to the calendar-feed help article (general.calendar_
// feed_help_url, a path under the same origin as DISCOURSE_URL). Built the
// same way from the session's discourse_url config, since there's no
// dedicated field for it in GET /api/v2/session.
const findOutMoreUrl = computed(() => {
  const base = sessionStore.config?.discourse_url || ''
  return `${base}/session/sso?return_path=${base}${t('general.calendar_feed_help_url')}`
})

const areaUrl = computed(() =>
  selectedArea.value ? `/calendar/group-area/${encodeURIComponent(selectedArea.value)}` : '',
)

onMounted(async () => {
  try {
    const data = await profileStore.fetchCalendars()
    if (data.group_areas?.length) {
      selectedArea.value = data.group_areas[0]
    }
  } catch {
    // Load error is rendered by the retry state below.
  } finally {
    loading.value = false
  }
})

function copy(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text)
  }
}
</script>

<template>
  <div data-testid="calendars-tab">
    <h3>{{ t('profile.calendars.title') }}</h3>
    <p>
      {{ t('profile.calendars.explainer') }}
      <a :href="findOutMoreUrl">{{ t('profile.calendars.find_out_more') }}</a>
    </p>

    <BAlert v-if="profileStore.calendars.error" :model-value="true" variant="danger" data-testid="calendars-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <template v-else-if="!loading && profileStore.calendars.data">
      <h5>{{ t('profile.calendars.my_events') }}</h5>
      <div class="input-group mb-4">
        <input type="text" class="form-control" readonly :value="profileStore.calendars.data.user_url" data-testid="calendars-user-url">
        <BButton variant="primary" @click="copy(profileStore.calendars.data.user_url)">{{ t('profile.calendars.copy_link') }}</BButton>
      </div>

      <h5>{{ t('profile.calendars.group_calendars') }}</h5>
      <template v-for="g in profileStore.calendars.data.groups" :key="g.id">
        <p class="mb-2">{{ g.name }}</p>
        <div class="input-group mb-4">
          <input type="text" class="form-control" readonly :value="g.url">
          <BButton variant="primary" @click="copy(g.url)">{{ t('profile.calendars.copy_link') }}</BButton>
        </div>
      </template>

      <template v-if="profileStore.calendars.data.is_admin && profileStore.calendars.data.admin_all_events_url">
        <h5>{{ t('profile.calendars.all_events') }}</h5>
        <div class="input-group mb-4">
          <input type="text" class="form-control" readonly :value="profileStore.calendars.data.admin_all_events_url">
          <BButton variant="primary" @click="copy(profileStore.calendars.data.admin_all_events_url)">{{ t('profile.calendars.copy_link') }}</BButton>
        </div>
      </template>

      <h5>{{ t('profile.calendars.events_by_area') }}</h5>
      <div class="input-group mb-3">
        <select v-model="selectedArea" class="form-control" data-testid="calendars-area-select">
          <option v-for="area in profileStore.calendars.data.group_areas" :key="area" :value="area">{{ area }}</option>
        </select>
        <input v-if="selectedArea" type="text" class="form-control" readonly :value="areaUrl">
        <BButton variant="primary" :disabled="!selectedArea" @click="copy(areaUrl)">{{ t('profile.calendars.copy_link') }}</BButton>
      </div>
    </template>
  </div>
</template>
