const {test, expect} = require('@playwright/test')
const { login } = require('./utils')
import faker from '@faker-js/faker';

test('Landing page has blurb', async ({page, baseURL}) => {
  // Need to set faker seed so that we get the same data each time for the screenshot.
  faker.seed(123);

  test.slow()
  // Simple test of page which is rendered with a Laravel blade.
  await page.goto(baseURL)
  const legend = page.locator('h2').first()
  await expect(legend).toHaveText('Learn and share repair skills with others')
  expect(await page.screenshot()).toMatchSnapshot();
})

test('Can log in', async({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
})