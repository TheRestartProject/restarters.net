<script setup>
import { useI18n } from 'vue-i18n'
import IconLogo from '~/components/icons/IconLogo.vue'
import ToastContainer from '~/components/ToastContainer.vue'

// Minimal chrome for auth pages (login/register/recover/reset), mirroring
// layouts/header_plain.blade.php's "show_login_join_to_anons" nav: just the
// brand mark plus login/join links, no full AppNavbar/AppFooter.
const { t } = useI18n()
</script>

<template>
  <div class="d-flex flex-column min-vh-100">
    <header class="container container-nav py-3 d-flex justify-content-between align-items-center">
      <NuxtLink to="/" data-testid="plain-logo">
        <IconLogo />
      </NuxtLink>
      <ul class="list-unstyled d-flex mb-0">
        <li class="ml-3">
          <NuxtLink to="/login" data-testid="plain-nav-login">{{ t('login.login_title') }}</NuxtLink>
        </li>
        <li class="ml-3">
          <NuxtLink to="/user/register" data-testid="plain-nav-join">{{ t('login.join_title') }}</NuxtLink>
        </li>
      </ul>
    </header>
    <main class="flex-grow-1">
      <slot />
    </main>
    <ToastContainer />
  </div>
</template>
