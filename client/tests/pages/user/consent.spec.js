import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ConsentPage from '../../../app/pages/user/consent.vue'
import { useSessionStore } from '../../../app/stores/session.js'
import { useProfileStore } from '../../../app/stores/profile.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

// Pins the consent checkboxes to the DB fields they set. A prior version bound
// the "Historical Repair Data" text to consent_gdpr and the "Personal Data"
// text to consent_past_data - swapped - so a user consenting to one thing set a
// different field. Legacy register-new.blade.php (07e6abd7cc^) pairs:
//   consent_gdpr        <-> reg-step-4-label1  (Personal Data)
//   consent_future_data <-> reg-step-4-label2  (Repair Data)
// consent_past_data (Historical Repair Data, reg-step-4-label3) has no
// checkbox at all in the Auth::check() branch this page ports - it's
// auto-granted (see the "Rebuild as the same wizard" gap-4 fix).

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

const bvnStubs = {
  BForm: { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' },
  BFormGroup: { template: '<div><slot name="label" /><slot /></div>' },
  BFormInput: {
    props: ['modelValue'],
    template:
      '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs" />',
  },
  BFormSelect: {
    props: ['modelValue'],
    template:
      '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs"><slot /></select>',
  },
  BFormCheckbox: {
    props: ['modelValue'],
    template:
      '<label v-bind="$attrs"><input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" /><slot /></label>',
  },
  BButton: { template: '<button v-bind="$attrs"><slot /></button>' },
  BAlert: { template: '<div><slot /></div>' },
}

// GET /api/v2/skills, grouped by App\Helpers\Fixometer::skillCategories()
// (1 = Organising, 2 = Technical) - same fixture as register.spec.js.
const MOCK_SKILLS = [
  { id: 101, skill_name: 'Event organising', description: null, category: 1 },
  { id: 102, skill_name: 'Soldering', description: null, category: 2 },
]

function mountConsent({ skillList, consent, updateSkills, updateEmailPreferences } = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  vi.stubGlobal('useRoute', () => ({ query: {}, params: {}, fullPath: '/user/consent' }))
  vi.stubGlobal('useNuxtApp', () => ({
    $api: {
      skill: { list: skillList || vi.fn().mockResolvedValue({ data: MOCK_SKILLS }) },
      auth: { consent: consent || vi.fn().mockResolvedValue({}) },
    },
  }))

  const wrapper = mount(ConsentPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, ...bvnStubs },
    },
  })

  const profileStore = useProfileStore()
  profileStore.updateSkills = updateSkills || vi.fn().mockResolvedValue({ tags: [] })
  profileStore.updateEmailPreferences = updateEmailPreferences || vi.fn().mockResolvedValue({ invites: false })

  return { wrapper, profileStore }
}

async function clickNext(wrapper) {
  await wrapper.find('[data-testid="consent-next"]').trigger('click')
}

async function clickPrev(wrapper) {
  await wrapper.find('[data-testid="consent-prev"]').trigger('click')
}

async function fillStep2(wrapper) {
  await wrapper.find('[data-testid="consent-age"]').setValue('1990')
  await wrapper.find('[data-testid="consent-country"]').setValue('GB')
}

async function goToStep4(wrapper) {
  await clickNext(wrapper) // step 1 -> 2
  await fillStep2(wrapper)
  await clickNext(wrapper) // step 2 -> 3
  await clickNext(wrapper) // step 3 -> 4
}

describe('pages/user/consent', () => {
  let sessionStore
  let navigateToMock

  beforeEach(() => {
    setActivePinia(createPinia())
    navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)
    sessionStore = useSessionStore()
    sessionStore.user = { id: 9, name: 'Jane Bloggs', email: 'jane@bloggs.net' }
    sessionStore.fetch = vi.fn().mockResolvedValue()
  })

  // Gap 4: the same 4-step wizard as anonymous registration (skills, personal
  // info, contact preferences, consent), not a single flat form.
  it('renders the same 4-step wizard as register.vue, one step at a time', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()

    expect(wrapper.find('[data-testid="consent-step-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="consent-step-2"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('Step 1 of 4')

    await clickNext(wrapper)
    expect(wrapper.find('[data-testid="consent-step-1"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="consent-step-2"]').exists()).toBe(true)

    await clickPrev(wrapper)
    expect(wrapper.find('[data-testid="consent-step-1"]').exists()).toBe(true)
  })

  it('fetches skills from the API and renders them as chip buttons grouped by category (step 1)', async () => {
    const skillList = vi.fn().mockResolvedValue({ data: MOCK_SKILLS })
    const { wrapper } = mountConsent({ skillList })
    await flushPromises()

    expect(skillList).toHaveBeenCalled()
    expect(wrapper.find('[data-testid="consent-skill-101"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Event organising')
  })

  it('pre-fills name/email from the session and disables both (step 2)', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()
    await clickNext(wrapper)

    const name = wrapper.find('[data-testid="consent-name"]')
    const email = wrapper.find('[data-testid="consent-email"]')
    expect(name.element.value).toBe('Jane Bloggs')
    expect(name.attributes('disabled')).toBeDefined()
    expect(email.element.value).toBe('jane@bloggs.net')
    expect(email.attributes('disabled')).toBeDefined()
  })

  it('has no password fields on step 2 (Auth::check() branch skips the password fieldset)', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()
    await clickNext(wrapper)

    expect(wrapper.find('[data-testid="consent-password"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Password')
  })

  it('includes a gender field on step 2', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()
    await clickNext(wrapper)

    expect(wrapper.find('[data-testid="consent-gender"]').exists()).toBe(true)
  })

  it('offers ages from 18 (not 16), matching register.vue and legacy', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()
    await clickNext(wrapper)

    const currentYear = new Date().getFullYear()
    const years = wrapper
      .findAll('#consent-age option')
      .map((o) => o.attributes('value'))
      .filter((v) => v !== '')
      .map(Number)
    expect(Math.max(...years)).toBe(currentYear - 18)
    expect(Math.min(...years)).toBe(currentYear - 99)
  })

  it('offers both a newsletter opt-in and an invites checkbox on step 3', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()
    await clickNext(wrapper) // step 2
    await fillStep2(wrapper)
    await clickNext(wrapper) // step 3

    const newsletter = wrapper.get('[data-testid="consent-newsletter"]')
    const invites = wrapper.get('[data-testid="consent-invites"]')
    expect(newsletter.text()).toContain(en.registration['reg-step-3-label1'])
    expect(invites.text()).toContain(en.registration['reg-step-3-label2'])
  })

  it('pairs each consent checkbox with the correct legal notice, and has no visible Historical Repair Data checkbox (step 4)', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()
    await goToStep4(wrapper)

    const gdpr = wrapper.get('[data-testid="consent-gdpr"]').text()
    const future = wrapper.get('[data-testid="consent-future-data"]').text()

    expect(gdpr).toContain('Personal Data')
    expect(gdpr).not.toContain('Historical Repair Data')
    expect(future).toContain('Repair Data')
    expect(future).not.toContain('Personal Data')

    // Gap 11: legacy auto-grants consent_past_data via a hidden input in
    // this branch - no visible "Historical Repair Data" checkbox.
    expect(wrapper.find('[data-testid="consent-past-data"]').exists()).toBe(false)
  })

  it('renders the step-4 intro copy above the checkboxes', async () => {
    const { wrapper } = mountConsent()
    await flushPromises()
    await goToStep4(wrapper)

    expect(wrapper.get('[data-testid="consent-intro"]').text()).toContain(en.registration['reg-step-4'])
  })

  it('does not submit when the GDPR/future-data consents are unchecked', async () => {
    const consent = vi.fn()
    const { wrapper } = mountConsent({ consent })
    await flushPromises()
    await goToStep4(wrapper)

    await wrapper.find('[data-testid="consent-form"]').trigger('submit')
    await flushPromises()

    expect(consent).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="consent-gdpr-error"]').exists()).toBe(true)
  })

  it('submits age/country/gender/city/newsletter plus consent_past_data auto-granted true, refreshes the session and redirects', async () => {
    const consent = vi.fn().mockResolvedValue({})
    const { wrapper } = mountConsent({ consent })
    await flushPromises()

    await clickNext(wrapper) // step 2
    await wrapper.find('[data-testid="consent-age"]').setValue('1990')
    await wrapper.find('[data-testid="consent-country"]').setValue('GB')
    await wrapper.find('[data-testid="consent-gender"]').setValue('Non-binary')
    await wrapper.find('[data-testid="consent-city"]').setValue('London')
    await clickNext(wrapper) // step 3
    await wrapper.find('[data-testid="consent-newsletter"] input').setValue(true)
    await clickNext(wrapper) // step 4

    await wrapper.find('[data-testid="consent-gdpr"] input').setValue(true)
    await wrapper.find('[data-testid="consent-future-data"] input').setValue(true)
    await wrapper.find('[data-testid="consent-form"]').trigger('submit')
    await flushPromises()

    expect(consent).toHaveBeenCalledWith({
      age: '1990',
      country: 'GB',
      city: 'London',
      gender: 'Non-binary',
      newsletter: true,
      consent_gdpr: true,
      consent_past_data: true,
      consent_future_data: true,
    })
    expect(sessionStore.fetch).toHaveBeenCalled()
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('persists step-1 skill selections via PATCH /users/me/skills after consent succeeds', async () => {
    const consent = vi.fn().mockResolvedValue({})
    const updateSkills = vi.fn().mockResolvedValue({ tags: [101] })
    const { wrapper } = mountConsent({ consent, updateSkills })
    await flushPromises()

    await wrapper.find('[data-testid="consent-skill-101"]').setValue(true)
    await goToStep4(wrapper)
    await wrapper.find('[data-testid="consent-gdpr"] input').setValue(true)
    await wrapper.find('[data-testid="consent-future-data"] input').setValue(true)
    await wrapper.find('[data-testid="consent-form"]').trigger('submit')
    await flushPromises()

    expect(updateSkills).toHaveBeenCalledWith({ tags: [101] })
  })

  it('persists the invites preference via PATCH /users/me/preferences after consent succeeds', async () => {
    const consent = vi.fn().mockResolvedValue({})
    const updateEmailPreferences = vi.fn().mockResolvedValue({ invites: true })
    const { wrapper } = mountConsent({ consent, updateEmailPreferences })
    await flushPromises()

    await clickNext(wrapper) // step 2
    await fillStep2(wrapper)
    await clickNext(wrapper) // step 3
    await wrapper.find('[data-testid="consent-invites"] input').setValue(true)
    await clickNext(wrapper) // step 4
    await wrapper.find('[data-testid="consent-gdpr"] input').setValue(true)
    await wrapper.find('[data-testid="consent-future-data"] input').setValue(true)
    await wrapper.find('[data-testid="consent-form"]').trigger('submit')
    await flushPromises()

    expect(updateEmailPreferences).toHaveBeenCalledWith({ invites: true })
  })

  it('renders 422 field errors, jumping back to the step that owns the field', async () => {
    const consent = vi.fn().mockRejectedValue({
      status: 422,
      data: { message: 'Validation failed', errors: { age: ['The age field is required.'] } },
    })
    const { wrapper } = mountConsent({ consent })
    await flushPromises()
    await goToStep4(wrapper)

    await wrapper.find('[data-testid="consent-gdpr"] input').setValue(true)
    await wrapper.find('[data-testid="consent-future-data"] input').setValue(true)
    await wrapper.find('[data-testid="consent-form"]').trigger('submit')
    await flushPromises()

    // `age` lives on step 2 - the error must actually be visible, not
    // stranded on step 4 where the user submitted from.
    expect(wrapper.find('[data-testid="consent-step-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="consent-age-error"]').text()).toBe('The age field is required.')
  })
})
