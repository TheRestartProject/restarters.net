<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// GET/PATCH /api/v2/users/{id}/admin-settings. Administrator-only.
// Functional spec: resources/js/components/AdminSettingsTab.vue +
// resources/views/user/profile/account.blade.php. Unlike most of its
// sibling tabs, this one IS id-scoped (see stores/profile.js), so it is
// shown whenever the viewer is an Administrator, regardless of whose
// profile they are editing.
//
// The legacy tab used vue-multiselect for the assigned-groups picker;
// GroupForm.vue already made the same call for its own networks/tags
// pickers (see its scope-cuts comment) - a flat multi-select control here
// too, no new dependency.
const props = defineProps({
  targetId: {
    type: Number,
    required: true,
  },
})

const { t } = useI18n()
const profileStore = useProfileStore()

const role = ref(null)
const selectedGroups = ref([])
const selectedPreferences = ref([])
const selectedPermissions = ref([])
const loading = ref(true)
const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')

const data = computed(() => profileStore.adminSettings.data)

watch(
  () => props.targetId,
  async (id) => {
    loading.value = true
    try {
      const loaded = await profileStore.fetchAdminSettings(id)
      applyData(loaded)
    } catch {
      // Load error is rendered below.
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

function applyData(loaded) {
  role.value = loaded.role
  selectedGroups.value = [...loaded.assigned_groups]
  selectedPreferences.value = [...loaded.preferences]
  selectedPermissions.value = [...loaded.permissions]
}

async function save() {
  saving.value = true
  feedback.value = ''

  try {
    await profileStore.updateAdminSettings(props.targetId, {
      user_role: role.value,
      assigned_groups: selectedGroups.value,
      preferences: selectedPreferences.value,
      permissions: selectedPermissions.value,
    })
    feedback.value = t('profile.admin_success')
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
  <div data-testid="admin-settings-tab">
    <h4>{{ t('auth.profile_admin') }}</h4>
    <p>{{ t('auth.profile_admin_text') }}</p>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="admin-settings-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BAlert v-if="profileStore.adminSettings.error" :model-value="true" variant="danger" data-testid="admin-settings-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <BForm v-else-if="!loading && data" data-testid="admin-settings-form" @submit.prevent="save">
      <div class="row">
        <div class="col-lg-6">
          <BFormGroup :label="`${t('auth.user_role')}:`" label-for="admin-role-select">
            <BFormSelect id="admin-role-select" v-model="role" data-testid="admin-role-select">
              <option v-for="r in data.roles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </BFormSelect>
          </BFormGroup>
        </div>

        <div class="col-lg-6">
          <BFormGroup :label="`${t('auth.assigned_groups')}:`" label-for="admin-groups-select">
            <select id="admin-groups-select" v-model="selectedGroups" class="form-control" multiple size="8" data-testid="admin-groups-select">
              <option v-for="g in data.groups" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>
          </BFormGroup>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6">
          <label>{{ t('client.profile.preferences_label') }}</label>
          <div v-for="preference in data.preferences_options" :key="preference.id" class="form-check">
            <input
              :id="`preference-${preference.id}`"
              v-model="selectedPreferences"
              type="checkbox"
              class="form-check-input"
              :value="preference.id"
              :data-testid="`admin-preference-${preference.id}`"
            >
            <label class="form-check-label" :for="`preference-${preference.id}`">{{ preference.name }}</label>
            <small v-if="preference.purpose" class="form-text text-muted">{{ preference.purpose }}</small>
          </div>
        </div>

        <div class="col-lg-6">
          <label>{{ t('client.profile.permissions_label') }}</label>
          <div v-for="permission in data.permissions_options" :key="permission.id" class="form-check">
            <input
              :id="`permission-${permission.id}`"
              v-model="selectedPermissions"
              type="checkbox"
              class="form-check-input"
              :value="permission.id"
              :data-testid="`admin-permission-${permission.id}`"
            >
            <label class="form-check-label" :for="`permission-${permission.id}`">{{ permission.name }}</label>
            <small v-if="permission.purpose" class="form-text text-muted">{{ permission.purpose }}</small>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end mt-3">
        <BButton type="submit" variant="primary" :disabled="saving || loading" data-testid="admin-settings-save">
          {{ t('auth.save_user') }}
        </BButton>
      </div>
    </BForm>
  </div>
</template>
