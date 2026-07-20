<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// GET/PATCH /api/v2/users/me/skills (own profile) or GET/PATCH
// /api/v2/users/{id}/skills (gap 5 - an Administrator editing someone
// else's, via profileStore.fetchUserSkills/updateUserSkills). Functional
// spec: resources/js/components/SkillsTab.vue +
// resources/views/user/profile/profile.blade.php. See stores/profile.js's
// class doc comment for why the two are kept in separate state slots
// (`skills` vs `userSkills`) - `source` below reads whichever one applies.
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
const source = computed(() => (props.isOwnProfile ? profileStore.skills : profileStore.userSkills))

const selected = ref([])
const loading = ref(true)
const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')

// Watches (not a plain onMounted) - same convention as ProfileInfoTab.vue's
// own targetId/isOwnProfile watcher.
watch(
  () => [props.isOwnProfile, props.targetId],
  async ([isOwnProfile, targetId]) => {
    loading.value = true
    try {
      const data = isOwnProfile ? await profileStore.fetchSkills() : await profileStore.fetchUserSkills(targetId)
      selected.value = data.selected
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
      ? await profileStore.updateSkills({ tags: selected.value })
      : await profileStore.updateUserSkills(props.targetId, { tags: selected.value })
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
  <div class="edit-panel" data-testid="skills-tab">
    <h4>{{ t('general.repair_skills') }}</h4>
    <p>{{ t('general.repair_skills_content') }}</p>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="skills-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BAlert v-if="source.error" :model-value="true" variant="danger" data-testid="skills-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <BForm v-else-if="!loading" data-testid="skills-form" @submit.prevent="save">
      <BFormGroup :label="`${t('general.your_repair_skills')}:`" label-for="skills-select">
        <select id="skills-select" v-model="selected" class="form-control" multiple size="10" data-testid="skills-select">
          <optgroup v-for="cat in source.data?.categories || []" :key="cat.id" :label="t(cat.label)">
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
