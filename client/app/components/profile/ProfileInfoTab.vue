<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// GET/PATCH /api/v2/users/me/profile (own profile) or
// GET/PATCH /api/v2/users/{id}/profile (gap 5 - an Administrator editing
// someone else's, via profileStore.fetchUserProfile/updateUserProfile).
// Functional spec: resources/js/components/ProfileInfoTab.vue +
// resources/views/user/profile/profile.blade.php. See stores/profile.js's
// class doc comment for why the two are kept in separate state slots
// (`info` vs `userProfile`) - `source` below reads whichever one applies.
const props = defineProps({
  targetId: {
    type: Number,
    default: null,
  },
  isOwnProfile: {
    type: Boolean,
    default: true,
  },
})

const { t } = useI18n()
const profileStore = useProfileStore()
const source = computed(() => (props.isOwnProfile ? profileStore.info : profileStore.userProfile))

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

// Watches (not a plain onMounted) so navigating between two different
// users' profiles without unmounting ProfileTabs.vue (e.g. an
// Administrator following two different /profile/edit/{id} links) re-fetches
// - same convention as AdminSettingsTab.vue's own targetId watcher.
watch(
  () => [props.isOwnProfile, props.targetId],
  async ([isOwnProfile, targetId]) => {
    loading.value = true
    try {
      const data = isOwnProfile ? await profileStore.fetchProfileInfo() : await profileStore.fetchUserProfile(targetId)
      applyData(data)
    } catch {
      // Load error is rendered by the retry state below.
    } finally {
      loading.value = false
    }
  },
  { immediate: true },
)

async function save() {
  saving.value = true
  feedback.value = ''
  fieldErrors.value = {}

  try {
    const payload = {
      name: form.name,
      email: form.email,
      country: form.country,
      townCity: form.townCity,
      age: form.age,
      gender: form.gender,
      biography: form.biography,
    }
    const data = props.isOwnProfile
      ? await profileStore.updateProfileInfo(payload)
      : await profileStore.updateUserProfile(props.targetId, payload)
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
  <div class="edit-panel" data-testid="profile-info-tab">
    <!-- Legacy shows a different heading when an Administrator edits someone
         else's profile (profile.blade.php's Auth::id() == $user->id check):
         own profile gets an <h3>, someone else's gets an <h4> "X's profile". -->
    <h3 v-if="isOwnProfile" data-testid="profile-info-heading">{{ t('general.profile') }}</h3>
    <h4 v-else data-testid="profile-info-heading">{{ t('profile.other_profile', { name: source.data?.name }) }}</h4>
    <p>{{ t('general.profile_content') }}</p>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="profile-info-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BAlert v-if="source.error" :model-value="true" variant="danger" data-testid="profile-info-load-error">
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
              <option v-for="c in source.data?.countries || []" :key="c.code" :value="c.code">{{ c.name }}</option>
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
              <option v-for="a in source.data?.ages || []" :key="a" :value="a">{{ a }}</option>
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
