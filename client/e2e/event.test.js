import { test, expect } from './fixtures'
import { USERS, login, logout, createGroup, createEvent, approveEvent, approveGroup } from './utils'

// Ports tests/Integration/event.test.js's three flows to the Nuxt client:
// create a future event, create a past event, and open the invite-
// volunteers modal from the event view page. See client/e2e/utils.js for
// the shared createGroup/createEvent/approveEvent helpers this file uses.
//
// Two behavioural differences from the legacy suite, both because the Nuxt
// components genuinely work differently (not test bugs):
//  - There's no "EVENT ACTIONS" dropdown any more - EventForm.vue/
//    party/view/[id].vue render a single "Invite Volunteers" button
//    directly (data-testid=event-view-invite).
//  - The invite button is gated on `canedit && upcoming && approved`, not
//    on the viewer attending the event (api-contracts-phase-c.md C1d gates
//    the invites endpoint to host/NC/admin) - so there's no need to join
//    the event first, unlike the legacy flow's explicit /party/join/ step.
//  - EventInviteModal.vue's "select group members" multiselect was
//    deliberately dropped (docs/nuxt-migration/api-gaps.md Phase C) in
//    favour of manual email entry against the same invites endpoint - the
//    modal + email textarea are the Nuxt equivalent asserted below.

test.describe('events', () => {
  test('Can create future event', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const groupId = await createGroup(page)
    const eventId = await createEvent(page, groupId, { past: false })
    await approveEvent(page, eventId)
  })

  test('Can create past event', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const groupId = await createGroup(page)
    const eventId = await createEvent(page, groupId, { past: true })
    await approveEvent(page, eventId)
  })

  test('Invite volunteers modal opens from the event view page', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const groupId = await createGroup(page)
    const eventId = await createEvent(page, groupId, { past: false })
    await approveEvent(page, eventId)

    await page.goto(`/party/view/${eventId}`, { waitUntil: 'domcontentloaded' })

    const inviteButton = page.getByTestId('event-view-invite')
    await expect(inviteButton).toBeVisible({ timeout: 10000 })
    await inviteButton.click()

    const modal = page.getByTestId('event-invite-modal')
    await expect(modal).toBeVisible({ timeout: 10000 })
    // The Nuxt equivalent of the legacy multiselect: manual email entry
    // against the same POST /api/v2/events/{id}/invites endpoint.
    await expect(page.getByTestId('event-invite-emails')).toBeVisible()
    await expect(page.getByTestId('event-invite-submit')).toBeVisible()
  })

  test('Host can request reviews on a finished event', async ({ page }) => {
    test.slow()
    await login(page, USERS.admin)

    const groupId = await createGroup(page)
    const eventId = await createEvent(page, groupId, { past: true })
    await approveEvent(page, eventId)

    await page.goto(`/party/view/${eventId}`, { waitUntil: 'domcontentloaded' })

    // The button only shows for a host viewing a finished event — it was a
    // dropped feature (the old event-request-review modal), so its presence
    // AND that it hits the real POST endpoint are both under test.
    const button = page.getByTestId('event-view-request-review')
    await expect(button).toBeVisible({ timeout: 10000 })

    const [response] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/v2/events/${eventId}/request-review`) && resp.request().method() === 'POST',
      ),
      button.click(),
    ])
    expect(response.status()).toBe(200)
  })

  test('Following the hosting group from the event page joins it', async ({ page }) => {
    test.slow()
    // Admin creates the group + event (admin becomes a host/member).
    await login(page, USERS.admin)
    const groupId = await createGroup(page)
    // Approve the group so its event is publicly viewable: GET
    // /api/v2/events/{id} hides events on unapproved groups from non-members
    // (the moderation gate), which is exactly who follows a group from here.
    await approveGroup(page, groupId)
    const eventId = await createEvent(page, groupId, { past: false })
    await approveEvent(page, eventId)
    await logout(page)

    // A different user who is NOT in the group sees the "follow group" button.
    await login(page, USERS.host)
    await page.goto(`/party/view/${eventId}`, { waitUntil: 'domcontentloaded' })

    const followButton = page.getByTestId('event-view-follow-group')
    await expect(followButton).toBeVisible({ timeout: 10000 })

    // This used to be a NuxtLink to the dead /group/join/{id} page; it must
    // now call the join API and succeed.
    const [response] = await Promise.all([
      page.waitForResponse(
        (resp) => resp.url().includes(`/api/v2/groups/${groupId}/members/me`) && resp.request().method() === 'POST',
      ),
      followButton.click(),
    ])
    expect(response.status()).toBe(200)
  })
})
