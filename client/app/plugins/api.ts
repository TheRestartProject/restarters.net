import createApi from '../api/index.js'
import { useAuthStore } from '../stores/auth.js'

/**
 * Provides $api - one instance of each resource class (see app/api/), all
 * sharing the runtime config base URL plus live token/locale getters so a
 * single instance can be reused for the whole app session.
 */
export default defineNuxtPlugin((nuxtApp) => {
  const runtimeConfig = useRuntimeConfig()

  const api = createApi({
    base: runtimeConfig.public.apiBase,
    getToken: () => useAuthStore().token,
    getLocale: () => nuxtApp.$i18n?.locale?.value,
    onUnauthorized: () => useAuthStore().clear(),
  })

  return {
    provide: {
      api,
    },
  }
})
