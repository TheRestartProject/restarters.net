<template>
  <div class="edit-panel">
    <div class="form-row">
      <div class="col-lg-6">
        <h4>{{ __('auth.change_password') }}</h4>
        <p>{{ __('auth.change_password_text') }}</p>
      </div>
    </div>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <fieldset class="registration__offset2">
        <div class="form-row">
          <div class="form-group col-lg-6">
            <label for="current-password">{{ __('auth.current_password') }}:</label>
            <input
                id="current-password"
                v-model="currentPassword"
                type="password"
                class="form-control"
                data-testid="password-current"
            >
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-lg-6">
            <label for="new-password">{{ __('auth.new_password') }}:</label>
            <input
                id="new-password"
                v-model="newPassword"
                type="password"
                class="form-control"
                data-testid="password-new"
            >
          </div>
          <div class="form-group col-lg-6">
            <label for="new-password-repeat">{{ __('auth.new_repeat_password') }}:</label>
            <input
                id="new-password-repeat"
                v-model="newPasswordConfirmation"
                type="password"
                class="form-control"
                data-testid="password-new-repeat"
            >
          </div>
        </div>
      </fieldset>

      <div class="form-row">
        <div class="form-group col-lg-12">
          <div class="d-flex justify-content-end">
            <b-btn
                type="submit"
                variant="primary"
                :disabled="saving"
                data-testid="password-save"
            >
              {{ __('auth.change_password') }}
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
  name: 'PasswordTab',
  data() {
    return {
      currentPassword: '',
      newPassword: '',
      newPasswordConfirmation: '',
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  methods: {
    async save() {
      this.saving = true
      this.feedback = null
      try {
        await axios.patch('/api/v2/users/me/password', {
          current_password: this.currentPassword,
          new_password: this.newPassword,
          new_password_confirmation: this.newPasswordConfirmation,
        })
        this.currentPassword = ''
        this.newPassword = ''
        this.newPasswordConfirmation = ''
        this.feedback = this.__('profile.password_changed')
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
