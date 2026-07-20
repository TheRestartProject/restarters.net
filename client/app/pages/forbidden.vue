<script setup>
import { onMounted, ref, watchEffect } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '~/composables/useAuth.js'

// /forbidden - resources/views/user/forbidden.blade.php. Reached from the
// `auth` middleware's role check (middleware/auth.js) or a direct
// navigateTo('/forbidden') when a page discovers server-side it shouldn't
// be here. Gives concrete recourse rather than a dead end: how to report
// it, a diagnostics block the user can paste into the report, and - when
// logged in - ways back into the app.
//
// Legacy's forbidden.blade.php `@extends('layouts.app')`, which only drops
// to the guest ("plain") header for `Auth::guest()` - anyone already signed
// in (the common case for a 403) sees the full site nav, not the bare
// logged-out chrome. `layout: 'plain'` below is just the initial/guest
// default; the watchEffect swaps to 'default' once the session resolves a
// logged-in user.
definePageMeta({ layout: 'plain' })

const { t } = useI18n()
useHead({ title: t('client.forbidden.title') })

const { user, loggedIn, logout } = useAuth()

watchEffect(() => {
  setPageLayout(loggedIn.value ? 'default' : 'plain')
})

// Captured client-side on mount for the diagnostics block below - there's
// no server-rendered request to read Request::url()/URL::previous() from
// in an SPA, so this mirrors them from the browser instead.
const time = ref('')
const url = ref('')
const previousUrl = ref('')

onMounted(() => {
  time.value = new Date().toLocaleString()
  url.value = window.location.href
  previousUrl.value = document.referrer
})

function goBack() {
  window.history.back()
}

async function logoutAndReturn() {
  await logout()
  await navigateTo('/login')
}
</script>

<template>
  <div class="container py-5" data-testid="forbidden-page">
    <h1>{{ t('client.forbidden.title') }}</h1>
    <img class="rounded img-fluid" src="/images/broken-toaster.png" :alt="t('client.forbidden.image_alt')">

    <p>{{ t('client.forbidden.text') }}</p>

    <p>{{ t('client.forbidden.report_intro') }}</p>
    <!-- eslint-disable-next-line vue/no-v-html — fixed lang string with embedded links -->
    <p data-testid="forbidden-report" v-html="t('client.forbidden.report_instructions')" />

    <p class="mb-1">{{ t('client.forbidden.diagnostic_intro') }}</p>
    <ul data-testid="forbidden-diagnostics">
      <li v-if="loggedIn" data-testid="forbidden-diagnostic-user">
        <strong>{{ t('client.forbidden.diagnostic_user') }}</strong>: {{ user?.name }}
      </li>
      <li data-testid="forbidden-diagnostic-time">
        <strong>{{ t('client.forbidden.diagnostic_time') }}</strong>: {{ time }}
      </li>
      <li data-testid="forbidden-diagnostic-url">
        <strong>{{ t('client.forbidden.diagnostic_url') }}</strong>: {{ url }}
      </li>
      <li data-testid="forbidden-diagnostic-previous-url">
        <strong>{{ t('client.forbidden.diagnostic_previous_url') }}</strong>: {{ previousUrl }}
      </li>
    </ul>

    <p>{{ t('client.forbidden.thanks') }}</p>

    <div v-if="loggedIn" data-testid="forbidden-recovery">
      <p class="mb-1">
        {{ t('client.forbidden.recovery_back_prefix') }}
        <BButton variant="link" class="p-0 align-baseline" data-testid="forbidden-back-link" @click="goBack">{{ t('client.forbidden.back_link') }}</BButton>{{ t('client.forbidden.recovery_back_middle') }}
        <NuxtLink to="/dashboard" data-testid="forbidden-dashboard-link">{{ t('client.forbidden.dashboard_link') }}</NuxtLink>.
      </p>
      <p>
        {{ t('client.forbidden.recovery_logout_prefix') }}
        <BButton variant="link" class="p-0 align-baseline" data-testid="forbidden-logout-link" @click="logoutAndReturn">{{ t('client.forbidden.logout_link') }}</BButton>
        {{ t('client.forbidden.recovery_logout_suffix') }}
      </p>
    </div>
  </div>
</template>
