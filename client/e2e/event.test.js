import { test, expect } from './fixtures'
import { USERS, login, createGroup, createEvent, approveEvent } from './utils'

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
})
