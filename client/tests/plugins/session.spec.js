import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useAuthStore } from '../../app/stores/auth.js'
import { useSessionStore } from '../../app/stores/session.js'

// The cold-boot session bootstrap (plugins/session.ts): a persisted token
// must repopulate user/config on full page load, or reloads render the app
// logged-out (the bug the grouptags e2e port exposed).
describe('plugins/session (cold-boot bootstrap)', () => {
  let mockApi
  let mockI18n
  let localeChosen

  beforeEach(() => {
    setActivePinia(createPinia())
    mockApi = {
      session: { fetch: vi.fn().mockResolvedValue({ data: { user: null, config: {}, flags: {}, site: null } }) },
    }
    mockI18n = { setLocale: vi.fn(), localeCodes: { value: ['de', 'en', 'es', 'fr', 'fr-BE', 'it', 'nl', 'nl-BE'] } }
    localeChosen = false
    vi.stubGlobal('useNuxtApp', () => ({ $api: mockApi, $i18n: mockI18n }))
    vi.stubGlobal('useState', () => ({ value: localeChosen }))
    vi.stubGlobal('defineNuxtPlugin', (fn) => fn)
  })

  async function runPlugin() {
    const mod = await import('../../app/plugins/session.ts')
    await mod.default()
  }

  it('fetches the session when a persisted token exists', async () => {
    useAuthStore().token = 'tok-persisted'
    mockApi.session.fetch.mockResolvedValueOnce({
      data: { user: { id: 1, name: 'Jane', role_name: 'Administrator' }, config: {}, flags: {} },
    })

    await runPlugin()

    expect(mockApi.session.fetch).toHaveBeenCalledTimes(1)
    expect(useSessionStore().user?.name).toBe('Jane')
    expect(useAuthStore().user?.role_name).toBe('Administrator')
  })

  // A guest needs the session too: it says which network's site this is, for
  // the logo, the newsletter sign-up and the default language.
  it('fetches the session for guests too', async () => {
    mockApi.session.fetch.mockResolvedValueOnce({
      data: { user: null, config: {}, flags: {}, site: { shortname: 'repairtogether', default_language: 'fr-BE' } },
    })

    await runPlugin()

    expect(mockApi.session.fetch).toHaveBeenCalledTimes(1)
    expect(useSessionStore().site?.shortname).toBe('repairtogether')
    expect(useAuthStore().token).toBeNull()
  })

  // Live (CheckForRepairNetwork): a visitor who hasn't picked a language gets
  // the network's language on its own site, whatever their browser says.
  describe("network site's language", () => {
    function siteIs(site) {
      mockApi.session.fetch.mockResolvedValueOnce({ data: { user: null, config: {}, flags: {}, site } })
    }

    it("switches a visitor who hasn't chosen a language to the network's language", async () => {
      siteIs({ shortname: 'repairtogether', default_language: 'fr-BE' })

      await runPlugin()

      expect(mockI18n.setLocale).toHaveBeenCalledWith('fr-BE')
    })

    it('keeps a language the visitor has already chosen', async () => {
      localeChosen = true
      siteIs({ shortname: 'repairtogether', default_language: 'fr-BE' })

      await runPlugin()

      expect(mockI18n.setLocale).not.toHaveBeenCalled()
    })

    it("leaves the Restarters site on the browser's language", async () => {
      siteIs({ shortname: 'restarters', default_language: 'en' })

      await runPlugin()

      expect(mockI18n.setLocale).not.toHaveBeenCalled()
    })

    it("ignores a network language the client doesn't offer", async () => {
      siteIs({ shortname: 'norway', default_language: 'no' })

      await runPlugin()

      expect(mockI18n.setLocale).not.toHaveBeenCalled()
    })
  })

  it('does not refetch when the session is already loaded', async () => {
    useAuthStore().token = 'tok'
    useSessionStore().loaded = true

    await runPlugin()

    expect(mockApi.session.fetch).not.toHaveBeenCalled()
  })

  it('clears the token when the session fetch fails, so the app treats it as a guest', async () => {
    useAuthStore().token = 'tok-expired'
    mockApi.session.fetch.mockRejectedValueOnce(Object.assign(new Error('500'), { status: 500 }))

    await expect(runPlugin()).resolves.toBeUndefined()

    // A 500 (or the token mapping to a deleted user) is not a /session 401, so
    // BaseAPI did not clear auth. Without clearing it here the token survives,
    // loggedIn stays true, and the auth guard lets the dead session onto
    // /dashboard - which 401s and shows an error while the navbar says guest.
    expect(useAuthStore().token).toBeNull()
    expect(useAuthStore().loggedIn).toBe(false)
  })

  it('clears the token when the session resolves with no user (e.g. a reset preview DB)', async () => {
    useAuthStore().token = 'tok-orphan'
    mockApi.session.fetch.mockResolvedValueOnce({ data: { user: null, config: {}, flags: {} } })

    await runPlugin()

    expect(useAuthStore().token).toBeNull()
    expect(useAuthStore().loggedIn).toBe(false)
  })
})
