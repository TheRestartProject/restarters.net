// Browser error reporting to Sentry.  Kept free of Nuxt and Sentry imports so it
// can be unit tested; app/plugins/00.sentry.client.ts wires it up.

// Errors from browser extensions and injected third-party scripts, which the old
// Vue frontend already ignored.
export const IGNORE_ERRORS = [
  'ResizeObserver loop limit exceeded',
  'ResizeObserver loop completed with undelivered notifications',
  'top.GLOBALS',
  'originalCreateNotification',
  'canvas.contentDocument',
  'MyApp_RemoveAllHighlights',
  'http://tt.epicplay.com',
  "Can't find variable: ZiteReader",
  'jigsaw is not defined',
  'ComboSearch is not defined',
  'http://loading.retry.widdit.com/',
  'atomicFindClose',
  'fb_xd_fragment',
  'bmi_SafeAddOnload',
  'EBCallBackMessageReceived',
  'conduitPage',
]

// Sentry options, or null when this deployment shouldn't report.  Only deployed
// apps set an environment, so local development, CI and tests never report.
// hasConsent is called for each event: as on the old frontend, only visitors who
// accepted analytics cookies are reported.
export function sentryOptions({ sentryDsn, sentryEnvironment } = {}, hasConsent = () => false) {
  if (!sentryDsn || !sentryEnvironment) return null

  return {
    dsn: sentryDsn,
    environment: sentryEnvironment,
    ignoreErrors: IGNORE_ERRORS,
    beforeSend: (event) => (hasConsent() ? event : null),
  }
}

const OUR_HOSTS = ['restarters.net', 'therestartproject.org']

function isOurs(host, currentHost) {
  return [...OUR_HOSTS, currentHost].some((ours) => ours && (host === ours || host.endsWith(`.${ours}`)))
}

// Generalise a path so that each broken link is one Sentry issue however many
// ids or builds it covers.  Matches app/Http/Middleware/ReportBrokenLinksToSentry.php.
export function pathShape(path) {
  return path
    .replace(/[?#].*$/, '')
    .replace(/-[A-Za-z0-9_]{6,}\.(js|css)$/, '-HASH.$1')
    .replace(/\/[A-Za-z0-9_-]{16,}(\.\w+)?$/, '/HASH$1')
    .replace(/\d+/g, 'N')
}

// A page that doesn't exist is only a bug when one of our pages linked to it:
// either an in-app link (previousPath is the route we came from), or a link on
// another of our sites (the document referrer, on a fresh page load).  Anything
// else is a crawler, a scanner or an old bookmark.  Returns the referring host
// and the missing path's shape, or null.
export function brokenLink({ path, previousPath, referrer, location }) {
  if (previousPath) {
    if (previousPath === path) return null
    return [location.host, pathShape(path)]
  }

  let from
  try {
    from = new URL(referrer)
  } catch {
    return null
  }

  // All our sites are https only, so only a faked referrer is http.
  if (from.protocol !== 'https:' || !isOurs(from.hostname, location.hostname)) return null
  if (from.host === location.host && from.pathname === path.replace(/[?#].*$/, '')) return null

  return [from.hostname, pathShape(path)]
}
