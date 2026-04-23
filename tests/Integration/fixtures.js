const playwright = require('@playwright/test');
const base = playwright.test;

// Extend base test with custom fixtures
const test = base.extend({
  page: async ({ page, baseURL }, use) => {
    // Set global variable on all pages to indicate Playwright tests
    await page.addInitScript(() => {
      window.PLAYWRIGHT_TEST = true;
    });

    // Add X-Playwright-Test header only to requests to our backend, not CDN resources
    // This avoids CORS issues while still allowing cache bypass in ItemController
    await page.route('**/*', async (route) => {
      const url = route.request().url();
      // Only add header for requests to our backend (baseURL)
      if (url.startsWith(baseURL) || url.startsWith('http://restarters_nginx')) {
        await route.continue({
          headers: {
            ...route.request().headers(),
            'X-Playwright-Test': 'true'
          }
        });
      } else {
        // For CDN and external resources, don't add the header
        await route.continue();
      }
    });

    await use(page);
  },
});

module.exports = { test };