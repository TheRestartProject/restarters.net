import { test, expect } from './fixtures'
import { USERS, login } from './utils'

// Smoke-level port of the dashboard's functional spec (design.md §6.2 B3
// task brief - resources/views/dashboard/index.blade.php +
// DashboardPage.vue). Deeper coverage of individual cards/empty states is
// left to client vitest component tests; this just proves the page loads
// real API data end to end.
test.describe('dashboard', () => {
  test('shows your groups and upcoming events for the logged-in user', async ({ page }) => {
    await login(page, USERS.admin)

    await expect(page.getByTestId('dashboard-content')).toBeVisible({ timeout: 15000 })
    await expect(page.getByTestId('dashboard-your-groups')).toBeVisible()
    await expect(page.getByTestId('dashboard-upcoming-events')).toBeVisible()
  })
})
