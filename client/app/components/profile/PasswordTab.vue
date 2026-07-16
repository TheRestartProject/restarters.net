<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useProfileStore } from '~/stores/profile.js'

// PATCH /api/v2/users/me/password. Functional spec:
// resources/js/components/PasswordTab.vue +
// resources/views/user/profile/account.blade.php. Always operates on
// Auth::user() - see stores/profile.js's class doc comment for why this
// tab is only ever shown while editing one's own profile.
const { t } = useI18n()
const profileStore = useProfileStore()

const form = reactive({
  currentPassword: '',
  newPassword: '',
  newPasswordConfirmation: '',
})

const saving = ref(false)
const feedback = ref('')
const feedbackVariant = ref('success')
const fieldErrors = ref({})

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

async function save() {
  saving.value = true
  feedback.value = ''
  fieldErrors.value = {}

  // Client-side mirror of updateMyPasswordv2's mismatch check, so the user
  // sees it instantly rather than round-tripping to the server for
  // something that can only ever fail one way.
  if (form.newPassword !== form.newPasswordConfirmation) {
    fieldErrors.value = { new_password_confirmation: [t('profile.password_new_mismatch')] }
    saving.value = false
    return
  }

  try {
    await profileStore.updatePassword({
      current_password: form.currentPassword,
      new_password: form.newPassword,
      new_password_confirmation: form.newPasswordConfirmation,
    })
    form.currentPassword = ''
    form.newPassword = ''
    form.newPasswordConfirmation = ''
    feedback.value = t('profile.password_changed')
    feedbackVariant.value = 'success'
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = err.data?.errors || {}
    }
    feedback.value = err?.data?.message || t('general.error_occurred')
    feedbackVariant.value = 'danger'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div data-testid="password-tab">
    <h4>{{ t('auth.change_password') }}</h4>
    <p>{{ t('auth.change_password_text') }}</p>

    <BAlert v-if="feedback" :model-value="true" :variant="feedbackVariant" dismissible data-testid="password-feedback" @dismissed="feedback = ''">
      {{ feedback }}
    </BAlert>

    <BForm data-testid="password-form" @submit.prevent="save">
      <BFormGroup :label="`${t('auth.current_password')}:`" label-for="password-current">
        <BFormInput id="password-current" v-model="form.currentPassword" type="password" data-testid="password-current" />
        <div v-if="fieldError('current_password')" class="invalid-feedback d-block" data-testid="password-current-error">
          {{ fieldError('current_password') }}
        </div>
      </BFormGroup>

      <BFormGroup :label="`${t('auth.new_password')}:`" label-for="password-new">
        <BFormInput id="password-new" v-model="form.newPassword" type="password" data-testid="password-new" />
        <div v-if="fieldError('new_password')" class="invalid-feedback d-block" data-testid="password-new-error">
          {{ fieldError('new_password') }}
        </div>
      </BFormGroup>

      <BFormGroup :label="`${t('auth.new_repeat_password')}:`" label-for="password-new-repeat">
        <BFormInput id="password-new-repeat" v-model="form.newPasswordConfirmation" type="password" data-testid="password-new-repeat" />
        <div v-if="fieldError('new_password_confirmation')" class="invalid-feedback d-block" data-testid="password-new-repeat-error">
          {{ fieldError('new_password_confirmation') }}
        </div>
      </BFormGroup>

      <div class="d-flex justify-content-end">
        <BButton type="submit" variant="primary" :disabled="saving" data-testid="password-save">
          {{ t('auth.change_password') }}
        </BButton>
      </div>
    </BForm>
  </div>
</template>
