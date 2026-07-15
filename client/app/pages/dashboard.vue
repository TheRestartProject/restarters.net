<script setup>
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '~/stores/auth.js'

// Stub dashboard (design.md §6.2 section 2 builds the real one out of
// GET /api/v2/dashboard). This stage only needs an auth-gated landing page
// for post-login redirects to prove the shell/middleware works end to end.
definePageMeta({ auth: true })

const { t } = useI18n()
useHead({ title: t('client.dashboard.title') })

const authStore = useAuthStore()
</script>

<template>
  <div class="container py-5" data-testid="dashboard-page">
    <h1 data-testid="dashboard-welcome">
      {{ t('client.dashboard.welcome', { name: authStore.user?.name }) }}
    </h1>
  </div>
</template>
