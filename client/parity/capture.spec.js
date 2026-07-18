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
import { test } from '@playwright/test'

const OUT = process.env.PARITY_OUT || 'parity-shots'

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

// The live site shows a cookie banner that covers page content and would
// otherwise poison every screenshot.
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

async function settle(page) {
  await page.waitForLoadState('networkidle').catch(() => {})
  // Let async widgets/stat counters paint.
  await page.waitForTimeout(1200)
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
