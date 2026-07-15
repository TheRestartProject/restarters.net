const base = require('@playwright/test')

// The API origin as seen from the browser running the tests. In containers
// this is www.example.com:8001 (host-gateway); on a host browser the same
// hostname resolves via /etc/hosts per the repo's standard setup.
const API_URL = process.env.PLAYWRIGHT_API_URL || 'http://www.example.com:8001'

/**
 * Shared test fixture for the SPA suite (successor to
 * tests/Integration/fixtures.js):
 * - window.PLAYWRIGHT_TEST flag for client code that needs to know.
 * - X-Playwright-Test header on API requests (backend cache-bypass), matched
 *   by origin rather than a hardcoded Docker hostname.
 * - Abort Google Maps requests so headless runs never hang on tiles.
 */
exports.test = base.test.extend({
  page: async ({ page }, use) => {
    await page.addInitScript(() => {
      window.PLAYWRIGHT_TEST = true
    })

    const apiOrigin = new URL(API_URL).host

    await page.route('**/*', (route) => {
      const url = route.request().url()

      if (/maps\.googleapis\.com|maps\.gstatic\.com|maps\.google\.|\.ggpht\.com|khms\d/.test(url)) {
        return route.abort()
      }

      if (url.includes(apiOrigin)) {
        return route.continue({
          headers: {
            ...route.request().headers(),
            'X-Playwright-Test': 'true',
          },
        })
      }

      return route.continue()
    })

    await use(page)
  },
})

exports.expect = base.expect
exports.API_URL = API_URL
