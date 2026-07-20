import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RegisterPage from '../../app/pages/user/register.vue'
import { useAuthStore } from '../../app/stores/auth.js'
import en from '../../i18n/locales/en.json'
import clientEn from '../../i18n/locales/client-en.json'

const NuxtLinkStub = {
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
}

const bvnStubs = {
  BForm: { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' },
  BFormGroup: { template: '<div><slot /></div>' },
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
    props: ['modelValue', 'value'],
    template:
      '<input type="checkbox" v-bind="$attrs" @change="$emit(\'update:modelValue\', $event.target.checked)" /><slot />',
  },
  BButton: { template: '<button v-bind="$attrs"><slot /></button>' },
  BAlert: { template: '<div><slot /></div>' },
}

// GET /api/v2/skills, grouped by App\Helpers\Fixometer::skillCategories()
// (1 = Organising, 2 = Technical) - real ids/names, not the hardcoded
// placeholder 1-8 the page used to submit.
const MOCK_SKILLS = [
  { id: 101, skill_name: 'Event organising', description: null, category: 1 },
  { id: 102, skill_name: 'Soldering', description: null, category: 2 },
]

function mountRegister(query = {}, { skillList, emailAvailable } = {}) {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })
  vi.stubGlobal('useRoute', () => ({ query, params: {}, fullPath: '/user/register' }))
  vi.stubGlobal('useNuxtApp', () => ({
    $api: {
      skill: { list: skillList || vi.fn().mockResolvedValue({ data: MOCK_SKILLS }) },
      auth: { emailAvailable: emailAvailable || vi.fn().mockResolvedValue({ data: { available: true, message: 'Email is available' } }) },
    },
  }))

  return mount(RegisterPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, ...bvnStubs },
    },
  })
}

async function clickNext(wrapper) {
  await wrapper.find('[data-testid="register-next"]').trigger('click')
}

async function clickPrev(wrapper) {
  await wrapper.find('[data-testid="register-prev"]').trigger('click')
}

async function fillStep2(wrapper) {
  await wrapper.find('[data-testid="register-name"]').setValue('Bob Fixer')
  await wrapper.find('[data-testid="register-email"]').setValue('bob@bloggs.net')
  await wrapper.find('[data-testid="register-password"]').setValue('passw0rd')
  await wrapper.find('[data-testid="register-password-confirmation"]').setValue('passw0rd')
  await wrapper.find('[data-testid="register-age"]').setValue('1990')
  await wrapper.find('[data-testid="register-country"]').setValue('GB')
}

// Steps 1 -> 4, filling in step 2's required fields along the way and
// leaving step 3's contact preferences at their defaults.
async function goToStep4(wrapper) {
  await clickNext(wrapper) // step 1 -> 2
  await fillStep2(wrapper)
  await clickNext(wrapper) // step 2 -> 3
  await clickNext(wrapper) // step 3 -> 4
}

describe('pages/user/register', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.stubGlobal('navigateTo', vi.fn())
  })

  it('renders a 4-step wizard, one step at a time, with a working Next/Previous', async () => {
    const wrapper = mountRegister()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="register-step-1"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="register-step-2"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('Step 1 of 4')

    await clickNext(wrapper)
    expect(wrapper.find('[data-testid="register-step-1"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="register-step-2"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Step 2 of 4')

    await clickPrev(wrapper)
    expect(wrapper.find('[data-testid="register-step-1"]').exists()).toBe(true)
  })

  it('only shows the submit button on the last step', async () => {
    const wrapper = mountRegister()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="register-submit"]').exists()).toBe(false)

    await clickNext(wrapper) // step 2
    await clickNext(wrapper) // step 3
    await clickNext(wrapper) // step 4
    expect(wrapper.find('[data-testid="register-submit"]').exists()).toBe(true)
  })

  it('fetches skills from the API and renders them as chip buttons grouped by category, not hardcoded ids', async () => {
    const skillList = vi.fn().mockResolvedValue({ data: MOCK_SKILLS })
    const wrapper = mountRegister({}, { skillList })
    await Promise.resolve()
    await Promise.resolve()

    expect(skillList).toHaveBeenCalled()
    expect(wrapper.find('[data-testid="register-skill-101"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="register-skill-102"]').exists()).toBe(true)
    // Real API-provided names, not the old placeholder "skill_1"/"skill_2" text.
    expect(wrapper.text()).toContain('Event organising')
    expect(wrapper.text()).toContain('Soldering')
    // No leftover hardcoded 1-8 ids from the old placeholder scheme.
    expect(wrapper.find('[data-testid="register-skill-1"]').exists()).toBe(false)
  })

  // Gap fix: GET /api/v2/skills orders alphabetically (SkillController::
  // listSkillsv2's own doc comment), but legacy's register-new.blade.php
  // looped over Skills::where('category', $key)->get() with no ORDER BY -
  // i.e. DB/insertion order (database/seeders/DefaultSkills.php). Re-sort by
  // id client-side so the chip order matches legacy rather than the API's.
  it('orders skill chips within a category by id, not the API-alphabetical order', async () => {
    const unordered = [
      { id: 4, skill_name: 'Finding venues', description: null, category: 1 },
      { id: 1, skill_name: 'Publicising events', description: null, category: 1 },
      { id: 2, skill_name: 'Recruiting volunteers', description: null, category: 1 },
    ]
    const skillList = vi.fn().mockResolvedValue({ data: unordered })
    const wrapper = mountRegister({}, { skillList })
    await Promise.resolve()
    await Promise.resolve()

    const ids = wrapper
      .findAll('[data-testid^="register-skill-"]')
      .map((input) => input.attributes('data-testid').replace('register-skill-', ''))

    expect(ids).toEqual(['1', '2', '4'])
  })

  it('shows an error state when the skills fetch fails', async () => {
    const skillList = vi.fn().mockRejectedValue(new Error('network error'))
    const wrapper = mountRegister({}, { skillList })
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="register-skills-error"]').exists()).toBe(true)
  })

  it('shows an empty state when the API returns no skills', async () => {
    const skillList = vi.fn().mockResolvedValue({ data: [] })
    const wrapper = mountRegister({}, { skillList })
    await Promise.resolve()
    await Promise.resolve()

    expect(wrapper.find('[data-testid="register-skills-empty"]').exists()).toBe(true)
  })

  it('pairs each consent checkbox with the correct legal notice (matches consent.vue)', async () => {
    const wrapper = mountRegister()
    await Promise.resolve()
    await goToStep4(wrapper)

    // The BFormCheckbox stub renders the input immediately followed by its
    // slotted (v-html) label - scope the assertion to that sibling, not the
    // whole fieldset, so each checkbox's own notice is checked in isolation.
    const gdpr = wrapper.get('[data-testid="register-consent-gdpr"]').element.nextElementSibling.textContent
    const future = wrapper.get('[data-testid="register-consent-future-data"]').element.nextElementSibling.textContent

    expect(gdpr).toContain('Personal Data')
    expect(future).toContain('Repair Data')
    expect(future).not.toContain('Personal Data')
  })

  it('does not submit when the GDPR/future-data consents are unchecked', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn()

    const wrapper = mountRegister()
    await Promise.resolve()
    await goToStep4(wrapper)

    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()

    expect(authStore.register).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="register-consent-gdpr-error"]').exists()).toBe(true)
  })

  it('submits the selected real skill ids plus the personal fields once both consents are checked', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn().mockResolvedValue({ token: 'tok-1', user: { id: 1 } })
    const navigateToMock = vi.fn()
    vi.stubGlobal('navigateTo', navigateToMock)

    const wrapper = mountRegister({ invite_code: 'abc123', invite_type: 'group' })
    await Promise.resolve()
    await Promise.resolve()

    // Step 1: select a skill chip before moving on.
    await wrapper.find('[data-testid="register-skill-101"]').setValue(true)
    await goToStep4(wrapper)

    await wrapper.find('[data-testid="register-consent-gdpr"]').setValue(true)
    await wrapper.find('[data-testid="register-consent-future-data"]').setValue(true)
    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    expect(authStore.register).toHaveBeenCalledWith(
      expect.objectContaining({
        name: 'Bob Fixer',
        email: 'bob@bloggs.net',
        password: 'passw0rd',
        password_confirmation: 'passw0rd',
        skills: [101],
        consent_gdpr: true,
        consent_future_data: true,
        invite_code: 'abc123',
        invite_type: 'group',
      })
    )
    expect(navigateToMock).toHaveBeenCalledWith('/dashboard')
  })

  it('does not call the API when the honeypot field is filled in', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn()

    const wrapper = mountRegister()
    await Promise.resolve()
    await goToStep4(wrapper)

    await wrapper.find('[data-testid="register-honeypot"]').setValue('I am a bot')
    await wrapper.find('[data-testid="register-consent-gdpr"]').setValue(true)
    await wrapper.find('[data-testid="register-consent-future-data"]').setValue(true)
    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()

    expect(authStore.register).not.toHaveBeenCalled()
  })

  it('renders 422 field errors, jumping back to the step that owns the field', async () => {
    const authStore = useAuthStore()
    authStore.register = vi.fn().mockRejectedValue({
      status: 422,
      data: { message: 'Validation failed', errors: { email: ['The email has already been taken.'] } },
    })

    const wrapper = mountRegister()
    await Promise.resolve()
    await goToStep4(wrapper)

    await wrapper.find('[data-testid="register-consent-gdpr"]').setValue(true)
    await wrapper.find('[data-testid="register-consent-future-data"]').setValue(true)
    await wrapper.find('[data-testid="register-form"]').trigger('submit')
    await Promise.resolve()
    await Promise.resolve()

    // `email` lives on step 2 - the error must actually be visible, not
    // stranded on step 4 where the user submitted from.
    expect(wrapper.find('[data-testid="register-step-2"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="register-email-error"]').text()).toBe(
      'The email has already been taken.'
    )
  })

  // Gap 3: legacy's step-2 email field checks availability on blur
  // (EmailValidation.vue -> POST /user/register/check-valid-email) and
  // shows an inline error immediately, rather than only surfacing a taken
  // email after the full form is submitted.
  describe('email availability check on blur (gap 3)', () => {
    it('calls GET /api/v2/auth/email-available on blur and shows the server message when taken', async () => {
      const emailAvailable = vi.fn().mockResolvedValue({ data: { available: false, message: 'An account with this email address already exists.' } })
      const wrapper = mountRegister({}, { emailAvailable })
      await Promise.resolve()
      await clickNext(wrapper)

      await wrapper.find('[data-testid="register-email"]').setValue('taken@bloggs.net')
      await wrapper.find('[data-testid="register-email"]').trigger('blur')
      await Promise.resolve()
      await Promise.resolve()

      expect(emailAvailable).toHaveBeenCalledWith('taken@bloggs.net')
      expect(wrapper.find('[data-testid="register-email-error"]').text()).toBe(
        'An account with this email address already exists.'
      )
    })

    it('clears the availability error once the user edits the field again', async () => {
      const emailAvailable = vi.fn().mockResolvedValue({ data: { available: false, message: 'Taken' } })
      const wrapper = mountRegister({}, { emailAvailable })
      await Promise.resolve()
      await clickNext(wrapper)

      await wrapper.find('[data-testid="register-email"]').setValue('taken@bloggs.net')
      await wrapper.find('[data-testid="register-email"]').trigger('blur')
      await Promise.resolve()
      await Promise.resolve()
      expect(wrapper.find('[data-testid="register-email-error"]').exists()).toBe(true)

      await wrapper.find('[data-testid="register-email"]').setValue('other@bloggs.net')
      expect(wrapper.find('[data-testid="register-email-error"]').exists()).toBe(false)
    })

    it('does not show an error when the address is available', async () => {
      const emailAvailable = vi.fn().mockResolvedValue({ data: { available: true, message: 'Email is available' } })
      const wrapper = mountRegister({}, { emailAvailable })
      await Promise.resolve()
      await clickNext(wrapper)

      await wrapper.find('[data-testid="register-email"]').setValue('free@bloggs.net')
      await wrapper.find('[data-testid="register-email"]').trigger('blur')
      await Promise.resolve()
      await Promise.resolve()

      expect(wrapper.find('[data-testid="register-email-error"]').exists()).toBe(false)
    })
  })
})
