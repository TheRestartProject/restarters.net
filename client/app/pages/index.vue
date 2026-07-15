<script setup>
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'

// Logged-in visitors land here transiently on refresh/bookmark of "/" - send
// them straight to the dashboard. Anonymous visitors see a placeholder home
// page (the real marketing landing page is a later migration slice -
// design.md §6.2 section 9).
definePageMeta({
  middleware: [
    () => {
      const authStore = useAuthStore()
      if (authStore.loggedIn) {
        return navigateTo('/dashboard')
      }
    },
  ],
})

const { t } = useI18n()
useHead({ title: t('client.app_name') })
</script>

<template>
  <div class="container py-5" data-testid="home-page">
    <h1 data-testid="home-title">{{ t('client.app_name') }}</h1>
  </div>
</template>
