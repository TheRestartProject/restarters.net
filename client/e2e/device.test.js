import path from 'path'
import { fileURLToPath } from 'url'
import { test, expect, API_URL } from './fixtures'
import { USERS, login, createGroup, createEvent, approveEvent, addDevice } from './utils'

// Ports tests/Integration/device.test.js's flows to the Nuxt client's
// DeviceForm.vue (an inline add form, not a modal - no vue-multiselect, no
// dropzone-during-create; see that component's own doc comment). See
// client/e2e/utils.js's addDevice() for the shared add-device helper.
//
// Behavioural differences from the legacy suite, all because the Nuxt
// component genuinely works differently (not test bugs):
//  - Only one spare-parts tick renders per device (DeviceRow.vue is a
//    single responsive table row, not duplicated mobile/desktop markup
//    like the legacy Blade view), so this asserts a count of 1, not 2.
//  - Photo upload only happens once a device already exists (DevicePhotos
//    .vue is edit-only - docs/nuxt-migration/api-gaps.md Phase C), so the
//    "device with photo" test creates a plain device first, then edits it
//    to attach the photo, rather than uploading during creation.
//  - Category suggestion applies as soon as the item-type text matches
//    (a plain <input list=datalist>, not a vue-multiselect requiring
//    Tab+Enter to pick a highlighted option).

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const TEST_IMAGE = path.join(__dirname, 'fixtures', 'test-image.png')

async function setUpApprovedEvent(page, { past = true } = {}) {
  const groupId = await createGroup(page)
  const eventId = await createEvent(page, groupId, { past })
  await approveEvent(page, eventId)
  return eventId
}

// The devices panel renders twice - a desktop box (event-devices-desktop)
// and mobile collapsible sections - so bare device-form/device-photo
// testids resolve to two elements and trip strict mode. Scope to desktop,
// which is what these tests drive.
const desktopPanel = (page) => page.getByTestId('event-devices-desktop')

test.describe('devices', () => {
  test('Spare parts tick shown when parts are needed', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const eventId = await setUpApprovedEvent(page)
    const response = await addDevice(page, eventId, { repairStatus: 'Fixed', spareParts: 'Manufacturer' })
    const { device } = await response.json()

    await expect(desktopPanel(page).getByTestId(`event-device-spare-parts-${device.id}`)).toBeVisible()
  })

  test('Spare parts tick not shown when parts are not needed', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const eventId = await setUpApprovedEvent(page)
    const response = await addDevice(page, eventId, { repairStatus: 'Fixed' })
    const { device } = await response.json()

    await expect(desktopPanel(page).getByTestId(`event-device-spare-parts-${device.id}`)).toHaveCount(0)
  })

  test('Can create misc powered device', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const eventId = await setUpApprovedEvent(page)
    const response = await addDevice(page, eventId, {})
    const { device } = await response.json()

    await expect(desktopPanel(page).getByTestId(`event-device-${device.id}`)).toBeVisible()
  })

  test('Can create device with photo', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const eventId = await setUpApprovedEvent(page)
    const response = await addDevice(page, eventId, {})
    const { device } = await response.json()

    await desktopPanel(page).getByTestId(`event-device-edit-${device.id}`).click()
    await expect(desktopPanel(page).getByTestId('device-photos')).toBeVisible({ timeout: 10000 })

    // Age was never set, so the edit form's age field should be blank.
    await expect(desktopPanel(page).getByTestId('device-form-age')).toHaveValue('')

    // TusImageUpload.vue mounts an Uppy Dashboard with two hidden file
    // inputs (plain picker + webkitdirectory folder picker) - same pattern
    // as group.test.js's image-upload test.
    const [uploadResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/v2/devices/${device.id}/images`) && resp.request().method() === 'POST',
        { timeout: 20000 },
      ),
      desktopPanel(page).locator('.uppy-Dashboard-input:not([webkitdirectory])').setInputFiles(TEST_IMAGE),
    ])
    expect(uploadResponse.status()).toBe(200)

    await expect(desktopPanel(page).getByTestId('device-photo').first()).toBeVisible({ timeout: 10000 })

    await desktopPanel(page).getByTestId('device-form-cancel').click()
    await expect(desktopPanel(page).getByTestId(`event-device-${device.id}`)).toBeVisible()
  })

  test('Automatic category suggestion from item type', async ({ page }) => {
    // The suggestion dataset (item_type -> category mappings) is derived
    // from previously recorded devices, so test DBs have none of the
    // production mappings the legacy spec hardcoded (that spec was excluded
    // from CI runs for the same reason). Self-seed one mapping via the API,
    // then verify the whole mechanism: options fetch -> exact-match ->
    // category autofill.
    test.slow()
    await login(page, USERS.admin)

    const eventId = await setUpApprovedEvent(page)

    // Pick a real category from the seeded DB and prime one mapping.
    const categoriesResponse = await page.request.get(`${API_URL}/api/v2/items`)
    const before = (await categoriesResponse.json()).data || []

    const itemType = `E2E Suggestotron ${Date.now()}`
    const seedDevice = await addDevice(page, eventId, { itemType })
    const seeded = (await seedDevice.json()).device

    // Fresh page load so the item-types cache refetches with the new mapping.
    await page.goto(`/party/view/${eventId}`, { waitUntil: 'domcontentloaded' })
    await page.getByTestId('add-powered-device-desktop').click()
    await desktopPanel(page).getByTestId('device-form').waitFor({ timeout: 10000 })

    await desktopPanel(page).getByTestId('device-form-item-type').fill(itemType)
    // Assert on the selected VALUE (category id): display labels can alias
    // the raw name (e.g. category 'Misc' renders as 'None of the above').
    // The control is DeviceForm's plain-element multiselect (a div, not a
    // <select>), which mirrors its selection into data-value.
    await expect(desktopPanel(page).getByTestId('device-form-category')).toHaveAttribute(
      'data-value',
      String(seeded.category.id),
      { timeout: 10000 },
    )

    // Sanity: the dataset genuinely grew (the mapping came from our seed).
    const after = (await (await page.request.get(`${API_URL}/api/v2/items`)).json()).data || []
    expect(after.length).toBeGreaterThan(before.length)
  })
})
