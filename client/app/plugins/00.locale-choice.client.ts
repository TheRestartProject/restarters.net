// Whether the visitor had already chosen a language when the app loaded.
// @nuxtjs/i18n writes the locale it detects from the browser into the same
// cookie as a choice made in the language menu, so this has to look before
// it runs (order -30: ahead of the router and the i18n plugins). The session
// plugin then gives a visitor who hadn't chosen the language of the network
// whose site this is, as live's CheckForRepairNetwork does.
export default defineNuxtPlugin({
  name: 'locale-choice',
  order: -30,
  setup() {
    useState('localeChosen', () => !!useCookie('restarters_locale').value)
  },
})
