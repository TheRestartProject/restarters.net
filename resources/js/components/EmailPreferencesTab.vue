<template>
  <div class="edit-panel">
    <div class="form-row">
      <div class="col-lg-12">
        <h4>{{ __('general.email_alerts') }}</h4>
        <p>{{ __('general.email_alerts_text') }}</p>
      </div>
    </div>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <fieldset class="email-options">
        <div class="form-check d-flex align-items-center justify-content-start">
          <input
              id="invites"
              v-model="invites"
              type="checkbox"
              class="checkbox-top form-check-input"
              data-testid="email-preferences-invites"
          >
          <label class="form-check-label" for="invites">
            {{ __('general.email_alerts_pref2') }}
          </label>
        </div>
      </fieldset>

      <div class="button-group row">
        <div class="col-sm-9 d-flex align-items-center justify-content-start">
          <a class="btn-preferences" :href="platformPreferencesUrl">
            {{ __('auth.set_preferences') }}
          </a>
        </div>
        <div class="col-sm-3 d-flex align-items-center justify-content-end">
          <b-btn
              type="submit"
              variant="primary"
              class="btn-save"
              :disabled="saving"
              data-testid="email-preferences-save"
          >
            {{ __('auth.save_preferences') }}
          </b-btn>
        </div>
      </div>
    </b-form>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'EmailPreferencesTab',
  props: {
    platformPreferencesUrl: {
      type: String,
      required: true,
    },
    initialInvites: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      invites: this.initialInvites,
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
        const { data } = await axios.patch('/api/v2/users/me/preferences', {
          invites: !!this.invites,
        })
        this.invites = !!data.data.invites
        this.feedback = this.__('profile.preferences_updated')
        this.feedbackVariant = 'success'
      } catch (e) {
        console.error('Failed to save email preferences', e)
        this.feedback = this.__('general.error_occurred')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
