const { test, expect } = require('@playwright/test');

test('Landing page has sign in', async ({ page, baseURL }) => {
  // Simple test of page which is rendered with a Laravel blade.
  await page.goto(baseURL);
  const legend = page.locator('legend')
  await expect(legend).toHaveText('Sign in');
});
