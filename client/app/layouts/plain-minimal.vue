<script setup>
import IconLogo from '~/components/icons/IconLogo.vue'
import LocaleSwitcher from '~/components/LocaleSwitcher.vue'
import ToastContainer from '~/components/ToastContainer.vue'
import CookieConsent from '~/components/CookieConsent.vue'

// Chrome for /user/recover only. plain.vue (login/register/reset/etc) shows
// the brand mark + impact-stats bar (includes/info.blade.php,
// #logostats-header) - but legacy's forgot-password.blade.php diverges from
// those: it includes just includes/logo.blade.php, a centered brand mark
// with no stats bar at all (git show 07e6abd7cc^, the commit before Phase F
// deleted the Blade + Vue 2 frontend). A dedicated layout rather than a prop
// on plain.vue/LogoStatsHeader, so the other plain-layout pages - which do
// want the stats bar - are untouched.
</script>

<template>
  <div class="d-flex flex-column min-vh-100">
    <header class="container container-nav py-3 d-flex justify-content-center">
      <NuxtLink to="/" data-testid="plain-minimal-logo">
        <IconLogo />
      </NuxtLink>
    </header>
    <main class="flex-grow-1">
      <slot />
    </main>
    <footer class="container d-flex justify-content-end py-3" data-testid="plain-language-bar">
      <LocaleSwitcher />
    </footer>
    <ToastContainer />
    <CookieConsent />
  </div>
</template>
