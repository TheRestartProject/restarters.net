// vue-i18n options for @nuxtjs/i18n (it loads <rootDir>/i18n/i18n.config.ts).
//
// A string missing from French or Belgian French shows the English text, as
// Laravel's fallback_locale does on live, rather than the key itself (e.g.
// "passwords.updated" after a password reset).
export default defineI18nConfig(() => ({
  fallbackLocale: 'en',
  missingWarn: false,
  fallbackWarn: false,
}))
