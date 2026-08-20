import { expect } from '@playwright/test'

// Seeded by Taskfile's playwright:seed-data (same fixed users as the legacy
// suite): jane@bloggs.net (Admin), nc@test.net (NetworkCoordinator),
// host@test.net (Host), all with password passw0rd.
export const USERS = {
  admin: { email: 'jane@bloggs.net', password: 'passw0rd' },
  nc: { email: 'nc@test.net', password: 'passw0rd' },
  host: { email: 'host@test.net', password: 'passw0rd' },
}

export async function login(page, { email, password } = USERS.admin) {
  // Clear any persisted session from a PRIOR login() in the same test (several
  // tests log in as one user for setup, then re-login as another to switch).
  // /login is now guest-gated (definePageMeta guest:true) - it redirects a
  // logged-in user to /dashboard - so without clearing first, goto('/login')
  // would bounce to /dashboard and the login form would never appear (a 120s
  // timeout waiting for login-email). localStorage.clear() needs an app origin,
  // so it is best-effort (about:blank on the very first login just no-ops).
  await page.evaluate(() => {
    try {
      localStorage.clear()
      sessionStorage.clear()
    } catch {
      // no window/origin yet (first login of the test) - nothing to clear
    }
  }).catch(() => {})
  await page.context().clearCookies()

  await page.goto('/login')
  await page.getByTestId('login-email').fill(email)
  await page.getByTestId('login-password').fill(password)
  await page.getByTestId('login-submit').click()
  await page.waitForURL('**/dashboard')
  await dismissOnboarding(page)
  await expect(page.getByTestId('nav-user-menu')).toBeVisible()
}

// The dashboard shows a first-run onboarding modal driven by the server-side
// `onboarding` flag. On a freshly seeded user (every CI run) it is present and,
// as a full-screen modal, intercepts pointer events on the navbar - so any test
// that clicks the nav (e.g. the user menu / logout) times out until it is
// dismissed. Close it right after login so the rest of the suite is stable.
export async function dismissOnboarding(page) {
  const close = page.getByTestId('onboarding-close')
  if (await close.isVisible().catch(() => false)) {
    await close.click()
    await expect(page.getByTestId('onboarding-modal')).toBeHidden()
  }
}

export async function logout(page) {
  await page.getByTestId('nav-user-menu').click()
  await page.getByTestId('nav-logout').click()
  // The navbar's logout handler navigates to /login, which uses the plain
  // layout (no navbar) — assert the login form itself.
  await page.waitForURL('**/login**')
  await expect(page.getByTestId('login-form')).toBeVisible()
}

// Ports tests/Integration/utils.js's createGroup/createEvent/approveEvent/
// addDevice to the Nuxt client's plain-input GroupForm.vue/EventForm.vue/
// DeviceForm.vue (no vue-multiselect - see those components' own doc
// comments), for event.test.js and device.test.js to share. group.test.js
// keeps its own inline "Can create group" flow (it's asserting the create
// UI itself, not just producing an id) rather than being refactored onto
// this helper.
export async function createGroup(page, { name } = {}) {
  const groupName = name || `E2E Group ${Date.now()}`

  await page.goto('/group/create')
  await page.getByTestId('group-form-name').fill(groupName)
  await page.locator('[data-testid="group-form-description"] .ql-editor').fill('An e2e test group created by Playwright.')
  // See group.test.js's own comment: real autocomplete suggestions depend
  // on a live Google key - typing a real location and leaving any
  // suggestion dropdown untouched is sufficient, since the server
  // re-geocodes the plain text itself (GroupController::createGroupv2).
  await page.getByTestId('location-picker-input').fill('London, UK')
  await page.getByTestId('group-form-timezone').fill('Europe/London')

  const [createResponse] = await Promise.all([
    page.waitForResponse(
      (resp) => new URL(resp.url()).pathname === '/api/v2/groups' && resp.request().method() === 'POST',
      { timeout: 20000 },
    ),
    page.getByTestId('group-form-submit').click(),
  ])
  expect(createResponse.status()).toBe(200)

  await page.waitForURL('**/group/edit/**', { timeout: 20000 })
  const match = page.url().match(/\/group\/edit\/(\d+)/)
  if (!match) throw new Error(`Could not parse group id from ${page.url()}`)
  return Number(match[1])
}

// Creates an event for the given group via /party/create/{groupId}
// (EventForm.vue). `past`: event dated ~2 weeks ago vs ~1 month in the
// future (matching createEvent's legacy "always comfortably in the
// future/past" comment re: calendar edge cases). Returns the numeric event
// id parsed from the post-create redirect to /party/edit/{id}.
export async function createEvent(page, groupId, { past = false, name } = {}) {
  const eventName = name || `E2E Event ${Date.now()}`

  await page.goto(`/party/create/${groupId}`)
  await page.getByTestId('event-form-venue').fill(eventName)
  await page.locator('[data-testid="event-form-description"] .ql-editor').fill('An e2e test event created by Playwright.')

  // Use the preselected group's own location rather than retyping an
  // address - mirrors the legacy flow's `.event-address .btn-primary`
  // click. The button only appears once the group's details have loaded
  // (EventForm.vue's immediate watcher on form.idgroups).
  await page.getByTestId('event-form-use-group-location').click({ timeout: 10000 })

  const eventDate = new Date()
  eventDate.setDate(eventDate.getDate() + (past ? -14 : 30))
  const dateStr = eventDate.toISOString().slice(0, 10)
  // The native date input is the mobile-only fallback (d-block d-lg-none)
  // sharing the same v-model as the desktop DatePicker widget - force:true
  // skips the visibility check so this works at any viewport size.
  await page.getByTestId('event-form-date-native').fill(dateStr, { force: true })

  await page.getByTestId('event-form-start').fill('13:00')
  await page.getByTestId('event-form-end').fill('14:00')

  const [createResponse] = await Promise.all([
    page.waitForResponse(
      (resp) => new URL(resp.url()).pathname === '/api/v2/events' && resp.request().method() === 'POST',
      { timeout: 20000 },
    ),
    page.getByTestId('event-form-submit').click(),
  ])
  expect(createResponse.status()).toBe(200)

  await page.waitForURL('**/party/edit/**', { timeout: 20000 })
  const match = page.url().match(/\/party\/edit\/(\d+)/)
  if (!match) throw new Error(`Could not parse event id from ${page.url()}`)
  return Number(match[1])
}

// Approves an event via the moderation select on /party/edit/{id}
// (EventForm.vue's data-testid=event-approve, only rendered for an admin
// viewing an unapproved event). Event updates go out as PATCH, unlike
// creates (POST) - see api/EventAPI.js.
export async function approveEvent(page, eventId) {
  if (!page.url().includes(`/party/edit/${eventId}`)) {
    await page.goto(`/party/edit/${eventId}`, { waitUntil: 'domcontentloaded' })
  }

  await page.getByTestId('event-approve').waitFor({ timeout: 15000 })
  await page.getByTestId('event-approve').selectOption('approve')

  const [saveResponse] = await Promise.all([
    page.waitForResponse(
      (resp) => resp.url().includes(`/api/v2/events/${eventId}`) && resp.request().method() === 'PATCH',
      { timeout: 15000 },
    ),
    page.getByTestId('event-form-submit').click(),
  ])
  expect(saveResponse.status()).toBe(200)
}

// Approves a group via the moderation select on /group/edit/{id}
// (GroupForm.vue's data-testid=group-form-moderate, only rendered for an
// admin viewing an unapproved group). Needed whenever a non-privileged user
// must view the group's events: GET /api/v2/events/{id} hides events on
// unapproved (unmoderated) groups from everyone but their host/coordinator/
// admin (EventController's userHasViewPartyPermission gate).
export async function approveGroup(page, groupId) {
  if (!page.url().includes(`/group/edit/${groupId}`)) {
    await page.goto(`/group/edit/${groupId}`, { waitUntil: 'domcontentloaded' })
  }

  await page.getByTestId('group-form-moderate').waitFor({ timeout: 15000 })
  await page.getByTestId('group-form-moderate').selectOption('approve')

  const [saveResponse] = await Promise.all([
    page.waitForResponse(
      (resp) => resp.url().includes(`/api/v2/groups/${groupId}`) && resp.request().method() === 'PATCH',
      { timeout: 15000 },
    ),
    page.getByTestId('group-form-submit').click(),
  ])
  expect(saveResponse.status()).toBe(200)
}

// Adds a device to an event via /party/view/{id} (EventDevicesPanel.vue +
// DeviceForm.vue's inline, non-modal add form - no vue-multiselect, no
// dropzone-during-create; see DeviceForm.vue's own doc comment for why
// photo upload only happens once a device already exists, i.e. via editing
// it after this call). Returns the POST response so callers can read the
// created device's id off its body.
// The devices panel renders twice (desktop box + mobile collapsible
// sections), so bare device-form* testids resolve to two elements and trip
// Playwright strict mode - scope to the desktop panel this helper drives.
export async function addDevice(page, eventId, { powered = true, itemType, category, repairStatus, spareParts } = {}) {
  const expectedUrl = `/party/view/${eventId}`
  if (!page.url().includes(expectedUrl)) {
    await page.goto(expectedUrl, { waitUntil: 'domcontentloaded' })
  }

  const addButtonTestId = powered ? 'add-powered-device-desktop' : 'add-unpowered-device-desktop'
  await page.getByTestId(addButtonTestId).click()
  await page.getByTestId('event-devices-desktop').getByTestId('device-form').waitFor({ timeout: 10000 })

  if (itemType) {
    await page.getByTestId('event-devices-desktop').getByTestId('device-form-item-type').fill(itemType)
  }

  // Category/status/spare-parts are DeviceForm's plain-element multiselects
  // (vue-multiselect markup on divs - see its doc comment), not native
  // <select>s: click the control, then click the option.
  const desktop = page.getByTestId('event-devices-desktop')
  const realOptions = desktop.locator('.multiselect__option:not(.multiselect__option--group)')

  await desktop.getByTestId('device-form-category').click()
  if (category) {
    await realOptions.filter({ hasText: category }).first().click()
  } else {
    await realOptions.first().click()
  }

  if (repairStatus) {
    await desktop.getByTestId('device-form-status').click()
    await desktop.getByTestId(`device-form-status-option-${repairStatus}`).click()
  }

  if (spareParts) {
    await desktop.getByTestId('device-form-spare-parts').click()
    await desktop.getByTestId(`device-form-spare-parts-option-${spareParts}`).click()
  }

  const [createResponse] = await Promise.all([
    page.waitForResponse(
      (resp) => new URL(resp.url()).pathname === '/api/v2/devices' && resp.request().method() === 'POST',
      { timeout: 15000 },
    ),
    page.getByTestId('event-devices-desktop').getByTestId('device-form-submit').click(),
  ])
  expect(createResponse.status()).toBe(200)

  return createResponse
}
