import { computed, ref } from 'vue'

// Per-category cookie consent, matching develop's gdpr-cookie-notice.
//
// develop stores a JSON cookie (public/js/gdpr-cookie-notice.js's
// acceptCookies) holding {date, necessary, performace, analytics, marketing},
// defaulting marketing to false and everything else to true, and the rest of
// the legacy site reads it back: header.blade.php computes
// window.restarters.analyticsCookieEnabled from
// Cookies.getJSON('gdprcookienotice'), which app.js then uses to decide
// whether to load Matomo and Sentry.
//
// This previously recorded a single all-or-nothing `accepted` boolean in
// localStorage, so a visitor had no way to decline analytics while keeping the
// site working - "Cookie settings" simply navigated to the policy article.
// That is a consent gap, not a styling one.
//
// A cookie, not localStorage, and deliberately develop's cookie name and key
// spellings - including "performace", which is misspelled in develop. Renaming
// it would silently discard the stored preference of every visitor who set one
// on the legacy site, and would not be read by the retained Blade widgets.
const COOKIE = 'gdprcookienotice'

export const COOKIE_CATEGORIES = ['performace', 'analytics', 'marketing']

// develop's defaults when the visitor clicks OK rather than saving choices.
const ACCEPT_ALL = { necessary: true, performace: true, analytics: true, marketing: false }

function readCookie() {
  if (typeof document === 'undefined') return null

  const match = document.cookie.split('; ').find((row) => row.startsWith(`${COOKIE}=`))
  if (!match) return null

  try {
    return JSON.parse(decodeURIComponent(match.slice(COOKIE.length + 1)))
  } catch {
    return null
  }
}

function writeCookie(value) {
  if (typeof document === 'undefined') return

  // A year, matching develop's config.expiration of 365 days.
  const expires = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toUTCString()
  document.cookie =
    `${COOKIE}=${encodeURIComponent(JSON.stringify(value))}; expires=${expires}; path=/; SameSite=Lax`
}

const stored = ref(readCookie())

export function useCookieConsent() {
  // Never show the banner during SSR/prerender - there is no cookie to read.
  const decided = computed(() => typeof document === 'undefined' || stored.value !== null)

  const choices = computed(() => ({ ...ACCEPT_ALL, ...(stored.value || {}) }))

  function persist(value) {
    const next = { ...value, necessary: true, date: new Date().toISOString() }
    writeCookie(next)
    stored.value = next
  }

  // The banner's OK button: develop's acceptCookies(false).
  function acceptAll() {
    persist(ACCEPT_ALL)
  }

  // The settings modal's Save: develop's acceptCookies(true), which reads each
  // category's switch.
  function save(selection) {
    persist({ ...ACCEPT_ALL, ...selection })
  }

  function reopen() {
    stored.value = null
  }

  // What the rest of the app gates analytics on, replacing develop's
  // window.restarters.analyticsCookieEnabled.
  const analyticsEnabled = computed(() => Boolean(stored.value && stored.value.analytics))

  return { decided, choices, acceptAll, save, reopen, analyticsEnabled }
}
