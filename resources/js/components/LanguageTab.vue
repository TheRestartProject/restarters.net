<template>
  <div class="edit-panel">
    <div class="form-row">
      <div class="col">
        <h4>{{ __('profile.language_panel_title') }}</h4>
      </div>
    </div>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <fieldset class="language">
        <div class="form-row">
          <div class="form-group col-lg-6">
            <label for="user_language">{{ __('profile.preferred_language') }}</label>
            <b-form-select
                id="user_language"
                v-model="selected"
                :disabled="loading"
                data-testid="language-select"
            >
              <option v-for="opt in supported" :key="opt.code" :value="opt.code">{{ opt.native }}</option>
            </b-form-select>
          </div>
        </div>
      </fieldset>

      <div class="form-row">
        <div class="form-group col-lg-12">
          <div class="d-flex justify-content-end">
            <b-btn
                type="submit"
                variant="primary"
                :disabled="saving || loading || selected === current"
                data-testid="language-save"
            >
              {{ __('partials.save') }}
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
  name: 'LanguageTab',
  data() {
    return {
      supported: [],
      selected: null,
      current: null,
      loading: true,
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  async mounted() {
    try {
      const { data } = await axios.get('/api/v2/users/me/language')
      this.supported = data.data.supported
      this.selected = data.data.language
      this.current = data.data.language
    } catch (e) {
      console.error('Failed to load language preferences', e)
    } finally {
      this.loading = false
    }
  },
  methods: {
    async save() {
      this.saving = true
      this.feedback = null
      try {
        const { data } = await axios.patch('/api/v2/users/me/language', {
          language: this.selected,
        })
        this.current = data.data.language
        this.feedback = this.__('profile.language_updated')
        this.feedbackVariant = 'success'
        // Locale change should reload to apply translations everywhere.
        setTimeout(() => window.location.reload(), 800)
      } catch (e) {
        console.error('Failed to save language preference', e)
        this.feedback = this.__('general.error_occurred')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
