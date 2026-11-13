import { test, expect } from './fixtures'
import { USERS, login } from './utils'

// Smoke-level port of the dashboard's functional spec (design.md §6.2 B3
// task brief - resources/views/dashboard/index.blade.php +
// DashboardPage.vue). Deeper coverage of individual cards/empty states is
// left to client vitest component tests; this just proves the page loads
// real API data end to end.
test.describe('dashboard', () => {
  test('shows your groups for the logged-in user', async ({ page }) => {
    await login(page, USERS.admin)

    await expect(page.getByTestId('dashboard-content')).toBeVisible({ timeout: 15000 })
    await expect(page.getByTestId('dashboard-your-groups')).toBeVisible()

    // USERS.admin (jane@bloggs.net) has no groups in the seeded dev DB, so
    // legacy's upcoming-events section (only ever shown alongside a user's
    // own groups - DashboardYourGroups.vue's `v-else` branch) is absent
    // rather than rendering its own empty state; the nearby-groups fallback
    // nests inside "Your Groups" instead (RES dashboard rebuild).
    await expect(page.getByTestId('dashboard-upcoming-events')).toHaveCount(0)
    await expect(page.getByTestId('dashboard-nearby-groups')).toBeVisible()
  })
})
