import { watch } from 'vue'
import { applyMomentLocale } from '../utils/momentLocale.js'

// Keep moment's locale in step with the active vue-i18n locale: at startup,
// and whenever the user switches language.
export default defineNuxtPlugin((nuxtApp) => {
  const locale = nuxtApp.$i18n?.locale

  if (!locale) return

  applyMomentLocale(locale.value)
  watch(locale, (code) => applyMomentLocale(code))
})
