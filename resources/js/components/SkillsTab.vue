<template>
  <div class="edit-panel">
    <h4>{{ __('general.repair_skills') }}</h4>
    <p>{{ __('general.repair_skills_content') }}</p>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <div class="form-group">
        <label for="tags">{{ __('general.your_repair_skills') }}:</label>
        <select
            id="tags"
            v-model="selected"
            class="form-control"
            multiple
            size="10"
            data-testid="skills-select"
        >
          <optgroup v-for="cat in categories" :key="cat.id" :label="__(cat.label)">
            <option v-for="skill in cat.skills" :key="skill.id" :value="skill.id">
              {{ __(skill.name) }}
            </option>
          </optgroup>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group col-lg-12">
          <div class="d-flex justify-content-end">
            <b-btn
                type="submit"
                variant="primary"
                :disabled="saving || loading"
                data-testid="skills-save"
            >
              {{ __('general.save_repair_skills') }}
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
  name: 'SkillsTab',
  data() {
    return {
      categories: [],
      selected: [],
      loading: true,
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  async mounted() {
    try {
      const { data } = await axios.get('/api/v2/users/me/skills')
      this.categories = data.data.categories
      this.selected = data.data.selected
    } catch (e) {
      console.error('Failed to load repair skills', e)
    } finally {
      this.loading = false
    }
  },
  methods: {
    async save() {
      this.saving = true
      this.feedback = null
      try {
        const { data } = await axios.patch('/api/v2/users/me/skills', {
          tags: this.selected,
        })
        this.selected = data.data.tags
        this.feedback = this.__('profile.skills_updated')
        this.feedbackVariant = 'success'
      } catch (e) {
        console.error('Failed to save repair skills', e)
        this.feedback = this.__('general.error_occurred')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
