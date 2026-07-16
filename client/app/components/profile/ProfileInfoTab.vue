<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// GET/PATCH /api/v2/users/me/profile. Functional spec:
// resources/js/components/ProfileInfoTab.vue +
// resources/views/user/profile/profile.blade.php. Always operates on
// Auth::user() - see stores/profile.js's class doc comment for why this
// tab is only ever shown while editing one's own profile.
const { t } = useI18n()
const profileStore = useProfileStore()

const form = reactive({
  name: '',
  email: '',
  country: '',
  townCity: '',
  age: '',
  gender: '',
  biography: '',
})

const loading = ref(true)
const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')
const fieldErrors = ref({})

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

function applyData(data) {
  form.name = data.name
  form.email = data.email
  form.country = data.country_code || ''
  form.townCity = data.location || ''
  form.age = data.age || ''
  form.gender = data.gender || ''
  form.biography = data.biography || ''
}

onMounted(async () => {
  try {
    const data = await profileStore.fetchProfileInfo()
    applyData(data)
  } catch {
    // Load error is rendered by the retry state below.
  } finally {
    loading.value = false
  }
})

async function save() {
  saving.value = true
  feedback.value = ''
  fieldErrors.value = {}

  try {
    const data = await profileStore.updateProfileInfo({
      name: form.name,
      email: form.email,
      country: form.country,
      townCity: form.townCity,
      age: form.age,
      gender: form.gender,
      biography: form.biography,
    })
    applyData(data)
    feedback.value = t('profile.profile_updated')
    feedbackVariant.value = 'success'
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = err.data?.errors || {}
    }
    feedback.value = t('general.error_occurred')
    feedbackVariant.value = 'danger'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div data-testid="profile-info-tab">
    <h3>{{ t('general.profile') }}</h3>
    <p>{{ t('general.profile_content') }}</p>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="profile-info-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BAlert v-if="profileStore.info.error" :model-value="true" variant="danger" data-testid="profile-info-load-error">
      {{ t('client.profile.load_error') }}
    </BAlert>

    <BForm v-else-if="!loading" data-testid="profile-info-form" @submit.prevent="save">
      <div class="row">
        <div class="col-lg-6">
          <BFormGroup :label="`${t('profile.name')}:`" label-for="profile-name">
            <BFormInput id="profile-name" v-model="form.name" data-testid="profile-name" />
            <div v-if="fieldError('name')" class="invalid-feedback d-block" data-testid="profile-name-error">{{ fieldError('name') }}</div>
          </BFormGroup>
        </div>
        <div class="col-lg-6">
          <BFormGroup :label="`${t('profile.country')}:`" label-for="profile-country">
            <BFormSelect id="profile-country" v-model="form.country" required data-testid="profile-country">
              <option value="" />
              <option v-for="c in profileStore.info.data?.countries || []" :key="c.code" :value="c.code">{{ c.name }}</option>
            </BFormSelect>
            <div v-if="fieldError('country')" class="invalid-feedback d-block" data-testid="profile-country-error">{{ fieldError('country') }}</div>
          </BFormGroup>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6">
          <BFormGroup :label="`${t('profile.email_address')}:`" label-for="profile-email">
            <BFormInput id="profile-email" v-model="form.email" data-testid="profile-email" />
            <div v-if="fieldError('email')" class="invalid-feedback d-block" data-testid="profile-email-error">{{ fieldError('email') }}</div>
          </BFormGroup>
        </div>
        <div class="col-lg-6">
          <BFormGroup :label="`${t('registration.town-city')}:`" label-for="profile-town-city">
            <BFormInput id="profile-town-city" v-model="form.townCity" data-testid="profile-town-city" />
          </BFormGroup>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6">
          <BFormGroup :label="`${t('registration.age')}:`" label-for="profile-age">
            <BFormSelect id="profile-age" v-model="form.age" required data-testid="profile-age">
              <option v-for="a in profileStore.info.data?.ages || []" :key="a" :value="a">{{ a }}</option>
            </BFormSelect>
            <div v-if="fieldError('age')" class="invalid-feedback d-block" data-testid="profile-age-error">{{ fieldError('age') }}</div>
          </BFormGroup>
        </div>
        <div class="col-lg-6">
          <BFormGroup :label="`${t('registration.gender')}:`" label-for="profile-gender">
            <BFormInput id="profile-gender" v-model="form.gender" data-testid="profile-gender" />
          </BFormGroup>
        </div>
      </div>

      <BFormGroup :label="`${t('profile.biography')}:`" label-for="profile-biography">
        <textarea id="profile-biography" v-model="form.biography" class="form-control" rows="8" data-testid="profile-biography" />
      </BFormGroup>

      <div class="d-flex justify-content-end mt-3">
        <BButton type="submit" variant="primary" :disabled="saving || loading" data-testid="profile-save">
          {{ t('profile.save_profile') }}
        </BButton>
      </div>
    </BForm>
  </div>
</template>
