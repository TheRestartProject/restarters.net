<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// GET/PATCH /api/v2/users/{id}/repair-directory-{options,role}. Functional
// spec: resources/js/components/RepairDirectoryTab.vue +
// resources/views/user/profile/repair-directory.blade.php. Unlike most of
// its sibling tabs, this one IS id-scoped (see stores/profile.js), so it
// is shown regardless of whether targetId is the viewer's own profile.
//
// This component does not fetch on its own mount: ProfileTabs.vue (and, for
// the "am I even allowed to see this page" gate, the /profile/edit/[[id]]
// page itself) both need the same GET /repair-directory-options response -
// one to decide the tab's visibility, one to render it - so the fetch is
// triggered once, by the page, via profileStore.fetchRepairDirectoryOptions
// (cached per target id). This component just reads the resulting store
// state reactively.
const props = defineProps({
  targetId: {
    type: Number,
    required: true,
  },
})

const { t } = useI18n()
const profileStore = useProfileStore()

const selected = ref(null)
const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')

const loading = computed(() => profileStore.repairDirectory.loading)
const current = computed(() => profileStore.repairDirectory.data?.current ?? null)
const options = computed(() => profileStore.repairDirectory.data?.options || [])

watch(
  current,
  (value) => {
    if (value !== null) {
      selected.value = value
    }
  },
  { immediate: true },
)

async function save() {
  saving.value = true
  feedback.value = ''

  try {
    await profileStore.updateRepairDirectoryRole(props.targetId, selected.value)
    feedback.value = t('profile.preferences_updated')
    feedbackVariant.value = 'success'
  } catch (err) {
    feedback.value = err?.data?.message || t('general.error_occurred')
    feedbackVariant.value = 'danger'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="edit-panel" data-testid="repair-directory-tab">
    <h4>{{ t('profile.repair_directory') }}</h4>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="repair-dir-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BAlert v-if="profileStore.repairDirectory.error" :model-value="true" variant="danger" data-testid="repair-dir-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <BForm v-else @submit.prevent="save">
      <BFormGroup :label="t('profile.repair_dir_role')" label-for="repair-dir-select">
        <BFormSelect id="repair-dir-select" v-model="selected" :disabled="loading" data-testid="repair-dir-select">
          <option v-for="opt in options" :key="opt.value" :value="opt.value" :disabled="opt.disabled">
            {{ t(opt.key) }}
          </option>
        </BFormSelect>
      </BFormGroup>

      <div class="button-group row">
        <div class="offset-9 col-sm-3 d-flex align-items-center justify-content-end">
          <BButton type="submit" variant="primary" :disabled="saving || loading || selected === current" data-testid="repair-dir-save">
            {{ t('auth.save_user') }}
          </BButton>
        </div>
      </div>
    </BForm>
  </div>
</template>
