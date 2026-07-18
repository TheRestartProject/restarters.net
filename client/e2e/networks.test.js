import { test, expect, API_URL } from './fixtures'
import { USERS, login, createGroup } from './utils'

// Coverage for the network management flows that the migration initially
// shipped broken/missing (no e2e exercised them):
//  - "Add groups" (POST /api/v2/networks/{id}/groups) was never implemented
//    server-side, so the modal 500'd/404'd in production.
//  - The network-logo upload had no API endpoint or UI at all.
// These tests hit the real backend so a missing endpoint / unwired UI fails.

const NETWORK_NAME = 'Test London'

async function getNetworkId(page) {
  const response = await page.request.get(`${API_URL}/api/v2/networks`)
  const body = await response.json()
  const networks = body.data || body
  const network = networks.find((n) => n.name === NETWORK_NAME)
  if (!network) throw new Error(`Network "${NETWORK_NAME}" not found`)
  return network.id
}

test.describe('network management', () => {
  test('Admin can associate a group with a network via the Add groups modal', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    // A fresh group is not yet in the network, so it is an association candidate.
    const groupId = await createGroup(page)
    const networkId = await getNetworkId(page)

    await page.goto(`/networks/${networkId}`, { waitUntil: 'domcontentloaded' })

    await page.getByTestId('network-show-add-groups').click()
    await expect(page.getByTestId('network-associate-groups-modal')).toBeVisible({ timeout: 10000 })

    await page.getByTestId('network-associate-groups-select').selectOption(String(groupId))

    const [response] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/v2/networks/${networkId}/groups`) && resp.request().method() === 'POST',
      ),
      page.getByTestId('network-associate-groups-submit').click(),
    ])
    expect(response.status()).toBe(200)

    // The group is now associated: it appears in the network's groups list.
    const check = await page.request.get(`${API_URL}/api/v2/networks/${networkId}/groups`)
    const body = await check.json()
    const groups = body.data || body
    expect(groups.some((g) => g.id === groupId)).toBe(true)
  })

  test('Manager sees the network logo upload control', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)
    const networkId = await getNetworkId(page)

    await page.goto(`/networks/${networkId}`, { waitUntil: 'domcontentloaded' })

    // The logo-management section (with its tus uploader) renders for a
    // manager — this is the UI that was entirely absent before.
    await expect(page.getByTestId('network-logo-manage')).toBeVisible({ timeout: 10000 })
    await expect(page.getByTestId('network-logo-upload')).toBeVisible()
  })
})
