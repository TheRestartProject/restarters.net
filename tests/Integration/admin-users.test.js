/**
 * Smoke-test coverage for /user/all migrated from a server-rendered blade
 * to the UsersPage Vue SPA backed by GET /api/v2/users.
 */

const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login } = require('./utils')

const ADMIN_EMAIL = 'jane@bloggs.net'
const RESTARTER_EMAIL = 'host@test.net'
const PASSWORD = 'passw0rd'

test('Admin /user/all renders the UsersPage Vue SPA', async ({ page, baseURL }) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  await page.goto(baseURL + '/user/all')
  await expect(page.locator('[data-testid="users-table"]')).toBeVisible({ timeout: 10000 })
  await expect(page.locator('[data-testid="users-filter-submit"]')).toBeVisible()
})

test('Non-admin is redirected away from /user/all', async ({ page, baseURL }) => {
  test.slow()
  await login(page, baseURL, RESTARTER_EMAIL, PASSWORD)
  await page.goto(baseURL + '/user/all')
  await expect(page).toHaveURL(/\/user\/forbidden$/)
})
