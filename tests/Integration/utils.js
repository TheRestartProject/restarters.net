const {expect} = require('@playwright/test')
const faker = require('faker')

const login = async function(page, baseURL, email = 'jane@bloggs.net', password = 'passw0rd') {
  // Load the login page.
  await page.goto(baseURL)
  await expect(page.locator('legend')).toHaveText('Sign in')

  await page.waitForSelector('#fp_email')
  await page.fill('#fp_email', email)
  await page.fill('#password', password)

  // Wait until the dashboard page loads.
  await Promise.all([
    page.click('button[type=submit]'),
    page.waitForNavigation(),
  ]);

  return page
}

exports.login = login

exports.createGroup = async function(page, baseURL) {
  // Go to groups page
  console.log("Load groups page")
  await page.click('a[href="http://localhost:8000/group"]'),

  // Click on add a new group button
  console.log("Click on create button")
  await page.click('a[href="/group/create"]')
  // await page.goto(baseURL + '/group/create')

  // Name
  await page.fill('#grp_name', faker.company.companyName())

  // Type into the RTE
  await page.fill('.note-editable', faker.lorem.sentence())

  // Always say London for geocoding.
  await page.fill('#autocomplete', 'London')

  await page.click('button[type=submit]')

  // Get redirected to Edit form which should have details section.
  await expect(page.locator('#details'))

  // Return id from URL
  const p = page.url().lastIndexOf('/')
  expect(p).toBeGreaterThan(0)

  const id = page.url().substring(p + 1)
  return id
}