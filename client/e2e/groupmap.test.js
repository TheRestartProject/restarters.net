import { test, expect, API_URL } from './fixtures'
import { USERS, login } from './utils'

// Ports the map+list flow from resources/js/components/GroupMap.vue's spec
// (RES-1995) to the Nuxt client's /group/map. Assertions stay DOM-based
// (container/list/marker-or-cluster presence), never tile-render-based -
// basemaps.cartocdn.com isn't blocked by fixtures.js like Google Maps is,
// but headless tile fetches can be slow and are irrelevant to what this
// page is responsible for.

// Seeded by Taskfile's playwright:seed-data (see tests/Integration/
// grouptags.test.js's header comment): network "Test London", group "Tag
// Test Group" in that network, geocoded to London (51.5, -0.1) - so it's
// always a mappable marker.
const TAG_TEST_GROUP = 'Tag Test Group'

// Mirrors group.test.js/grouptags.test.js's own local helper - resolves a
// seeded group's id via the names index rather than hardcoding it.
async function getGroupIdByName(page, name) {
  const response = await page.request.get(`${API_URL}/api/v2/groups/names`)
  const body = await response.json()
  const groups = body.data || body
  const group = groups.find((g) => g.name === name)
  if (!group) throw new Error(`Group "${name}" not found`)
  return group.id
}

test.describe('group map', () => {
  test('renders the map, lists the seeded group, and offers a place search box', async ({ page }) => {
    test.slow()

    const groupId = await getGroupIdByName(page, TAG_TEST_GROUP)

    await login(page, USERS.admin)

    const [namesResponse] = await Promise.all([
      page.waitForResponse(
        (resp) => new URL(resp.url()).pathname === '/api/v2/groups/names' && resp.request().method() === 'GET'
      ),
      page.goto('/group/map'),
    ])
    expect(namesResponse.status()).toBe(200)

    await expect(page.getByTestId('group-map-page')).toBeVisible()
    await expect(page.getByTestId('groups-tab-map')).toHaveClass(/active/)

    // The map container (GroupMap.vue) and the underlying Leaflet map both
    // render, without waiting on tiles.
    await expect(page.getByTestId('group-map')).toBeVisible()
    await expect(page.locator('.leaflet-container')).toBeVisible({ timeout: 15000 })

    // At least one marker or cluster bubble is on the map (individual
    // groups render as .leaflet-marker-icon <img>; groups close together
    // cluster into a .marker-cluster <div> - either is a real pin).
    await expect(page.locator('.leaflet-marker-icon, .marker-cluster').first()).toBeVisible({ timeout: 15000 })

    // The seeded group is in the list panel.
    await expect(page.getByTestId(`group-row-link-${groupId}`)).toBeVisible()
    await expect(page.getByTestId(`group-row-link-${groupId}`)).toHaveText(TAG_TEST_GROUP)

    // The Photon place-search box (leaflet-control-geocoder, uncollapsed).
    await expect(page.locator('.leaflet-control-geocoder')).toBeVisible()
    await expect(page.locator('.leaflet-control-geocoder-form input')).toBeVisible()
    await expect(page.locator('.leaflet-control-geocoder-form input')).toHaveAttribute(
      'placeholder',
      'Search for a place...'
    )

    // The list's own name filter.
    await expect(page.getByTestId('group-map-search')).toBeVisible()
  })

  test('the search box filters the list down to matching groups', async ({ page }) => {
    const groupId = await getGroupIdByName(page, TAG_TEST_GROUP)

    await login(page, USERS.admin)
    await page.goto('/group/map')
    await page.waitForResponse(
      (resp) => new URL(resp.url()).pathname === '/api/v2/groups/names' && resp.request().method() === 'GET'
    )
    await expect(page.getByTestId(`group-row-link-${groupId}`)).toBeVisible()

    await page.getByTestId('group-map-search').fill('Tag Test Group')

    await expect(page.getByTestId(`group-row-link-${groupId}`)).toBeVisible()
    // Every remaining row's link text should contain the search term.
    const rowLinks = page.locator('[data-testid^="group-row-link-"]')
    const count = await rowLinks.count()
    for (let i = 0; i < count; i++) {
      await expect(rowLinks.nth(i)).toContainText('Tag Test Group')
    }
  })
})
