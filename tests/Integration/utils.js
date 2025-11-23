const {expect} = require('@playwright/test')
const faker = require('faker')

// Debug logging utility
const DEBUG = process.env.PLAYWRIGHT_DEBUG === 'true' || process.env.DEBUG === 'playwright'
const log = (message, ...args) => {
  if (DEBUG) {
    const timestamp = new Date().toISOString()
    console.log(`[${timestamp}] [PLAYWRIGHT-DEBUG] ${message}`, ...args)
  }
}

const login = async function(page, baseURL, email = 'jane@bloggs.net', password = 'passw0rd') {
  log('Starting login process', { email, baseURL })
  
  // Load the login page.
  log('Navigating to login page')
  await page.goto(baseURL + '/login')
  await expect(page.locator('legend')).toHaveText('Sign in')

  log('Filling login credentials')
  await page.waitForSelector('#fp_email')
  await page.fill('#fp_email', email)
  await page.fill('#password', password)

  log('Submitting login form')
  await page.click('button[type=submit]')
  await page.waitForSelector('section.dashboard')

  log('Login completed successfully')
  return page
}

exports.login = login

exports.createGroup = async function(page, baseURL) {
  log('Starting group creation')
  
  // Go to groups page
  log('Navigating to groups page')
  await page.goto('/group', { timeout: 30000 })

  // Click on add a new group button
  log('Clicking create group button')
  await page.click('a[href="/group/create"]')
  // await page.goto(baseURL + '/group/create')

  const groupName = faker.company.companyName()
  log('Filling group form', { groupName })
  
  // Name
  await page.fill('#group_name', groupName)

  // Type into the RTE
  await page.fill('.ql-editor', faker.lorem.sentence())

  await page.fill('.timezone', 'Europe/London')

  // Always say London for geocoding.
  //
  // Google seems to block autocomplete when running on CircleCI (but not locally).  So we have to hack around that by
  // setting some hidden inputs directly.  The code spots this via a timer.
  log('Setting location to London (hardcoded for CI)')
  await page.evaluate('document.getElementById("lat").setAttribute("value", 51.5074);')
  await page.evaluate('document.getElementById("lng").setAttribute("value", -0.1276);')
  await page.evaluate('document.querySelector(\'[placeholder="Enter your address"]\').setAttribute("value", "London, UK");')

  // Now create it.  Wait a hardcoded time to let the autocomplete code sort itself out.  This is
  // ugly, but will do.
  log('Submitting group creation form')
  await page.waitForTimeout(3000);
  await page.click('button[type=submit]')

  // Should get redirected to Edit form.  We used to wait on #details, but this stopped working for reasons we don't
  // understand.  It may be as design in Playwright.  However the page URL will have been updated and we can use that
  // to check that the create redirected to edit.
  log('Waiting for redirect to edit page')
  const WAIT_FOR_URL_TIMEOUT = process.env.PLAYWRIGHT_WAIT_URL_TIMEOUT ? parseInt(process.env.PLAYWRIGHT_WAIT_URL_TIMEOUT) : 30000;
  await page.waitForURL('**/edit/**', { timeout: WAIT_FOR_URL_TIMEOUT });

  // Return id from URL
  const p = page.url().lastIndexOf('/')
  expect(p).toBeGreaterThan(0)

  const id = page.url().substring(p + 1)
  log('Group created successfully', { id, groupName })
  return id
}

exports.createEvent = async function(page, baseURL, idgroups, past) {
  log('Starting event creation', { idgroups, past })
  
  // Go to groups page
  log('Navigating to group view page')
  await page.goto('/group/view/' + idgroups)

  // Click on Add New Event button
  log('Clicking create event button')
  await page.click('a[href="/party/create"]')

  const eventName = faker.company.companyName()
  log('Filling event form', { eventName })
  
  // Venue name
  await page.fill('#event_name', eventName)

  // Select the group.  Bit hard to get the select to open, but tabbing from the previous field works.
  log('Selecting group for event')
  await page.click('#event_link')
  await page.keyboard.press('Tab')
  await page.click('.multiselect__content-wrapper > .multiselect__content > .multiselect__element > .multiselect__option--highlight > span')

  // Type into the RTE
  await page.fill('.ql-editor', faker.lorem.sentence())

  // Set a date.
  log('Setting event date', { past })
  await page.click('#event_date button')

  if (past) {
    log('Setting past date - going back a month')
    // Go back a month
    await page.locator('[aria-label="Previous month"]').click()
  }

  await page.click('#event_date .b-calendar-grid > .b-calendar-grid-body > .row:last-child .btn:last-child')

  log('Setting event times')
  await page.click('#event_time input[name="start"]')
  await page.fill('#event_time input[name="start"]', '13:00')

  await page.click('#event_time input[name="end"]')
  await page.fill('#event_time input[name="end"]', '14:00')

  // Use group location.
  log('Using group location for event')
  await page.click('.event-address .btn-primary')

  log('Submitting event creation form')
  await page.click('button[type=submit]')

  // Should see created message.
  log('Waiting for event creation confirmation')
  const handle = await page.waitForSelector('.creation-message')
  const id = await handle.getAttribute('id')

  log('Event created successfully', { id, eventName })
  return id
}

exports.approveEvent = async function(page, baseURL, idevents) {
  log('Starting event approval', { idevents })
  
  // Go to event edit page.
  log('Navigating to event edit page')
  await page.goto('/party/edit/' + idevents)

  // Set approve.
  log('Setting event status to approved')
  await page.selectOption('.event-approve .custom-select', 'approve')

  // Approve
  log('Saving event approval')
  await page.locator('text=Save Event').click()

  // Should show change.
  log('Waiting for approval confirmation')
  await page.locator('text=Event details updated.')
  
  log('Event approved successfully', { idevents })
}

exports.addDevice = async function(page, baseURL, idevents, powered, photo, fixed, spareparts, itemType = null, category = null) {
  log('Starting device addition', { idevents, powered, photo, fixed, spareparts, itemType, category })
  
  // Check if we're already on the correct event view page
  const expectedUrl = '/party/view/' + idevents
  const currentUrl = page.url()
  
  if (!currentUrl.includes(expectedUrl)) {
    log('Navigating to event view page', { expectedUrl, currentUrl })
    await page.goto(expectedUrl)
  } else {
    log('Already on correct event view page, skipping navigation', { currentUrl })
  }

  var addsel = powered ? '.add-powered-device-desktop' : '.add-unpowered-device-desktop'
  log('Using device selector', { addsel, powered })

  // Get current device count.
  await page.waitForSelector(addsel)
  var current = await page.locator('h3:visible').count()
  log('Current device count', { current })

  // Click the add button.
  log('Clicking add device button')
  await page.locator(addsel).click()

  // Set item type if provided
  if (itemType) {
    log('Setting specific item type', { itemType })
    await page.fill('.item-type:visible input', itemType)
    await page.keyboard.press('Tab')
  } else {
    // Item type is focused, so we just need one tab to get to the category.
    log('Skipping item type, tabbing to category field')
    await page.keyboard.press('Tab')
  }

  // Set category if provided
  if (category) {
    log('Setting specific category', { category })
    await page.keyboard.type(category)
    await page.keyboard.press('Enter')
  } else {
    // Then select first category.
    log('Setting default category (first option)')
    await page.keyboard.press('Enter')
  }

  if (fixed) {
    log('Setting repair status to fixed')
    // Go to repair outcome and select fixed (first).
    await page.locator('.repair-outcome:visible').focus()
    await page.keyboard.press('Enter')
  }

  if (spareparts) {
    log('Setting spare parts required')
    await page.locator('.spare-parts:visible').click()
    await page.keyboard.press('Enter')
  }

  if (photo) {
    log('Adding photo to device')
    const fileChooserPromise = page.waitForEvent('filechooser')
    page.locator('.add-device .vue-dropzone:visible').click()
    const fileChooser = await fileChooserPromise;
    await fileChooser.setFiles('public/images/community.jpg');

    // Wait for the file upload to complete - dropzone shows .dz-preview when file is being uploaded
    // and adds .dz-success class when upload succeeds
    log('Waiting for photo upload to complete')
    await expect(page.locator('.add-device .dz-preview.dz-success:visible')).toBeVisible({ timeout: 30000 })
  }

  log('Submitting device creation')
  await page.locator('text=Add item >> visible=true').click()

  // Wait for device to show.
  log('Waiting for device to appear in list')
  await expect(page.locator('h3:visible')).toHaveCount(current + 1)

  // Check that the photo appears.
  log('Opening device for verification')
  await page.locator('.edit:visible').last().click()

  if (photo) {
    log('Verifying photo was uploaded')
    // Should see the dropzone and uploaded photo.
    await expect(page.locator('.device-photos:visible img')).toHaveCount(2)
  } else {
    log('Verifying no additional photos present')
    // Just dropzone
    await expect(page.locator('.device-photos:visible img')).toHaveCount(1)
  }

  // Age of device when editing is 0, which should show blank.
  log('Verifying device age field')
  await expect(page.locator('.device-age-edit:visible')).toHaveValue('')

  // Close the device edit.
  log('Closing device edit modal')
  await page.locator('.cancel').click()

  // Age of device is 0, which should show as 0.
  log('Verifying device age display')
  await expect(page.locator('.device-age-summary:visible').last()).toHaveText('-')
  
  log('Device added successfully')
}

exports.unfollowGroup = async function(page, idgroups) {
  await page.goto('/group/view/' + idgroups)

  await page.click('#groupactions .dropdown-toggle >> visible=true')

  await page.click('#groupactions .dropdown-menu > li:nth-child(7) > .dropdown-item >> visible=true')

  await page.click('#confirmmodal .btn-primary')

  await page.locator('text=You have now unfollowed')
}