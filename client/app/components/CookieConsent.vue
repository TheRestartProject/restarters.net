<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCookieConsent, COOKIE_CATEGORIES } from '~/composables/useCookieConsent.js'

// Fixed cookie-consent bar, matching gdpr-cookie-notice: the description, a
// "Cookie settings" control, and an "OK" accept-all button.
//
// "Cookie settings" opens the per-category modal (gdpr-cookie-notice's
// modal.html), it does NOT navigate to the policy article - that is develop's
// separate "statement" link, which lives inside the modal. Sending the
// settings control to a static article left a visitor no way to decline
// analytics at all.
const { t } = useI18n()
const { decided, choices, acceptAll, save } = useCookieConsent()

const showSettings = ref(false)

// Working copy so cancelling the modal does not persist anything.
const draft = ref({ ...choices.value })

watch(showSettings, (open) => {
  if (open) draft.value = { ...choices.value }
})

// The stored cookie key is develop's misspelled "performace"; the translation
// key is spelled correctly. Mapping here keeps the misspelling confined to the
// persisted format, where changing it would drop existing consent.
const CATEGORY_KEYS = { performace: 'performance', analytics: 'analytics', marketing: 'marketing' }
const categoryKey = (category) => CATEGORY_KEYS[category] ?? category

function onSave() {
  save(draft.value)
  showSettings.value = false
}
</script>

<template>
  <div v-if="!decided" class="cookie-consent" role="region" aria-label="Cookie notice" data-testid="cookie-consent">
    <p class="cookie-consent__text mb-0">
      {{ t('client.cookies.banner_text') }}
    </p>
    <div class="cookie-consent__actions">
      <button
        type="button"
        class="cookie-consent__item cookie-consent__settings"
        data-testid="cookie-consent-settings"
        @click="showSettings = true"
      >
        {{ t('client.cookies.settings') }}
      </button>
      <button type="button" class="cookie-consent__item cookie-consent__accept" data-testid="cookie-consent-accept" @click="acceptAll">
        {{ t('client.cookies.accept') }}
      </button>
    </div>

    <BModal
      v-model="showSettings"
      :title="t('client.cookies.settings')"
      :ok-title="t('client.cookies.save')"
      ok-only
      data-testid="cookie-consent-modal"
      @ok="onSave"
    >
      <!-- Essential is listed but has no switch: develop renders "Always on"
           in place of the toggle, because the site cannot function without it. -->
      <div class="cookie-category" data-testid="cookie-category-essential">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <h3 class="h6 mb-1">{{ t('client.cookies.essential_title') }}</h3>
          <span class="text-muted small text-nowrap">{{ t('client.cookies.always_on') }}</span>
        </div>
        <p class="small text-muted mb-0">{{ t('client.cookies.essential_desc') }}</p>
      </div>

      <div
        v-for="category in COOKIE_CATEGORIES"
        :key="category"
        class="cookie-category"
        :data-testid="`cookie-category-${category}`"
      >
        <div class="d-flex justify-content-between align-items-start gap-3">
          <h3 class="h6 mb-1">{{ t(`client.cookies.${categoryKey(category)}_title`) }}</h3>
          <BFormCheckbox
            v-model="draft[category]"
            switch
            :data-testid="`cookie-toggle-${category}`"
            :aria-label="t(`client.cookies.${categoryKey(category)}_title`)"
          />
        </div>
        <p class="small text-muted mb-0">{{ t(`client.cookies.${categoryKey(category)}_desc`) }}</p>
      </div>

      <NuxtLink to="/about/cookie-policy" data-testid="cookie-consent-statement" @click="showSettings = false">
        {{ t('client.cookies.statement') }}
      </NuxtLink>
    </BModal>
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
  border: 0;
  background: transparent;
  cursor: pointer;
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

.cookie-category {
  padding-bottom: 0.75rem;
  margin-bottom: 0.75rem;
  border-bottom: 1px solid #dee2e6;
}

// The OK accept button - brand orange fill (.gdpr-cookie-notice-nav-item-btn).
.cookie-consent__accept {
  border: 0;
  background: #f9a33f;
  border-radius: 3px;
  cursor: pointer;
}
</style>
