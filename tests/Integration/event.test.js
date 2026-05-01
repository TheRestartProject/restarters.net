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
  // /party/join/ uses redirect()->back() (goes to edit page), so navigate to view explicitly.
  // Avoid waitForLoadState('networkidle') — the event view polls continuously and never idles.
  await page.goto('/party/join/' + eventid)
  await page.goto('/party/view/' + eventid)

  // EventHeading renders EventActions twice: d-block d-md-none (mobile, hidden at desktop)
  // and d-none d-md-block (desktop, visible). Use :visible to target the shown instance only.
  await page.locator('button:visible', { hasText: 'EVENT ACTIONS' }).waitFor({ timeout: 30000 })
  await page.locator('button:visible', { hasText: 'EVENT ACTIONS' }).click()

  // After opening the desktop dropdown, only its items are visible; the mobile dropdown stays closed.
  await page.locator('.dropdown-item:visible', { hasText: 'Invite Volunteers' }).click()

  // The Vue EventInviteModal should open — it has a multiselect for group members.
  // The old Blade modal (now removed from view) had only a textarea, not a multiselect.
  // BootstrapVue adds .show to the active modal div.
  await expect(page.locator('.modal.show')).toBeVisible({ timeout: 10000 })
  await expect(page.locator('.modal.show .multiselect')).toBeVisible({ timeout: 5000 })
})