// Playwright config for the VISUAL PARITY capture harness (client/parity).
//
// Deliberately separate from playwright.client.config.js (client/e2e) so the
// CI suite never picks this up: it needs live-site credentials and outbound
// network access, and it asserts nothing - it only captures matched
// dev-vs-live screenshot pairs for diffing.
//
// Run: task parity:capture
const config = {
  testDir: 'client/parity',
  outputDir: '/tmp/test-results/parity-output',
  // Live pages + full-page screenshots at two viewports are slow. This is a
  // whole-suite-of-pages budget, not a single-page one: each `logged-in pages`
  // test walks ~28 URLs in one go, and the legacy side additionally blocks
  // ~5s per page on a Discourse cURL timeout when that container is down. At
  // 300s this silently truncated the desktop run partway through the page
  // list, so pages late in the list were never captured and looked simply
  // absent rather than failed.
  timeout: 1800 * 1000,
  retries: 0,
  // Serial: never hammer the live site, and keep capture order deterministic.
  workers: 1,
  use: {
    trace: 'off',
    video: 'off',
    // Each spec navigates with absolute URLs (two different systems).
    ignoreHTTPSErrors: true,
  },
  projects: [
    {
      name: 'parity-chromium',
      use: { browserName: 'chromium' },
    },
  ],
}

module.exports = config
