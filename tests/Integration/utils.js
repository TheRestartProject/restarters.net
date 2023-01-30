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
  await page.goto('/group', { timeout: 30000 })

  // Click on add a new group button
  await page.click('a[href="/group/create"]')
  // await page.goto(baseURL + '/group/create')

  // Name
  await page.fill('#group_name', faker.company.companyName())

  // Type into the RTE
  await page.fill('.ql-editor', faker.lorem.sentence())

  await page.fill('.timezone', 'Europe/London')

  // Always say London for geocoding.
  //
  // Google seems to block autocomplete when running on CircleCI (but not locally).  So we have to hack around that by
  // setting some hidden inputs directly.
  await page.fill('#lat', '51.5074', {
    force: true,
  })
  await page.fill('#lng', '-0.1276' , {
    force: true,
  })
  await page.fill('#location', 'London, UK' , {
    force: true,
  })

  // Now create it.
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

exports.addDevice = async function(page, baseURL, idevents, powered, photo, fixed, spareparts) {
  // Go to event edit page.
  await page.goto('/party/view/' + idevents)

  var addsel = powered ? '.add-powered-device-desktop' : '.add-unpowered-device-desktop'

  // Get current device count.
  await page.waitForSelector(addsel)
  var current = await page.locator('h3:visible').count()

  // Click the add button.
  await page.locator(addsel).click()

  // Tab to category and select first.
  await page.keyboard.press('Tab')
  await page.keyboard.press('Enter')

  if (fixed) {
    // Go to repair outcome and select fixed (first).
    await page.locator('.repair-outcome:visible').focus()
    await page.keyboard.press('Enter')
  }

  if (spareparts) {
    await page.locator('.spare-parts').click()
    await page.keyboard.press('Enter')
  }

  if (photo) {
    const [fileChooser] = await Promise.all([
      page.waitForEvent('filechooser'),

      // Trigger file upload.
      page.locator('.add-device .vue-dropzone:visible').click(),
    ]);

    await fileChooser.setFiles('public/images/community.jpg');
  }

  await page.locator('text=Add item >> visible=true').click()

  // Wait for device to show.
  await expect(page.locator('h3:visible')).toHaveCount(current + 1)

  // Check that the photo appears.
  await page.locator('.edit:visible').click()

  if (photo) {
    // Should see the dropzone and uploaded photo.
    await expect(page.locator('.device-photos:visible img')).toHaveCount(2)
  } else {
    // Just dropzone
    await expect(page.locator('.device-photos:visible img')).toHaveCount(1)
  }

  // Close the device edit.
  await page.locator('.cancel').click()
}

exports.unfollowGroup = async function(page, idgroups) {
  await page.goto('/group/view/' + idgroups)

  await page.click('#groupactions .dropdown-toggle >> visible=true')

  await page.click('#groupactions .dropdown-menu > li:nth-child(7) > .dropdown-item >> visible=true')

  await page.click('#confirmmodal .btn-primary')

  await page.locator('text=You have now unfollowed')
}