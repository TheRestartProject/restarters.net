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
      <NuxtLink to="/about/cookie-policy" class="cookie-consent__item cookie-consent__settings" data-testid="cookie-consent-settings">
        {{ t('client.cookies.settings') }}
      </NuxtLink>
      <button type="button" class="cookie-consent__item cookie-consent__accept" data-testid="cookie-consent-accept" @click="accept">
        {{ t('client.cookies.accept') }}
      </button>
    </div>
  </div>
</template>

<style scoped lang="scss">
// Dark fixed bar across the foot of the viewport, matching the legacy
// gdpr-cookie-notice (_gdpr-cookie-notice.scss): bar #333, muted-white
// description, and a right-aligned action row whose two items ("Cookie
// settings" + "OK") are bold, non-underlined, button-like text - OK alone
// carries the brand-orange fill.
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
  background: #333;
  color: rgba(255, 255, 255, 0.75);
  font-size: 14px;
}

.cookie-consent__text {
  flex: 1 1 320px;
  color: rgba(255, 255, 255, 0.75);
}

// margin-left: auto pins the row to the right, matching legacy .gdpr-cookie-notice-nav.
.cookie-consent__actions {
  display: flex;
  align-items: center;
  margin-left: auto;
  white-space: nowrap;
}

// Both controls share the legacy .gdpr-cookie-notice-nav-item look: bold white
// text, no underline, generous horizontal padding - so "Cookie settings" reads
// as a button, not a hyperlink.
.cookie-consent__item {
  display: block;
  height: 40px;
  line-height: 40px;
  padding: 0 16px;
  font-size: 15px;
  font-weight: 600;
  color: #fff;
  text-decoration: none;

  &:hover {
    color: #fff;
    text-decoration: none;
  }
}

// The OK accept button - brand orange fill (.gdpr-cookie-notice-nav-item-btn).
.cookie-consent__accept {
  border: 0;
  background: #f9a33f;
  border-radius: 3px;
  cursor: pointer;
}
</style>
