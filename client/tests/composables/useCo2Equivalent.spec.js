import { createApp } from 'vue'
import { createI18n } from 'vue-i18n'
import { describe, expect, it } from 'vitest'
import { useCo2Equivalent } from '../../app/composables/useCo2Equivalent.js'
import en from '../../i18n/locales/en.json'

// useCo2Equivalent() calls useI18n(), which needs an active component
// instance (it's always used from within a <script setup> in practice, e.g.
// ImpactStats.vue) - mount a throwaway host component to get one, same
// technique as `@vue/test-utils`' internal `withSetup` helpers.
function withI18n(callback) {
  let result
  const i18n = createI18n({ legacy: false, locale: 'en', messages: { en } })
  const app = createApp({
    setup() {
      result = callback()
      return () => null
    },
  })
  app.use(i18n)
  const el = document.createElement('div')
  app.mount(el)
  app.unmount()
  return result
}

describe('composables/useCo2Equivalent', () => {
  it('compares a small CO2 figure to tree seedlings (60kg each)', () => {
    const { equivalentConsumer } = withI18n(() => useCo2Equivalent())
    // 600 / 60 = 10 seedlings
    expect(equivalentConsumer(600)).toContain('10')
    expect(equivalentConsumer(600)).toContain('tree seedlings')
  })

  it('uses the singular seedling phrasing for a value that rounds to 1', () => {
    const { equivalentConsumer } = withI18n(() => useCo2Equivalent())
    // 60 / 60 = 1 seedling
    expect(equivalentConsumer(60)).toContain('1 tree seedling')
    expect(equivalentConsumer(60)).not.toContain('seedlings')
  })

  it('switches to hectares of trees at the 13501 threshold (12 tonnes/hectare/year)', () => {
    const { equivalentConsumer } = withI18n(() => useCo2Equivalent())
    // 13501 is just over the threshold: 13501 / 12000 rounds to 1 hectare
    expect(equivalentConsumer(13501)).toContain('hectare')
    expect(equivalentConsumer(13501)).not.toContain('seedling')
  })

  it('stays on the seedling comparison just below the threshold', () => {
    const { equivalentConsumer } = withI18n(() => useCo2Equivalent())
    expect(equivalentConsumer(13500)).toContain('seedling')
  })

  it('popoverConsumer returns the matching explanation text for each bracket', () => {
    const { popoverConsumer } = withI18n(() => useCo2Equivalent())
    expect(popoverConsumer(100)).toMatch(/urban setting/)
    expect(popoverConsumer(20000)).toMatch(/plantation or woodlot/)
  })
})
