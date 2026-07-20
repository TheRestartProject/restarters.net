<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'

// legacy forgot-password.blade.php shows just a centered logo, no impact-
// stats bar - unlike the other plain-layout pages (login/register/reset) -
// see layouts/plain-minimal.vue's own doc comment.
definePageMeta({ layout: 'plain-minimal' })

const { t } = useI18n()
useHead({ title: t('auth.forgotten_pw') })

const authStore = useAuthStore()

const email = ref('')
const submitting = ref(false)
const generalError = ref('')
const sent = ref(false)

async function submit() {
  generalError.value = ''
  submitting.value = true

  try {
    await authStore.forgotPassword({ email: email.value })
    sent.value = true
  } catch (err) {
    generalError.value = err?.data?.message || t('general.error_occurred')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="login-page">
    <div class="container">
      <div class="entry-panel card card__login col-12 mt-5 text-left" data-testid="recover-page">
        <h1>{{ t('auth.forgotten_pw') }}</h1>
        <p>{{ t('auth.forgotten_pw_text') }}</p>

        <BAlert v-if="sent" :model-value="true" variant="success" data-testid="recover-success">
          {{ t('client.recover.sent') }}
        </BAlert>
        <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="recover-error">
          {{ generalError }}
        </BAlert>

        <BForm v-if="!sent" data-testid="recover-form" @submit.prevent="submit">
          <BFormGroup :label="`${t('auth.email_address')}:`" label-for="email">
            <BFormInput id="email" v-model="email" type="email" required data-testid="recover-email" />
          </BFormGroup>

          <div class="row entry-panel__actions">
            <div class="col-8 align-content-center d-flex">
              <NuxtLink class="entry-panel__link" to="/login" data-testid="recover-sign-in-link">
                {{ t('auth.sign_in') }}
              </NuxtLink>
            </div>
            <div class="col-4 align-content-center justify-content-end d-flex">
              <BButton type="submit" variant="primary" :disabled="submitting" data-testid="recover-submit">
                {{ t('auth.reset') }}
              </BButton>
            </div>
          </div>
        </BForm>
      </div>
    </div>
  </section>
</template>
