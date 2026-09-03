// Playwright config for the Nuxt SPA suite (client/e2e). The legacy Blade
// suite keeps playwright.config.js/tests/Integration until cutover.
// Local run (host):   npx playwright test --config=playwright.client.config.js
//                     (SPA on http://localhost:${CLIENT_PORT:-3000}, API on :8001)
// Container/CI run:   task docker:test:playwright:client
//                     (PLAYWRIGHT_BASE_URL=http://restarters_client:3000)
const config = {
  testDir: 'client/e2e',
  outputDir: '/tmp/test-results/playwright-client-output',
  timeout: 120 * 1000,
  retries: 2,
  // Auth flows mutate the same seeded users; keep serial until G3 evaluates
  // parallelism (token auth removed the legacy session-CSRF constraint).
  workers: 1,
  use: {
    baseURL: process.env.PLAYWRIGHT_BASE_URL || `http://localhost:${process.env.CLIENT_PORT || 3000}`,
    trace: 'on',
    screenshot: 'on',
    video: 'on',
  },
  projects: [
    {
      name: 'Desktop Chromium',
      use: {
        browserName: 'chromium',
        viewport: { width: 1280, height: 720 },
      },
    },
  ],
}

module.exports = config
