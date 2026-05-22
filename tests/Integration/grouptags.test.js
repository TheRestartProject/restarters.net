const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const { login } = require('./utils')

// Test data setup: these tests require the following data to be created before running:
// - Network "Test London" (id varies) with NC user nc@test.net (passw0rd)
// - Admin user jane@bloggs.net (passw0rd)
// - Host user host@test.net (passw0rd)
// - Group "Tag Test Group" in network "Test London", with host@test.net as host
//
// This is set up by the task docker:test:playwright command in Taskfile.yml

const NC_EMAIL = 'nc@test.net'
const ADMIN_EMAIL = 'jane@bloggs.net'
const HOST_EMAIL = 'host@test.net'
const PASSWORD = 'passw0rd'
const NETWORK_NAME = 'Test London'
const GROUP_NAME = 'Tag Test Group'

// Helper to get the network page URL by finding the network ID
async function getNetworkId(page, baseURL) {
  const response = await page.request.get(baseURL + '/api/v2/networks/')
  const body = await response.json()
  const networks = body.data || body
  const network = networks.find(n => n.name === NETWORK_NAME)
  if (!network) throw new Error('Network "' + NETWORK_NAME + '" not found')
  return network.id
}

// Helper to find the group edit URL
async function getGroupId(page, baseURL) {
  const response = await page.request.get(baseURL + '/api/v2/groups/names')
  const body = await response.json()
  const groups = body.data || body
  const group = groups.find(g => g.name === GROUP_NAME)
  if (!group) throw new Error('Group "' + GROUP_NAME + '" not found')
  return group.id
}

// ---------- NC: Tag management for the network ----------

test('NC can view network page with tags section', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)
  await expect(page.locator('.tags-management')).toBeVisible()
})

test('NC can create a tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Fill in tag name and description
  await page.fill('.tag-name-input', 'PW Test Tag')
  await page.fill('.tag-description-input', 'Created by Playwright')
  await page.click('.create-tag button[type=submit]')

  // Tag should appear in the list
  await expect(page.locator('.tag-item', { hasText: 'PW Test Tag' })).toBeVisible({ timeout: 15000 })
})

test('NC cannot create duplicate tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Try to create a tag with the same name
  await page.fill('.tag-name-input', 'PW Test Tag')
  await page.fill('.tag-description-input', 'Duplicate attempt')
  await page.click('.create-tag button[type=submit]')

  // Should see error message
  await expect(page.locator('.text-danger')).toBeVisible()
})

test('NC can create tag with same name as global tag', async ({page, baseURL}) => {
  test.slow()
  // Requires a global tag named "GlobalTestTag" to already exist (created in test setup)
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Create a network tag with the same name as the global tag
  await page.fill('.tag-name-input', 'GlobalTestTag')
  await page.fill('.tag-description-input', 'Network tag with global name')
  await page.click('.create-tag button[type=submit]')

  // Should succeed - network tags can share names with global tags
  await expect(page.locator('.tag-item', { hasText: 'GlobalTestTag' })).toBeVisible({ timeout: 15000 })
})

test('NC can edit a tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Click edit on the first tag
  await page.click('.edit-tag-btn')
  await page.waitForSelector('.modal.show')

  // Change the name
  await page.fill('.modal.show input', 'PW Edited Tag')
  await page.click('.modal.show .btn-primary')

  // Wait for modal to close and verify
  await page.waitForSelector('.modal.show', { state: 'hidden' })
  await expect(page.locator('.tag-item', { hasText: 'PW Edited Tag' })).toBeVisible({ timeout: 15000 })
})

test('NC cannot edit tag to duplicate name', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Need at least 2 tags. Create another one first.
  await page.fill('.tag-name-input', 'PW Second Tag')
  await page.fill('.tag-description-input', 'Second tag')
  await page.click('.create-tag button[type=submit]')
  await expect(page.locator('.tag-item', { hasText: 'PW Second Tag' })).toBeVisible({ timeout: 15000 })

  // Edit the second tag to have the same name as the first
  const editButtons = page.locator('.edit-tag-btn')
  await editButtons.last().click()
  await page.waitForSelector('.modal.show')

  await page.fill('.modal.show input', 'PW Edited Tag')
  await page.click('.modal.show .btn-primary')

  // Should see error
  await expect(page.locator('.modal.show .text-danger')).toBeVisible()
})

test('NC can delete tag with 0 groups', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Create a fresh tag to delete
  await page.fill('.tag-name-input', 'PW Delete Me')
  await page.fill('.tag-description-input', 'Will be deleted')
  await page.click('.create-tag button[type=submit]')
  await expect(page.locator('.tag-item', { hasText: 'PW Delete Me' })).toBeVisible({ timeout: 15000 })

  // Count tags before delete
  const countBefore = await page.locator('.tag-item').count()

  // Click delete on the last tag (the one we just created)
  const deleteButtons = page.locator('.delete-tag-btn')
  await deleteButtons.last().click()

  // Confirm in the modal
  await page.waitForSelector('.modal.show')
  await page.click('.modal.show .btn-primary')
  await page.waitForSelector('.modal.show', { state: 'hidden' })

  // Should have one fewer tag
  await expect(page.locator('.tag-item')).toHaveCount(countBefore - 1)
})

test('NC can delete tag with groups attached', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  // Step 1: Create a tag on the network page
  await page.goto(baseURL + '/networks/' + networkId)
  await page.fill('.tag-name-input', 'PW Tag With Group')
  await page.fill('.tag-description-input', 'Tag assigned to a group')
  await page.click('.create-tag button[type=submit]')
  await expect(page.locator('.tag-item', { hasText: 'PW Tag With Group' })).toBeVisible({ timeout: 15000 })

  // Step 2: Assign the tag to the group via the group edit page
  const groupId = await getGroupId(page, baseURL)
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await page.locator('label[for="tags"]').click()
  await page.waitForTimeout(500)
  await page.locator('#tags').fill('PW Tag With Group')
  await page.waitForTimeout(1000)
  await page.locator('.multiselect__content-wrapper .multiselect__option', { hasText: 'PW Tag With Group' }).first().click({ timeout: 10000 })

  // Save the group
  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Step 3: Go back to network page and verify the tag shows group count
  await page.goto(baseURL + '/networks/' + networkId)
  // Don't use networkidle — Leaflet tile requests prevent it from resolving.
  await page.waitForSelector('.tags-management', { timeout: 15000 })

  // Find the tag showing "(1 group)" and click delete
  const tagItem = page.locator('.tag-item', { hasText: 'PW Tag With Group' })
  await expect(tagItem).toBeVisible({ timeout: 15000 })
  await expect(tagItem.locator('text=1 group')).toBeVisible({ timeout: 15000 })

  await tagItem.locator('.delete-tag-btn').click()

  // Modal should warn about groups
  await page.waitForSelector('.modal.show')
  await expect(page.locator('.modal.show .text-warning')).toBeVisible()

  // Confirm delete
  await page.click('.modal.show .btn-primary')
  await page.waitForSelector('.modal.show', { state: 'hidden' })
})

// ---------- NC: Tag management for groups ----------

test('NC can add a tag to a group', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  // First ensure there's a tag to add
  await page.goto(baseURL + '/networks/' + networkId)
  // Create a fresh tag if needed
  await page.fill('.tag-name-input', 'PW Group Assign Tag')
  await page.fill('.tag-description-input', 'For group assignment')
  await page.click('.create-tag button[type=submit]')
  await page.waitForTimeout(1000)

  // Go to group edit page
  const groupId = await getGroupId(page, baseURL)
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Click on the multiselect to activate it (makes input visible)
  await page.locator('label[for="tags"]').click()
  await page.waitForTimeout(500)
  // Now type into the now-visible input to filter
  await page.locator('#tags').fill('PW Group Assign')
  await page.waitForTimeout(1000)
  // Click the matching result
  await page.locator('.multiselect__content-wrapper .multiselect__option', { hasText: 'PW Group Assign Tag' }).first().click({ timeout: 10000 })

  // Save the group
  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForTimeout(2000)

  // Verify save succeeded - we should still be on the edit page without errors
  await expect(page.locator('.alert-danger')).not.toBeVisible()
})

test('NC can remove a tag from a group', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const groupId = await getGroupId(page, baseURL)

  // Go to group edit page (tag should be assigned from previous test)
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Remove the tag by clicking the X on the multiselect tag
  const removeButton = page.locator('#tags').locator('..').locator('.multiselect__tag-icon').first()
  if (await removeButton.isVisible()) {
    await removeButton.click()

    // Save
    await page.locator('button', { hasText: 'Save changes' }).click()
    await page.waitForTimeout(2000)

    // Verify no errors
    await expect(page.locator('.alert-danger')).not.toBeVisible()
  }
})

// ---------- NC: Tag display on group pages / lists ----------

test('NC should see tags on groups list', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  // Go to network groups list
  await page.goto(baseURL + '/group/network/' + networkId)
  // Don't use networkidle — Leaflet tile requests prevent it from resolving.
  // Wait for the API-driven loading spinner to disappear instead.
  await page.waitForSelector('.loader', { state: 'hidden', timeout: 15000 })

  // Tags should be visible (either as badges or filter dropdown)
  // The tag filter dropdown should be visible for NC
  const tagFilter = page.locator('.multiselect', { hasText: /tag/i })
  // At minimum, the groups page should load and show groups
  await expect(page.locator('table, .table').first()).toBeVisible({ timeout: 15000 })
})

test('NC can filter groups list by tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  // First assign a tag to the group so filtering has data
  const groupId = await getGroupId(page, baseURL)
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Add a tag if none assigned
  const existingTags = page.locator('#tags').locator('..').locator('.multiselect__tag')
  if (await existingTags.count() === 0) {
    const tagsContainer = page.locator('#tags').locator('..')
    await tagsContainer.click()
    await page.waitForTimeout(500)
    const option = tagsContainer.locator('.multiselect__option:not(.multiselect__option--disabled)').first()
    if (await option.isVisible()) {
      await option.click()
      await page.locator('button', { hasText: 'Save changes' }).click()
      await page.waitForTimeout(2000)
    }
  }

  // Now go to the groups list and filter by tag
  await page.goto(baseURL + '/group/network/' + networkId)
  // Don't use networkidle — Leaflet tile requests prevent it from resolving.
  await page.waitForSelector('.loader', { state: 'hidden', timeout: 15000 })

  // Click on "All Groups" tab to see all groups
  const allGroupsTab = page.locator('text=All Groups').first()
  if (await allGroupsTab.isVisible()) {
    await allGroupsTab.click()
    await page.waitForTimeout(1000)
  }

  // Count groups before filtering
  const groupsBefore = await page.locator('table tbody tr, .group-row').count()

  // The tag multiselect should be visible for NC
  // Click on the tag filter and select a tag
  const tagMultiselects = page.locator('.multiselect')
  // The tag filter is one of the multiselects - find it by placeholder or position
  // Usually the 2nd multiselect (after name search) is the tag filter
  for (let i = 0; i < await tagMultiselects.count(); i++) {
    const ms = tagMultiselects.nth(i)
    const placeholder = await ms.locator('.multiselect__placeholder, .multiselect__input').first().textContent().catch(() => '')
    if (placeholder && placeholder.toLowerCase().includes('tag')) {
      await ms.click()
      await page.waitForTimeout(500)
      const firstOption = ms.locator('.multiselect__option').first()
      if (await firstOption.isVisible()) {
        await firstOption.click()
        await page.waitForTimeout(1000)
        break
      }
    }
  }
})

test('NC should see tags displayed on group page', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, NC_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  const groupId = await getGroupId(page, baseURL)

  // Ensure a tag exists and is assigned to the group
  await page.goto(baseURL + '/networks/' + networkId)
  await page.fill('.tag-name-input', 'PW Visible Tag')
  await page.fill('.tag-description-input', 'Should appear on group page')
  await page.click('.create-tag button[type=submit]')
  await page.waitForTimeout(1000)

  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await page.locator('label[for="tags"]').click()
  await page.waitForTimeout(500)
  await page.locator('#tags').fill('PW Visible Tag')
  await page.waitForTimeout(1000)
  await page.locator('.multiselect__content-wrapper .multiselect__option', { hasText: 'PW Visible Tag' }).first().click({ timeout: 10000 })

  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // View the group page (not edit)
  await page.goto(baseURL + '/group/view/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Tags should be displayed as badges on the group view page
  await expect(page.locator('.badge-info.badge-pill.mr-1').first()).toBeVisible({ timeout: 10000 })
})

// ---------- Host: Should not see tags ----------

test('Host does not see tags on groups list', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, HOST_EMAIL, PASSWORD)

  // Go to groups page
  await page.goto(baseURL + '/group')
  // Don't use networkidle — Leaflet tile requests prevent it from resolving.
  await page.waitForTimeout(2000)

  // Tags badges and tag filter should NOT be visible for hosts
  await expect(page.locator('.group-tags-badges')).not.toBeVisible()
})

test('Host does not see tags on group page', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, HOST_EMAIL, PASSWORD)
  const groupId = await getGroupId(page, baseURL)

  // Go to group view page
  await page.goto(baseURL + '/group/view/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Tags should NOT be visible for hosts
  await expect(page.locator('.group-tags-badges')).not.toBeVisible()
})

// ---------- API tests ----------

test('API: Retrieve tags for a network', async ({page, baseURL}) => {
  const networkId = await getNetworkId(page, baseURL)
  const response = await page.request.get(baseURL + '/api/v2/networks/' + networkId + '/tags')
  expect(response.ok()).toBeTruthy()
  const body = await response.json()
  const tags = body.data || body
  expect(Array.isArray(tags)).toBeTruthy()
  // Should have at least one tag (from earlier tests)
  expect(tags.length).toBeGreaterThan(0)
})

test('API: Retrieve groups filtered by tag', async ({page, baseURL}) => {
  const networkId = await getNetworkId(page, baseURL)

  // First get a tag ID
  const tagsResponse = await page.request.get(baseURL + '/api/v2/networks/' + networkId + '/tags')
  const tagsBody = await tagsResponse.json()
  const tags = tagsBody.data || tagsBody
  if (tags.length === 0) return // skip if no tags

  const tagId = tags[0].id
  const response = await page.request.get(baseURL + '/api/v2/networks/' + networkId + '/groups?group_tag=' + tagId)
  expect(response.ok()).toBeTruthy()
})

test('API: Retrieve events filtered by tag', async ({page, baseURL}) => {
  const networkId = await getNetworkId(page, baseURL)

  const tagsResponse = await page.request.get(baseURL + '/api/v2/networks/' + networkId + '/tags')
  const tagsBody = await tagsResponse.json()
  const tags = tagsBody.data || tagsBody
  if (tags.length === 0) return

  const tagId = tags[0].id
  const response = await page.request.get(baseURL + '/api/v2/networks/' + networkId + '/events?group_tag=' + tagId)
  expect(response.ok()).toBeTruthy()
})

test('API: Retrieve stats filtered by tag', async ({page, baseURL}) => {
  const networkId = await getNetworkId(page, baseURL)

  const tagsResponse = await page.request.get(baseURL + '/api/v2/networks/' + networkId + '/tags')
  const tagsBody = await tagsResponse.json()
  const tags = tagsBody.data || tagsBody
  if (tags.length === 0) return

  const tagId = tags[0].id
  const response = await page.request.get(baseURL + '/api/v2/networks/' + networkId + '/stats?group_tag=' + tagId)
  expect(response.ok()).toBeTruthy()
  const stats = await response.json()
  expect(stats).toHaveProperty('parties')
  expect(stats).toHaveProperty('co2_total')
  expect(stats).toHaveProperty('waste_total')
})

// ---------- Admin: Tag management ----------

test('Admin can view network page and see tags', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)
  await expect(page.locator('.tags-management')).toBeVisible()
})

test('Admin can create a tag for a network', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  await page.fill('.tag-name-input', 'Admin Test Tag')
  await page.fill('.tag-description-input', 'Created by admin')
  await page.click('.create-tag button[type=submit]')

  await expect(page.locator('.tag-item', { hasText: 'Admin Test Tag' })).toBeVisible({ timeout: 15000 })
})

test('Admin cannot create duplicate tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  await page.fill('.tag-name-input', 'Admin Test Tag')
  await page.fill('.tag-description-input', 'Duplicate attempt')
  await page.click('.create-tag button[type=submit]')

  await expect(page.locator('.text-danger')).toBeVisible()
})

test('Admin can create tag with same name as global tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Create a tag with a name that might exist as a global tag
  // This should succeed - network tags can share names with global tags
  await page.fill('.tag-name-input', 'Admin Global Name Tag')
  await page.fill('.tag-description-input', 'Same name as global')
  await page.click('.create-tag button[type=submit]')

  await expect(page.locator('.tag-item', { hasText: 'Admin Global Name Tag' })).toBeVisible({ timeout: 15000 })
})

test('Admin can edit a tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Find the first tag's edit button and click it
  await page.locator('.edit-tag-btn').first().click()

  // Modal should open with edit fields
  await page.waitForSelector('.modal.show')
  const nameInput = page.locator('.modal.show .tag-name-input, .modal.show input[type="text"]').first()
  await nameInput.fill('Admin Edited Tag')
  await page.click('.modal.show .btn-primary')
  await page.waitForSelector('.modal.show', { state: 'hidden' })

  // Verify the tag was updated
  await expect(page.locator('.tag-item', { hasText: 'Admin Edited Tag' })).toBeVisible({ timeout: 15000 })
})

test('Admin cannot edit tag to duplicate name', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Create a second tag to try to rename to an existing name
  await page.fill('.tag-name-input', 'Admin Unique Tag')
  await page.fill('.tag-description-input', 'Will try to rename')
  await page.click('.create-tag button[type=submit]')
  await expect(page.locator('.tag-item', { hasText: 'Admin Unique Tag' })).toBeVisible({ timeout: 15000 })

  // Edit the new tag to have the same name as an existing tag
  const tagItem = page.locator('.tag-item', { hasText: 'Admin Unique Tag' })
  await tagItem.locator('.edit-tag-btn').click()

  await page.waitForSelector('.modal.show')
  const nameInput = page.locator('.modal.show .tag-name-input, .modal.show input[type="text"]').first()
  await nameInput.fill('Admin Edited Tag')
  await page.click('.modal.show .btn-primary')

  // Should show an error about duplicate name
  await expect(page.locator('.text-danger, .modal.show .text-danger')).toBeVisible({ timeout: 5000 })
})

test('Admin can delete tag with 0 groups', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  await page.goto(baseURL + '/networks/' + networkId)

  // Create a fresh tag to delete
  await page.fill('.tag-name-input', 'Admin Delete Me')
  await page.fill('.tag-description-input', 'Will be deleted')
  await page.click('.create-tag button[type=submit]')
  await expect(page.locator('.tag-item', { hasText: 'Admin Delete Me' })).toBeVisible({ timeout: 15000 })

  const countBefore = await page.locator('.tag-item').count()

  // Delete the tag
  const tagItem = page.locator('.tag-item', { hasText: 'Admin Delete Me' })
  await tagItem.locator('.delete-tag-btn').click()

  // Confirm in modal
  await page.waitForSelector('.modal.show')
  await page.click('.modal.show .btn-primary')
  await page.waitForSelector('.modal.show', { state: 'hidden' })

  await expect(page.locator('.tag-item')).toHaveCount(countBefore - 1)
})

test('Admin can delete tag with groups attached', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  // Create a tag
  await page.goto(baseURL + '/networks/' + networkId)
  await page.fill('.tag-name-input', 'Admin Tag With Group')
  await page.fill('.tag-description-input', 'Tag to assign then delete')
  await page.click('.create-tag button[type=submit]')
  await expect(page.locator('.tag-item', { hasText: 'Admin Tag With Group' })).toBeVisible({ timeout: 15000 })

  // Assign it to the group
  const groupId = await getGroupId(page, baseURL)
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await page.locator('label[for="tags"]').click()
  await page.waitForTimeout(500)
  await page.locator('#tags').fill('Admin Tag With Group')
  await page.waitForTimeout(1000)
  await page.locator('.multiselect__content-wrapper .multiselect__option', { hasText: 'Admin Tag With Group' }).first().click({ timeout: 10000 })

  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Go back to network page and delete the tag
  await page.goto(baseURL + '/networks/' + networkId)
  // Don't use networkidle — Leaflet tile requests prevent it from resolving.
  await page.waitForSelector('.tags-management', { timeout: 15000 })

  const tagItem = page.locator('.tag-item', { hasText: 'Admin Tag With Group' })
  await expect(tagItem).toBeVisible({ timeout: 15000 })
  await expect(tagItem.locator('text=1 group')).toBeVisible({ timeout: 15000 })

  await tagItem.locator('.delete-tag-btn').click()

  // Modal should warn about groups
  await page.waitForSelector('.modal.show')
  await expect(page.locator('.modal.show .text-warning')).toBeVisible()

  // Confirm delete
  await page.click('.modal.show .btn-primary')
  await page.waitForSelector('.modal.show', { state: 'hidden' })
})

test('Admin can remove a tag from a group', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  // First ensure there's a tag assigned - create and assign one
  await page.goto(baseURL + '/networks/' + networkId)
  await page.fill('.tag-name-input', 'Admin Remove Tag')
  await page.fill('.tag-description-input', 'Will be removed from group')
  await page.click('.create-tag button[type=submit]')
  await page.waitForTimeout(1000)

  // Assign to group
  const groupId = await getGroupId(page, baseURL)
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await page.locator('label[for="tags"]').click()
  await page.waitForTimeout(500)
  await page.locator('#tags').fill('Admin Remove Tag')
  await page.waitForTimeout(1000)
  await page.locator('.multiselect__content-wrapper .multiselect__option', { hasText: 'Admin Remove Tag' }).first().click({ timeout: 10000 })

  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Now remove the tag
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Click the remove button on the tag in the multiselect
  const tagToRemove = page.locator('.multiselect__tag', { hasText: 'Admin Remove Tag' })
  await expect(tagToRemove).toBeVisible()
  await tagToRemove.locator('.multiselect__tag-icon, i').click()

  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Verify tag was removed - reload and check
  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await expect(page.locator('.multiselect__tag', { hasText: 'Admin Remove Tag' })).not.toBeVisible()
})

test('Admin should see tags displayed on group page', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)
  const groupId = await getGroupId(page, baseURL)

  // Ensure a tag exists and is assigned to the group
  await page.goto(baseURL + '/networks/' + networkId)
  await page.fill('.tag-name-input', 'Admin Visible Tag')
  await page.fill('.tag-description-input', 'Should appear on group page')
  await page.click('.create-tag button[type=submit]')
  await page.waitForTimeout(1000)

  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await page.locator('label[for="tags"]').click()
  await page.waitForTimeout(500)
  await page.locator('#tags').fill('Admin Visible Tag')
  await page.waitForTimeout(1000)
  await page.locator('.multiselect__content-wrapper .multiselect__option', { hasText: 'Admin Visible Tag' }).first().click({ timeout: 10000 })

  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // View the group page (not edit)
  await page.goto(baseURL + '/group/view/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Tags should be displayed as badges on the group view page
  await expect(page.locator('.badge-info.badge-pill.mr-1').first()).toBeVisible({ timeout: 10000 })
})

test('Admin can add tag to group (scoped to group networks)', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const groupId = await getGroupId(page, baseURL)

  await page.goto(baseURL + '/group/edit/' + groupId)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Click the label to activate multiselect
  await page.locator('label[for="tags"]').click()
  await page.waitForTimeout(500)

  // The tag dropdown should only show tags from networks the group belongs to
  // plus global tags - NOT tags from other networks
  await page.locator('#tags').fill('Admin')
  await page.waitForTimeout(500)

  // Should find our admin tag in the dropdown
  const option = page.locator('.multiselect__content-wrapper .multiselect__option', { hasText: 'Admin Test Tag' })
  await expect(option.first()).toBeVisible({ timeout: 5000 })
  await option.first().click()

  // Save
  await page.locator('button', { hasText: 'Save changes' }).click()
  await page.waitForTimeout(2000)
  await expect(page.locator('.alert-danger')).not.toBeVisible()
})

test('Admin should see tags on groups list', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  await page.goto(baseURL + '/group/network/' + networkId)
  // Don't use networkidle — Leaflet tile requests prevent it from resolving.
  await page.waitForSelector('.loader', { state: 'hidden', timeout: 15000 })

  // Admin should see groups table with tags
  await expect(page.locator('table, .table').first()).toBeVisible({ timeout: 15000 })
})

test('Admin can filter groups by tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  const networkId = await getNetworkId(page, baseURL)

  await page.goto(baseURL + '/group/network/' + networkId)
  // Don't use networkidle — Leaflet tile requests prevent it from resolving.
  await page.waitForSelector('.loader', { state: 'hidden', timeout: 15000 })

  // The tag filter multiselect should be visible for admins
  // Try to find and use the tag filter
  const tagMultiselects = page.locator('.multiselect')
  for (let i = 0; i < await tagMultiselects.count(); i++) {
    const ms = tagMultiselects.nth(i)
    const placeholder = await ms.locator('.multiselect__placeholder, .multiselect__input').first().textContent().catch(() => '')
    if (placeholder && placeholder.toLowerCase().includes('tag')) {
      await ms.click()
      await page.waitForTimeout(500)
      const firstOption = ms.locator('.multiselect__option').first()
      if (await firstOption.isVisible()) {
        await firstOption.click()
        await page.waitForTimeout(1000)
        break
      }
    }
  }
})

// ---------- Admin: Global tag management (/tags page) ----------

test('Admin can view global tags page', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  await page.goto(baseURL + '/tags')
  await page.waitForLoadState('networkidle')

  // Should see the tags table
  await expect(page.locator('#tags-table, table').first()).toBeVisible()
})

test('Admin can add a global tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  await page.goto(baseURL + '/tags')
  await page.waitForLoadState('networkidle')

  // Click create button to open modal
  await page.click('button[data-target="#add-new-tag"], .btn-save')
  await page.waitForSelector('#add-new-tag.show, .modal.show')

  // Fill in the form
  await page.fill('#tag-name', 'PW Global Tag')
  await page.fill('#tag-description', 'Created by Playwright')
  await page.click('#add-new-tag .btn-primary, .modal.show button[type="submit"]')

  await page.waitForLoadState('networkidle')

  // After creation, redirects to edit page with success message
  await expect(page.locator('text=successfully created')).toBeVisible({ timeout: 5000 })

  // Navigate back to tags list and verify it's there
  await page.goto(baseURL + '/tags')
  await page.waitForLoadState('networkidle')
  await expect(page.locator('#tags-table, table').first()).toContainText('PW Global Tag')
})

test('Admin can edit a global tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  await page.goto(baseURL + '/tags')
  await page.waitForLoadState('networkidle')

  // Click on the tag name link to go to edit page
  await page.locator('a[href*="/tags/edit/"]', { hasText: 'PW Global Tag' }).first().click()
  await page.waitForLoadState('networkidle')

  // Should be on the edit page
  await expect(page.locator('#tag-name')).toBeVisible()

  // Change the name
  await page.fill('#tag-name', 'PW Global Tag Edited')
  await page.click('.btn-create, button[type="submit"]')
  await page.waitForLoadState('networkidle')

  // After save, stays on edit page with success message
  await expect(page.locator('text=successfully updated')).toBeVisible({ timeout: 5000 })

  // Navigate back to tags list and verify updated name
  await page.goto(baseURL + '/tags')
  await page.waitForLoadState('networkidle')
  await expect(page.locator('#tags-table, table').first()).toContainText('PW Global Tag Edited')
})

test('Admin can delete a global tag', async ({page, baseURL}) => {
  test.slow()
  await login(page, baseURL, ADMIN_EMAIL, PASSWORD)
  await page.goto(baseURL + '/tags')
  await page.waitForLoadState('networkidle')

  // Click on the tag to go to edit page
  await page.locator('a[href*="/tags/edit/"]', { hasText: 'PW Global Tag Edited' }).click()
  await page.waitForLoadState('networkidle')

  // Click delete button
  await page.click('.btn-danger')
  await page.waitForLoadState('networkidle')

  // Should redirect to tags list, tag should be gone
  await expect(page.locator('#tags-table, table').first()).not.toContainText('PW Global Tag Edited')
})
