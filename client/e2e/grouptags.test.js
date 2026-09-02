import { test, expect, API_URL } from './fixtures'
import { USERS, login } from './utils'

// Ports tests/Integration/grouptags.test.js's permission matrix to the
// Nuxt client. The legacy suite drove a CSS-class-selector, vue-multiselect
// based UI (.tag-item/.create-tag/.edit-tag-btn/.delete-tag-btn, plus a
// multiselect on the group-edit tag picker); the Nuxt port is built on
// design.md §6.2 Phase D/E's generic AdminCrudTable.vue and a plain
// BFormCheckboxGroup instead, both with a real data-testid contract - see
// docs/nuxt-migration/api-gaps.md's E1 "testid-prefix=tag" entry (written
// for exactly this port) for the full rationale and the mapping from the
// legacy brief's tag-item/create-tag/edit-tag/delete-tag naming onto
// tag-row-*/tag-create-*/tag-edit-*/tag-delete-*.
//
// Network-scoped tags (managed from /networks/{id}, testid-prefix="tag")
// and global tags (managed from /tags, testid-prefix="tags") are DIFFERENT
// endpoints with different validation shapes, confirmed by reading both
// controllers directly:
//  - NetworkController::create/updateNetworkTagv2 do a manual duplicate
//    check and return a bare {message} (no field-level `errors`), so
//    AdminCrudTable's applyApiError() falls through to its *general* error
//    slot (tag-create-error / tag-edit-error), not a per-field one.
//  - GroupTagController::create/updateGroupTagv2 use a Laravel `unique`
//    validation rule, which DOES return field-level `errors.name`, landing
//    on tags-create-name-error / tags-edit-name-error instead.
// Each duplicate-name test below asserts the testid that actually matches
// its endpoint's error shape, not a guess.
//
// Every test builds its own tag/group state with a unique (Date.now())
// name rather than relying on tag names created by earlier tests, unlike
// the legacy suite's "NC cannot edit tag to duplicate name" (which reused
// "PW Test Tag" from an earlier test) - see superpowers:systematic-
// debugging's general anti-flake guidance: inter-test ordering coupling is
// the class of bug this avoids.
//
// GET .../tags, .../groups?group_tag=, .../events?group_tag=,
// .../stats?group_tag= are all public routes (routes/api.php: they sit
// outside every auth:sanctum,api group under /networks) - confirmed by
// reading the route file directly, so the 4 "API:" tests below need no
// login/token at all, matching the legacy suite's own (token-less)
// behaviour for the same requests.

const NETWORK_NAME = 'Test London'
const GROUP_NAME = 'Tag Test Group'

const ROLES = [
  { label: 'NC', user: USERS.nc },
  { label: 'Admin', user: USERS.admin },
]

// ---------- id lookups ----------

async function getNetworkId(page) {
  const response = await page.request.get(`${API_URL}/api/v2/networks`)
  const body = await response.json()
  const networks = body.data || body
  const network = networks.find((n) => n.name === NETWORK_NAME)
  if (!network) throw new Error(`Network "${NETWORK_NAME}" not found`)
  return network.id
}

async function getGroupId(page) {
  const response = await page.request.get(`${API_URL}/api/v2/groups/names`)
  const body = await response.json()
  const groups = body.data || body
  const group = groups.find((g) => g.name === GROUP_NAME)
  if (!group) throw new Error(`Group "${GROUP_NAME}" not found`)
  return group.id
}

// ---------- network-scoped tag helpers (testid-prefix="tag") ----------

async function openNetworkTagsPage(page, networkId) {
  await page.goto(`/networks/${networkId}`)
  await expect(page.getByTestId('tags-management')).toBeVisible({ timeout: 10000 })
}

async function createNetworkTag(page, networkId, name) {
  // NetworkTagsManager renders an always-visible inline create form
  // (network-tags-create-*), not a modal opened by an add button. Earlier this
  // helper drove a modal UI that was never built, so it hung 360s waiting for
  // tag-add-button and timed the whole e2e job out.
  await page.getByTestId('network-tags-create-name').fill(name)

  const [response] = await Promise.all([
    page.waitForResponse(
      (resp) => resp.url().includes(`/api/v2/networks/${networkId}/tags`) && resp.request().method() === 'POST',
    ),
    page.getByTestId('network-tags-create-submit').click(),
  ])
  return response
}

async function editNetworkTagByRow(page, tagId, newName) {
  // Edit IS a modal here (network-tags-edit-modal), opened from the per-row
  // network-tag-edit-<id> button.
  await page.getByTestId(`network-tag-edit-${tagId}`).click()
  await expect(page.getByTestId('network-tags-edit-modal')).toBeVisible()
  await page.getByTestId('network-tags-edit-name').fill(newName)

  const [response] = await Promise.all([
    page.waitForResponse((resp) => resp.url().includes(`/tags/${tagId}`) && resp.request().method() === 'PUT'),
    page.getByTestId('network-tags-edit-submit').click(),
  ])
  return response
}

function networkTagRowId(response) {
  return response.json().then((body) => (body.data || body).id)
}

// ---------- global tag helpers (testid-prefix="tags", /tags page) ----------

async function openGlobalTagsPage(page) {
  await page.goto('/tags')
  await expect(page.getByTestId('tags-table')).toBeVisible({ timeout: 10000 })
}

async function createGlobalTag(page, name) {
  await page.getByTestId('tags-add-button').click()
  await expect(page.getByTestId('tags-create-modal')).toBeVisible()
  await page.getByTestId('tags-create-name').fill(name)

  const [response] = await Promise.all([
    page.waitForResponse((resp) => resp.url().includes('/api/v2/group-tags') && resp.request().method() === 'POST'),
    page.getByTestId('tags-create-submit').click(),
  ])
  return response
}

// ---------- group-edit tag assignment (GroupMultiSelect) ----------

// GroupForm.vue passes tagOptions to GroupMultiSelect, not to a checkbox
// group: selected tags show as chips, and the unselected ones live in a
// dropdown that is only rendered while the search box has focus. Unique
// per-test tag names keep the `hasText` filters unambiguous without needing
// exact-match regex gymnastics.
async function setGroupTagAssignment(page, groupId, tagName, checked) {
  await page.goto(`/group/edit/${groupId}`)
  await expect(page.getByTestId('group-form')).toBeVisible({ timeout: 10000 })

  const container = page.getByTestId('group-form-tags')
  await expect(container).toBeVisible({ timeout: 10000 })

  const chip = container.locator('.group-multiselect__chip').filter({ hasText: tagName })

  if (checked) {
    // Focus opens the dropdown and typing narrows it; the group headings are
    // plain list items, so only the actionable ones are real options.
    const search = container.getByTestId('group-form-tags-search')
    await search.click()
    await search.fill(tagName)

    const option = container.locator('li.list-group-item-action').filter({ hasText: tagName })
    await expect(option).toHaveCount(1, { timeout: 10000 })
    await option.click()

    // Selecting clears the search box but leaves the dropdown open, and it is
    // absolutely positioned over the rest of the form - including the submit
    // button. Blur closes it (the component's own @blur="open = false").
    await search.blur()
  } else {
    await expect(chip).toHaveCount(1, { timeout: 10000 })
    await chip.locator('.group-multiselect__chip-remove').click()
  }

  await expect(chip).toHaveCount(checked ? 1 : 0, { timeout: 10000 })

  const [response] = await Promise.all([
    page.waitForResponse(
      (resp) => resp.url().includes(`/api/v2/groups/${groupId}`) && resp.request().method() === 'PATCH',
    ),
    page.getByTestId('group-form-submit').click(),
  ])
  expect(response.status()).toBe(200)
  return response
}

// ============================================================
// A. Network tag CRUD matrix (NC and Admin have identical rights here -
//    NetworkPolicy::view/associateGroups is the same condition for both,
//    see docs/nuxt-migration/api-gaps.md's E1 permission-collapse note).
// ============================================================

for (const { label, user } of ROLES) {
  test.describe(`${label}: network tag management`, () => {
    test(`${label} can view the network page with the tags-management section`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      await openNetworkTagsPage(page, networkId)
    })

    test(`${label} can create a network tag`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      await openNetworkTagsPage(page, networkId)

      const tagName = `${label} Create Tag ${Date.now()}`
      const response = await createNetworkTag(page, networkId, tagName)
      expect(response.status()).toBe(201)
      await expect(page.getByTestId('network-tags-manager')).toContainText(tagName)
    })

    test(`${label} cannot create a duplicate network tag`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      await openNetworkTagsPage(page, networkId)

      const tagName = `${label} Dup Tag ${Date.now()}`
      await createNetworkTag(page, networkId, tagName)

      await page.getByTestId('network-tags-create-name').fill(tagName)
      await page.getByTestId('network-tags-create-submit').click()

      // NetworkController's duplicate check returns a bare {message}, not
      // field-level errors - AdminCrudTable surfaces that as the general
      // create-error paragraph, not a per-field one.
      await expect(page.getByTestId('network-tags-create-error')).toBeVisible({ timeout: 10000 })
      await expect(page.getByTestId('network-tags-create-form')).toBeVisible()
    })

    test(`${label} can create a network tag with the same name as an existing global tag`, async ({ page }) => {
      test.slow()
      const globalTagName = `Shared Name Tag ${Date.now()}`

      // Global tags are Administrator-only, regardless of who runs the
      // rest of this test.
      await login(page, USERS.admin)
      await openGlobalTagsPage(page)
      const globalResponse = await createGlobalTag(page, globalTagName)
      expect(globalResponse.status()).toBe(201)

      await login(page, user)
      const networkId = await getNetworkId(page)
      await openNetworkTagsPage(page, networkId)

      const response = await createNetworkTag(page, networkId, globalTagName)
      expect(response.status()).toBe(201)
      await expect(page.getByTestId('network-tags-manager')).toContainText(globalTagName)
    })

    test(`${label} can edit a network tag`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      await openNetworkTagsPage(page, networkId)

      const originalName = `${label} Edit Tag ${Date.now()}`
      const createResponse = await createNetworkTag(page, networkId, originalName)
      const tagId = await networkTagRowId(createResponse)

      const editedName = `${originalName} Edited`
      const editResponse = await editNetworkTagByRow(page, tagId, editedName)
      expect(editResponse.status()).toBe(200)
      await expect(page.getByTestId('network-tags-edit-modal')).toBeHidden({ timeout: 10000 })
      await expect(page.getByTestId('network-tags-manager')).toContainText(editedName)
    })

    test(`${label} cannot edit a network tag to a duplicate name`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      await openNetworkTagsPage(page, networkId)

      const firstName = `${label} Existing Tag ${Date.now()}`
      await createNetworkTag(page, networkId, firstName)

      const secondName = `${label} Second Tag ${Date.now()}`
      const secondResponse = await createNetworkTag(page, networkId, secondName)
      const secondId = await networkTagRowId(secondResponse)

      await page.getByTestId(`network-tag-edit-${secondId}`).click()
      await expect(page.getByTestId('network-tags-edit-modal')).toBeVisible()
      await page.getByTestId('network-tags-edit-name').fill(firstName)
      await page.getByTestId('network-tags-edit-submit').click()

      await expect(page.getByTestId('network-tags-edit-error')).toBeVisible({ timeout: 10000 })
    })

    test(`${label} can delete a network tag with 0 groups`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      await openNetworkTagsPage(page, networkId)

      const tagName = `${label} Delete Me ${Date.now()}`
      const createResponse = await createNetworkTag(page, networkId, tagName)
      const tagId = await networkTagRowId(createResponse)

      await page.getByTestId(`network-tag-delete-${tagId}`).click()
      await expect(page.getByTestId('network-tags-delete-modal')).toBeVisible()
      // 0 groups -> no in-use warning.
      await expect(page.getByTestId('network-tags-delete-warning')).toHaveCount(0)

      const deleteResponse = await Promise.all([
        page.waitForResponse((resp) => resp.url().includes(`/tags/${tagId}`) && resp.request().method() === 'DELETE'),
        page.getByTestId('network-tags-delete-confirm').click(),
      ]).then(([resp]) => resp)
      expect(deleteResponse.status()).toBe(200)
      await expect(page.getByTestId(`network-tag-${tagId}`)).toHaveCount(0)
    })

    test(`${label} sees a warning deleting a network tag with groups attached`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      const groupId = await getGroupId(page)

      await openNetworkTagsPage(page, networkId)
      const tagName = `${label} Tag With Group ${Date.now()}`
      const createResponse = await createNetworkTag(page, networkId, tagName)
      const tagId = await networkTagRowId(createResponse)

      await setGroupTagAssignment(page, groupId, tagName, true)

      await openNetworkTagsPage(page, networkId)
      await expect(page.getByTestId(`network-tag-${tagId}`)).toContainText('1')

      await page.getByTestId(`network-tag-delete-${tagId}`).click()
      await expect(page.getByTestId('network-tags-delete-modal')).toBeVisible()
      await expect(page.getByTestId('network-tags-delete-warning')).toBeVisible()

      // Clean up the assignment so this tag doesn't linger attached.
      await page.getByTestId('network-tags-delete-cancel').click()
      await setGroupTagAssignment(page, groupId, tagName, false)
    })
  })
}

// ============================================================
// B. Tag assignment via group edit
// ============================================================

for (const { label, user } of ROLES) {
  test.describe(`${label}: group tag assignment`, () => {
    test(`${label} can add and then remove a tag from the group`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      const groupId = await getGroupId(page)

      await openNetworkTagsPage(page, networkId)
      const tagName = `${label} Assign Tag ${Date.now()}`
      await createNetworkTag(page, networkId, tagName)

      await setGroupTagAssignment(page, groupId, tagName, true)
      await expect(page.getByTestId('group-form-error')).toHaveCount(0)

      await setGroupTagAssignment(page, groupId, tagName, false)
      await expect(page.getByTestId('group-form-error')).toHaveCount(0)
    })
  })
}

test.describe('tag visibility', () => {
  for (const { label, user } of ROLES) {
    test(`${label} sees an assigned tag on the group view page`, async ({ page }) => {
      test.slow()
      await login(page, user)
      const networkId = await getNetworkId(page)
      const groupId = await getGroupId(page)

      await openNetworkTagsPage(page, networkId)
      const tagName = `${label} Visible Tag ${Date.now()}`
      await createNetworkTag(page, networkId, tagName)

      await setGroupTagAssignment(page, groupId, tagName, true)

      await page.goto(`/group/view/${groupId}`, { waitUntil: 'domcontentloaded' })
      await expect(page.getByTestId('group-view-tags')).toContainText(tagName, { timeout: 10000 })

      // Clean up.
      await setGroupTagAssignment(page, groupId, tagName, false)
    })
  }

  test('Host does not see tags on the group view page', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)
    const networkId = await getNetworkId(page)
    const groupId = await getGroupId(page)

    await openNetworkTagsPage(page, networkId)
    const tagName = `Host Hidden Tag ${Date.now()}`
    await createNetworkTag(page, networkId, tagName)
    await setGroupTagAssignment(page, groupId, tagName, true)

    // Group.php's getFilteredTagsForUser() strips non-global tags server-
    // side for anyone who isn't an admin or a coordinator of the tag's
    // network - a host gets an empty `tags` array back regardless of what
    // the group actually has assigned, so the badge section never renders.
    await login(page, USERS.host)
    await page.goto(`/group/view/${groupId}`, { waitUntil: 'domcontentloaded' })
    await expect(page.getByTestId('group-view-tags')).toHaveCount(0)

    // Clean up as admin.
    await login(page, USERS.admin)
    await setGroupTagAssignment(page, groupId, tagName, false)
  })

  test('Host is redirected away from the network page entirely', async ({ page }) => {
    test.slow()
    // GET /api/v2/networks is a public route (routes/api.php), so resolving
    // the id needs no login.
    const networkId = await getNetworkId(page)

    // Host coordinates no networks and isn't an Administrator -
    // NetworkPolicy::view has no reduced-permission "read only" case, so
    // the page itself redirects to /forbidden on mount (pages/networks/
    // [id].vue) rather than hiding just the tags section.
    await login(page, USERS.host)
    await page.goto(`/networks/${networkId}`)
    await page.waitForURL('**/forbidden**', { timeout: 10000 })
  })
})

test('NC/Admin can filter a network\'s groups table by tag', async ({ page }) => {
  test.slow()
  await login(page, USERS.admin)
  const networkId = await getNetworkId(page)
  const groupId = await getGroupId(page)

  await openNetworkTagsPage(page, networkId)
  const tagName = `Filter Tag ${Date.now()}`
  await createNetworkTag(page, networkId, tagName)
  await setGroupTagAssignment(page, groupId, tagName, true)

  await page.goto(`/networks/${networkId}`)
  const filter = page.getByTestId('network-show-tag-filter')
  await expect(filter).toBeVisible({ timeout: 10000 })

  const [filterResponse] = await Promise.all([
    page.waitForResponse(
      (resp) => resp.url().includes(`/api/v2/networks/${networkId}/groups`) && resp.url().includes('group_tag='),
    ),
    filter.selectOption({ label: tagName }),
  ])
  expect(filterResponse.ok()).toBeTruthy()
  await expect(page.getByTestId('groups-table')).toContainText(GROUP_NAME)

  // Clean up.
  await setGroupTagAssignment(page, groupId, tagName, false)
})

// ============================================================
// C. Pure-API tests - public, unauthenticated GETs (routes/api.php: all 4
//    sit outside every auth:sanctum,api group under /networks/{id}).
// ============================================================

test.describe('API', () => {
  test('Retrieve tags for a network', async ({ page }) => {
    const networkId = await getNetworkId(page)
    const response = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/tags`)
    expect(response.ok()).toBeTruthy()
    const body = await response.json()
    const tags = body.data || body
    expect(Array.isArray(tags)).toBeTruthy()
  })

  test('Retrieve groups filtered by tag', async ({ page }) => {
    const networkId = await getNetworkId(page)
    const tagsResponse = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/tags`)
    const tags = (await tagsResponse.json()).data || []
    test.skip(tags.length === 0, 'no tags exist on this network yet')

    const response = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/groups?group_tag=${tags[0].id}`)
    expect(response.ok()).toBeTruthy()
  })

  test('Retrieve events filtered by tag', async ({ page }) => {
    const networkId = await getNetworkId(page)
    const tagsResponse = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/tags`)
    const tags = (await tagsResponse.json()).data || []
    test.skip(tags.length === 0, 'no tags exist on this network yet')

    const response = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/events?group_tag=${tags[0].id}`)
    expect(response.ok()).toBeTruthy()
  })

  test('Retrieve stats filtered by tag', async ({ page }) => {
    const networkId = await getNetworkId(page)
    const tagsResponse = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/tags`)
    const tags = (await tagsResponse.json()).data || []
    test.skip(tags.length === 0, 'no tags exist on this network yet')

    const response = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/stats?group_tag=${tags[0].id}`)
    expect(response.ok()).toBeTruthy()
    const stats = await response.json()
    expect(stats).toHaveProperty('parties')
    expect(stats).toHaveProperty('co2_total')
    expect(stats).toHaveProperty('waste_total')
  })
})

// ============================================================
// D. Global tag management (/tags, Administrator-only, testid-prefix="tags")
// ============================================================

test.describe('global tags admin page', () => {
  test('Admin can view the global tags page', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)
    await openGlobalTagsPage(page)
    await expect(page.getByTestId('tags-add-button')).toBeVisible()
  })

  test('Admin can add, edit and delete a global tag', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)
    await openGlobalTagsPage(page)

    const name = `E2E Global Tag ${Date.now()}`
    const createResponse = await createGlobalTag(page, name)
    expect(createResponse.status()).toBe(201)
    const tagId = (await createResponse.json()).data.id
    await expect(page.getByTestId('tags-create-modal')).toBeHidden({ timeout: 10000 })
    await expect(page.getByTestId('tags-table')).toContainText(name)

    const editedName = `${name} Edited`
    await page.getByTestId(`tags-edit-link-${tagId}`).click()
    await expect(page.getByTestId('tags-edit-modal')).toBeVisible()
    await page.getByTestId('tags-edit-name').fill(editedName)
    await page.getByTestId('tags-edit-submit').click()
    await expect(page.getByTestId('tags-edit-modal')).toBeHidden({ timeout: 10000 })
    await expect(page.getByTestId('tags-table')).toContainText(editedName)

    // Delete - lives inside the edit form since 638e285f4e ("admin Delete
    // moves to the edit form, where develop has it"), not on the row.
    await page.getByTestId(`tags-edit-link-${tagId}`).click()
    await expect(page.getByTestId('tags-edit-modal')).toBeVisible()
    await page.getByTestId(`tags-delete-${tagId}`).click()
    await expect(page.getByTestId('tags-delete-modal')).toBeVisible()
    await page.getByTestId('tags-delete-confirm').click()
    await expect(page.getByTestId('tags-delete-modal')).toBeHidden({ timeout: 10000 })
    // Row-count 0 rather than table-not-contains: the list refetches after a
    // delete, and while the loading placeholder is up the table element
    // itself is absent, which fails a not-toContainText outright.
    await expect(page.getByTestId(`tags-row-${tagId}`)).toHaveCount(0, { timeout: 10000 })
  })

  test('Admin cannot create a duplicate global tag', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)
    await openGlobalTagsPage(page)

    const name = `E2E Dup Global Tag ${Date.now()}`
    await createGlobalTag(page, name)
    await expect(page.getByTestId('tags-create-modal')).toBeHidden({ timeout: 10000 })

    await page.getByTestId('tags-add-button').click()
    await page.getByTestId('tags-create-name').fill(name)
    await page.getByTestId('tags-create-submit').click()

    // GroupTagController uses a Laravel `unique` rule -> field-level error.
    await expect(page.getByTestId('tags-create-name-error')).toBeVisible({ timeout: 10000 })
  })
})
