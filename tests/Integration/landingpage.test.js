const {test, expect} = require('@playwright/test')
const { login } = require('./utils')

test('Landing page has sign in', async ({page, baseURL}) => {
  // Simple test of page which is rendered with a Laravel blade.
  await page.goto(baseURL)
  const legend = page.locator('legend')
  await expect(legend).toHaveText('Sign in')
})

test('Can log in', async({page, baseURL}) => {
  await login(page, baseURL)
})