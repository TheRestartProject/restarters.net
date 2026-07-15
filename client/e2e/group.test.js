import path from 'path'
import { fileURLToPath } from 'url'
import { test, expect, API_URL } from './fixtures'
import { USERS, login } from './utils'

// Ports tests/Integration/group.test.js's three flows to the Nuxt client:
// create a group, join+unfollow an existing group, and persist a group
// image upload. See client/e2e/utils.js and client/e2e/fixtures.js for the
// shared login/routing conventions this file follows.

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const TEST_IMAGE = path.join(__dirname, 'fixtures', 'test-image.png')

// Seeded by Taskfile's playwright:seed-data (see tests/Integration/
// grouptags.test.js's header comment): network "Test London", group "Tag
// Test Group" in that network with host@test.net as its host.
const TAG_TEST_GROUP = 'Tag Test Group'

// Mirrors grouptags.test.js's getGroupId helper - resolves a seeded group's
// id via the names index rather than hardcoding it.
async function getGroupIdByName(page, name) {
  const response = await page.request.get(`${API_URL}/api/v2/groups/names`)
  const body = await response.json()
  const groups = body.data || body
  const group = groups.find((g) => g.name === name)
  if (!group) throw new Error(`Group "${name}" not found`)
  return group.id
}

test.describe('groups', () => {
  test('Can create group', async ({ page }) => {
    test.slow()

    await login(page, USERS.admin)
    await page.goto('/group/create')

    const groupName = `E2E Test Group ${Date.now()}`
    await page.getByTestId('group-form-name').fill(groupName)
    await page
      .locator('[data-testid="group-form-description"] .ql-editor')
      .fill('An e2e test group created by Playwright.')
    // The maps autocomplete/place-details proxy exists but real suggestions
    // depend on a live Google key resolving - the manual-entry path is what
    // GroupController::createGroupv2 actually needs (it re-geocodes the
    // plain text server-side), so typing a real location and leaving any
    // suggestion dropdown untouched is sufficient here.
    await page.getByTestId('location-picker-input').fill('London, UK')
    await page.getByTestId('group-form-timezone').fill('Europe/London')

    const [createResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => new URL(resp.url()).pathname === '/api/v2/groups' && resp.request().method() === 'POST',
        { timeout: 20000 },
      ),
      page.getByTestId('group-form-submit').click(),
    ])
    expect(createResponse.status()).toBe(200)

    // GroupCreateTest.php + legacy Playwright contract: create redirects to
    // the edit page for the new group.
    await page.waitForURL('**/group/edit/**', { timeout: 20000 })
    await expect(page.getByTestId('group-edit-page')).toBeVisible()
  })

  test('Can join and unfollow group', async ({ page }) => {
    test.slow()

    // nc@test.net is only linked to Tag Test Group's network, not the group
    // itself (unlike host@test.net, seeded as a member) - see
    // tests/Integration/grouptags.test.js's header comment.
    await login(page, USERS.nc)

    const id = await getGroupIdByName(page, TAG_TEST_GROUP)
    await page.goto(`/group/view/${id}`)

    const joinButton = page.getByTestId(`group-join-${id}`)
    const leaveButton = page.getByTestId(`group-leave-${id}`)

    // Self-healing: GroupJoinButton renders exactly one of these two
    // testids depending on current membership. A prior failed/retried run
    // may have left nc mid-flow (joined but not left), so normalize to "not
    // a member" first rather than assuming a fixed starting state.
    await expect(joinButton.or(leaveButton)).toBeVisible({ timeout: 10000 })
    if (await leaveButton.isVisible()) {
      await Promise.all([
        page.waitForResponse(
          (resp) => resp.url().includes(`/api/v2/groups/${id}/members/me`) && resp.request().method() === 'DELETE',
        ),
        leaveButton.click(),
      ])
      await expect(joinButton).toBeVisible()
    }

    await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/v2/groups/${id}/members/me`) && resp.request().method() === 'POST',
      ),
      joinButton.click(),
    ])
    await expect(leaveButton).toBeVisible()

    await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/v2/groups/${id}/members/me`) && resp.request().method() === 'DELETE',
      ),
      leaveButton.click(),
    ])
    await expect(joinButton).toBeVisible()
  })

  test('Group image upload persists on view page', async ({ page }) => {
    test.slow()

    // host@test.net has host role on Tag Test Group, so can_edit is true.
    await login(page, USERS.host)

    const id = await getGroupIdByName(page, TAG_TEST_GROUP)
    await page.goto(`/group/edit/${id}`)
    await expect(page.getByTestId('group-form')).toBeVisible()

    // TusImageUpload.vue mounts an Uppy Dashboard, which renders two hidden
    // `.uppy-Dashboard-input` file inputs (the normal picker plus a
    // webkitdirectory folder picker) - target the plain file one.
    // autoProceed:true starts the tus upload immediately on selection.
    const [uploadResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/v2/groups/${id}/images`) && resp.request().method() === 'POST',
        { timeout: 20000 },
      ),
      page.locator('.uppy-Dashboard-input:not([webkitdirectory])').setInputFiles(TEST_IMAGE),
    ])
    expect(uploadResponse.status()).toBe(200)

    await page.goto(`/group/view/${id}`)
    await expect(page.locator('img.groupImage[src*="/uploads/"]')).toBeVisible({ timeout: 10000 })
  })
})
