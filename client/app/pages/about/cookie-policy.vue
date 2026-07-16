<script setup>
import { useI18n } from 'vue-i18n'

// Port of resources/views/features/cookie-policy.blade.php (routes/web.php:
// GET /about/cookie-policy, a plain closure - no controller). The legacy
// page's content was hardcoded English with no lang keys at all; this
// introduces client.cookie_policy.* (client-<loc>.json, hand-maintained -
// there is no Laravel-side counterpart to generate from) rather than
// porting the hardcoded strings verbatim, per this migration's i18n
// convention (design.md §7). The legacy page also links a
// ".gdpr-cookie-notice-settings-button" to reopen the GDPR cookie-consent
// banner - no such banner has been ported to the Nuxt client yet (see
// docs/nuxt-migration/api-gaps.md), so that link is dropped rather than
// wired to nothing.
definePageMeta({ layout: 'plain' })

const { t } = useI18n()
useHead({ title: t('client.cookie_policy.title') })
</script>

<template>
  <div class="container py-5" data-testid="cookie-policy-page">
    <h1 data-testid="cookie-policy-title">{{ t('client.cookie_policy.title') }}</h1>

    <p>{{ t('client.cookie_policy.intro') }}</p>
    <p>{{ t('client.cookie_policy.outline') }}</p>

    <h3>{{ t('client.cookie_policy.managing_heading') }}</h3>
    <p>{{ t('client.cookie_policy.managing_text') }}</p>

    <h2>{{ t('client.cookie_policy.how_heading') }}</h2>
    <p>{{ t('client.cookie_policy.how_text') }}</p>
    <ul>
      <li>{{ t('client.cookie_policy.type_necessary') }}</li>
      <li>{{ t('client.cookie_policy.type_analytical') }}</li>
      <li>{{ t('client.cookie_policy.type_marketing') }}</li>
    </ul>

    <h2>{{ t('client.cookie_policy.kinds_heading') }}</h2>
    <p>{{ t('client.cookie_policy.kinds_text') }}</p>

    <h2>{{ t('client.cookie_policy.table_heading') }}</h2>
    <div class="table-responsive">
      <table class="table" data-testid="cookie-policy-table">
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
