<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'

definePageMeta({ layout: 'plain' })

const { t } = useI18n()
useHead({ title: t('login.login_title') })

const authStore = useAuthStore()
const route = useRoute()

const email = ref(typeof route.query.email === 'string' ? route.query.email : '')
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

    const redirect =
      typeof route.query.redirect === 'string' && route.query.redirect
        ? route.query.redirect
        : '/dashboard'
    await navigateTo(redirect)
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
  <section class="login-page">
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

            <BAlert v-if="generalError" :model-value="true" variant="danger" data-testid="login-error">
              {{ generalError }}
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

            <div class="row entry-panel__actions">
              <div class="col-6 col-md-8 align-content-center flex-column d-flex">
                <div class="row">
                  <div class="col-12">
                    <NuxtLink class="entry-panel__link" to="/user/recover" data-testid="login-forgot-password-link">
                      {{ t('auth.forgot_password') }}
                    </NuxtLink>
                  </div>
                  <div class="col-12">
                    <NuxtLink class="entry-panel__link" to="/user/register" data-testid="login-register-link">
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
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
