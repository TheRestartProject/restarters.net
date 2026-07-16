<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// GET/PATCH /api/v2/users/me/skills. Functional spec:
// resources/js/components/SkillsTab.vue +
// resources/views/user/profile/profile.blade.php. Always operates on
// Auth::user() - see stores/profile.js's class doc comment for why this
// tab is only ever shown while editing one's own profile.
const { t } = useI18n()
const profileStore = useProfileStore()

const selected = ref([])
const loading = ref(true)
const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')

onMounted(async () => {
  try {
    const data = await profileStore.fetchSkills()
    selected.value = data.selected
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
    const data = await profileStore.updateSkills({ tags: selected.value })
    selected.value = data.tags
    feedback.value = t('profile.skills_updated')
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
  <div data-testid="skills-tab">
    <h4>{{ t('general.repair_skills') }}</h4>
    <p>{{ t('general.repair_skills_content') }}</p>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="skills-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BAlert v-if="profileStore.skills.error" :model-value="true" variant="danger" data-testid="skills-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <BForm v-else-if="!loading" data-testid="skills-form" @submit.prevent="save">
      <BFormGroup :label="`${t('general.your_repair_skills')}:`" label-for="skills-select">
        <select id="skills-select" v-model="selected" class="form-control" multiple size="10" data-testid="skills-select">
          <optgroup v-for="cat in profileStore.skills.data?.categories || []" :key="cat.id" :label="t(cat.label)">
            <option v-for="skill in cat.skills" :key="skill.id" :value="skill.id">{{ t(skill.name) }}</option>
          </optgroup>
        </select>
      </BFormGroup>

      <div class="d-flex justify-content-end">
        <BButton type="submit" variant="primary" :disabled="saving" data-testid="skills-save">
          {{ t('general.save_repair_skills') }}
        </BButton>
      </div>
    </BForm>
  </div>
</template>
