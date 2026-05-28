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

// ---------- Helpers driving the shared AdminCrudPage UI ----------

// Open `/<path>` as the admin and wait for the b-table to be present.
async function openAdminPage(page, baseURL, path, prefix) {
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  await page.goto(baseURL + path)
  await page.waitForLoadState('networkidle')
  await expect(page.locator(`[data-testid="${prefix}-table"]`)).toBeVisible()
}

// Open the create modal, fill the named fields, save, and assert the displayed
// value appears in the table. `fields` is a map from form field key to value;
// values that look like '@select:x' fill via selectOption instead of fill.
async function createItem(page, prefix, fields, displayValue) {
  await page.click(`[data-testid="${prefix}-add-button"]`)
  await page.waitForSelector(`#${prefix}-create-modal.show`)
  await fillFields(page, prefix, 'create', fields)
  await page.click(`#${prefix}-create-modal .modal-footer .btn-primary`)
  await expect(page.locator(`[data-testid="${prefix}-table"]`)).toContainText(displayValue, { timeout: 5000 })
}

// Open the edit modal for the row matching `rowText`, change fields, save,
// and assert the new displayed value appears in the table.
async function editItem(page, prefix, rowText, fields, newDisplayValue) {
  const row = page.locator('tr', { hasText: rowText })
  await row.locator(`[data-testid^="${prefix}-edit-link-"]`).click()
  await page.waitForSelector(`#${prefix}-edit-modal.show`)
  await fillFields(page, prefix, 'edit', fields)
  await page.click(`#${prefix}-edit-modal .modal-footer .btn-primary`)
  await expect(page.locator(`[data-testid="${prefix}-table"]`)).toContainText(newDisplayValue, { timeout: 5000 })
}

// Click the delete button for the row matching `rowText`, confirm in the
// ConfirmModal, and assert the row no longer appears in the table.
async function deleteItem(page, prefix, rowText) {
  const row = page.locator('tr', { hasText: rowText })
  await row.locator(`[data-testid^="${prefix}-delete-"]`).click()
  await page.waitForSelector('#confirmmodal.show')
  await page.click('#confirmmodal .modal-footer .btn-primary')
  await expect(page.locator(`[data-testid="${prefix}-table"]`)).not.toContainText(rowText, { timeout: 5000 })
}

// Fill a set of fields in the named modal (mode = 'create' | 'edit').
// Values prefixed with '@select:' are sent through selectOption; everything
// else is sent through fill.
async function fillFields(page, prefix, mode, fields) {
  for (const [key, value] of Object.entries(fields)) {
    const sel = `[data-testid="${prefix}-${mode}-${key}"]`
    if (typeof value === 'string' && value.startsWith('@select:')) {
      await page.selectOption(sel, value.slice('@select:'.length))
    } else {
      await page.fill(sel, value)
    }
  }
}

// ---------- Brands ----------

test.describe('Admin brands page', () => {
  test('renders the Vue SPA with the add button', async ({ page, baseURL }) => {
    test.slow()
    await openAdminPage(page, baseURL, '/brands', 'brands')
    await expect(page.locator('[data-testid="brands-add-button"]')).toBeVisible()
  })

  test('admin can create, edit, and delete a brand', async ({ page, baseURL }) => {
    test.slow()
    await openAdminPage(page, baseURL, '/brands', 'brands')

    const name = stamp() + '-brand'
    const renamed = name + '-edited'

    await createItem(page, 'brands', { brand_name: name }, name)
    await editItem(page, 'brands', name, { brand_name: renamed }, renamed)
    await deleteItem(page, 'brands', renamed)
  })
})

// ---------- Skills ----------

test.describe('Admin skills page', () => {
  test('renders the Vue SPA with the add button', async ({ page, baseURL }) => {
    test.slow()
    await openAdminPage(page, baseURL, '/skills', 'skills')
    await expect(page.locator('[data-testid="skills-add-button"]')).toBeVisible()
  })

  test('admin can create, edit (changing category), and delete a skill', async ({ page, baseURL }) => {
    test.slow()
    await openAdminPage(page, baseURL, '/skills', 'skills')

    const name = stamp() + '-skill'
    const renamed = name + '-edited'

    await createItem(
      page,
      'skills',
      { skill_name: name, category: '@select:1', description: 'created by playwright' },
      name
    )
    await editItem(
      page,
      'skills',
      name,
      { skill_name: renamed, category: '@select:2' },
      renamed
    )
    await deleteItem(page, 'skills', renamed)
  })
})

// ---------- Categories ----------

test.describe('Admin categories page', () => {
  test('renders the Vue SPA, edit only - no add or delete buttons', async ({ page, baseURL }) => {
    test.slow()
    await openAdminPage(page, baseURL, '/category', 'categories')
    // allowCreate=false and allowDelete=false
    await expect(page.locator('[data-testid="categories-add-button"]')).toHaveCount(0)
    await expect(page.locator('[data-testid^="categories-delete-"]')).toHaveCount(0)
  })

  test('admin can edit a category', async ({ page, baseURL }) => {
    test.slow()
    await openAdminPage(page, baseURL, '/category', 'categories')

    // Open the first row's edit modal (we don't care which category for a smoke test;
    // we just want to verify the PUT round-trips and we see the new description back
    // in the row).
    await page.locator('[data-testid^="categories-edit-link-"]').first().click()
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
    await openAdminPage(page, baseURL, '/role', 'roles')
    // Bespoke page - no add affordance
    await expect(page.locator('[data-testid="roles-add-button"]')).toHaveCount(0)
  })

  test('admin can toggle a permission on a role', async ({ page, baseURL }) => {
    test.slow()
    await openAdminPage(page, baseURL, '/role', 'roles')

    // Edit the Host role specifically (id=3 - stable across environments)
    const openHostEdit = async () => {
      await page.locator('[data-testid="roles-edit-link-3"]').click()
      await page.waitForSelector('#roles-edit-modal.show')
    }
    const firstCheckbox = () =>
      page.locator('[data-testid="roles-edit-permissions"] input[type=checkbox]').first()
    const saveModal = () => page.click('#roles-edit-modal .modal-footer .btn-primary')

    await openHostEdit()
    const wasChecked = await firstCheckbox().isChecked()
    await firstCheckbox().setChecked(!wasChecked)
    await saveModal()
    await expect(page.locator('#roles-edit-modal.show')).toHaveCount(0, { timeout: 5000 })

    // Re-open and confirm the new state stuck
    await openHostEdit()
    await expect(firstCheckbox()).toBeChecked({ checked: !wasChecked })

    // Put it back so the test is idempotent
    await firstCheckbox().setChecked(wasChecked)
    await saveModal()
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
