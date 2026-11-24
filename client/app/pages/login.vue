<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'
import { useSsoBridge } from '~/composables/useSsoBridge.js'
import { isTalkSignOn } from '~/utils/talkSignOn.js'

definePageMeta({ layout: 'plain', guest: true })

const { t } = useI18n()
useHead({ title: t('login.login_title') })

const authStore = useAuthStore()
const route = useRoute()

const email = ref(typeof route.query.email === 'string' ? route.query.email : '')

// Where to go after logging in: an app path, or back to Talk's sign-on
// through the SSO bridge (utils/talkSignOn.js). Someone already logged in to
// the app arrives here from Talk's "Log in" when they have no web session;
// send them straight back.
const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : ''
const toTalk = isTalkSignOn(redirect)

// A browser that comes straight back here from the bridge didn't keep the
// web session (cookies blocked?); sending it round again would never end.
const BRIDGED_AT = 'restarters_talk_bridged_at'

function bridgedJustNow() {
  try {
    return Date.now() - Number(sessionStorage.getItem(BRIDGED_AT) || 0) < 60000
  } catch {
    return false
  }
}

async function backToTalk() {
  try {
    sessionStorage.setItem(BRIDGED_AT, String(Date.now()))
  } catch {
    // No sessionStorage: nothing to guard with, go anyway.
  }

  if (!(await useSsoBridge().goTo(redirect, { fallback: false }))) {
    await navigateTo('/dashboard')
  }
}

onMounted(() => {
  if (toTalk && authStore.loggedIn) {
    if (bridgedJustNow()) {
      navigateTo('/dashboard')
    } else {
      backToTalk()
    }
  }
})

// UserController::reset() redirects here with the passwords.updated flash
// after a successful password change; login.blade.php prints it as a success
// alert above the form. Carried as a query flag since the SPA has no session
// flash.
const resetSucceeded = computed(() => route.query.reset === '1')
const password = ref('')
// Honeypot: a field real users never see or fill in (layouts/plain.vue and
// this page render it visually hidden). Bots that blindly fill every form
// field trip it; on submit we silently drop the request instead of hitting
// the API (design.md task brief: "honeypot hidden field my_name").
const myName = ref('')

const submitting = ref(false)
const generalError = ref('')
const fieldErrors = ref({})

async function submit() {
  if (myName.value) {
    // Silently ignore - looks like a bot submission.
    return
  }

  generalError.value = ''
  fieldErrors.value = {}
  submitting.value = true

  try {
    await authStore.login({
      email: email.value,
      password: password.value,
      invite_code: route.query.invite_code,
      invite_type: route.query.invite_type,
      invite_hash: route.query.invite_hash,
    })

    if (toTalk) {
      await backToTalk()
      return
    }

    // Only a path in this app: not '//host' or '/\host', which browsers read as another site.
    await navigateTo(/^\/(?![/\\])/.test(redirect) ? redirect : '/dashboard')
  } catch (err) {
    if (err?.status === 422) {
      fieldErrors.value = err.data?.errors || {}
      generalError.value = t('auth.failed')
    } else {
      generalError.value = t('auth.failed')
    }
  } finally {
    submitting.value = false
  }
}

const emailErrors = computed(() => fieldErrors.value.email || [])
</script>

<template>
  <section>
    <div class="container">
      <div class="row row-expanded pb-3">
        <div class="col-lg-6 d-flex">
          <BForm class="card card__login col-12 panel" data-testid="login-form" @submit.prevent="submit">
            <div style="position: absolute; left: -9999px;" aria-hidden="true">
              <label for="my_name">Name</label>
              <input
                id="my_name"
                v-model="myName"
                type="text"
                name="my_name"
                tabindex="-1"
                autocomplete="off"
                data-testid="login-honeypot"
              >
            </div>

            <legend>{{ t('login.login_title') }}</legend>

            <BAlert v-if="resetSucceeded" :model-value="true" variant="success" data-testid="login-reset-success">
              {{ t('passwords.updated') }}
            </BAlert>

            <BFormGroup :label="`${t('auth.email_address')}:`" label-for="email">
              <BFormInput
                id="email"
                v-model="email"
                type="email"
                required
                autofocus
                data-testid="login-email"
              />
              <div v-if="emailErrors.length" class="invalid-feedback d-block" data-testid="login-email-error">
                {{ emailErrors[0] }}
              </div>
            </BFormGroup>

            <BFormGroup :label="`${t('auth.password')}:`" label-for="password">
              <BFormInput
                id="password"
                v-model="password"
                type="password"
                required
                data-testid="login-password"
              />
            </BFormGroup>

            <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="login-error">
              {{ generalError }}
            </BAlert>

            <div class="row entry-panel__actions">
              <div class="col-6 col-md-8 align-content-center flex-column d-flex">
                <div class="row">
                  <div class="col-12">
                    <NuxtLink class="entry-panel__link" to="/user/recover" data-testid="login-forgot-password-link">
                      {{ t('auth.forgot_password') }}
                    </NuxtLink>
                  </div>
                  <div class="col-12">
                    <NuxtLink
                      class="entry-panel__link"
                      :to="{ path: '/user/register', query: route.query }"
                      data-testid="login-register-link"
                    >
                      {{ t('auth.create_account') }}
                    </NuxtLink>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-4 align-content-center flex-column justify-content-end d-flex">
                <BButton
                  type="submit"
                  variant="primary"
                  :disabled="submitting"
                  data-testid="login-submit"
                >
                  {{ t('auth.login') }}
                </BButton>
              </div>
            </div>
          </BForm>
        </div>
        <div class="col-lg-6">
          <div class="card card__content col-12 panel panel__orange">
            <h3 style="font-weight: 700">{{ t('login.whatis') }}</h3>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div v-html="t('login.whatis_content')" />
            <!-- Legacy linked "Find out more" to /about, which rendered the
                 home/marketing page; the SPA's landing (/) is that same content. -->
            <NuxtLink to="/" class="card__link" data-testid="login-more-link">
              {{ t('login.more') }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
