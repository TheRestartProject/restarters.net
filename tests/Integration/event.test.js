const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login, createGroup, createEvent, approveEvent} = require('./utils')

test('Can create future event', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, false)
  await approveEvent(page, baseURL, eventid)
})

test('Can create past event', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, true)
  await approveEvent(page, baseURL, eventid)
})

test('Invite volunteers modal opens from Event Actions dropdown', async ({page, baseURL}) => {
  test.slow()

  await login(page, baseURL)
  const groupid = await createGroup(page, baseURL)
  const eventid = await createEvent(page, baseURL, groupid, false)
  await approveEvent(page, baseURL, eventid)

  // Join the event so isAttending=true (the invite button is gated on this).
  // /party/join/ redirects straight to the event view with is_attending already set.
  // Avoid waitForLoadState('networkidle') — the event view makes ongoing API calls that
  // prevent idle from ever being reached, causing a 10-minute CI timeout.
  await page.goto('/party/join/' + eventid)
  await page.waitForSelector(':text("EVENT ACTIONS")', { timeout: 30000 })

  // Open the Event Actions dropdown
  await page.locator('button', { hasText: 'EVENT ACTIONS' }).first().click()

  // Click "Invite Volunteers" in the dropdown
  await page.locator('.dropdown-item', { hasText: 'Invite Volunteers' }).first().click()

  // The Vue EventInviteModal should open — it has a multiselect for group members.
  // The old Blade modal (now removed from view) had only a textarea, not a multiselect.
  // BootstrapVue adds .show to the active modal div.
  await expect(page.locator('.modal.show')).toBeVisible({ timeout: 10000 })
  await expect(page.locator('.modal.show .multiselect')).toBeVisible({ timeout: 5000 })
})