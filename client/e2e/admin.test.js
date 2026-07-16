import { test, expect } from './fixtures'
import { USERS, login } from './utils'

// Ports tests/Integration/admin-users.test.js and
// admin-reference-data.test.js to the Nuxt client. Both legacy files were
// already smoke-level (create/edit/delete round-trips "timed out the CI
// Playwright step" per admin-reference-data.test.js's own header comment) -
// this port goes further because pages/brands.vue's generic
// AdminCrudTable.vue (design.md §6.2 Phase D task D4) gives every
// reference-data page the same well-defined data-testid contract, so a
// full create/edit/delete round-trip is no more code than a pure smoke
// test. group-tags has its own coverage in grouptags.test.js (E5).

test.describe('admin', () => {
  test('Admin sees /user/all with working filters', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)
    await page.goto('/user/all')

    await expect(page.getByTestId('users-table')).toBeVisible({ timeout: 10000 })
    await expect(page.getByTestId('users-filter-submit')).toBeVisible()

    await page.getByTestId('users-filter-name').fill('Jane')
    await page.getByTestId('users-filter-submit').click()

    await expect(page.getByTestId('users-table')).toContainText('Jane Bloggs')
  })

  test('Host is redirected away from /user/all', async ({ page }) => {
    test.slow()
    await login(page, USERS.host)
    await page.goto('/user/all')
    await page.waitForURL('**/forbidden**', { timeout: 10000 })
  })

  test('Host is redirected away from /role', async ({ page }) => {
    test.slow()
    await login(page, USERS.host)
    await page.goto('/role')
    await page.waitForURL('**/forbidden**', { timeout: 10000 })
  })

  test('Admin brands CRUD round-trip, including duplicate-name 422', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)
    await page.goto('/brands')

    await expect(page.getByTestId('brands-table')).toBeVisible({ timeout: 10000 })
    await expect(page.getByTestId('brands-add-button')).toBeVisible()

    const brandName = `E2E Brand ${Date.now()}`

    // Create
    await page.getByTestId('brands-add-button').click()
    await expect(page.getByTestId('brands-create-modal')).toBeVisible()
    await page.getByTestId('brands-create-brand_name').fill(brandName)

    const [createResponse] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/api/v2/brands') && resp.request().method() === 'POST'),
      page.getByTestId('brands-create-submit').click(),
    ])
    const { data: created } = await createResponse.json()
    const brandId = created.id

    await expect(page.getByTestId('brands-create-modal')).toBeHidden({ timeout: 10000 })
    await expect(page.getByTestId('brands-table')).toContainText(brandName)

    // Duplicate name -> 422 surfaced as a field error, modal stays open
    await page.getByTestId('brands-add-button').click()
    await page.getByTestId('brands-create-brand_name').fill(brandName)
    await page.getByTestId('brands-create-submit').click()
    await expect(page.getByTestId('brands-create-brand_name-error')).toBeVisible({ timeout: 10000 })
    await expect(page.getByTestId('brands-create-modal')).toBeVisible()
    // AdminCrudTable's create-modal Cancel button has no data-testid (only
    // the delete modal's does) - match its label text instead.
    await page.getByTestId('brands-create-modal').getByRole('button', { name: /cancel/i }).click()

    // Edit
    const editedName = `${brandName} Edited`
    await page.getByTestId(`brands-edit-link-${brandId}`).click()
    await expect(page.getByTestId('brands-edit-modal')).toBeVisible()
    await page.getByTestId('brands-edit-brand_name').fill(editedName)
    await page.getByTestId('brands-edit-submit').click()
    await expect(page.getByTestId('brands-edit-modal')).toBeHidden({ timeout: 10000 })
    await expect(page.getByTestId('brands-table')).toContainText(editedName)

    // Delete
    await page.getByTestId(`brands-delete-${brandId}`).click()
    await expect(page.getByTestId('brands-delete-modal')).toBeVisible()
    await page.getByTestId('brands-delete-confirm').click()
    await expect(page.getByTestId('brands-delete-modal')).toBeHidden({ timeout: 10000 })
    await expect(page.getByTestId('brands-table')).not.toContainText(editedName)
  })

  test('editId deep-link opens the brands edit modal', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    // Create a brand first so there's a real id to deep-link to.
    await page.goto('/brands')
    await expect(page.getByTestId('brands-table')).toBeVisible({ timeout: 10000 })
    const brandName = `E2E Deep Link Brand ${Date.now()}`
    await page.getByTestId('brands-add-button').click()
    await page.getByTestId('brands-create-brand_name').fill(brandName)

    const [createResponse] = await Promise.all([
      page.waitForResponse((resp) => resp.url().includes('/api/v2/brands') && resp.request().method() === 'POST'),
      page.getByTestId('brands-create-submit').click(),
    ])
    const { data } = await createResponse.json()
    const brandId = data.id

    await page.goto(`/brands?editId=${brandId}`)
    await expect(page.getByTestId('brands-edit-modal')).toBeVisible({ timeout: 10000 })
    await expect(page.getByTestId('brands-edit-brand_name')).toHaveValue(brandName)
  })
})
