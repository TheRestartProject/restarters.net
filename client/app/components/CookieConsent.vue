<script setup>
import { useI18n } from 'vue-i18n'
import { useCookieConsent } from '~/composables/useCookieConsent.js'

// Fixed cookie-consent bar, matching the legacy gdpr-cookie-notice banner
// (resources/js/gdpr-cookie-notice/en.js): the description text, a "Cookie
// settings" link to the cookie policy, and an "OK" accept button. Shown until
// the visitor accepts (persisted via useCookieConsent).
const { t } = useI18n()
const { accepted, accept } = useCookieConsent()
</script>

<template>
  <div v-if="!accepted" class="cookie-consent" role="region" aria-label="Cookie notice" data-testid="cookie-consent">
    <p class="cookie-consent__text mb-0">
      {{ t('client.cookies.banner_text') }}
    </p>
    <div class="cookie-consent__actions">
      <NuxtLink to="/about/cookie-policy" class="cookie-consent__settings" data-testid="cookie-consent-settings">
        {{ t('client.cookies.settings') }}
      </NuxtLink>
      <button type="button" class="btn btn-primary cookie-consent__accept" data-testid="cookie-consent-accept" @click="accept">
        {{ t('client.cookies.accept') }}
      </button>
    </div>
  </div>
</template>

<style scoped lang="scss">
// Dark fixed bar across the foot of the viewport, matching the legacy notice.
.cookie-consent {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1050;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: #2b2b2b;
  color: #fff;
  font-size: 0.85rem;
}

.cookie-consent__text {
  flex: 1 1 320px;
}

.cookie-consent__actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  white-space: nowrap;
}

.cookie-consent__settings {
  color: #fff;
  text-decoration: underline;

  &:hover {
    color: #fff;
  }
}
</style>
