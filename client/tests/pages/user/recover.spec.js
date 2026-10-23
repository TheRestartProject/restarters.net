import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import RecoverPage from '../../../app/pages/user/recover.vue'
import { useAuthStore } from '../../../app/stores/auth.js'
import en from '../../../i18n/locales/en.json'
import clientEn from '../../../i18n/locales/client-en.json'

const NuxtLinkStub = { props: ['to'], template: '<a :href="to"><slot /></a>' }

const bvnStubs = {
  BForm: { template: '<form @submit.prevent="$emit(\'submit\', $event)"><slot /></form>' },
  BFormGroup: { template: '<div><slot name="label" /><slot /></div>' },
  BFormInput: {
    props: ['modelValue'],
    template:
      '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" v-bind="$attrs" />',
  },
  BButton: { template: '<button v-bind="$attrs"><slot /></button>' },
  BAlert: { template: '<div><slot /></div>' },
}

function mountRecover() {
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en: { ...en, ...clientEn } } })

  return mount(RecoverPage, {
    global: {
      plugins: [i18n],
      stubs: { NuxtLink: NuxtLinkStub, ...bvnStubs },
    },
  })
}

describe('pages/user/recover', () => {
  let capturedMeta

  beforeEach(() => {
    setActivePinia(createPinia())

    // definePageMeta is stubbed to a no-op in tests/setup.ts - capture what
    // the page passed so the layout choice itself is pinned (matches
    // pages/index.spec.js's pattern for the same stub).
    capturedMeta = null
    vi.stubGlobal('definePageMeta', (meta) => {
      capturedMeta = meta
      return meta
    })
  })

  // Gap fix: legacy forgot-password.blade.php shows just a centered logo, no
  // impact-stats bar - unlike the other plain-layout pages (login/register/
  // reset), which is what plain.vue renders. A prior version put this page on
  // plain.vue too, showing the full logo+stats header. See
  // layouts/plain-minimal.vue's own doc comment.
  it('uses the plain-minimal layout, not the logo+stats plain layout the other guest pages share', () => {
    mountRecover()
    expect(capturedMeta.layout).toBe('plain-minimal')
  })

  it('renders the forgotten-password form', () => {
    const wrapper = mountRecover()

    expect(wrapper.find('[data-testid="recover-page"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="recover-form"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="recover-success"]').exists()).toBe(false)
  })

  // Gap fix: legacy forgot-password.blade.php always re-renders the same
  // form alongside its flash message (App\Http\Controllers\UserController
  // ::recover returns the same view either way) - it was previously hidden
  // here via `v-if="!sent"`, which had no legacy equivalent and blocked
  // retrying with a different address.
  it('shows the server\'s success message and keeps the form visible so the user can try again', async () => {
    const authStore = useAuthStore()
    authStore.forgotPassword = vi.fn().mockResolvedValue({ message: 'We have e-mailed your password reset link!' })

    const wrapper = mountRecover()
    await wrapper.find('[data-testid="recover-email"]').setValue('jane@bloggs.net')
    await wrapper.find('[data-testid="recover-form"]').trigger('submit')
    await flushPromises()

    expect(authStore.forgotPassword).toHaveBeenCalledWith({ email: 'jane@bloggs.net' })
    expect(wrapper.find('[data-testid="recover-success"]').text()).toBe('We have e-mailed your password reset link!')
    expect(wrapper.find('[data-testid="recover-form"]').exists()).toBe(true)
  })

  it('shows the server error message when the request fails', async () => {
    const authStore = useAuthStore()
    authStore.forgotPassword = vi.fn().mockRejectedValue({ data: { message: 'Something went wrong.' } })

    const wrapper = mountRecover()
    await wrapper.find('[data-testid="recover-form"]').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[data-testid="recover-error"]').text()).toBe('Something went wrong.')
  })

  // Gap fix: AuthController::forgotPasswordv2 (passwords.user - "We can't
  // find a user with that e-mail address.") surfaces a "no such account"
  // failure as a 422 validation error on the `email` field, not the
  // generic top-level `message` - that specific reason must reach the
  // user, not a generic "something went wrong".
  it('shows the specific email-not-found reason from errors.email, not the generic top-level message', async () => {
    const authStore = useAuthStore()
    authStore.forgotPassword = vi.fn().mockRejectedValue({
      data: {
        message: 'The given data was invalid.',
        errors: { email: ["We can't find a user with that e-mail address."] },
      },
    })

    const wrapper = mountRecover()
    await wrapper.find('[data-testid="recover-form"]').trigger('submit')
    await flushPromises()

    expect(wrapper.find('[data-testid="recover-error"]').text()).toBe("We can't find a user with that e-mail address.")
  })
})
