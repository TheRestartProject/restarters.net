const {expect} = require('@playwright/test')
const faker = require('faker')

const login = async function(page, baseURL, email = 'jane@bloggs.net', password = 'passw0rd') {
  // Load the login page.
  await page.goto(baseURL + '/login')
  await expect(page.locator('legend')).toHaveText('Sign in')

  await page.waitForSelector('#fp_email')
  await page.fill('#fp_email', email)
  await page.fill('#password', password)

  await page.click('button[type=submit]')
  await page.waitForSelector('section.dashboard')

  return page
}

exports.login = login

exports.createGroup = async function(page, baseURL) {
  // Go to groups page
  await page.goto('/group')

  // Click on add a new group button
  await page.click('a[href="/group/create"]')
  // await page.goto(baseURL + '/group/create')

  // Name
  await page.fill('#grp_name', faker.company.companyName())

  // Type into the RTE
  await page.fill('.ql-editor', faker.lorem.sentence())

  // Always say London for geocoding.
  await page.fill('#autocomplete', 'London')
  await page.fill('.timezone', 'Europe/London')

  await page.click('button[type=submit]')

  // Should get redirected to Edit form.  We used to wait on #details, but this stopped working for reasons we don't
  // understand.  It may be as design in Playwright.  However the page URL will have been updated and we can use that
  // to check that the create redirected to edit.
  await page.waitForURL('**/edit/**');

  // Return id from URL
  const p = page.url().lastIndexOf('/')
  expect(p).toBeGreaterThan(0)

  const id = page.url().substring(p + 1)
  return id
}

exports.createEvent = async function(page, baseURL, idgroups) {
  // Go to groups page
  await page.goto('/group/view/' + idgroups)

  // Click on Add New Event button
  await page.click('a[href="/party/create"]')

  // Venue name
  await page.fill('#event_name', faker.company.companyName())

  // Select the group.  Bit hard to get the select to open, but tabbing from the previous field works.
  await page.click('#event_link')
  await page.keyboard.press('Tab')
  await page.click('.multiselect__content-wrapper > .multiselect__content > .multiselect__element > .multiselect__option--highlight > span')

  // Type into the RTE
  await page.fill('.ql-editor', faker.lorem.sentence())

  // Set a date.
  await page.click('#event_date button')
  await page.click('#event_date .b-calendar-grid > .b-calendar-grid-body > .row:last-child .btn:last-child')

  await page.click('#event_time input[name="start"]')
  await page.fill('#event_time input[name="start"]', '13:00')

  await page.click('#event_time input[name="end"]')
  await page.fill('#event_time input[name="end"]', '14:00')

  // Use group location.
  await page.click('.event-address .btn-primary')

  await page.click('button[type=submit]')

  // Should get redirected to Edit form.
  await page.waitForURL('**/edit/**');

  // Return id from URL
  const p = page.url().lastIndexOf('/')
  expect(p).toBeGreaterThan(0)

  const id = page.url().substring(p + 1)
  return id
}

exports.createEvent = async function(page, baseURL, idgroups, past) {
  // Go to groups page
  await page.goto('/group/view/' + idgroups)

  // Click on Add New Event button
  await page.click('a[href="/party/create"]')

  // Venue name
  await page.fill('#event_name', faker.company.companyName())

  // Select the group.  Bit hard to get the select to open, but tabbing from the previous field works.
  await page.click('#event_link')
  await page.keyboard.press('Tab')
  await page.click('.multiselect__content-wrapper > .multiselect__content > .multiselect__element > .multiselect__option--highlight > span')

  // Type into the RTE
  await page.fill('.ql-editor', faker.lorem.sentence())

  // Set a date.
  await page.click('#event_date button')

  if (past) {
    // Go back a month
    await page.locator('[aria-label="Previous month"]').click()
  }

  await page.click('#event_date .b-calendar-grid > .b-calendar-grid-body > .row:last-child .btn:last-child')

  await page.click('#event_time input[name="start"]')
  await page.fill('#event_time input[name="start"]', '13:00')

  await page.click('#event_time input[name="end"]')
  await page.fill('#event_time input[name="end"]', '14:00')

  // Use group location.
  await page.click('.event-address .btn-primary')

  await page.click('button[type=submit]')

  // Should get redirected to Edit form.
  await page.waitForURL('**/edit/**');

  // Return id from URL
  const p = page.url().lastIndexOf('/')
  expect(p).toBeGreaterThan(0)

  const id = page.url().substring(p + 1)
  return id
}

exports.approveEvent = async function(page, baseURL, idevents) {
  // Go to event edit page.
  await page.goto('/party/edit/' + idevents)

  // Set approve.
  await page.selectOption('.event-approve .custom-select', 'approve')

  // Approve
  await page.locator('text=Save Event').click()

  // Should show change.
  await page.locator('text=Event details updated.')
}

exports.addDevice = async function(page, baseURL, idevents, powered) {
  // Go to event edit page.
  await page.goto('/party/view/' + idevents)

  await page.locator(powered ? '.add-powered-device-desktop' : '.add-unpowered-device-desktop').click()

  // Tab to category
  await page.keyboard.press('Tab')
  await page.keyboard.press('Enter')

  await page.locator('text=Add item >> visible=true').click()

  // Wait for device to show.
  await expect(page.locator('h3 >> text=Misc')).toHaveCount(2)
}

exports.unfollowGroup = async function(page, idgroups) {
  await page.goto('/group/view/' + idgroups)

  await page.click('#groupactions .dropdown-toggle >> visible=true')

  await page.click('#groupactions .dropdown-menu > li:nth-child(6) > .dropdown-item >> visible=true')

  await page.click('#confirmmodal .btn-primary')

  await page.locator('text=You have now unfollowed')
}