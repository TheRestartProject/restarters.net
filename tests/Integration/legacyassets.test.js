const { test } = require('./fixtures')
const { expect } = require('@playwright/test')

// The MediaWiki wiki imports our CSS via absolute URLs that pre-date the
// Mix -> Vite migration. Vite outputs hashed filenames under /build/, so
// these public, unhashed paths only work if the Vite build also writes copies
// to the legacy locations. Vite plugin in vite.config.js does that copy.

test('GET /css/wiki.css returns CSS (legacy path used by wiki)', async ({page, baseURL}) => {
  const response = await page.request.get(baseURL + '/css/wiki.css')
  expect(response.status()).toBe(200)
  const contentType = response.headers()['content-type'] || ''
  expect(contentType.toLowerCase()).toMatch(/css/)
  const body = await response.text()
  expect(body.length).toBeGreaterThan(100)
})

test('GET /global/css/app.css returns CSS (legacy path used by wiki)', async ({page, baseURL}) => {
  const response = await page.request.get(baseURL + '/global/css/app.css')
  expect(response.status()).toBe(200)
  const contentType = response.headers()['content-type'] || ''
  expect(contentType.toLowerCase()).toMatch(/css/)
  const body = await response.text()
  expect(body.length).toBeGreaterThan(100)
})
