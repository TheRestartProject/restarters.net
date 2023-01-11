const { test, expect } = require('@playwright/test');

test('Workbench page loads', async ({ page, baseURL }) => {
  test.slow()
  // Simple test of a page which is rendered using a Vue component.
  await page.goto(baseURL + '/workbench');

  // Wait for Vue to render and create element with id layout.
  const layout = page.locator('#layout')
  await expect(layout).toHaveText(/Volunteer from anywhere/);
});

