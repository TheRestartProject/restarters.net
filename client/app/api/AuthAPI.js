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

  resetPassword({ recovery, password, password_confirmation }) {
    return this.$post('/api/v2/auth/password/reset', {
      recovery,
      password,
      password_confirmation,
    })
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
