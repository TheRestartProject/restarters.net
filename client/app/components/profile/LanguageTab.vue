<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// GET/PATCH /api/v2/users/me/language. Functional spec:
// resources/js/components/LanguageTab.vue +
// resources/views/user/profile/account.blade.php. Always operates on
// Auth::user() - see stores/profile.js's class doc comment for why this
// tab is only ever shown while editing one's own profile.
const { t } = useI18n()
const profileStore = useProfileStore()

const selected = ref(null)
const loading = ref(true)
const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')

const current = computed(() => profileStore.language.data?.language ?? null)
const supported = computed(() => profileStore.language.data?.supported || [])

onMounted(async () => {
  try {
    const data = await profileStore.fetchLanguage()
    selected.value = data.language
  } catch {
    // Load error leaves the select empty; the form stays unusable, matching
    // the legacy component's silent-fail-to-console behaviour.
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  feedback.value = ''

  try {
    await profileStore.updateLanguage({ language: selected.value })
    feedback.value = t('profile.language_updated')
    feedbackVariant.value = 'success'
    // Locale change should apply everywhere - mirrors the legacy tab's
    // full-page reload (setTimeout(() => window.location.reload(), 800)).
    setTimeout(() => window.location.reload(), 800)
  } catch {
    feedback.value = t('general.error_occurred')
    feedbackVariant.value = 'danger'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="edit-panel" data-testid="language-tab">
    <h4>{{ t('profile.language_panel_title') }}</h4>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="language-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BForm data-testid="language-form" @submit.prevent="save">
      <BFormGroup :label="t('profile.preferred_language')" label-for="language-select">
        <BFormSelect id="language-select" v-model="selected" :disabled="loading" data-testid="language-select">
          <option v-for="opt in supported" :key="opt.code" :value="opt.code">{{ opt.native }}</option>
        </BFormSelect>
      </BFormGroup>

      <div class="d-flex justify-content-end">
        <BButton type="submit" variant="primary" :disabled="saving || loading || selected === current" data-testid="language-save">
          {{ t('partials.save') }}
        </BButton>
      </div>
    </BForm>
  </div>
</template>
