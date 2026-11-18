<script setup>
import LogoStatsHeader from '~/components/LogoStatsHeader.vue'
import LocaleSwitcher from '~/components/LocaleSwitcher.vue'
import ToastContainer from '~/components/ToastContainer.vue'
import CookieConsent from '~/components/CookieConsent.vue'

// Chrome for the logged-out pages (landing / login / register / recover /
// reset). Legacy header_plain showed the brand mark + the global impact-stats
// bar (includes/info.blade.php, #logostats-header) on these pages - NOT a
// login/join nav. The `show_login_join_to_anons` login/join header was only set
// on other pages (events/view for anons, faultcat), none of which are in this
// SPA's plain-layout set - so the login/join links a prior version rendered
// here were a parity divergence. The language selector partial
// (partials.languages) sits at the foot.
</script>

<template>
  <div class="plain-layout d-flex flex-column">
    <!-- .login-page: live's wrapper (top padding, entry-form styling) - _entry.scss -->
    <main class="login-page flex-grow-1">
      <div class="container">
        <LogoStatsHeader />
      </div>
      <slot />
    </main>
    <!-- No .container: live's fixed .language-bar spans the full viewport. -->
    <footer data-testid="plain-language-bar">
      <LocaleSwitcher />
    </footer>
    <ToastContainer />
    <CookieConsent />
  </div>
</template>
