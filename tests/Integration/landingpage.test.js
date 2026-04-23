const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login } = require('./utils')

test('Landing page has blurb', async ({page, baseURL}) => {
  test.slow()
  // Simple test of page which is rendered with a Laravel blade.
  await page.goto(baseURL)
  const legend = page.locator('h2').first()
  await expect(legend).toHaveText('Learn and share repair skills with others')
})

test('Can log in', async({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
})