<template>
  <div class="edit-panel">
    <div class="form-row">
      <div class="col-lg-6">
        <h4>{{ __('auth.profile_admin') }}</h4>
        <p>{{ __('auth.profile_admin_text') }}</p>
      </div>
    </div>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <fieldset class="user_role">
        <div class="form-row">
          <div class="form-group col-lg-6">
            <label for="user_role">{{ __('auth.user_role') }}:</label>
            <select
                id="user_role"
                v-model="role"
                class="form-control"
                :disabled="loading"
                data-testid="admin-role-select"
            >
              <option :value="null" disabled>Choose role</option>
              <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
          </div>

          <div class="form-group col-lg-6">
            <label for="assigned_groups">{{ __('auth.assigned_groups') }}:</label>
            <multiselect
                id="assigned_groups"
                v-model="selectedGroups"
                :options="groups"
                :multiple="true"
                track-by="id"
                label="name"
                :disabled="loading"
                data-testid="admin-groups-select"
            />
          </div>

          <div class="form-group col-lg-6 pr-0">
            <label>Preferences:</label>
            <div v-for="preference in preferencesOptions" :key="preference.id" class="form-group form-check">
              <input
                  :id="`preference-${preference.id}`"
                  v-model="selectedPreferences"
                  type="checkbox"
                  class="form-check-input"
                  :value="preference.id"
              >
              <label class="form-check-label" :for="`preference-${preference.id}`">
                {{ preference.name }}
              </label>
              <small v-if="preference.purpose" class="form-text text-muted">{{ preference.purpose }}</small>
            </div>
          </div>

          <div class="form-group col-lg-6">
            <label>Permissions:</label>
            <div v-for="permission in permissionsOptions" :key="permission.id" class="form-group form-check">
              <input
                  :id="`permission-${permission.id}`"
                  v-model="selectedPermissions"
                  type="checkbox"
                  class="form-check-input"
                  :value="permission.id"
              >
              <label class="form-check-label" :for="`permission-${permission.id}`">
                {{ permission.name }}
              </label>
              <small v-if="permission.purpose" class="form-text text-muted">{{ permission.purpose }}</small>
            </div>
          </div>
        </div>
      </fieldset>

      <div class="form-row">
        <div class="form-group col-lg-12">
          <div class="d-flex justify-content-end">
            <b-btn
                type="submit"
                variant="primary"
                :disabled="saving || loading"
                data-testid="admin-settings-save"
            >
              {{ __('auth.save_user') }}
            </b-btn>
          </div>
        </div>
      </div>
    </b-form>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminSettingsTab',
  props: {
    userId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      role: null,
      roles: [],
      groups: [],
      selectedGroups: [],
      preferencesOptions: [],
      selectedPreferences: [],
      permissionsOptions: [],
      selectedPermissions: [],
      loading: true,
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  async mounted() {
    try {
      const { data } = await axios.get(`/api/v2/users/${this.userId}/admin-settings`)
      this.role = data.data.role
      this.roles = data.data.roles
      this.groups = data.data.groups
      this.preferencesOptions = data.data.preferences_options
      this.permissionsOptions = data.data.permissions_options
      this.selectedPreferences = data.data.preferences
      this.selectedPermissions = data.data.permissions
      const assignedIds = data.data.assigned_groups
      this.selectedGroups = this.groups.filter(g => assignedIds.includes(g.id))
    } catch (e) {
      console.error('Failed to load admin settings', e)
    } finally {
      this.loading = false
    }
  },
  methods: {
    async save() {
      this.saving = true
      this.feedback = null
      try {
        const { data } = await axios.patch(`/api/v2/users/${this.userId}/admin-settings`, {
          user_role: this.role,
          assigned_groups: this.selectedGroups.map(g => g.id),
          preferences: this.selectedPreferences,
          permissions: this.selectedPermissions,
        })
        this.role = data.data.role
        this.feedback = this.__('profile.admin_success')
        this.feedbackVariant = 'success'
      } catch (e) {
        const message = e.response && e.response.data && e.response.data.message
        this.feedback = message || this.__('general.error_occurred')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
