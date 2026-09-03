import { test, expect } from './fixtures'
import { USERS, login } from './utils'

// The /notifications page (the old /profile/notifications) was never built in
// the initial migration — the profile "Notifications" tab linked to a route
// with no page, so it 404'd. These tests exercise the page and that link so
// the regression can't recur silently.

test.describe('notifications', () => {
  test('Notifications page renders for a logged-in user', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    await page.goto('/notifications', { waitUntil: 'domcontentloaded' })

    await expect(page.getByTestId('notifications-page')).toBeVisible({ timeout: 10000 })
    // Either a list or the empty state renders — but never a 404.
    await expect(page.getByTestId('notifications-list').or(page.getByTestId('notifications-empty'))).toBeVisible({
      timeout: 10000,
    })
  })

  test('Profile Notifications tab navigates to the notifications page', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    await page.goto('/profile/edit', { waitUntil: 'domcontentloaded' })

    const tab = page.getByTestId('profile-tab-nav-notifications')
    await expect(tab).toBeVisible({ timeout: 10000 })
    await tab.click()

    await page.waitForURL('**/notifications', { timeout: 10000 })
    await expect(page.getByTestId('notifications-page')).toBeVisible({ timeout: 10000 })
  })
})
