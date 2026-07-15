import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import en from '../i18n/locales/en.json'
import fr from '../i18n/locales/fr.json'
import frBE from '../i18n/locales/fr-BE.json'
import clientEn from '../i18n/locales/client-en.json'
import clientFr from '../i18n/locales/client-fr.json'
import clientFrBE from '../i18n/locales/client-fr-BE.json'

// Each locale is split across two files, merged by @nuxtjs/i18n in order:
// <code>.json is generated from lang/ (translations:export-client, CI-checked)
// and client-<code>.json holds hand-maintained client-only keys.
describe('i18n locales', () => {
  it('loads valid JSON for every configured locale file', () => {
    for (const messages of [en, fr, frBE, clientEn, clientFr, clientFrBE]) {
      expect(messages).toBeTypeOf('object')
    }
  })

  it('resolves generated and client-only keys through vue-i18n', () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'en',
      messages: { en: { ...en, ...clientEn } },
    })

    // Client-only key (client-en.json).
    expect(i18n.global.t('client.app_name')).toBe('Restarters')
    // Generated-from-Laravel key (en.json, lang/en/auth.php).
    expect(i18n.global.t('auth.login')).toBe('Login')
  })
})
