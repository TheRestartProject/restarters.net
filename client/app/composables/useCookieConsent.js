import { ref } from 'vue'

// Records whether the visitor has dismissed/accepted the cookie notice, in
// localStorage (SSR-safe: guarded like useColumnPreferences). The legacy site
// used the gdpr-cookie-notice library backed by a cookie; the SPA currently
// loads no analytics/marketing scripts, so this is a consent RECORD + the
// banner UI - when analytics is added later, gate it on `accepted`.
const STORAGE_KEY = 'restarters_cookie_consent'

const accepted = ref(readInitial())

function readInitial() {
  if (typeof localStorage === 'undefined') {
    return true // never show the banner during SSR/prerender
  }
  try {
    return localStorage.getItem(STORAGE_KEY) === 'accepted'
  } catch {
    return false
  }
}

export function useCookieConsent() {
  function accept() {
    accepted.value = true
    if (typeof localStorage !== 'undefined') {
      try {
        localStorage.setItem(STORAGE_KEY, 'accepted')
      } catch {
        // storage unavailable (private mode) - banner just won't persist
      }
    }
  }

  return { accepted, accept }
}
