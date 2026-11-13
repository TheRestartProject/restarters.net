import * as Sentry from '@sentry/vue'
import type { Router } from 'vue-router'
import { START_LOCATION } from 'vue-router'
import { useCookieConsent } from '~/composables/useCookieConsent.js'
import { brokenLink, sentryOptions } from '~/utils/sentry.js'

// Browser errors go to the same Sentry project as the Laravel side, as they did
// from the old Vue frontend.
export default defineNuxtPlugin({
  name: 'sentry',
  // Before Nuxt's router (-20): a page that doesn't exist fails the router while
  // the app starts, and no plugin after it runs.
  order: -30,
  setup(nuxtApp) {
    const { analyticsEnabled } = useCookieConsent()
    const options = sentryOptions(useRuntimeConfig().public, () => analyticsEnabled.value)

    if (!options) return

    // Errors in components, plus uncaught errors and promise rejections.
    Sentry.init({ app: nuxtApp.vueApp, ...options })

    // Where each in-app navigation came from, so that a missing page can tell
    // an in-app link from a fresh page load.  Nuxt re-navigates to the first
    // page once the app is created, which isn't a link from anywhere.
    let previousPath: string | null = null
    nuxtApp.hook('app:created', () => {
      ;(nuxtApp.$router as Router).beforeEach((to, from) => {
        if (from === START_LOCATION) {
          previousPath = null
        } else if (from.fullPath !== to.fullPath) {
          previousPath = from.fullPath
        }
      })
    })

    const reported = new Set<string>()

    function reportBrokenLink() {
      const link = brokenLink({
        path: window.location.pathname + window.location.search,
        previousPath,
        referrer: document.referrer,
        location: window.location,
      })

      if (!link) return

      const [host, shape] = link
      const key = `${host} ${shape}`

      // Once per broken link per visit.
      if (reported.has(key)) return
      reported.add(key)

      Sentry.captureMessage(`Broken link from ${host} to ${shape}`, {
        level: 'warning',
        fingerprint: ['broken-link', host, shape],
        tags: { referrer_host: host },
        contexts: { broken_link: { url: window.location.href, referrer: document.referrer, from: previousPath } },
      })
    }

    // Errors Nuxt shows on its error page: from middleware, plugins and routing.
    // A page that doesn't exist is reported only as a broken link.
    nuxtApp.hook('app:error', (error) => {
      if ((error as { statusCode?: number })?.statusCode === 404) {
        reportBrokenLink()
      } else {
        Sentry.captureException(error)
      }
    })
  },
})
