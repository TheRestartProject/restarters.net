import fs from 'node:fs'
import path from 'node:path'
import { describe, expect, it } from 'vitest'

/**
 * Pins the auth gate of EVERY page.
 *
 * Why this exists: /fixometer and /device/search shipped with no auth gate at
 * all, even though the legacy app served both from inside
 * `Route::middleware('auth', 'verifyUserConsent', 'ensureAPIToken')`
 * (routes/web.php at 07e6abd7cc^) and the live site still 302s anonymous
 * visitors to /login. Nothing in the suite pinned the auth model, so a page
 * that silently became public looked completely fine in isolation - visual
 * checks cannot detect a wrong auth gate.
 *
 * This test derives each page's declared gate from its definePageMeta and
 * compares it against the map below. It fails if a gate CHANGES or if a page is
 * ADDED without an entry, forcing the auth decision to be explicit and reviewed
 * rather than defaulting to "public".
 *
 * Gate vocabulary (mirrors middleware/auth.global.ts):
 *   public            - no gate
 *   auth              - definePageMeta({ auth: true })
 *   auth+role:<Role>  - definePageMeta({ auth: true, role: '<Role>' })
 *   guest             - definePageMeta({ guest: true }); logged-in users bounced
 *
 * When adding a page, verify the intended gate against the legacy route's
 * middleware group before adding it here - do not simply paste in whatever the
 * page currently declares.
 */

// Resolved from the vitest working directory (client/) - import.meta.url is not
// a file: URL under the configured test environment.
const PAGES_DIR = path.resolve(process.cwd(), 'app/pages')

// Verified against the legacy routes/web.php middleware groups at 07e6abd7cc^.
const EXPECTED_GATES = {
  '/': 'public',
  '/about/cookie-policy': 'public',
  '/brands': 'auth+role:Administrator',
  // category sits inside the legacy auth group (line 321) and CategoryController
  // enforces no role, so auth-without-role is correct here.
  '/category': 'auth',
  '/dashboard': 'auth',
  '/device/search': 'auth',
  '/fixometer': 'auth',
  '/forbidden': 'public',
  '/group': 'auth',
  '/group/all': 'auth',
  '/group/create': 'auth',
  '/group/edit/{id}': 'auth',
  // Invite links must open for logged-out recipients.
  '/group/invite/{code}': 'public',
  '/group/map': 'auth',
  '/group/nearby': 'auth',
  '/group/view/{id}': 'auth',
  '/login': 'guest',
  '/networks': 'auth',
  '/networks/{id}': 'auth',
  // networks/edit.blade.php sits behind develop's auth+role middleware; the
  // page itself also gates on Administrator-or-coordinator-of-this-network.
  '/networks/{id}/edit': 'auth',
  '/notifications': 'auth',
  '/party': 'auth',
  '/party/all': 'auth',
  '/party/all-past': 'auth',
  '/party/create/{group_id?}': 'auth',
  '/party/duplicate/{id}': 'auth',
  '/party/edit/{id}': 'auth',
  '/party/invite/{code}': 'public',
  // Legacy served /party/view/{id} at line 100, OUTSIDE the auth group - public.
  '/party/view/{id}': 'public',
  '/profile': 'auth',
  '/profile/edit/{id?}': 'auth',
  '/profile/{id}': 'auth',
  '/role': 'auth+role:Administrator',
  '/skills': 'auth+role:Administrator',
  '/tags': 'auth+role:Administrator',
  '/user/all': 'auth+role:Administrator',
  '/user/consent': 'auth',
  '/user/recover': 'public',
  '/user/register': 'guest',
  '/user/reset': 'public',
}

function routePathFor(relative) {
  let route = `/${relative.replace(/\.vue$/, '')}`
  route = route.replace(/\/index$/, '')
  route = route.replace(/\[\[(\w+)\]\]/g, '{$1?}')
  route = route.replace(/\[(\w+)\]/g, '{$1}')
  return route === '' ? '/' : route
}

// Strip comments BEFORE looking for definePageMeta. Without this, a
// commented-out `// definePageMeta({ auth: true })` still matches and the page
// reads as gated - which silently defeats the whole point of this test (proven:
// commenting out fixometer's gate did not fail this suite until this was fixed).
function stripComments(source) {
  return source
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .split('\n')
    .filter((line) => !line.trim().startsWith('//'))
    .join('\n')
}

function gateFor(source) {
  const meta = stripComments(source).match(/definePageMeta\(\s*\{(.*?)\}\s*\)/s)
  const body = meta ? meta[1] : ''

  if (/guest:\s*true/.test(body)) {
    return 'guest'
  }

  if (/auth:\s*true/.test(body)) {
    const role = body.match(/role:\s*'([^']+)'/)
    return role ? `auth+role:${role[1]}` : 'auth'
  }

  return 'public'
}

function collectPages(dir, base = dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name)

    if (entry.isDirectory()) {
      return collectPages(full, base)
    }

    if (!entry.name.endsWith('.vue')) {
      return []
    }

    return [{
      route: routePathFor(path.relative(base, full).split(path.sep).join('/')),
      gate: gateFor(fs.readFileSync(full, 'utf8')),
    }]
  })
}

describe('page auth gating', () => {
  const pages = collectPages(PAGES_DIR)

  it('finds pages to check', () => {
    expect(pages.length).toBeGreaterThan(0)
  })

  it('every page declares the expected auth gate', () => {
    const actual = Object.fromEntries(pages.map((p) => [p.route, p.gate]))
    const expected = Object.fromEntries(
      Object.keys(actual).map((route) => [route, EXPECTED_GATES[route]])
    )

    // Compared as whole objects so a failure prints every drifted route at once.
    expect(actual).toStrictEqual(expected)
  })

  it('has no unlisted pages (a new page must declare its gate here)', () => {
    const unlisted = pages.map((p) => p.route).filter((r) => !(r in EXPECTED_GATES))

    expect(unlisted).toStrictEqual([])
  })

  it('has no stale entries for deleted pages', () => {
    const routes = new Set(pages.map((p) => p.route))
    const stale = Object.keys(EXPECTED_GATES).filter((r) => !routes.has(r))

    expect(stale).toStrictEqual([])
  })
})
