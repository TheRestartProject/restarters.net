import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'

// nuxt.config.ts can't be imported directly outside a Nuxt build context
// (defineNuxtConfig is an ambient macro), so this asserts on the source
// text - enough to catch an accidental flip of the load-bearing options.
const configSource = readFileSync(
  join(process.cwd(), 'nuxt.config.ts'),
  'utf-8'
)

describe('nuxt.config.ts sanity', () => {
  it('is a pure SPA (ssr: false)', () => {
    expect(configSource).toMatch(/ssr:\s*false/)
  })

  it('registers the expected modules', () => {
    for (const mod of [
      '@pinia/nuxt',
      'pinia-plugin-persistedstate/nuxt',
      '@bootstrap-vue-next/nuxt',
      '@nuxtjs/i18n',
    ]) {
      expect(configSource).toContain(mod)
    }
  })

  it('reads the API base and isApp flag from the environment', () => {
    expect(configSource).toContain('NUXT_PUBLIC_API_BASE')
    expect(configSource).toContain('NUXT_PUBLIC_IS_APP')
  })
})
