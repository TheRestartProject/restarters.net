<script setup>
import { useI18n } from 'vue-i18n'

definePageMeta({ layout: 'plain' })

const { t } = useI18n()
useHead({ title: t('client.forbidden.title') })
</script>

<template>
  <div class="container py-5" data-testid="forbidden-page">
    <h1>{{ t('client.forbidden.title') }}</h1>
    <p>{{ t('client.forbidden.text') }}</p>
    <NuxtLink to="/" data-testid="forbidden-home-link">{{ t('client.forbidden.home_link') }}</NuxtLink>
  </div>
</template>
