const { test as base } = require('@playwright/test');

// Extend base test with custom fixtures
const test = base.extend({
  page: async ({ page }, use) => {
    // Set global variable on all pages to indicate Playwright tests
    await page.addInitScript(() => {
      window.PLAYWRIGHT_TEST = true;
    });
    
    await use(page);
  },
});

module.exports = { test };