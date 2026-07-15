<template>
  <div class="edit-panel">
    <div class="form-row">
      <div class="col-lg-12">
        <h3>{{ __('general.profile') }}</h3>
        <p>{{ __('general.profile_content') }}</p>
      </div>
    </div>

    <b-alert :show="!!feedback" :variant="feedbackVariant" dismissible @dismissed="feedback = null">
      {{ feedback }}
    </b-alert>

    <b-form @submit.prevent="save">
      <div class="form-row">
        <div class="form-group col-lg-6">
          <label for="name">{{ __('profile.name') }}:</label>
          <input
              id="name"
              v-model="name"
              type="text"
              class="form-control"
              data-testid="profile-name"
          >
        </div>

        <div class="form-group col-lg-6">
          <label for="country">{{ __('profile.country') }}:<sup>*</sup></label>
          <b-form-select
              id="country"
              v-model="country"
              required
              aria-required="true"
              data-testid="profile-country"
          >
            <option value="" />
            <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
          </b-form-select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-lg-6">
          <label for="email">{{ __('profile.email_address') }}:</label>
          <input
              id="email"
              v-model="email"
              type="text"
              class="form-control"
              data-testid="profile-email"
          >
        </div>
        <div class="form-group col-lg-6">
          <label for="townCity">{{ __('registration.town-city') }}:</label>
          <input
              id="townCity"
              v-model="townCity"
              type="text"
              class="form-control"
              data-testid="profile-town-city"
          >
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-lg-6">
          <label for="age">{{ __('registration.age') }}:</label>
          <b-form-select
              id="age"
              v-model="age"
              required
              aria-required="true"
              data-testid="profile-age"
          >
            <option v-for="a in ages" :key="a" :value="a">{{ a }}</option>
          </b-form-select>
        </div>

        <div class="form-group col-lg-6">
          <label for="gender">{{ __('registration.gender') }}:</label>
          <input
              id="gender"
              v-model="gender"
              class="form-control"
              data-testid="profile-gender"
          >
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-lg-12">
          <label for="biography">{{ __('profile.biography') }}:</label>
          <textarea
              id="biography"
              v-model="biography"
              class="form-control"
              rows="8"
              cols="80"
              data-testid="profile-biography"
          />
        </div>
      </div>

      <div class="form-row">
        <div class="form-group col-lg-12">
          <div class="d-flex justify-content-end">
            <b-btn
                type="submit"
                variant="primary"
                :disabled="saving || loading"
                data-testid="profile-save"
            >
              {{ __('profile.save_profile') }}
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
  name: 'ProfileInfoTab',
  data() {
    return {
      name: '',
      email: '',
      country: '',
      townCity: '',
      age: '',
      gender: '',
      biography: '',
      countries: [],
      ages: [],
      loading: true,
      saving: false,
      feedback: null,
      feedbackVariant: 'success',
    }
  },
  async mounted() {
    try {
      const { data } = await axios.get('/api/v2/users/me/profile')
      this.name = data.data.name
      this.email = data.data.email
      this.country = data.data.country_code || ''
      this.townCity = data.data.location || ''
      this.age = data.data.age || ''
      this.gender = data.data.gender || ''
      this.biography = data.data.biography || ''
      this.countries = data.data.countries
      this.ages = data.data.ages
    } catch (e) {
      console.error('Failed to load profile', e)
    } finally {
      this.loading = false
    }
  },
  methods: {
    async save() {
      this.saving = true
      this.feedback = null
      try {
        const { data } = await axios.patch('/api/v2/users/me/profile', {
          name: this.name,
          email: this.email,
          country: this.country,
          townCity: this.townCity,
          age: this.age,
          gender: this.gender,
          biography: this.biography,
        })
        this.name = data.data.name
        this.email = data.data.email
        this.country = data.data.country_code || ''
        this.townCity = data.data.location || ''
        this.age = data.data.age || ''
        this.gender = data.data.gender || ''
        this.biography = data.data.biography || ''
        this.feedback = this.__('profile.profile_updated')
        this.feedbackVariant = 'success'
      } catch (e) {
        console.error('Failed to save profile', e)
        this.feedback = this.__('general.error_occurred')
        this.feedbackVariant = 'danger'
      } finally {
        this.saving = false
      }
    },
  },
}
</script>
