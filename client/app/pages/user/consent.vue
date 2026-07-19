<script setup>
// Consent-completion page: authenticated users whose data consents are
// outstanding (admin-created accounts, pre-2018 legacy accounts) land here —
// auth.global redirects them — and cannot mutate anything server-side until
// POST /api/v2/auth/consent succeeds (VerifyUserConsentApi enforces it with
// 403 consent_required). Port of the logged-in branch of the old Blade
// registration form.
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'

definePageMeta({ auth: true, layout: 'plain' })

const { t, tm } = useI18n()
const route = useRoute()
const sessionStore = useSessionStore()
const { $api } = useNuxtApp()

const form = reactive({
  age: '',
  country: '',
  city: '',
  consent_gdpr: false,
  consent_past_data: false,
  consent_future_data: false,
})

const submitting = ref(false)
const fieldErrors = ref({})
const generalError = ref('')

const currentYear = new Date().getFullYear()
const ageOptions = computed(() => {
  // Match legacy Fixometer::allAges() and the sibling register.vue: minimum age
  // 18 (birth year currentYear-18), exclusive lower bound at currentYear-100.
  const years = []
  for (let y = currentYear - 18; y > currentYear - 100; y--) years.push(y)
  return years
})

const countries = computed(() => {
  // tm() returns compiled message ASTs for precompiled JSON locales, so
  // resolve each name through t() - always a plain string.
  return Object.keys(tm('countries') || {})
    .map((code) => ({ code, name: t(`countries.${code}`) }))
    .sort((a, b) => a.name.localeCompare(b.name))
})

async function submit() {
  fieldErrors.value = {}
  generalError.value = ''

  const consentErrors = {}
  for (const key of ['consent_gdpr', 'consent_past_data', 'consent_future_data']) {
    if (!form[key]) consentErrors[key] = [t('registration.reg-step-4')]
  }
  if (Object.keys(consentErrors).length) {
    fieldErrors.value = consentErrors
    return
  }

  submitting.value = true
  try {
    await $api.auth.consent({ ...form })
    // Refresh the session so consent.given flips and the middleware stops
    // redirecting here.
    await sessionStore.fetch()

    const redirect =
      typeof route.query.redirect === 'string' && route.query.redirect
        ? route.query.redirect
        : '/dashboard'
    await navigateTo(redirect)
  } catch (e) {
    if (e?.status === 422 && e?.data?.errors) {
      fieldErrors.value = e.data.errors
    } else {
      generalError.value = e?.data?.message || t('general.error_occurred')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="container py-4" style="max-width: 640px" data-testid="consent-page">
    <h1>{{ t('registration.reg-step-4-heading') }}</h1>

    <BAlert :model-value="!!generalError" variant="danger" data-testid="consent-error">
      {{ generalError }}
    </BAlert>

    <BForm data-testid="consent-form" @submit.prevent="submit">
      <BFormGroup :label="`${t('registration.age')}:`" label-for="consent-age">
        <BFormSelect id="consent-age" v-model="form.age" required data-testid="consent-age">
          <option v-for="y in ageOptions" :key="y" :value="String(y)">{{ y }}</option>
        </BFormSelect>
        <div v-if="fieldErrors.age" class="text-danger small" data-testid="consent-age-error">
          {{ fieldErrors.age[0] }}
        </div>
      </BFormGroup>

      <BFormGroup :label="`${t('registration.country')}:`" label-for="consent-country">
        <BFormSelect id="consent-country" v-model="form.country" required data-testid="consent-country">
          <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
        </BFormSelect>
        <div v-if="fieldErrors.country" class="text-danger small" data-testid="consent-country-error">
          {{ fieldErrors.country[0] }}
        </div>
      </BFormGroup>

      <BFormGroup :label="`${t('registration.town-city')}:`" label-for="consent-city">
        <BFormInput id="consent-city" v-model="form.city" data-testid="consent-city" />
      </BFormGroup>

      <BFormGroup>
        <!-- Legacy rendered registration.reg-step-4 as a <legend> above the
             consent checkboxes; the SPA had only reused it as an error string. -->
        <p class="fw-bold" data-testid="consent-intro">{{ t('registration.reg-step-4') }}</p>

        <!-- Field/label pairing matches legacy register-new.blade.php step 4
             (verified at 07e6abd7cc^): consent_gdpr = Personal Data (label1),
             consent_future_data = Repair Data (label2), consent_past_data =
             Historical Repair Data (label3). A prior version had label1/label3
             swapped, so the checkbox text described a different consent than the
             field it set - a consent-integrity bug. -->
        <BFormCheckbox v-model="form.consent_gdpr" data-testid="consent-gdpr">
          <!-- eslint-disable-next-line vue/no-v-html — fixed lang string with embedded links -->
          <span v-html="t('registration.reg-step-4-label1')" />
        </BFormCheckbox>
        <div v-if="fieldErrors.consent_gdpr" class="text-danger small" data-testid="consent-gdpr-error">
          {{ fieldErrors.consent_gdpr[0] }}
        </div>

        <BFormCheckbox v-model="form.consent_past_data" data-testid="consent-past-data">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="t('registration.reg-step-4-label3')" />
        </BFormCheckbox>
        <div v-if="fieldErrors.consent_past_data" class="text-danger small" data-testid="consent-past-data-error">
          {{ fieldErrors.consent_past_data[0] }}
        </div>

        <BFormCheckbox v-model="form.consent_future_data" data-testid="consent-future-data">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="t('registration.reg-step-4-label2')" />
        </BFormCheckbox>
        <div v-if="fieldErrors.consent_future_data" class="text-danger small" data-testid="consent-future-data-error">
          {{ fieldErrors.consent_future_data[0] }}
        </div>
      </BFormGroup>

      <BButton type="submit" variant="primary" :disabled="submitting" data-testid="consent-submit">
        {{ t('registration.complete-profile') }}
      </BButton>
    </BForm>
  </div>
</template>
