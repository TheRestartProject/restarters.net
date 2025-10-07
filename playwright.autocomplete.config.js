// playwright.autocomplete.config.js
// Configuration specifically for the autocomplete test
// @ts-check
const { devices } = require('@playwright/test');

/** @type {import('@playwright/test').PlaywrightTestConfig} */
const config = {
  // Generate trace if a test fails; can be viewed using something like:
  // npx playwright show-trace test-results/group-Can-create-group-Desktop-Chromium-retry1/trace.zip
  // Only use 1 worker, otherwise we hit CSRF issues.
  workers: 1,

  // Only run the autocomplete test
  grep: /Automatic category suggestion from item type/,

  use: {
    trace: 'on',
    // Take screenshot on failure for debugging
    screenshot: 'on',
    // Also capture video on failure for additional context
    video: 'on',
    // Configurable timeout for waitForURL operations
    navigationTimeout: 30000,
    // Note: X-Playwright-Test header is added via fixtures.js route interception
    // to avoid CORS issues with CDN resources
  },
  projects: [
    {
      name: 'Desktop Chromium',
      use: {
        browserName: 'chromium',
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:8000'
      },
    },
  ],
  testDir: 'tests/Integration',
  outputDir: '/tmp/test-results',

  // Flakiness
  // Timeout per test - needs to be less than 10 minutes to avoid Circle CI timeout kicking in.
  timeout: 10 * 60 * 1000, // Increased timeout for the slow autocomplete test
  navigationTimeout: 2 * 60 * 1000,
  actionTimeout: 2 * 60 * 1000,
  retries: 0
};

module.exports = config;