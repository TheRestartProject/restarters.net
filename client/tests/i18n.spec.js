import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import en from '../i18n/locales/en.json'
import fr from '../i18n/locales/fr.json'
import frBE from '../i18n/locales/fr-BE.json'
import clientEn from '../i18n/locales/client-en.json'
import clientFr from '../i18n/locales/client-fr.json'
import clientFrBE from '../i18n/locales/client-fr-BE.json'
import nlBE from '../i18n/locales/nl-BE.json'
import clientNlBE from '../i18n/locales/client-nl-BE.json'

const LOCALE_DIR = join(dirname(fileURLToPath(import.meta.url)), '../i18n/locales')

// Each locale is split across two files, merged by @nuxtjs/i18n in order:
// <code>.json is generated from lang/ (translations:export-client, CI-checked)
// and client-<code>.json holds hand-maintained client-only keys.
const LOCALES = ['en', 'fr', 'fr-BE', 'de', 'es', 'it', 'nl', 'nl-BE']

// Live's language menu (config/laravellocalization.php) - nuxt.config.ts must offer exactly these.
describe('i18n locale set', () => {
  it('ships a generated and a client file for each of live\'s locales', () => {
    for (const code of LOCALES) {
      for (const file of [`${code}.json`, `client-${code}.json`]) {
        const messages = JSON.parse(readFileSync(join(LOCALE_DIR, file), 'utf8'))
        expect(messages).toBeTypeOf('object')
      }
      // Generated file carries real translations, not just an empty shell.
      expect(Object.keys(JSON.parse(readFileSync(join(LOCALE_DIR, `${code}.json`), 'utf8'))).length).toBeGreaterThan(10)
    }
  })

  it('registers the same locales in nuxt.config.ts and the switcher', () => {
    const config = readFileSync(join(LOCALE_DIR, '../../nuxt.config.ts'), 'utf8')
    const switcher = readFileSync(join(LOCALE_DIR, '../../app/components/LocaleSwitcher.vue'), 'utf8')
    const codes = (src, re) => [...src.matchAll(re)].map((m) => m[1])
    expect(codes(config, /\{ code: '([\w-]+)', language/g)).toEqual(['de', 'en', 'es', 'fr', 'fr-BE', 'it', 'nl', 'nl-BE'])
    expect(codes(switcher, /\{ code: '([\w-]+)', native/g)).toEqual(['de', 'en', 'es', 'fr', 'fr-BE', 'it', 'nl', 'nl-BE'])
  })

  it('falls back to English for strings a locale lacks', () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'nl-BE',
      fallbackLocale: 'en',
      missingWarn: false,
      fallbackWarn: false,
      messages: { en: { ...en, ...clientEn }, 'nl-BE': { ...nlBE, ...clientNlBE } },
    })
    expect(i18n.global.t('client.app_name')).toBe('Restarters')
    expect(i18n.global.t('auth.login')).not.toMatch(/^auth\./)
  })
})

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
