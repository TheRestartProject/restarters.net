/**
 * Playwright coverage for the reference-data admin pages migrated to Vue:
 *   /brands     -> BrandsPage     (AdminCrudPage shell)
 *   /skills     -> SkillsPage     (AdminCrudPage shell with select + textarea)
 *   /category   -> CategoriesPage (AdminCrudPage shell, edit-only)
 *   /role       -> RolesPage      (bespoke; checkbox-group permission matrix)
 *
 * Each test goes through the admin user, drives the Vue UI via the
 * `data-testid` hooks the components expose, and verifies the change
 * round-trips to the /api/v2/<resource> endpoint by re-rendering the page.
 *
 * Group-tags is covered separately in grouptags.test.js.
 */

const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login } = require('./utils')

const ADMIN_EMAIL = 'jane@bloggs.net'
const RESTARTER_EMAIL = 'host@test.net' // any non-admin works; host is also non-admin for these pages
const PASSWORD = 'passw0rd'

// Tag values with a Playwright marker so successive runs don't collide and
// so a tester can spot rows created by the suite at a glance.
const stamp = () => 'PW-' + Date.now().toString(36)

// ---------- Brands ----------

test.describe('Admin brands page', () => {
  test('renders the Vue SPA with the add button', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/brands')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('[data-testid="brands-table"]')).toBeVisible()
    await expect(page.locator('[data-testid="brands-add-button"]')).toBeVisible()
  })

  test('admin can create, edit, and delete a brand', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/brands')
    await page.waitForLoadState('networkidle')

    const name = stamp() + '-brand'
    const renamed = name + '-edited'

    // Create
    await page.click('[data-testid="brands-add-button"]')
    await page.waitForSelector('#brands-create-modal.show')
    await page.fill('[data-testid="brands-create-brand_name"]', name)
    await page.click('#brands-create-modal .modal-footer .btn-primary')
    await expect(page.locator('[data-testid="brands-table"]')).toContainText(name, {
      timeout: 5000,
    })

    // Edit
    const row = page.locator('tr', { hasText: name })
    await row.locator('[data-testid^="brands-edit-link-"]').click()
    await page.waitForSelector('#brands-edit-modal.show')
    await page.fill('[data-testid="brands-edit-brand_name"]', renamed)
    await page.click('#brands-edit-modal .modal-footer .btn-primary')
    await expect(page.locator('[data-testid="brands-table"]')).toContainText(renamed, {
      timeout: 5000,
    })

    // Delete (via ConfirmModal)
    const renamedRow = page.locator('tr', { hasText: renamed })
    await renamedRow.locator('[data-testid^="brands-delete-"]').click()
    await page.waitForSelector('#confirmmodal.show')
    await page.click('#confirmmodal .modal-footer .btn-primary')
    await expect(page.locator('[data-testid="brands-table"]')).not.toContainText(renamed, {
      timeout: 5000,
    })
  })
})

// ---------- Skills ----------

test.describe('Admin skills page', () => {
  test('renders the Vue SPA with the add button', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/skills')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('[data-testid="skills-table"]')).toBeVisible()
    await expect(page.locator('[data-testid="skills-add-button"]')).toBeVisible()
  })

  test('admin can create, edit (changing category), and delete a skill', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/skills')
    await page.waitForLoadState('networkidle')

    const name = stamp() + '-skill'
    const renamed = name + '-edited'

    // Create with category = 1 (Organising)
    await page.click('[data-testid="skills-add-button"]')
    await page.waitForSelector('#skills-create-modal.show')
    await page.fill('[data-testid="skills-create-skill_name"]', name)
    await page.selectOption('[data-testid="skills-create-category"]', '1')
    await page.fill('[data-testid="skills-create-description"]', 'created by playwright')
    await page.click('#skills-create-modal .modal-footer .btn-primary')
    await expect(page.locator('[data-testid="skills-table"]')).toContainText(name, {
      timeout: 5000,
    })

    // Edit: rename and switch category to 2 (Technical)
    const row = page.locator('tr', { hasText: name })
    await row.locator('[data-testid^="skills-edit-link-"]').click()
    await page.waitForSelector('#skills-edit-modal.show')
    await page.fill('[data-testid="skills-edit-skill_name"]', renamed)
    await page.selectOption('[data-testid="skills-edit-category"]', '2')
    await page.click('#skills-edit-modal .modal-footer .btn-primary')
    await expect(page.locator('[data-testid="skills-table"]')).toContainText(renamed, {
      timeout: 5000,
    })

    // Delete
    const renamedRow = page.locator('tr', { hasText: renamed })
    await renamedRow.locator('[data-testid^="skills-delete-"]').click()
    await page.waitForSelector('#confirmmodal.show')
    await page.click('#confirmmodal .modal-footer .btn-primary')
    await expect(page.locator('[data-testid="skills-table"]')).not.toContainText(renamed, {
      timeout: 5000,
    })
  })
})

// ---------- Categories ----------

test.describe('Admin categories page', () => {
  test('renders the Vue SPA, edit only - no add or delete buttons', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/category')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('[data-testid="categories-table"]')).toBeVisible()
    // allowCreate=false and allowDelete=false
    await expect(page.locator('[data-testid="categories-add-button"]')).toHaveCount(0)
    await expect(page.locator('[data-testid^="categories-delete-"]')).toHaveCount(0)
  })

  test('admin can edit a category', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/category')
    await page.waitForLoadState('networkidle')

    // Open the first row's edit modal (we don't care which category for a smoke test;
    // we just want to verify the PUT round-trips and we see the new description back
    // in the row).
    const firstEdit = page.locator('[data-testid^="categories-edit-link-"]').first()
    await firstEdit.click()
    await page.waitForSelector('#categories-edit-modal.show')

    const newDesc = 'pw-desc-' + Date.now().toString(36)
    const descField = page.locator('[data-testid="categories-edit-description_short"]')
    await descField.fill(newDesc)
    await page.click('#categories-edit-modal .modal-footer .btn-primary')

    // Re-open the same row and confirm the description persisted
    await page.reload()
    await page.waitForLoadState('networkidle')
    await page.locator('[data-testid^="categories-edit-link-"]').first().click()
    await page.waitForSelector('#categories-edit-modal.show')
    await expect(descField).toHaveValue(newDesc)
  })
})

// ---------- Roles ----------

test.describe('Admin roles page', () => {
  test('renders the Vue SPA with the roles table', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/role')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('[data-testid="roles-table"]')).toBeVisible()
    // Bespoke page - no add / delete affordances
    await expect(page.locator('[data-testid="roles-add-button"]')).toHaveCount(0)
  })

  test('admin can toggle a permission on a role', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
    await page.goto(baseURL + '/role')
    await page.waitForLoadState('networkidle')

    // Edit the Host role specifically (id=3 - stable across environments)
    await page.locator('[data-testid="roles-edit-link-3"]').click()
    await page.waitForSelector('#roles-edit-modal.show')

    // Grab the first checkbox in the permission group; capture its current
    // checked state, toggle it, save, re-open and confirm.
    const group = page.locator('[data-testid="roles-edit-permissions"]')
    const firstBox = group.locator('input[type=checkbox]').first()
    const wasChecked = await firstBox.isChecked()
    if (wasChecked) {
      await firstBox.uncheck()
    } else {
      await firstBox.check()
    }
    await page.click('#roles-edit-modal .modal-footer .btn-primary')

    // Modal closes on success
    await expect(page.locator('#roles-edit-modal.show')).toHaveCount(0, { timeout: 5000 })

    // Re-open and confirm the new state stuck
    await page.locator('[data-testid="roles-edit-link-3"]').click()
    await page.waitForSelector('#roles-edit-modal.show')
    const reopened = group.locator('input[type=checkbox]').first()
    await expect(reopened).toBeChecked({ checked: !wasChecked })

    // Put it back so the test is idempotent
    if (wasChecked) {
      await reopened.check()
    } else {
      await reopened.uncheck()
    }
    await page.click('#roles-edit-modal .modal-footer .btn-primary')
  })

  test('non-admin is redirected away from /role', async ({ page, baseURL }) => {
    test.slow()
    await login(page, baseURL, RESTARTER_EMAIL, PASSWORD)
    await page.goto(baseURL + '/role')
    await page.waitForLoadState('networkidle')

    // Controller redirects to RouteServiceProvider::HOME (= /dashboard)
    await expect(page).not.toHaveURL(/\/role/)
  })
})
