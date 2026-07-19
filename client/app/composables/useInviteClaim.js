import { useAuthStore } from '../stores/auth.js'
import { useToastStore } from '../stores/toast.js'

/**
 * Shared logic behind pages/group/invite/[code].vue and
 * pages/party/invite/[code].vue (design.md §5's shareable-link flow):
 * logged in -> POST /api/v2/invites/claim then redirect to the
 * group/event page with a success toast; logged out -> redirect to /login
 * carrying invite_code/invite_type/redirect so the code survives the
 * login-or-register round trip.
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
    return navigateTo(`/login?${query.toString()}`)
  }

  const { $api } = useNuxtApp()
  const toastStore = useToastStore()

  try {
    const { data } = await $api.auth.claimInvite({
      invite_code: code,
      invite_type: inviteType,
    })

    toastStore.success(data.already_member ? alreadyMemberMessage : joinedMessage)
    return navigateTo(`${viewPathPrefix}${data.id}`)
  } catch (err) {
    toastStore.error(err)
    return navigateTo('/dashboard')
  }
}
