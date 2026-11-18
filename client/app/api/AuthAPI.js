import BaseAPI from './BaseAPI.js'

/**
 * Login/register/logout + password reset + the invite-claim family
 * (design.md §4.2).
 */
export default class AuthAPI extends BaseAPI {
  login({ email, password, invite_code, invite_type, invite_hash }) {
    return this.$post('/api/v2/auth/login', {
      email,
      password,
      invite_code,
      invite_type,
      invite_hash,
    })
  }

  register(payload) {
    return this.$post('/api/v2/auth/register', payload)
  }

  logout() {
    return this.$post('/api/v2/auth/logout')
  }

  forgotPassword({ email }) {
    return this.$post('/api/v2/auth/password/forgot', { email })
  }

  // Records outstanding data consents (plus profile basics) for the current
  // user - the completion flow VerifyUserConsentApi gates mutations on.
  consent(payload) {
    return this.$post('/api/v2/auth/consent', payload)
  }

  resetPassword({ recovery, password, password_confirmation }) {
    return this.$post('/api/v2/auth/password/reset', {
      recovery,
      password,
      password_confirmation,
    })
  }

  // Validates a recovery token before the reset form renders, and returns
  // the account email so the user can confirm which account they're
  // resetting (legacy reset-password.blade.php's $valid_code / fp_email).
  recoveryInfo(token) {
    return this.$get('/api/v2/auth/password/recovery/' + token)
  }

  emailAvailable(email) {
    return this.$get('/api/v2/auth/email-available', { email })
  }

  ssoTicket() {
    return this.$post('/api/v2/auth/sso-ticket')
  }

  claimInvite({ invite_code, invite_type, invite_hash }) {
    return this.$post('/api/v2/invites/claim', {
      invite_code,
      invite_type,
      invite_hash,
    })
  }
}
