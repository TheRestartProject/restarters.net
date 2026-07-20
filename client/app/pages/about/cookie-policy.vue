<script setup>
import { useI18n } from 'vue-i18n'
import { useCookieConsent } from '~/composables/useCookieConsent.js'

// Port of resources/views/features/cookie-policy.blade.php (routes/web.php:
// GET /about/cookie-policy, a plain closure - no controller). The legacy
// page's content was hardcoded English with no lang keys at all; this
// introduces client.cookie_policy.* (client-<loc>.json, hand-maintained -
// there is no Laravel-side counterpart to generate from) rather than
// porting the hardcoded strings verbatim into separate sentence fragments,
// per this migration's i18n convention (design.md §7) - except this is a
// legal/policy page (parity-v2 rendered-screenshot review), so most of
// these keys hold the exact HTML legacy renders (bold lead-ins, the
// aboutcookies.org.uk link) and are rendered with v-html rather than
// paraphrased into plain-text sentences, same as e.g. login.vue's
// `login.whatis_content`.
//
// `plain-minimal` layout (logo only, no impact-stats bar): legacy's
// `#logostats-header` on THIS page is hand-rolled in the page itself as
// just a logo link, not `includes/info.blade.php`'s stats bar - confirmed
// by reading both header_plain.blade.php (just <head> boilerplate) and
// this page's own markup. Same "logo-only chrome" need as pages/user/
// recover.vue, which is why layouts/plain-minimal.vue already exists -
// reused here rather than duplicated.
definePageMeta({ layout: 'plain-minimal' })

const { t } = useI18n()
useHead({ title: t('client.cookie_policy.title') })

// Gap 3 (parity-v2 gap list) / rendered-screenshot gap 2: the legacy page's
// ".gdpr-cookie-notice-settings-button" reopens the GDPR cookie-consent
// banner so a visitor can revisit their choice after already accepting it,
// as an inline link inside the "Managing cookies" sentence (not a separate
// button). `accepted` is the same module-level singleton ref components/
// CookieConsent.vue reads (useCookieConsent.js) - flipping it back to false
// here re-shows that banner without touching localStorage, so a hard
// reload still remembers the visitor's prior choice (matching legacy: this
// link only *reopens the notice UI*, it doesn't forget consent by itself -
// re-accepting re-persists the same value).
const { accepted } = useCookieConsent()
function reopenCookieSettings() {
  accepted.value = false
}
</script>

<template>
  <div class="container py-5" data-testid="cookie-policy-page">
    <h1 data-testid="cookie-policy-title">{{ t('client.cookie_policy.title') }}</h1>

    <!-- eslint-disable-next-line vue/no-v-html -->
    <p v-html="t('client.cookie_policy.intro_html')" />
    <p>{{ t('client.cookie_policy.outline') }}</p>

    <h3>{{ t('client.cookie_policy.managing_heading') }}</h3>
    <p>
      {{ t('client.cookie_policy.managing_text_before') }}<a
        href="#"
        data-testid="cookie-policy-reopen-settings"
        @click.prevent="reopenCookieSettings"
      >{{ t('client.cookie_policy.reopen_settings') }}</a>{{ t('client.cookie_policy.managing_text_after') }}
    </p>

    <h2>{{ t('client.cookie_policy.how_heading') }}</h2>
    <p>{{ t('client.cookie_policy.how_text') }}</p>
    <ul>
      <!-- eslint-disable vue/no-v-html -->
      <li v-html="t('client.cookie_policy.type_necessary_html')" />
      <li v-html="t('client.cookie_policy.type_analytical_html')" />
      <li v-html="t('client.cookie_policy.type_marketing_html')" />
      <!-- eslint-enable vue/no-v-html -->
    </ul>

    <h2>{{ t('client.cookie_policy.kinds_heading') }}</h2>
    <!-- eslint-disable-next-line vue/no-v-html -->
    <p v-html="t('client.cookie_policy.kinds_text_html')" />
    <ul>
      <!-- eslint-disable vue/no-v-html -->
      <li v-html="t('client.cookie_policy.first_party_html')" />
      <li v-html="t('client.cookie_policy.third_party_html')" />
      <!-- eslint-enable vue/no-v-html -->
    </ul>

    <!-- eslint-disable-next-line vue/no-v-html -->
    <p v-html="t('client.cookie_policy.persistent_intro_html')" />
    <ul>
      <!-- eslint-disable vue/no-v-html -->
      <li v-html="t('client.cookie_policy.persistent_html')" />
      <li v-html="t('client.cookie_policy.session_html')" />
      <!-- eslint-enable vue/no-v-html -->
    </ul>

    <h3>{{ t('client.cookie_policy.cookies_we_use_heading') }}</h3>

    <h2>{{ t('client.cookie_policy.table_heading') }}</h2>
    <div class="table-responsive">
      <table class="table" style="border: 1px solid gray" data-testid="cookie-policy-table">
        <thead>
          <tr>
            <th>{{ t('client.cookie_policy.table_name') }}</th>
            <th>{{ t('client.cookie_policy.table_domain') }}</th>
            <th>{{ t('client.cookie_policy.table_description') }}</th>
            <th>{{ t('client.cookie_policy.table_type') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ t('client.cookie_policy.row_session_name') }}</td>
            <td>{{ t('client.cookie_policy.row_domain') }}</td>
            <td>{{ t('client.cookie_policy.row_session_description') }}</td>
            <td>{{ t('client.cookie_policy.type_necessary_label') }}</td>
          </tr>
          <tr>
            <td>{{ t('client.cookie_policy.row_xsrf_name') }}</td>
            <td>{{ t('client.cookie_policy.row_domain') }}</td>
            <td>{{ t('client.cookie_policy.row_xsrf_description') }}</td>
            <td>{{ t('client.cookie_policy.type_necessary_label') }}</td>
          </tr>
          <tr>
            <td>{{ t('client.cookie_policy.row_mediawiki_name') }}</td>
            <td>{{ t('client.cookie_policy.row_domain') }}</td>
            <td>{{ t('client.cookie_policy.row_mediawiki_description') }}</td>
            <td>{{ t('client.cookie_policy.type_necessary_label') }}</td>
          </tr>
          <tr>
            <td>{{ t('client.cookie_policy.row_wiki_name') }}</td>
            <td>{{ t('client.cookie_policy.row_domain') }}</td>
            <td>{{ t('client.cookie_policy.row_wiki_description') }}</td>
            <td>{{ t('client.cookie_policy.type_necessary_label') }}</td>
          </tr>
          <tr>
            <td>{{ t('client.cookie_policy.row_analytics_name') }}</td>
            <td>{{ t('client.cookie_policy.row_domain') }}</td>
            <td>{{ t('client.cookie_policy.row_analytics_description') }}</td>
            <td>{{ t('client.cookie_policy.type_analytical_label') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <h2>{{ t('client.cookie_policy.third_party_heading') }}</h2>
    <p>{{ t('client.cookie_policy.third_party_none') }}</p>
  </div>
</template>
