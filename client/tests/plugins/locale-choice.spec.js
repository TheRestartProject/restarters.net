import { beforeEach, describe, expect, it, vi } from 'vitest'

// plugins/00.locale-choice.client.ts records, before @nuxtjs/i18n writes its
// own detected locale into the cookie, whether the visitor had already chosen
// a language - so a network's site can apply its default language otherwise.
describe('plugins/locale-choice', () => {
  let cookie
  let state

  beforeEach(() => {
    cookie = { value: undefined }
    state = {}
    vi.stubGlobal('defineNuxtPlugin', (plugin) => plugin)
    vi.stubGlobal('useCookie', (key) => (key === 'restarters_locale' ? cookie : { value: undefined }))
    vi.stubGlobal('useState', (key, init) => {
      state[key] ??= { value: init() }
      return state[key]
    })
  })

  async function runPlugin() {
    const mod = await import('../../app/plugins/00.locale-choice.client.ts')
    mod.default.setup()
    return state.localeChosen.value
  }

  it('runs before the router and @nuxtjs/i18n', async () => {
    const mod = await import('../../app/plugins/00.locale-choice.client.ts')
    expect(mod.default.order).toBeLessThan(-20)
  })

  it('records that the visitor has chosen a language when the locale cookie is set', async () => {
    cookie.value = 'nl'
    expect(await runPlugin()).toBe(true)
  })

  it('records that the visitor has not chosen a language when there is no locale cookie', async () => {
    expect(await runPlugin()).toBe(false)
  })
})
