<script setup>
// Consent-completion page: authenticated users whose data consents are
// outstanding (admin-created accounts, pre-2018 legacy accounts) land here —
// auth.global redirects them — and cannot mutate anything server-side until
// POST /api/v2/auth/consent succeeds (VerifyUserConsentApi enforces it with
// 403 consent_required).
//
// Legacy handles this exact case INSIDE the same 4-step registration wizard
// used for anonymous sign-up (register-new.blade.php's Auth::check()
// branches), not as a separate flat form: step 1 skills, step 2 personal
// info (name/email pre-filled and disabled, gender field, no password
// fieldset), step 3 newsletter + invites, step 4 consent (with
// consent_past_data auto-granted via a hidden input, no visible checkbox -
// see register-new.blade.php:233-241). Rebuilt here as the same wizard
// shape as pages/user/register.vue rather than a single flat page.
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useSessionStore } from '~/stores/session.js'
import { useProfileStore } from '~/stores/profile.js'

definePageMeta({ auth: true, layout: 'plain' })

const { t, tm } = useI18n()
const route = useRoute()
const sessionStore = useSessionStore()
const profileStore = useProfileStore()
const { $api } = useNuxtApp()

useHead({ title: t('registration.reg-step-4-heading') })

// Step 1: skills - same DB-backed source/grouping as register.vue's step 1
// (App\Helpers\Fixometer::allSkills() via GET /api/v2/skills, grouped
// client-side into the legacy skillCategories() buckets: 1 = Organising,
// 2 = Technical).
const SKILL_CATEGORIES = [
  { id: 1, labelKey: 'Organising skills - please select at least one if you’d like to host events' },
  { id: 2, labelKey: 'Technical skills' },
]

const allSkills = ref([])
const skillsLoading = ref(true)
const skillsError = ref(false)

const skillCategories = computed(() =>
  SKILL_CATEGORIES.map((category) => ({
    ...category,
    skills: allSkills.value.filter((skill) => skill.category === category.id),
  }))
)

onMounted(async () => {
  try {
    const { data } = await $api.skill.list()
    allSkills.value = data || []
  } catch {
    skillsError.value = true
  } finally {
    skillsLoading.value = false
  }
})

const TOTAL_STEPS = 4
const currentStep = ref(1)

function nextStep() {
  if (currentStep.value < TOTAL_STEPS) currentStep.value += 1
}

function prevStep() {
  if (currentStep.value > 1) currentStep.value -= 1
}

const currentYear = new Date().getFullYear()
const ageOptions = computed(() => {
  // Match legacy Fixometer::allAges() and the sibling register.vue: minimum
  // age 18 (birth year currentYear-18), exclusive lower bound at
  // currentYear-100.
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

const form = reactive({
  skills: [],
  age: '',
  country: '',
  city: '',
  gender: '',
  newsletter: false,
  invites: false,
  consent_gdpr: false,
  consent_future_data: false,
})

const submitting = ref(false)
const fieldErrors = ref({})
const generalError = ref('')

function fieldError(field) {
  return fieldErrors.value[field]?.[0] || ''
}

// Which step each field lives on, so a 422 is actually visible to the user
// rather than silently sitting on whichever step they submitted from - same
// convention as register.vue's STEP_FIELDS/jumpToFirstErroredStep.
const STEP_FIELDS = {
  2: ['age', 'country', 'gender', 'city'],
  4: ['consent_gdpr', 'consent_future_data'],
}

function jumpToFirstErroredStep(errors) {
  for (const [step, fields] of Object.entries(STEP_FIELDS)) {
    if (fields.some((field) => errors[field])) {
      currentStep.value = Number(step)
      return
    }
  }
}

async function submit() {
  fieldErrors.value = {}
  generalError.value = ''

  // Belt-and-braces alongside the native `required` attribute on the
  // checkboxes below, same convention as register.vue: both consents are
  // mandatory. consent_past_data isn't asked for here at all - legacy's
  // Auth::check() branch auto-grants it via a hidden always-checked input
  // rather than a visible checkbox (register-new.blade.php:233-241) - see
  // the `consent_past_data: true` sent below.
  const consentErrors = {}
  if (!form.consent_gdpr) {
    consentErrors.consent_gdpr = [t('registration.reg-step-4')]
  }
  if (!form.consent_future_data) {
    consentErrors.consent_future_data = [t('registration.reg-step-4')]
  }
  if (Object.keys(consentErrors).length) {
    fieldErrors.value = consentErrors
    currentStep.value = 4
    return
  }

  submitting.value = true
  try {
    await $api.auth.consent({
      age: form.age,
      country: form.country,
      city: form.city,
      gender: form.gender,
      newsletter: form.newsletter,
      consent_gdpr: true,
      consent_past_data: true,
      consent_future_data: true,
    })

    // POST /api/v2/auth/consent has no skills/invites fields of its own
    // (it's scoped to profile basics + the three consent flags) - the
    // already-existing profile-edit endpoints cover the rest of what the
    // legacy wizard's steps 1/3 collect here. Best-effort: neither should
    // block getting the user past the consent wall if it fails.
    if (form.skills.length) {
      await profileStore.updateSkills({ tags: form.skills }).catch(() => {})
    }
    await profileStore.updateEmailPreferences({ invites: form.invites }).catch(() => {})

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
      jumpToFirstErroredStep(fieldErrors.value)
    }
    generalError.value = e?.data?.message || t('general.error_occurred')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="registration">
    <div class="container">
      <div class="registration-wizard mx-auto">
        <BForm data-testid="consent-form" @submit.prevent="submit">
          <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="consent-error">
            {{ generalError }}
          </BAlert>

          <!-- STEP 1: skills -->
          <div v-if="currentStep === 1" class="panel registration-step" data-testid="consent-step-1">
            <span class="registration-step__status">{{ t('registration.step-1') }}</span>
            <h3>{{ t('registration.reg-step-1-heading') }}</h3>

            <fieldset>
              <legend>{{ t('registration.reg-step-1-1') }}</legend>

              <BAlert v-if="skillsError" :model-value="true" variant="danger" data-testid="consent-skills-error">
                {{ t('client.register.load_error') }}
              </BAlert>
              <p v-else-if="!skillsLoading && !allSkills.length" data-testid="consent-skills-empty">
                {{ t('client.register.no_skills') }}
              </p>
              <template v-else-if="!skillsLoading">
                <div v-for="category in skillCategories" :key="category.id" class="mb-3">
                  <template v-if="category.skills.length">
                    <h5>{{ t(category.labelKey) }}</h5>
                    <div class="row row-compressed">
                      <div v-for="skill in category.skills" :key="skill.id" class="col-6 col-lg-3">
                        <input
                          :id="`consent-skill-${skill.id}`"
                          v-model="form.skills"
                          type="checkbox"
                          class="visually-hidden"
                          :value="skill.id"
                          :data-testid="`consent-skill-${skill.id}`"
                        >
                        <label :for="`consent-skill-${skill.id}`" class="btn btn-checkbox">
                          <span>{{ t(skill.skill_name) }}</span>
                        </label>
                      </div>
                    </div>
                  </template>
                </div>
              </template>
            </fieldset>

            <div class="button-group d-flex justify-content-end">
              <BButton type="button" variant="primary" data-testid="consent-next" @click="nextStep">
                {{ t('registration.next-step') }}
              </BButton>
            </div>
          </div>

          <!-- STEP 2: personal info -->
          <div v-else-if="currentStep === 2" class="panel registration-step" data-testid="consent-step-2">
            <span class="registration-step__status">{{ t('registration.step-2') }}</span>
            <h3>{{ t('registration.reg-step-2-heading') }}</h3>

            <fieldset>
              <legend>{{ t('registration.reg-step-2-1') }}</legend>

              <div class="row">
                <div class="col-lg-6">
                  <BFormGroup label-for="consent-name">
                    <!-- resources/sass/_registration.scss's `label sup { color:
                         #FF0000; font-size: 16px; }` - required-field marker,
                         verbatim from register-new.blade.php's
                         `<label>...:<sup>*</sup></label>` (present on this
                         disabled field too - the Auth::check() branch keeps
                         the same label markup). -->
                    <template #label>{{ t('general.your_name') }}:<sup>*</sup></template>
                    <BFormInput id="consent-name" :model-value="sessionStore.user?.name" disabled data-testid="consent-name" />
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup label-for="consent-email">
                    <template #label>{{ t('auth.email_address') }}:<sup>*</sup></template>
                    <BFormInput id="consent-email" :model-value="sessionStore.user?.email" disabled data-testid="consent-email" />
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup label-for="consent-age" :description="t('registration.age_help')">
                    <template #label>{{ t('registration.age') }}:<sup>*</sup></template>
                    <BFormSelect id="consent-age" v-model="form.age" required data-testid="consent-age">
                      <option value="">&nbsp;</option>
                      <option v-for="y in ageOptions" :key="y" :value="String(y)">{{ y }}</option>
                    </BFormSelect>
                    <div v-if="fieldError('age')" class="invalid-feedback d-block" data-testid="consent-age-error">
                      {{ fieldError('age') }}
                    </div>
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup label-for="consent-country" :description="t('registration.country_help')">
                    <template #label>{{ t('registration.country') }}:<sup>*</sup></template>
                    <BFormSelect id="consent-country" v-model="form.country" required data-testid="consent-country">
                      <option value="">&nbsp;</option>
                      <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
                    </BFormSelect>
                    <div v-if="fieldError('country')" class="invalid-feedback d-block" data-testid="consent-country-error">
                      {{ fieldError('country') }}
                    </div>
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup :label="`${t('registration.gender')}:`" label-for="consent-gender" :description="t('registration.gender_help')">
                    <BFormInput id="consent-gender" v-model="form.gender" data-testid="consent-gender" />
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup
                    :label="`${t('registration.town-city')}:`"
                    label-for="consent-city"
                    :description="t('registration.town-city_help')"
                  >
                    <BFormInput
                      id="consent-city"
                      v-model="form.city"
                      :placeholder="t('registration.town-city-placeholder')"
                      data-testid="consent-city"
                    />
                  </BFormGroup>
                </div>
              </div>
            </fieldset>

            <div class="button-group d-flex justify-content-between">
              <BButton type="button" variant="link" data-testid="consent-prev" @click="prevStep">
                {{ t('registration.previous-step') }}
              </BButton>
              <BButton type="button" variant="primary" data-testid="consent-next" @click="nextStep">
                {{ t('registration.next-step') }}
              </BButton>
            </div>
          </div>

          <!-- STEP 3: contact preferences -->
          <div v-else-if="currentStep === 3" class="panel registration-step" data-testid="consent-step-3">
            <span class="registration-step__status">{{ t('registration.step-3') }}</span>
            <h3>{{ t('registration.reg-step-3-heading') }}</h3>

            <fieldset>
              <legend>{{ t('registration.reg-step-3-2b') }}</legend>
              <legend>{{ t('registration.reg-step-3-1a') }}</legend>

              <BFormCheckbox v-model="form.newsletter" data-testid="consent-newsletter">
                {{ t('registration.reg-step-3-label1') }}
              </BFormCheckbox>
              <BFormCheckbox v-model="form.invites" data-testid="consent-invites">
                {{ t('registration.reg-step-3-label2') }}
              </BFormCheckbox>
            </fieldset>

            <div class="button-group d-flex justify-content-between">
              <BButton type="button" variant="link" data-testid="consent-prev" @click="prevStep">
                {{ t('registration.previous-step') }}
              </BButton>
              <BButton type="button" variant="primary" data-testid="consent-next" @click="nextStep">
                {{ t('registration.next-step') }}
              </BButton>
            </div>
          </div>

          <!-- STEP 4: consent -->
          <div v-else class="panel registration-step" data-testid="consent-step-4">
            <span class="registration-step__status">{{ t('registration.step-4') }}</span>
            <h3>{{ t('registration.reg-step-4-heading') }}</h3>

            <fieldset>
              <legend data-testid="consent-intro">{{ t('registration.reg-step-4') }}</legend>

              <!-- Field/label pairing matches legacy register-new.blade.php
                   step 4 (verified at 07e6abd7cc^): consent_gdpr = Personal
                   Data (label1), consent_future_data = Repair Data
                   (label2). consent_past_data (Historical Repair Data,
                   label3) has no checkbox at all in this Auth::check()
                   branch - it's auto-granted, see the submit() handler. -->
              <BFormCheckbox v-model="form.consent_gdpr" required data-testid="consent-gdpr">
                <!-- eslint-disable-next-line vue/no-v-html — fixed lang string with embedded links -->
                <span v-html="t('registration.reg-step-4-label1')" />
              </BFormCheckbox>
              <div v-if="fieldError('consent_gdpr')" class="invalid-feedback d-block" data-testid="consent-gdpr-error">
                {{ fieldError('consent_gdpr') }}
              </div>

              <BFormCheckbox v-model="form.consent_future_data" required data-testid="consent-future-data">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <span v-html="t('registration.reg-step-4-label2')" />
              </BFormCheckbox>
              <div v-if="fieldError('consent_future_data')" class="invalid-feedback d-block" data-testid="consent-future-data-error">
                {{ fieldError('consent_future_data') }}
              </div>
            </fieldset>

            <div class="button-group d-flex justify-content-between">
              <BButton type="button" variant="link" data-testid="consent-prev" @click="prevStep">
                {{ t('registration.previous-step') }}
              </BButton>
              <BButton type="submit" variant="primary" :disabled="submitting" data-testid="consent-submit">
                {{ t('registration.complete-profile') }}
              </BButton>
            </div>
          </div>
        </BForm>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Step-scoped layout - the .panel chrome itself comes from the global brand
   styles (_panels.scss). The skill-toggle .btn-checkbox override below is
   NOT covered by that global stylesheet, though - see its own comment. */
.registration-wizard {
  max-width: 760px;
}

.registration-step {
  position: relative;
}

.registration-step__status {
  position: absolute;
  top: 20px;
  right: 25px;
  font-size: 14px;
  color: #757575;
}

.registration-step h3 {
  padding-right: 100px;
}

/* Required-field marker: resources/sass/_registration.scss's
   `label sup { color: #FF0000; font-size: 16px; top: auto; left: auto; }`
   (top/left reset cancel the reboot default superscript raise/offset so
   the * sits right after the label text, not floated up and over). */
label sup {
  color: #ff0000;
  font-size: 16px;
  top: auto;
  left: auto;
}

/* Skill toggle buttons (step 1): resources/sass/_buttons.scss's
   label.btn.btn-checkbox - flat light-gray, no border/shadow, sentence-case
   - not the global _buttons.scss .btn-checkbox { @extend .btn-primary },
   which is the bordered/shadowed/uppercase/black-when-checked look used
   everywhere else .btn-checkbox appears. That global rule was ported from
   the widget CSS bundle (resources/global/css/app.scss)
   header_plain.blade.php now loads; register-new.blade.php (which this
   Auth::check() consent flow is also part of - see the top-of-file doc
   comment) originally loaded resources/sass/app.scss instead (git show
   07e6abd7cc^, the commit before Phase F deleted both the Blade view and
   that stylesheet) - restore its rule here, scoped to this page, same as
   register.vue's own copy of this override, rather than touching the
   shared global stylesheet other .btn-checkbox consumers (e.g.
   SkillsTab.vue) still rely on.
   !important on the properties that need to beat @extend .btn-primary's own
   !important background-color, matching the legacy rule's own !importants. */
.btn-checkbox {
  width: 100%;
  font-size: 16px;
  padding: 16px;
  padding-bottom: 13px;
  margin-bottom: 15px;
  background-color: #e4e4e4 !important;
  background-image: none !important;
  border: none !important;
  border-bottom: 3px solid #b6b6b6 !important;
  color: #000;
  border-radius: 0;
  text-transform: none !important;
  letter-spacing: normal;
  font-weight: 400 !important;
  box-shadow: none !important;
}

input[type='checkbox']:focus + .btn-checkbox {
  background-color: #e4e4e4;
  padding: 16px;
  color: #000;
  border-radius: 0;
}

input[type='checkbox']:checked + .btn-checkbox {
  background-color: #ffbe5f !important;
  padding: 16px;
  border: 0 !important;
  color: #222 !important;
  font-size: 16px;
  border-radius: 0;
}
</style>
