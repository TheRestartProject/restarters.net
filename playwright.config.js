// playwright.config.js
// @ts-check
const { devices } = require('@playwright/test');

/** @type {import('@playwright/test').PlaywrightTestConfig} */
const config = {
  // Generate trace if a test fails; can be viewed using something like:
  // npx playwright show-trace test-results/group-Can-create-group-Desktop-Chromium-retry1/trace.zip
  // Only use 1 worker, otherwise we hit CSRF issues.
  workers: 1,

  // Exclude the slow autocomplete test from the main test run
  grep: /^(?!.*Automatic category suggestion from item type)/,

  use: {
    trace: 'on',
    // Take screenshot on failure for debugging
    screenshot: 'on',
    // Also capture video on failure for additional context
    video: 'on',
    // Configurable timeout for waitForURL operations
    navigationTimeout: 30000,
    // Note: We use route interception to add X-Playwright-Test header only to our backend,
    // not to CDN resources, to avoid CORS issues. See baseURL project config below.
  },
  projects: [
    {
      name: 'Desktop Chromium',
      use: {
        browserName: 'chromium',
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://restarters_nginx'
      },
    },
    // TODO The other browsers don't work reliably yet.
    // {
    //   name: 'Desktop Safari',
    //   use: {
    //     browserName: 'webkit',
    //     viewport: { width: 1200, height: 750 },
    //   }
    // },
    // // Test against mobile viewports.
    // {
    //   name: 'Mobile Chrome',
    //   use: devices['Pixel 5'],
    // },
    // {
    //   name: 'Mobile Safari',
    //   use: devices['iPhone 12'],
    // },
    // {
    //   name: 'Desktop Firefox',
    //   use: {
    //     browserName: 'firefox',
    //     viewport: { width: 800, height: 600 },
    //   }
    // },
  ],
  testDir: 'tests/Integration',
  outputDir: '/tmp/test-results',

  // Flakiness
  // Timeout per test - needs to be less than 10 minutes to avoid Circle CI timeout kicking in.
  timeout: 5 * 60 * 1000,
  navigationTimeout: 2 * 60 * 1000,
  actionTimeout: 2 * 60 * 1000,
  retries: 0
};

module.exports = config;