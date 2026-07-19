<script setup>
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'

definePageMeta({ layout: 'plain', guest: true })

const { t, tm } = useI18n()
useHead({ title: t('login.join_title') })

const authStore = useAuthStore()
const route = useRoute()

// Placeholder skill checkboxes (design.md task brief: "skills multiselect
// placeholder as simple checkboxes for now") - the real skill list is
// DB-backed (App\Helpers\Fixometer::allSkills()) and lands with the
// reference-data API slice (design.md §5.8); ids here are illustrative only
// and not guaranteed to match production skill ids.
const skillCategories = [
  { id: 1, labelKey: 'client.register.skills_organising', skills: [1, 2, 3, 4] },
  { id: 2, labelKey: 'client.register.skills_technical', skills: [5, 6, 7, 8] },
]
const skillLabels = {
  1: 'client.register.skill_1',
  2: 'client.register.skill_2',
  3: 'client.register.skill_3',
  4: 'client.register.skill_4',
  5: 'client.register.skill_5',
  6: 'client.register.skill_6',
  7: 'client.register.skill_7',
  8: 'client.register.skill_8',
}

const currentYear = new Date().getFullYear()
const birthYears = []
for (let y = currentYear - 18; y > currentYear - 100; y--) {
  birthYears.push(y)
}

const countries = computed(() => {
  // tm() returns compiled message ASTs (not strings) for precompiled JSON
  // locales, so resolve each name through t() — always a plain string.
  return Object.keys(tm('countries') || {})
    .map((code) => ({ code, name: t(`countries.${code}`) }))
    .sort((a, b) => a.name.localeCompare(b.name))
})

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  age: '',
  country: '',
  city: '',
  gender: '',
  skills: [],
  newsletter: false,
  invites: false,
  consent_gdpr: false,
  consent_future_data: false,
})

const myName = ref('')
const submitting = ref(false)
const generalError = ref('')
const fieldErrors = ref({})

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

async function submit() {
  if (myName.value) {
    return
  }

  generalError.value = ''
  fieldErrors.value = {}

  // Belt-and-braces alongside the native `required` attribute on the
  // checkboxes below: both consents are mandatory (design.md task brief -
  // "consent checkboxes required"; matches registration.reg-step-4's copy).
  const consentErrors = {}
  if (!form.consent_gdpr) {
    consentErrors.consent_gdpr = [t('registration.reg-step-4')]
  }
  if (!form.consent_future_data) {
    consentErrors.consent_future_data = [t('registration.reg-step-4')]
  }
  if (Object.keys(consentErrors).length) {
    fieldErrors.value = consentErrors
    generalError.value = t('general.error_occurred')
    return
  }

  submitting.value = true

  try {
    await authStore.register({
      ...form,
      invite_code: route.query.invite_code,
      invite_type: route.query.invite_type,
      invite_hash: route.query.invite_hash,
    })

    const redirect =
      typeof route.query.redirect === 'string' && route.query.redirect
        ? route.query.redirect
        : '/dashboard'
    await navigateTo(redirect)
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = err.data?.errors || {}
      generalError.value = t('general.error_occurred')
    } else {
      generalError.value = t('general.error_occurred')
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="registration">
    <div class="container">
      <BForm data-testid="register-form" @submit.prevent="submit">
        <div style="position: absolute; left: -9999px;" aria-hidden="true">
          <label for="my_name">Name</label>
          <input
            id="my_name"
            v-model="myName"
            type="text"
            name="my_name"
            tabindex="-1"
            autocomplete="off"
            data-testid="register-honeypot"
          >
        </div>

        <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="register-error">
          {{ generalError }}
        </BAlert>

        <fieldset class="panel">
          <legend>{{ t('registration.reg-step-1-heading') }}</legend>
          <p>{{ t('registration.reg-step-1-1') }}</p>

          <div v-for="category in skillCategories" :key="category.id" class="mb-3">
            <h5>{{ t(category.labelKey) }}</h5>
            <div class="row row-compressed">
              <div v-for="skillId in category.skills" :key="skillId" class="col-6 col-lg-3">
                <BFormCheckbox
                  v-model="form.skills"
                  :value="skillId"
                  :data-testid="`register-skill-${skillId}`"
                >
                  {{ t(skillLabels[skillId]) }}
                </BFormCheckbox>
              </div>
            </div>
          </div>
        </fieldset>

        <fieldset class="panel">
          <legend>{{ t('registration.reg-step-2-1') }}</legend>

          <div class="row">
            <div class="col-lg-6">
              <BFormGroup :label="`${t('general.your_name')}:`" label-for="name">
                <BFormInput id="name" v-model="form.name" required data-testid="register-name" />
                <div v-if="fieldError('name')" class="invalid-feedback d-block" data-testid="register-name-error">
                  {{ fieldError('name') }}
                </div>
              </BFormGroup>
            </div>
            <div class="col-lg-6">
              <BFormGroup :label="`${t('auth.email_address')}:`" label-for="email">
                <BFormInput id="email" v-model="form.email" type="email" required data-testid="register-email" />
                <div v-if="fieldError('email')" class="invalid-feedback d-block" data-testid="register-email-error">
                  {{ fieldError('email') }}
                </div>
              </BFormGroup>
            </div>
            <div class="col-lg-6">
              <BFormGroup :label="`${t('registration.age')}:`" label-for="age" :description="t('registration.age_help')">
                <BFormSelect id="age" v-model="form.age" required data-testid="register-age">
                  <option value="">&nbsp;</option>
                  <option v-for="year in birthYears" :key="year" :value="year">{{ year }}</option>
                </BFormSelect>
                <div v-if="fieldError('age')" class="invalid-feedback d-block" data-testid="register-age-error">
                  {{ fieldError('age') }}
                </div>
              </BFormGroup>
            </div>
            <div class="col-lg-6">
              <BFormGroup :label="`${t('registration.country')}:`" label-for="country" :description="t('registration.country_help')">
                <BFormSelect id="country" v-model="form.country" required data-testid="register-country">
                  <option value="">&nbsp;</option>
                  <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
                </BFormSelect>
                <div v-if="fieldError('country')" class="invalid-feedback d-block" data-testid="register-country-error">
                  {{ fieldError('country') }}
                </div>
              </BFormGroup>
            </div>
            <div class="col-lg-6">
              <BFormGroup :label="`${t('registration.gender')}:`" label-for="gender" :description="t('registration.gender_help')">
                <BFormInput id="gender" v-model="form.gender" data-testid="register-gender" />
              </BFormGroup>
            </div>
            <div class="col-lg-6">
              <BFormGroup
                :label="`${t('registration.town-city')}:`"
                label-for="city"
                :description="t('registration.town-city_help')"
              >
                <BFormInput
                  id="city"
                  v-model="form.city"
                  :placeholder="t('registration.town-city-placeholder')"
                  data-testid="register-city"
                />
              </BFormGroup>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-6">
              <BFormGroup :label="`${t('auth.password')}:`" label-for="password">
                <BFormInput id="password" v-model="form.password" type="password" required data-testid="register-password" />
                <div v-if="fieldError('password')" class="invalid-feedback d-block" data-testid="register-password-error">
                  {{ fieldError('password') }}
                </div>
              </BFormGroup>
            </div>
            <div class="col-lg-6">
              <BFormGroup :label="`${t('auth.repeat_password')}:`" label-for="password-confirm">
                <BFormInput
                  id="password-confirm"
                  v-model="form.password_confirmation"
                  type="password"
                  required
                  data-testid="register-password-confirmation"
                />
              </BFormGroup>
            </div>
          </div>
        </fieldset>

        <fieldset class="panel">
          <legend>{{ t('registration.reg-step-3-heading') }}</legend>
          <BFormCheckbox v-model="form.newsletter" data-testid="register-newsletter">
            {{ t('registration.reg-step-3-label1') }}
          </BFormCheckbox>
          <BFormCheckbox v-model="form.invites" data-testid="register-invites">
            {{ t('registration.reg-step-3-label2') }}
          </BFormCheckbox>
        </fieldset>

        <fieldset class="panel">
          <legend>{{ t('registration.reg-step-4') }}</legend>
          <BFormCheckbox v-model="form.consent_gdpr" required data-testid="register-consent-gdpr">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span v-html="t('registration.reg-step-4-label1')" />
          </BFormCheckbox>
          <div v-if="fieldError('consent_gdpr')" class="invalid-feedback d-block" data-testid="register-consent-gdpr-error">
            {{ fieldError('consent_gdpr') }}
          </div>
          <BFormCheckbox v-model="form.consent_future_data" required data-testid="register-consent-future-data">
            <!-- eslint-disable-next-line vue/no-v-html -->
            <span v-html="t('registration.reg-step-4-label2')" />
          </BFormCheckbox>
          <div
            v-if="fieldError('consent_future_data')"
            class="invalid-feedback d-block"
            data-testid="register-consent-future-data-error"
          >
            {{ fieldError('consent_future_data') }}
          </div>
        </fieldset>

        <div class="button-group d-flex justify-content-end">
          <BButton type="submit" variant="primary" :disabled="submitting" data-testid="register-submit">
            {{ t('registration.complete-profile') }}
          </BButton>
        </div>
      </BForm>
    </div>
  </section>
</template>
