/**
 * Smoke-test coverage for the reference-data admin pages migrated to Vue:
 *   /brands     -> BrandsPage     (AdminCrudPage shell)
 *   /skills     -> SkillsPage     (AdminCrudPage shell with select + textarea)
 *   /category   -> CategoriesPage (AdminCrudPage shell, edit-only)
 *   /role       -> RolesPage      (bespoke; checkbox-group permission matrix)
 *
 * Group-tags has its own end-to-end coverage in grouptags.test.js.
 *
 * NOTE: these are deliberately lightweight - they only verify the Vue mount
 * succeeds and the page exposes the expected affordances. Full create/edit/
 * delete round-trips were tried but timed out the CI Playwright step
 * (10m no-output budget). They can be added back in a follow-up once we
 * understand the per-test budget better.
 */

const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login } = require('./utils')

const ADMIN_EMAIL = 'jane@bloggs.net'
const RESTARTER_EMAIL = 'host@test.net' // any non-admin works; host is also non-admin for these pages
const PASSWORD = 'passw0rd'

// Visit `/<path>` as the admin and assert the named Vue table mounted.
async function smokeAdminPage(page, baseURL, path, prefix) {
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  await page.goto(baseURL + path)
  await expect(page.locator(`[data-testid="${prefix}-table"]`)).toBeVisible({ timeout: 10000 })
}

test('Admin brands page renders the Vue SPA with the add button', async ({ page, baseURL }) => {
  test.slow()
  await smokeAdminPage(page, baseURL, '/brands', 'brands')
  await expect(page.locator('[data-testid="brands-add-button"]')).toBeVisible()
})

test('Admin skills page renders the Vue SPA with the add button', async ({ page, baseURL }) => {
  test.slow()
  await smokeAdminPage(page, baseURL, '/skills', 'skills')
  await expect(page.locator('[data-testid="skills-add-button"]')).toBeVisible()
})

test('Admin categories page renders the Vue SPA, edit-only', async ({ page, baseURL }) => {
  test.slow()
  await smokeAdminPage(page, baseURL, '/category', 'categories')
  // allowCreate=false and allowDelete=false on this resource
  await expect(page.locator('[data-testid="categories-add-button"]')).toHaveCount(0)
  await expect(page.locator('[data-testid^="categories-delete-"]')).toHaveCount(0)
})

test('Admin roles page renders the Vue SPA with the roles table', async ({ page, baseURL }) => {
  test.slow()
  await smokeAdminPage(page, baseURL, '/role', 'roles')
  // Bespoke RolesPage: no add affordance
  await expect(page.locator('[data-testid="roles-add-button"]')).toHaveCount(0)
})

test('Non-admin is redirected away from /role', async ({ page, baseURL }) => {
  test.slow()
  await login(page, baseURL, RESTARTER_EMAIL, PASSWORD)
  await page.goto(baseURL + '/role')
  // Controller redirects to RouteServiceProvider::HOME (= /dashboard)
  await expect(page).not.toHaveURL(/\/role$/, { timeout: 10000 })
})
