// Visual parity capture harness.
// =================================
// Drives BOTH local dev systems - the new Nuxt SPA and the old legacy Blade app
// (the parity target) - through the SAME page list at the SAME viewports,
// saving matched screenshot pairs for side-by-side diffing:
//
//   parity-shots/<viewport>/<slug>__new.png
//   parity-shots/<viewport>/<slug>__old.png
//
// This exists because page-by-page eyeballing repeatedly missed real
// differences (wrong auth gating, wrong layout, wrong styling). Rendered-page
// pairs are the only way to catch "the styles are completely wrong".
//
// Run: task parity:capture
// NOT part of the CI suite: it lives outside client/e2e and uses its own
// config (playwright.parity.config.js), so `task docker:test:playwright:client`
// never picks it up.
//
// Both targets are LOCAL DEV instances on the SAME seeded database, so no
// production credentials are used and mutating flows can be exercised safely.
// ESM: client/package.json is "type": "module" (same as client/e2e/*.test.js).
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, resolve } from 'node:path'

import { test } from '@playwright/test'

const OUT = process.env.PARITY_OUT || 'parity-shots'

// Ids for the detail pages, published by parity-fixtures.php (which
// `task parity:capture` runs before this spec). They MUST NOT be hardcoded:
// every `migrate:fresh --seed` renumbers users, networks and events, and a
// stale id renders as a 404/permission-denied page on BOTH systems - which
// then gets "compared" as a matching pair and reported as parity. That is
// exactly how the event view page stayed unverified for the whole migration.
// Resolved relative to this file, not cwd, so it works whichever directory
// playwright is invoked from.
const FIXTURES = (() => {
  const path = resolve(dirname(fileURLToPath(import.meta.url)), '../../parity-fixtures.json')
  try {
    return JSON.parse(readFileSync(path, 'utf8'))
  } catch (e) {
    throw new Error(
      `Could not read ${path}: ${e.message}\n` +
        'Run the fixtures first (task parity:capture does this for you):\n' +
        '  task docker:run:bash -- "php artisan tinker parity-fixtures.php"'
    )
  }
})()

const VIEWPORTS = [
  { name: 'desktop', width: 1440, height: 900 },
  { name: 'mobile', width: 390, height: 844 },
]

// Paths that exist on BOTH systems with the same URL shape. Detail pages
// (group/view/{id} etc.) need per-system ids and are handled separately later.
const PAGES = [
  { slug: '01-landing', path: '/', auth: false },
  { slug: '02-login', path: '/login', auth: false },
  { slug: '03-register', path: '/user/register', auth: false },
  { slug: '04-dashboard', path: '/dashboard', auth: true },
  { slug: '05-fixometer', path: '/fixometer', auth: true },
  { slug: '06-groups-all', path: '/group/all', auth: true },
  { slug: '07-events-all', path: '/party/all', auth: true },
  { slug: '08-groups-nearby', path: '/group/nearby', auth: true },
  // Detail pages: same URL shape on both systems (legacy /group/view/{id} =
  // GroupController::view; Nuxt /group/view/{id}). Group id 1 = the seeded
  // "Tag Test Group" (playwright:seed-data). These were the pages the earlier
  // 8-page harness never covered - exactly where visual diffs were missed.
  { slug: '09-group-view', path: '/group/view/1', auth: true },
  // Everything below covers the remaining parity-v2 clusters (group forms,
  // networks, profile, admin CRUD, static). Each path was probed against the
  // legacy instance first and returns 200/302 there - paths that 404 on
  // legacy (/notifications, /user/consent, /forbidden, /group/map) are
  // Nuxt-only or live at a different legacy URL, so they are NOT comparable
  // here and are tracked in the findings docs instead.
  { slug: '10-group-create', path: '/group/create', auth: true },
  { slug: '11-networks', path: '/networks', auth: true },
  { slug: '12-profile', path: '/profile', auth: true },
  // NB 07-events-all, 13-events-past and 14-device-search have NO usable
  // `old` reference: develop's routes/web.php:335,370,371 point at
  // PartyController::allUpcoming / ::allPast / DeviceController::search,
  // none of which exist on develop - so those three pages 500 for any
  // logged-in user there and the `__old` shot is just the error page.
  // The Nuxt versions render correctly, i.e. this branch FIXES them.
  // Compare them against develop's source, not against these screenshots.
  { slug: '13-events-past', path: '/party/all-past', auth: true },
  { slug: '14-device-search', path: '/device/search', auth: true },
  { slug: '15-event-create', path: '/party/create', auth: true },
  { slug: '16-admin-categories', path: '/category', auth: true },
  { slug: '17-admin-roles', path: '/role', auth: true },
  { slug: '18-admin-skills', path: '/skills', auth: true },
  { slug: '19-admin-tags', path: '/tags', auth: true },
  { slug: '20-admin-users', path: '/user/all', auth: true },
  { slug: '21-admin-brands', path: '/brands', auth: true },
  { slug: '22-cookie-policy', path: '/about/cookie-policy', auth: false },
  { slug: '23-recover', path: '/user/recover', auth: false },
  // --- pages 24-32: the coverage gap -------------------------------------
  // Everything above was added ad hoc, and an audit of `client/app/pages`
  // against this list found 17 routes that had NEVER been render-compared -
  // including the event view page, which a manual check then showed was not
  // at parity (its actions were not in a dropdown). Verifying a subset and
  // reporting it as the whole is how that was missed, so these close the gap
  // for every route that exists on BOTH systems at the same URL shape.
  //
  // Not listed, because develop has no equivalent at the same path and a
  // capture would only compare our page against develop's 404:
  //   /notifications, /user/consent, /group/map   - Nuxt-only
  //   /forbidden                                  - develop uses /user/forbidden
  //   /group/invite/[code], /party/invite/[code]  - need a live invite token
  // These are tracked in the findings docs instead.
  { slug: '24-group-index', path: '/group', auth: true },
  { slug: '25-event-index', path: '/party', auth: true },
  // NB develop's /party/view/{id} is a PUBLIC route that abort(404)s when
  // Fixometer::userHasViewPartyPermission fails, rather than redirecting to
  // login like the auth-gated routes do. So a logged-out 404 here is correct
  // behaviour, not a missing event - jane hosts the group and attends the
  // event, so it renders for her.
  { slug: '26-event-view', path: `/party/view/${FIXTURES.event}`, auth: true },
  { slug: '27-event-edit', path: `/party/edit/${FIXTURES.event}`, auth: true },
  { slug: '28-group-edit', path: `/group/edit/${FIXTURES.group}`, auth: true },
  { slug: '29-network-view', path: `/networks/${FIXTURES.network}`, auth: true },
  { slug: '30-profile-view', path: `/profile/${FIXTURES.user}`, auth: true },
  { slug: '31-profile-edit', path: `/profile/edit/${FIXTURES.user}`, auth: true },
  { slug: '32-event-duplicate', path: `/party/duplicate/${FIXTURES.event}`, auth: true },
]

// Both systems are LOCAL DEV instances sharing the same seeded database:
//   new = the Nuxt SPA under development (restarters_client)
//   old = the legacy Blade app from origin/develop (restarters_legacy_nginx,
//         host port 8005; see docs/nuxt-migration/findings/parity-audit.md)
// Comparing dev-to-dev (rather than against production) means:
//   - no production credentials are needed anywhere,
//   - both sides show IDENTICAL data, so every difference is a real difference
//     rather than production-vs-seed-data noise,
//   - and non-read-only flows (create/edit wizards) can be exercised safely.
const SYSTEMS = [
  {
    name: 'new',
    base: process.env.PARITY_NEW_BASE || 'http://restarters_client:3000',
    email: process.env.PARITY_EMAIL || 'jane@bloggs.net',
    password: process.env.PARITY_PASSWORD || 'passw0rd',
    async login(page, base, email, password) {
      await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' })
      await page.getByTestId('login-email').fill(email)
      await page.getByTestId('login-password').fill(password)
      await page.getByTestId('login-submit').click()
      await page.waitForURL((u) => !/\/login/.test(u.pathname), { timeout: 30000 })
    },
  },
  {
    name: 'old',
    base: process.env.PARITY_OLD_BASE || 'http://restarters_legacy_nginx',
    email: process.env.PARITY_EMAIL || 'jane@bloggs.net',
    password: process.env.PARITY_PASSWORD || 'passw0rd',
    async login(page, base, email, password) {
      await page.goto(`${base}/login`, { waitUntil: 'domcontentloaded' })
      // NB: my_name / my_time are bot honeypots - never fill my_name.
      await page.fill('input[name="email"]', email)
      await page.fill('input[name="password"]', password)
      await page.click('#login-form-submit')
      await page.waitForURL((u) => !/\/login/.test(u.pathname), { timeout: 30000 })
    },
  },
]

// Suppress the cookie notice BEFORE the page's own scripts run, rather than
// clicking it away afterwards. Clicking is unreliable: the banner is fixed to
// the viewport, and at mobile widths it covers a tall strip of the page, so a
// missed or late click silently obscures exactly the region under comparison.
// That cost a real verification - the groups-list row/header restack could not
// be checked at 390px because the banner sat over it in both captures.
//
//   new  = Nuxt reads localStorage 'restarters_cookie_consent' === 'accepted'
//          (client/app/composables/useCookieConsent.js)
//   old  = legacy gates gdprCookieNotice() on `!window.noCookieNotice`
//          (origin/develop resources/js/app.js:334)
//
// addInitScript runs on every navigation in the context, so this survives the
// page.goto() loop without needing to be re-applied per page.
async function suppressCookieNotice(page) {
  await page.addInitScript(() => {
    try {
      window.noCookieNotice = true
      localStorage.setItem('restarters_cookie_consent', 'accepted')
    } catch {
      // storage unavailable before navigation - the click fallback still runs
    }
  })
}

// Fallback for anything the pre-seed misses (e.g. a banner variant keyed on
// something else). Kept deliberately: belt-and-braces, since a banner in a
// screenshot invalidates that page's comparison rather than merely looking untidy.
async function dismissCookieBanner(page) {
  for (const sel of ['#cookie-ok', 'button:has-text("OK")', '.cookie-settings-ok', '#cookiescript_accept']) {
    try {
      const el = page.locator(sel).first()
      if (await el.isVisible({ timeout: 800 })) {
        await el.click({ timeout: 2000 })
        await page.waitForTimeout(300)
        return
      }
    } catch {
      // not present - fine
    }
  }
}

// The Nuxt SPA revived the (dead-in-develop) post-registration onboarding
// modal, so it covers the dashboard for low-login users. Dismiss it before
// shooting so the underlying page can be compared against develop, which
// never shows it (see docs/nuxt-migration/findings/parity-v2).
async function dismissOnboardingModal(page) {
  try {
    const close = page.getByTestId('onboarding-close')
    if (await close.isVisible({ timeout: 800 })) {
      await close.click({ timeout: 2000 })
      await page.waitForTimeout(400)
    }
  } catch {
    // not present - fine
  }
}

// KNOWN CAPTURE ARTIFACT - do not chase these as parity diffs:
// the legacy nav's message/notification counters are fed by Discourse. When
// the restarters_discourse container isn't running (the usual case for a
// parity run) those counters render as "--" placeholders in every `__old`
// shot and the legacy request blocks ~5s on a cURL timeout first. The Nuxt
// side degrades to "0" instead. That difference is an environment artifact,
// not a design regression.
async function settle(page) {
  await page.waitForLoadState('networkidle').catch(() => {})
  // Let async widgets/stat counters paint.
  await page.waitForTimeout(1200)
  await dismissOnboardingModal(page)
}

async function shoot(page, viewport, slug, system) {
  await settle(page)
  await page.screenshot({
    path: `${OUT}/${viewport}/${slug}__${system}.png`,
    fullPage: true,
  })
}

for (const system of SYSTEMS) {
  for (const vp of VIEWPORTS) {
    test.describe(`${system.name} @ ${vp.name}`, () => {
      test.use({ viewport: { width: vp.width, height: vp.height } })

      test(`logged-out pages`, async ({ page }) => {
        await suppressCookieNotice(page)
        for (const p of PAGES.filter((x) => !x.auth)) {
          await page.goto(`${system.base}${p.path}`, { waitUntil: 'domcontentloaded' })
          await dismissCookieBanner(page)
          await shoot(page, vp.name, `${p.slug}--loggedout`, system.name)
        }
      })

      test(`logged-in pages`, async ({ page }) => {
        test.skip(
          !system.email || !system.password,
          `No credentials for ${system.name} (set PARITY_${system.name.toUpperCase()}_EMAIL/PASSWORD)`
        )
        await suppressCookieNotice(page)
        await system.login(page, system.base, system.email, system.password)
        await dismissCookieBanner(page)

        for (const p of PAGES.filter((x) => x.auth)) {
          await page.goto(`${system.base}${p.path}`, { waitUntil: 'domcontentloaded' })
          await dismissCookieBanner(page)
          await shoot(page, vp.name, p.slug, system.name)
        }
      })
    })
  }
}
