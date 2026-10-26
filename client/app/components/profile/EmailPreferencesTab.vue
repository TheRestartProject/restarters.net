<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'
import { useSessionStore } from '~/stores/session.js'

// GET/PATCH /api/v2/users/me/preferences (own profile) or GET/PATCH
// /api/v2/users/{id}/preferences (gap 5 - an Administrator editing someone
// else's, via profileStore.fetchUserEmailPreferences/
// updateUserEmailPreferences). Functional spec:
// resources/js/components/EmailPreferencesTab.vue +
// resources/views/user/profile/email-preferences.blade.php. See
// stores/profile.js's class doc comment for why the two are kept as
// separate store actions/state slots.
const props = defineProps({
  targetId: {
    type: Number,
    default: null,
  },
  isOwnProfile: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()
const profileStore = useProfileStore()
const sessionStore = useSessionStore()

const invites = ref(false)
const loading = ref(true)
const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')

// PLATFORM_COMMUNITY_URL isn't part of GET /api/v2/session's config today
// (config.discourse_url is the base Discourse origin) - built the same way
// the legacy Blade partial did, from that base URL.
const platformPreferencesUrl = `${sessionStore.config?.discourse_url || ''}/u/${sessionStore.user?.username || ''}/preferences/emails`

// Watches (not a plain onMounted) - same convention as ProfileInfoTab.vue's
// own targetId/isOwnProfile watcher.
watch(
  () => [props.isOwnProfile, props.targetId],
  async ([isOwnProfile, targetId]) => {
    loading.value = true
    try {
      const data = isOwnProfile
        ? await profileStore.fetchEmailPreferences()
        : await profileStore.fetchUserEmailPreferences(targetId)
      invites.value = !!data.invites
    } catch {
      // Load error is rendered by the retry state below.
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

async function save() {
  saving.value = true
  feedback.value = ''

  try {
    const data = props.isOwnProfile
      ? await profileStore.updateEmailPreferences({ invites: !!invites.value })
      : await profileStore.updateUserEmailPreferences(props.targetId, { invites: !!invites.value })
    invites.value = !!data.invites
    feedback.value = t('profile.preferences_updated')
    feedbackVariant.value = 'success'
  } catch {
    feedback.value = t('general.error_occurred')
    feedbackVariant.value = 'danger'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="edit-panel" data-testid="email-preferences-tab">
    <h4>{{ t('general.email_alerts') }}</h4>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <p v-html="t('general.email_alerts_text')" />

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="email-preferences-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BForm v-if="!loading" data-testid="email-preferences-form" @submit.prevent="save">
      <BFormCheckbox id="invites" v-model="invites" data-testid="email-preferences-invites">
        {{ t('general.email_alerts_pref2') }}
      </BFormCheckbox>

      <div class="button-group row mt-3">
        <div class="col-sm-9 d-flex align-items-center justify-content-start">
          <a class="btn-preferences" :href="platformPreferencesUrl">{{ t('auth.set_preferences') }}</a>
        </div>
        <div class="col-sm-3 d-flex align-items-center justify-content-end">
          <BButton type="submit" variant="primary" class="btn-save" :disabled="saving" data-testid="email-preferences-save">
            {{ t('auth.save_preferences') }}
          </BButton>
        </div>
      </div>
    </BForm>
  </div>
</template>
