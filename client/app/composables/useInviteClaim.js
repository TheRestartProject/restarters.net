import { useAuthStore } from '../stores/auth.js'
import { useToastStore } from '../stores/toast.js'

/**
 * Shared logic behind pages/group/invite/[code].vue and
 * pages/party/invite/[code].vue (design.md §5's shareable-link flow):
 * logged in -> POST /api/v2/invites/claim then redirect to the group/event
 * page with a success toast; logged out -> redirect to /user/register.
 *
 * REGISTER, not login: GroupController::confirmCodeInvite and
 * PartyController::confirmCodeInvite both redirect a logged-out visitor to
 * /user/register with the auth.login_before_using_shareable_link banner ("To
 * complete your invitation please create an account below, or if you already
 * have an account login here"). A shareable link is normally handed to
 * someone who does not have an account yet, so sending them to the sign-in
 * form put the wrong step first. The banner still offers the login route.
 *
 * invite_code/invite_type/redirect ride along so the code survives the round
 * trip either way.
 *
 * Pulled out of the page components so it can be unit-tested directly
 * without mounting an async-setup page through Suspense (design.md §8).
 */
export async function claimInvite({
  code,
  inviteType,
  viewPathPrefix,
  joinedMessage,
  alreadyMemberMessage,
  currentPath,
}) {
  const authStore = useAuthStore()

  if (!authStore.loggedIn) {
    const query = new URLSearchParams({
      invite_code: code,
      invite_type: inviteType,
      redirect: currentPath,
    })
    return navigateTo(`/user/register?${query.toString()}`)
  }

  const { $api } = useNuxtApp()
  const toastStore = useToastStore()

  try {
    const { data } = await $api.auth.claimInvite({
      invite_code: code,
      invite_type: inviteType,
    })

    // develop's message names the group/event and links to it
    // (groups.you_have_joined / events.you_have_joined, flashed by
    // AcceptUserInvites). We used a generic unlinked string, and for groups
    // reused groups.invite_confirmed - develop's text for the unrelated
    // email-hash accept-invite flow.
    const message = data.already_member ? alreadyMemberMessage : joinedMessage
    toastStore.success(typeof message === 'function' ? message(data) : message)
    return navigateTo(`${viewPathPrefix}${data.id}`)
  } catch (err) {
    toastStore.error(err)
    return navigateTo('/dashboard')
  }
}
