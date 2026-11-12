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
      // CARTO's raster basemaps are watermarked without a key. Falls back to
      // CARTO_API_KEY, which the Laravel side already has as a Fly secret and
      // Nitro shares the machine environment with - no second secret needed.
      cartoApiKey: process.env.NUXT_PUBLIC_CARTO_API_KEY || process.env.CARTO_API_KEY || '',
      // Browser errors go to the same Sentry project as Laravel.  Reporting is
      // off unless the deployment names an environment (the Fly configs set it
      // alongside SENTRY_ENVIRONMENT), so local development and CI never report.
      sentryDsn: process.env.NUXT_PUBLIC_SENTRY_DSN || 'https://50fd2fa440af4bb4a230f40ca8d8cf90@o879179.ingest.sentry.io/5831645',
      sentryEnvironment: process.env.NUXT_PUBLIC_SENTRY_ENVIRONMENT || '',
      // Off on PR previews (fly.preview.toml), which can't write photos to the
      // live bucket they read from; the upload widgets then say so.
      imageUpload: process.env.NUXT_PUBLIC_IMAGE_UPLOAD !== 'false',
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
      // Same set and order as live's footer menu (config/laravellocalization.php).
      { code: 'de', language: 'de-DE', files: ['de.json', 'client-de.json'] },
      { code: 'en', language: 'en-GB', files: ['en.json', 'client-en.json'] },
      { code: 'es', language: 'es-ES', files: ['es.json', 'client-es.json'] },
      { code: 'fr', language: 'fr-FR', files: ['fr.json', 'client-fr.json'] },
      { code: 'fr-BE', language: 'fr-BE', files: ['fr-BE.json', 'client-fr-BE.json'] },
      { code: 'it', language: 'it-IT', files: ['it.json', 'client-it.json'] },
      { code: 'nl', language: 'nl-NL', files: ['nl.json', 'client-nl.json'] },
      { code: 'nl-BE', language: 'nl-BE', files: ['nl-BE.json', 'client-nl-BE.json'] },
    ],
    detectBrowserLanguage: {
      useCookie: true,
      cookieKey: 'restarters_locale',
      redirectOn: 'root',
    },
  },

  vite: {
    server: {
      // The Playwright container reaches the dev server as
      // http://restarters_client:3000; Vite's allowedHosts protection
      // rejects non-localhost Host headers by default after a container
      // restart. Dev-server only — no effect on production builds.
      allowedHosts: ['restarters_client', 'localhost', 'www.example.com'],
    },
    css: {
      preprocessorOptions: {
        scss: {
          api: 'modern-compiler',
        },
      },
    },
  },
})
