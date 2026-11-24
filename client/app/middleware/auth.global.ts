import { useAuthStore } from '../stores/auth.js'
import { isTalkSignOn } from '../utils/talkSignOn.js'

/**
 * Reads definePageMeta({ auth: true, role: '...' }) on the target route.
 * Unauthenticated -> /login?redirect=... . Wrong role -> /forbidden.
 * (design.md §4.4; the API is the real enforcer via 401/403 JSON - this is
 * UX-level gating only.)
 *
 * Role comparison mirrors app/User.php::hasRole() (see composables/useAuth.js
 * for the full rationale): 'Root' satisfies every role check, everything
 * else is an exact match against the session's `user.role_name` - NOT
 * `user.role` (that field, per the GET /api/v2/session contract, is a
 * separate identifier alongside role_name; role_name is the human role
 * name pages declare in `role: '...'`).
 */
export default defineNuxtRouteMiddleware((to) => {
  const requiresAuth = to.meta.auth === true
  const requiredRole = to.meta.role

  // Guest-only pages (definePageMeta({ guest: true })): the legacy app gated
  // these with Laravel's `guest` middleware - LoginController's
  // `$this->middleware('guest')->except(['index','logout'])` (the named `index`
  // method does not exist on that controller, so the login form itself WAS
  // gated) and RegisterController's unconditional `$this->middleware('guest')`.
  // A logged-in user landing on them was bounced away rather than shown a login
  // or registration form again.
  if (to.meta.guest === true) {
    const guestStore = useAuthStore()

    // Except the login page on the way back to Talk's sign-on, which sends
    // a logged-in user on through the SSO bridge (pages/login.vue).
    if (guestStore.loggedIn && !isTalkSignOn(to.query?.redirect)) {
      return navigateTo('/dashboard')
    }

    return
  }

  if (!requiresAuth) {
    return
  }

  const authStore = useAuthStore()

  if (!authStore.loggedIn) {
    return navigateTo(`/login?redirect=${encodeURIComponent(to.fullPath)}`)
  }

  if (requiredRole) {
    const roleName = authStore.user?.role_name
    const satisfies = roleName === 'Root' || roleName === requiredRole

    if (!satisfies) {
      return navigateTo('/forbidden')
    }
  }

  // GDPR consent gate (design §4.2): users with outstanding consents can
  // read public pages but get routed to the completion form before using
  // authenticated pages — the API enforces the same server-side with 403
  // consent_required. Strict === false: while the session user is still
  // loading, consent is undefined and we must not redirect.
  if (to.path !== '/user/consent' && authStore.user?.consent?.given === false) {
    return navigateTo(`/user/consent?redirect=${encodeURIComponent(to.fullPath)}`)
  }
})
