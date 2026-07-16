<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'
import { useSessionStore } from '~/stores/session.js'

// GET/PATCH /api/v2/users/me/preferences. Functional spec:
// resources/js/components/EmailPreferencesTab.vue +
// resources/views/user/profile/email-preferences.blade.php. Always
// operates on Auth::user() - see stores/profile.js's class doc comment for
// why this tab is only ever shown while editing one's own profile.
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

onMounted(async () => {
  try {
    const data = await profileStore.fetchEmailPreferences()
    invites.value = !!data.invites
  } catch {
    // Load error is rendered by the retry state below.
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  feedback.value = ''

  try {
    const data = await profileStore.updateEmailPreferences({ invites: !!invites.value })
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
  <div data-testid="email-preferences-tab">
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

      <div class="d-flex justify-content-between align-items-center mt-3">
        <a class="btn-preferences" :href="platformPreferencesUrl">{{ t('auth.set_preferences') }}</a>
        <BButton type="submit" variant="primary" :disabled="saving" data-testid="email-preferences-save">
          {{ t('auth.save_preferences') }}
        </BButton>
      </div>
    </BForm>
  </div>
</template>
