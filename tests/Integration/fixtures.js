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
      } else if (/maps\.googleapis\.com|maps\.gstatic\.com|maps\.google\.|\.ggpht\.com|khms\d/i.test(url)) {
        // Google Maps JS and tiles never finish loading in the headless Docker
        // environment, so the page 'load' event never fires and any navigation that
        // waits for it (the default for page.goto) hangs until the per-test timeout —
        // which silently eats >10 min and trips CircleCI's no-output timeout. The app
        // already tolerates the map not rendering, so abort these requests; 'load' then
        // fires and navigations complete. No test asserts on the map.
        await route.abort();
      } else {
        // For other CDN and external resources, don't add the header
        await route.continue();
      }
    });

    await use(page);
  },
});

module.exports = { test };