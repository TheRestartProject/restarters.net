<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'

// 4-step wizard, ported from resources/views/auth/register-new.blade.php
// (design.md parity rebuild - verified at 07e6abd7cc^): step 1 skills, step 2
// personal info, step 3 contact preferences, step 4 consent. Each
// registration.step-N/reg-step-N-heading pair below is the legacy status
// line + <h3> for that panel.
definePageMeta({ layout: 'plain', guest: true })

const { t, tm } = useI18n()
useHead({ title: t('login.join_title') })

const authStore = useAuthStore()
const route = useRoute()
const { $api } = useNuxtApp()

// Skills (step 1) are DB-backed - App\Helpers\Fixometer::allSkills() via the
// public GET /api/v2/skills (App\Http\Controllers\API\SkillController) -
// grouped client-side into the same two categories the legacy blade looped
// over (Fixometer::skillCategories(): 1 = Organising, 2 = Technical). Both
// the category label and each skill's name are used directly as i18n keys,
// matching the legacy blade's `@lang($skill_category)` / `@lang($skill->skill_name)`
// and the existing pages/skills.vue + SkillsTab.vue client-side equivalents.
const SKILL_CATEGORIES = [
  { id: 1, labelKey: 'Organising skills - please select at least one if you’d like to host events' },
  { id: 2, labelKey: 'Technical skills' },
]

const allSkills = ref([])
const skillsLoading = ref(true)
const skillsError = ref(false)

// GET /api/v2/skills orders alphabetically (a deliberate general-listing
// convenience - see SkillController::listSkillsv2's own doc comment), but
// the legacy blade looped over `Skills::where('category', $key)->get()` with
// no ORDER BY, i.e. DB/insertion order (database/seeders/DefaultSkills.php's
// row order). Re-sort by id here to match that order rather than the API's.
const skillCategories = computed(() =>
  SKILL_CATEGORIES.map((category) => ({
    ...category,
    skills: allSkills.value
      .filter((skill) => skill.category === category.id)
      .sort((a, b) => a.id - b.id),
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

// Legacy's step-2 email field is a dedicated `emailvalidation` component
// (resources/js/components/EmailValidation.vue) that checks availability
// on blur (POST /user/register/check-valid-email) and shows an inline
// invalid-feedback error immediately, rather than only surfacing a taken
// email after the full form is submitted. GET /api/v2/auth/email-available
// is the v2 equivalent (AuthAPI.js#emailAvailable) - already implemented
// server-side, just not wired up here yet.
const emailAvailabilityError = ref('')

async function checkEmailAvailable() {
  if (!form.email) {
    return
  }

  try {
    const { data } = await $api.auth.emailAvailable(form.email)
    emailAvailabilityError.value = data?.available ? '' : data?.message || ''
  } catch {
    // Best-effort, same as the legacy component: a failed availability
    // check doesn't block the user, the real validation happens on submit.
    emailAvailabilityError.value = ''
  }
}

// Which step each field lives on, so a 422 targeting e.g. `email` (step 2)
// is actually visible to the user rather than silently sitting on whichever
// step they submitted from.
const STEP_FIELDS = {
  2: ['name', 'email', 'age', 'country', 'gender', 'city', 'password', 'password_confirmation'],
  3: ['newsletter', 'invites'],
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
  if (myName.value) {
    return
  }

  generalError.value = ''
  fieldErrors.value = {}

  // Belt-and-braces alongside the native `required` attribute on the
  // checkboxes below: both consents are mandatory (matches
  // registration.reg-step-4's copy). Field/label pairing matches
  // pages/user/consent.vue's step-4 (verified against the legacy blade):
  // consent_gdpr <-> reg-step-4-label1 (Personal Data), consent_future_data
  // <-> reg-step-4-label2 (Repair Data). The legacy blade's hidden
  // consent_past_data auto-grant only fires for its Auth::check() branch
  // (an already-authenticated user completing their profile) - out of scope
  // for this anonymous registration form; that flow is pages/user/consent.vue.
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
      jumpToFirstErroredStep(fieldErrors.value)
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
      <div class="registration-wizard mx-auto">
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

          <!-- STEP 1: skills -->
          <div v-if="currentStep === 1" class="panel registration-step" data-testid="register-step-1">
            <span class="registration-step__status">{{ t('registration.step-1') }}</span>
            <h3>{{ t('registration.reg-step-1-heading') }}</h3>

            <fieldset>
              <legend>{{ t('registration.reg-step-1-1') }}</legend>

              <BAlert v-if="skillsError" :model-value="true" variant="danger" data-testid="register-skills-error">
                {{ t('client.register.load_error') }}
              </BAlert>
              <p v-else-if="!skillsLoading && !allSkills.length" data-testid="register-skills-empty">
                {{ t('client.register.no_skills') }}
              </p>
              <template v-else-if="!skillsLoading">
                <div v-for="category in skillCategories" :key="category.id" class="mb-3">
                  <template v-if="category.skills.length">
                    <h5>{{ t(category.labelKey) }}</h5>
                    <div class="row row-compressed">
                      <div v-for="skill in category.skills" :key="skill.id" class="col-6 col-lg-3">
                        <input
                          :id="`skill-${skill.id}`"
                          v-model="form.skills"
                          type="checkbox"
                          class="visually-hidden"
                          :value="skill.id"
                          :data-testid="`register-skill-${skill.id}`"
                        >
                        <label :for="`skill-${skill.id}`" class="btn btn-checkbox">
                          <span>{{ t(skill.skill_name) }}</span>
                        </label>
                      </div>
                    </div>
                  </template>
                </div>
              </template>
            </fieldset>

            <div class="button-group d-flex justify-content-end">
              <BButton type="button" variant="primary" data-testid="register-next" @click="nextStep">
                {{ t('registration.next-step') }}
              </BButton>
            </div>
          </div>

          <!-- STEP 2: personal info -->
          <div v-else-if="currentStep === 2" class="panel registration-step" data-testid="register-step-2">
            <span class="registration-step__status">{{ t('registration.step-2') }}</span>
            <h3>{{ t('registration.reg-step-2-heading') }}</h3>

            <fieldset>
              <legend>{{ t('registration.reg-step-2-1') }}</legend>

              <div class="row">
                <div class="col-lg-6">
                  <BFormGroup label-for="name">
                    <!-- resources/sass/_registration.scss's `label sup { color:
                         #FF0000; font-size: 16px; }` - required-field marker,
                         verbatim from register-new.blade.php's
                         `<label>...:<sup>*</sup></label>`. -->
                    <template #label>{{ t('general.your_name') }}:<sup>*</sup></template>
                    <BFormInput id="name" v-model="form.name" required data-testid="register-name" />
                    <div v-if="fieldError('name')" class="invalid-feedback d-block" data-testid="register-name-error">
                      {{ fieldError('name') }}
                    </div>
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup label-for="email">
                    <template #label>{{ t('auth.email_address') }}:<sup>*</sup></template>
                    <BFormInput
                      id="email"
                      v-model="form.email"
                      type="email"
                      required
                      data-testid="register-email"
                      @update:model-value="emailAvailabilityError = ''"
                      @blur="checkEmailAvailable"
                    />
                    <div v-if="fieldError('email') || emailAvailabilityError" class="invalid-feedback d-block" data-testid="register-email-error">
                      {{ fieldError('email') || emailAvailabilityError }}
                    </div>
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup label-for="age" :description="t('registration.age_help')">
                    <template #label>{{ t('registration.age') }}:<sup>*</sup></template>
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
                  <BFormGroup label-for="country" :description="t('registration.country_help')">
                    <template #label>{{ t('registration.country') }}:<sup>*</sup></template>
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
            </fieldset>

            <fieldset>
              <legend>{{ t('registration.reg-step-2-2') }}</legend>

              <div class="row">
                <div class="col-lg-6">
                  <BFormGroup label-for="password">
                    <template #label>{{ t('auth.password') }}:<sup>*</sup></template>
                    <BFormInput id="password" v-model="form.password" type="password" required data-testid="register-password" />
                    <div v-if="fieldError('password')" class="invalid-feedback d-block" data-testid="register-password-error">
                      {{ fieldError('password') }}
                    </div>
                  </BFormGroup>
                </div>
                <div class="col-lg-6">
                  <BFormGroup label-for="password-confirm">
                    <template #label>{{ t('auth.repeat_password') }}:<sup>*</sup></template>
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

            <div class="button-group d-flex justify-content-between">
              <BButton type="button" variant="link" data-testid="register-prev" @click="prevStep">
                {{ t('registration.previous-step') }}
              </BButton>
              <BButton type="button" variant="primary" data-testid="register-next" @click="nextStep">
                {{ t('registration.next-step') }}
              </BButton>
            </div>
          </div>

          <!-- STEP 3: contact preferences -->
          <div v-else-if="currentStep === 3" class="panel registration-step" data-testid="register-step-3">
            <span class="registration-step__status">{{ t('registration.step-3') }}</span>
            <h3>{{ t('registration.reg-step-3-heading') }}</h3>

            <fieldset>
              <legend>{{ t('registration.reg-step-3-2b') }}</legend>
              <legend>{{ t('registration.reg-step-3-1a') }}</legend>

              <BFormCheckbox v-model="form.newsletter" data-testid="register-newsletter">
                {{ t('registration.reg-step-3-label1') }}
              </BFormCheckbox>
              <BFormCheckbox v-model="form.invites" data-testid="register-invites">
                {{ t('registration.reg-step-3-label2') }}
              </BFormCheckbox>
            </fieldset>

            <div class="button-group d-flex justify-content-between">
              <BButton type="button" variant="link" data-testid="register-prev" @click="prevStep">
                {{ t('registration.previous-step') }}
              </BButton>
              <BButton type="button" variant="primary" data-testid="register-next" @click="nextStep">
                {{ t('registration.next-step') }}
              </BButton>
            </div>
          </div>

          <!-- STEP 4: consent -->
          <div v-else class="panel registration-step" data-testid="register-step-4">
            <span class="registration-step__status">{{ t('registration.step-4') }}</span>
            <h3>{{ t('registration.reg-step-4-heading') }}</h3>

            <fieldset>
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

            <div class="button-group d-flex justify-content-between">
              <BButton type="button" variant="link" data-testid="register-prev" @click="prevStep">
                {{ t('registration.previous-step') }}
              </BButton>
              <BButton type="submit" variant="primary" :disabled="submitting" data-testid="register-submit">
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
   styles (_panels.scss). registration-wizard deliberately has no max-width:
   legacy's register-new.blade.php has no narrowing wrapper of its own, so
   the wizard fills the bootstrap .container it sits in (~1140px), not a
   separate, narrower cap. */
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
   which is the bordered/shadowed/uppercase look used everywhere else
   .btn-checkbox appears. That global rule was ported from the widget CSS
   bundle (resources/global/css/app.scss) header_plain.blade.php now loads;
   register-new.blade.php originally loaded resources/sass/app.scss instead
   (git show 07e6abd7cc^, the commit before Phase F deleted both the Blade
   view and that stylesheet) - restore its rule here, scoped to this page,
   rather than touching the shared global stylesheet other .btn-checkbox
   consumers (e.g. SkillsTab.vue) still rely on.
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
