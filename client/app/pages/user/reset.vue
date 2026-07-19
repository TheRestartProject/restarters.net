<script setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'

definePageMeta({ layout: 'plain' })

const { t } = useI18n()
useHead({ title: t('auth.reset_password') })

const authStore = useAuthStore()
const route = useRoute()
const { $api } = useNuxtApp()

const recovery = typeof route.query.recovery === 'string' ? route.query.recovery : ''

// Legacy reset-password.blade.php validated the recovery token server-side
// on load ($valid_code) rather than only on submit, and showed the account
// email (disabled fp_email input) so the user can confirm which account
// they're resetting. `valid` stays false - showing the invalid-code state -
// until a successful lookup confirms otherwise.
const loading = ref(true)
const valid = ref(false)
const accountEmail = ref('')

const password = ref('')
const passwordConfirmation = ref('')
const submitting = ref(false)
const generalError = ref('')
const fieldErrors = ref({})
const done = ref(false)

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

onMounted(async () => {
  if (!recovery) {
    loading.value = false
    return
  }

  try {
    const { data } = await $api.auth.recoveryInfo(recovery)
    valid.value = !!data?.valid
    accountEmail.value = data?.email || ''
  } catch {
    valid.value = false
  } finally {
    loading.value = false
  }
})

async function submit() {
  generalError.value = ''
  fieldErrors.value = {}
  submitting.value = true

  try {
    await authStore.resetPassword({
      recovery,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
    done.value = true
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = err.data?.errors || {}
    }
    generalError.value = err?.data?.message || t('general.error_occurred')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="entry-wrapper align-items-center justify-content-center">
    <div class="container">
      <div v-if="loading" class="entry-panel card card__login col-12 mt-5 text-left" data-testid="reset-loading">
        <div class="placeholder-glow">
          <span class="placeholder col-12" style="height: 6rem" />
        </div>
      </div>

      <div v-else-if="!valid" class="entry-panel card card__login col-12 mt-5 text-left" data-testid="reset-invalid-code">
        <h1>{{ t('auth.reset_password') }}</h1>
        <p class="login-text">
          {{ t('client.reset.invalid_code') }}
          <NuxtLink to="/user/recover" data-testid="reset-recover-link">{{ t('auth.forgot_password') }}</NuxtLink>
        </p>
      </div>

      <div v-else class="entry-panel card card__login col-12 mt-5 text-left" data-testid="reset-page">
        <h1>{{ t('auth.reset_password') }}</h1>

        <BAlert v-if="done" :model-value="true" variant="success" data-testid="reset-success">
          {{ t('client.reset.done') }}
        </BAlert>
        <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="reset-error">
          {{ generalError }}
        </BAlert>

        <BForm v-if="!done" data-testid="reset-form" @submit.prevent="submit">
          <BFormGroup :label="`${t('auth.email_address')}:`" label-for="fp_email">
            <BFormInput id="fp_email" :model-value="accountEmail" disabled data-testid="reset-email" />
          </BFormGroup>

          <BFormGroup :label="`${t('auth.password')}:`" label-for="password">
            <BFormInput id="password" v-model="password" type="password" required data-testid="reset-password" />
            <div v-if="fieldError('password')" class="invalid-feedback d-block" data-testid="reset-password-error">
              {{ fieldError('password') }}
            </div>
          </BFormGroup>

          <BFormGroup :label="`${t('auth.repeat_password')}:`" label-for="confirm_password">
            <BFormInput
              id="confirm_password"
              v-model="passwordConfirmation"
              type="password"
              required
              data-testid="reset-password-confirmation"
            />
          </BFormGroup>

          <div class="row entry-panel__actions">
            <div class="col-12 justify-content-end d-flex">
              <BButton type="submit" variant="primary" :disabled="submitting" data-testid="reset-submit">
                {{ t('auth.change_password') }}
              </BButton>
            </div>
          </div>
        </BForm>
      </div>
    </div>
  </section>
</template>
