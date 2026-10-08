import { ofetch } from 'ofetch'
import APIError from './APIError.js'

/**
 * Base class for API resource classes (SessionAPI, AuthAPI, ...).
 *
 * Construction is decoupled from Pinia/i18n so it can be unit-tested without
 * a Nuxt app context: the plugin (plugins/api.ts) supplies live getters for
 * the token/locale/unauthorized-callback, and tests can supply stub ones.
 *
 * config:
 *   base            - API base URL (runtimeConfig.public.apiBase)
 *   getToken()      - returns the current auth token, or null/undefined
 *   getLocale()     - returns the current i18n locale code, or null/undefined
 *   onUnauthorized() - called when a 401 comes back from /session (see §4.4
 *                      client conventions: a 401 elsewhere must NOT log out)
 */
export default class BaseAPI {
  constructor(config = {}) {
    this.base = config.base
    this.getToken = config.getToken || (() => null)
    this.getLocale = config.getLocale || (() => null)
    this.onUnauthorized = config.onUnauthorized || (() => {})
  }

  async $request(method, path, { params, body } = {}) {
    const token = this.getToken()
    const locale = this.getLocale()

    // Every API call carries the locale so the server's APISetLocale
    // middleware can localize response strings (design.md §4 conventions).
    const query = { ...(params || {}) }
    if (locale) {
      query.locale = locale
    }

    const headers = {}
    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    try {
      return await ofetch(path, {
        baseURL: this.base,
        method,
        query,
        body,
        headers,
      })
    } catch (error) {
      const status = error?.response?.status ?? error?.statusCode ?? null
      const data = error?.response?._data ?? error?.data ?? null

      // Only /session's 401 means "you are no longer logged in" - a 401 on
      // any other endpoint leaves the session intact (design.md §4.4).
      if (status === 401 && this._isSessionPath(path)) {
        this.onUnauthorized()
      }

      throw new APIError({ status, data, path, method })
    }
  }

  _isSessionPath(path) {
    return path === '/api/v2/session' || path.startsWith('/api/v2/session?')
  }

  $get(path, params) {
    return this.$request('GET', path, { params })
  }

  $post(path, body) {
    return this.$request('POST', path, { body })
  }

  $patch(path, body) {
    return this.$request('PATCH', path, { body })
  }

  $put(path, body) {
    return this.$request('PUT', path, { body })
  }

  $del(path, body) {
    return this.$request('DELETE', path, { body })
  }
}
