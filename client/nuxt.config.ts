// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },

  // Pure SPA: bootstrap-vue-next has no SSR support, and everything the
  // client needs arrives via the API (see docs/nuxt-migration/design.md §6).
  ssr: false,

  future: {
    compatibilityVersion: 4,
  },

  modules: [
    '@pinia/nuxt',
    'pinia-plugin-persistedstate/nuxt',
    ['@bootstrap-vue-next/nuxt', { css: false }],
    '@nuxtjs/i18n',
    '@nuxt/eslint',
  ],

  css: [
    // Global brand stylesheet: tokens, Bootstrap 5 overrides, nav, footer,
    // typography, buttons/forms/alerts/cards/tables/tabs base — seeded
    // from resources/global/css (design.md §6.3). Page/component-scoped
    // styles do not belong here; see app/assets/css/restarters.scss.
    '~/assets/css/restarters.scss',
  ],

  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8001',
      // Reserved for the future Capacitor build (design.md §1).
      isApp: process.env.NUXT_PUBLIC_IS_APP === 'true',
    },
  },

  app: {
    head: {
      title: 'Restarters',
      htmlAttrs: {
        lang: 'en',
      },
    },
  },

  i18n: {
    // Default langDir ('locales', relative to client/i18n/) resolves to
    // client/i18n/locales/<code>.json — see design.md §7.
    defaultLocale: 'en',
    strategy: 'no_prefix',
    // Several ported lang/*.php strings deliberately carry raw HTML (e.g.
    // login.whatis_content, registration.reg-step-4-label1/2 - consent
    // copy with embedded <a> links), rendered via v-html at the call site.
    // unplugin-vue-i18n's default strict HTML check would hard-fail the
    // build on these; they're intentional, not an XSS foot-gun (fixed
    // strings from our own lang files, not user input).
    compilation: {
      strictMessage: false,
    },
    locales: [
      // <code>.json is GENERATED from lang/ by `php artisan
      // translations:export-client` (CI-checked; never hand-edit).
      // client-<code>.json holds client-only keys (the 'client' group) with
      // no Laravel counterpart, and is hand-maintained.
      { code: 'en', language: 'en-GB', files: ['en.json', 'client-en.json'] },
      { code: 'fr', language: 'fr-FR', files: ['fr.json', 'client-fr.json'] },
      { code: 'fr-BE', language: 'fr-BE', files: ['fr-BE.json', 'client-fr-BE.json'] },
    ],
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'restarters_locale',
      redirectOn: 'root',
    },
  },

  vite: {
    css: {
      preprocessorOptions: {
        scss: {
          api: 'modern-compiler',
        },
      },
    },
  },
})
