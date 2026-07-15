<template>
  <div class="edit-panel">
    <div class="form-row">
      <div class="col-lg-12">
        <h4>{{ __('profile.repair_directory') }}</h4>
      </div>
    </div>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <fieldset>
        <div class="form-group row justify-content-center">
          <label for="repair-dir-role" class="col-lg-6">{{ __('profile.repair_dir_role') }}</label>
          <div class="col-lg-6">
            <b-form-select
                id="repair-dir-role"
                v-model="selected"
                :disabled="loading"
                data-testid="repair-dir-select"
            >
              <option
                  v-for="opt in options"
                  :key="opt.value"
                  :value="opt.value"
                  :disabled="opt.disabled"
              >{{ __(opt.key) }}</option>
            </b-form-select>
          </div>
        </div>
      </fieldset>

      <div class="button-group row">
        <div class="offset-9 col-sm-3 d-flex align-items-center justify-content-end">
          <b-btn
              type="submit"
              variant="primary"
              class="btn-save"
              :disabled="saving || loading || selected === current"
              data-testid="repair-dir-save"
          >
            {{ __('auth.save_user') }}
          </b-btn>
        </div>
      </div>
    </b-form>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'RepairDirectoryTab',
  props: {
    userId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      options: [],
      current: null,
      selected: null,
      loading: true,
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  async mounted() {
    try {
      const { data } = await axios.get(`/api/v2/users/${this.userId}/repair-directory-options`)
      this.options = data.data.options
      this.current = data.data.current
      this.selected = data.data.current
    } catch (e) {
      console.error('Failed to load repair-directory options', e)
    } finally {
      this.loading = false
    }
  },
  methods: {
    async save() {
      this.saving = true
      this.feedback = null
      try {
        const { data } = await axios.patch(
            `/api/v2/users/${this.userId}/repair-directory-role`,
            { role: this.selected }
        )
        this.current = data.data.role
        this.feedback = this.__('profile.preferences_updated')
        this.feedbackVariant = 'success'
      } catch (e) {
        console.error('Failed to save repair-directory role', e)
        this.feedback = this.__('general.error_occurred')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
