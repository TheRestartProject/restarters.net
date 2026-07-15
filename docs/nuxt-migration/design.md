# Nuxt 4 client migration — top-level design

Status: ACTIVE. Master progress tracker: `plans/active/nuxt-migration.md`.
Evidence base: `docs/nuxt-migration/findings/*.md` (ten-dimension codebase survey +
completeness critique, July 2026). This document records the decisions; the findings
files record the inventories (route-by-route, component-by-component) that ground them.

## 1. Goal and end state

A branch (`nuxt-client`) where:

- **Laravel serves no frontend.** It is an API server (`/api/*`) plus a small
  permanent server-rendered surface (§9): exports, iCal feeds, partner embeds/stat
  widgets, Discourse SSO, email deep-links, tus uploads, wiki/global CSS assets.
- **A separate Nuxt 4 app** in `client/` is the entire user interface, talking to
  the API only. No Blade-encoded props, no manual component registration, no shared
  session bootstrapping — everything the client knows arrives via API responses.
- Docker Compose runs them as separate containers; Playwright regression passes
  against the Nuxt app; the whole suite is green on CircleCI.
- A future Capacitor app is possible without rework (static SPA build, token auth,
  `IS_APP`-style runtime flag reserved) but is **not built now**.

## 2. Stack

| Concern | Choice | Notes |
|---|---|---|
| Framework | Nuxt 4 (`nuxt@^4`, `app/` dir structure) | User requirement; Freegle is on Nuxt 3.21 — same conventions, newer layout |
| UI kit | `bootstrap-vue-next` **plus** `@bootstrap-vue-next/nuxt` (the module is a separate package; `css:false`) + Bootstrap 5.3 SCSS | Freegle model; verified building under Nuxt 4 + ssr:false |
| State | Pinia + `@pinia/nuxt` + `pinia-plugin-persistedstate` | Options-style stores (Freegle model); persist **only** auth tokens + locale via `pick` |
| API client | `BaseAPI` class + one `<Resource>API` class per resource + plugin-injected `$api` | Freegle model (api/BaseAPI.js); `$fetch`-based (Node 18 fetch bugs that made Freegle avoid it are gone on Node 22) |
| i18n | `@nuxtjs/i18n`, lazy JSON locales generated from `lang/*.php` | §7 |
| Testing | Vitest + @vue/test-utils + happy-dom; Playwright e2e | §8 |
| Icons | Inline SVG components ported from `partials/svg-icons/*.blade.php` + `bootstrap-icons` | vue-awesome has no Vue 3 build |
| Maps | `@vue-leaflet/vue-leaflet` (Leaflet 1.9, CARTO tiles) + `leaflet.markercluster` + `leaflet-control-geocoder` (Photon) — porting the RES-1995 GroupMap work as-is | replaces vue2-leaflet. Package is stale (0.10.1, 2023) but thin and proven (Freegle ships it); accepted knowingly |
| Rich text | Quill via a thin Vue 3 wrapper | replaces vue2-editor; keep Quill semantics (`.ql-editor` selectors live in Playwright) |
| Uploads | Uppy + tus (client) → existing `TusController` (server) | PR #868's subsystem becomes the canonical mechanism (§5.4) |
| Validation | `@vuelidate/core` | replaces vuelidate 0.x |
| Multiselect | `vue-multiselect` v3 (Vue 3 release) | keeps UX + `.multiselect__*` selector family |
| Date picking | `vue-datepicker-next` | b-form-datepicker has no bootstrap-vue-next equivalent; Freegle ships this package. Unmaintained-but-proven (last release 2023) — accepted knowingly |

Version pins are recorded in `client/package.json`; expect to pin vue/pinia versions
if instability appears (Freegle pins hard for this reason).

## 3. Repo & runtime topology

**Monorepo.** The Nuxt app lives in `client/` at the repo root. One branch, one PR,
one CI pipeline, one docker-compose — the deciding factors over Freegle's split-repo
model (Freegle needs a third orchestration repo to compensate; we don't want one).

Dev/CI topology (cross-origin, CORS-enabled):

```
browser ── http://localhost:3000 ──► restarters_client (node:22, nuxi dev / preview)
   │                                        │  NUXT_PUBLIC_API_BASE
   └── http://localhost:8001 ─────► restarters_nginx ──► restarters (php-fpm)  [API only]
                                            └──► restarters_db (mysql:8)
```

- Cross-origin is safe because auth is **header-token based** (§4) — no credentialed
  CORS, no third-party-cookie/ITP concerns. `config/cors.php` is rewritten with the
  explicit dev/prod origins; the hand-rolled `AddCorsHeaders` middleware
  (`Access-Control-Allow-Origin: *`, GET-only preflight) is deleted.
- Production topology (Fly.io) is **out of scope for this branch** (flagged): the
  natural shape is nginx-fly serving the static `nuxi generate` output at `/` and
  proxying `/api` to php-fpm in the same image, which keeps one Fly app and makes
  prod same-origin. Nothing in this design precludes it.

## 4. Authentication

### 4.1 Decision

**Primary: Sanctum personal access tokens (Bearer), stored in localStorage via the
persisted auth store.** This follows the Freegle precedent (localStorage dual-token,
explicitly not cookies), keeps CORS credential-free, unblocks Playwright
parallelism (no session CSRF), and is what a Capacitor WebView needs anyway.

The alternative (Sanctum SPA stateful cookies) was rejected because it demands a
shared parent domain + credentialed CORS in every environment (awkward in dev and
in CI, hostile to Safari/ITP when origins split) and still requires a parallel token
path for Capacitor later. The one real thing cookies bought us — Discourse SSO and
MediaWiki silent login — is preserved with a session bridge (§4.3).

### 4.2 Server changes

- Install `laravel/sanctum`. `User` gains `HasApiTokens`.
- New auth guard `sanctum` alongside the legacy `api` TokenGuard. All first-party
  routes move to `auth:sanctum,api` (tries Sanctum, falls back to legacy token) so
  **Zapier / TRP.org / RepairTogether integrations keep working unchanged** on their
  existing `users.api_token` credentials. New SPA traffic is pure Sanctum.
  `EnsureAPIToken` middleware + the `restarters_apitoken` cookie die with Blade.
- New endpoints (plain `api` middleware group — **no session, no CSRF**: bearer
  auth is not ambient-credential auth, so CSRF protection is inapplicable; a
  phpunit test asserts login succeeds with no CSRF token. The web-guard session
  is established **only** at the bridge (§4.3), never on the XHR path — a
  cross-origin XHR's Set-Cookie is discarded by the browser anyway, and skipping
  web-guard login here also avoids paying LogInToWiki's synchronous MediaWiki
  round-trip for cookies that could never land):
  - `POST /api/v2/auth/login` {email, password, invite_code?} → `{token, user}`.
    Does **not** fire `Illuminate\Auth\Events\Login` (that would trigger
    LogInToWiki uselessly — see above); instead invokes the audit path
    (LogSuccessfulLogin behaviour) directly. Wiki login happens at the bridge.
  - `POST /api/v2/auth/logout` → revokes current token; queues the Discourse
    logout + wiki-cookie-forget listeners (fires `Logout` on the web guard only
    if a web session exists).
  - `POST /api/v2/auth/register` {…, invite_hash?, invite_code?} → creates user,
    returns `{token, user}`; resolves invite hashes/codes **statelessly**
    (replaces the `AcceptUserInvites` session-array middleware; identifiers
    passed in the payload, resolved against `Invite`/shareable codes)
  - `POST /api/v2/invites/claim` {code|hash} (authed) — applies a group/event
    invite to the current user. This replaces the session-bridged
    **shareable-link flow** (`GET /group/invite/{code}`, `/party/invite/{code}`):
    those URLs become SPA routes; if the visitor is logged in the page claims
    immediately, otherwise it routes through login/register carrying the code.
    `shareable_link` generation moves to the frontend URL; Laravel keeps
    redirectors for previously-shared links (§5).
  - `POST /api/v2/auth/password/forgot`, `POST /api/v2/auth/password/reset` —
    JSON versions of the real custom `recovery`/`recovery_expires` flow (the
    laravel-ui `ForgotPasswordController`/`ResetPasswordController`/`RegisterController`
    are dead code and are deleted, not ported)
  - `GET /api/v2/auth/email-available?email=` (existing `check-valid-email` logic)
- `GET /api/v2/session` — the single client-bootstrap call: `{user: {...roles,
  networks, language, preferences, consent: {gdpr, past_data, future_data}} |
  null, config: {discourse_url, gtm_id, …}, flags}`. Replaces every `window.*`
  global, navbar `Auth::user()` read and env-inlined Blade value.
  `PATCH /api/v2/session {locale}` persists locale.
- **GDPR consent gate** (today: `VerifyUserConsent` middleware wraps the whole
  authenticated Blade app, redirecting to the register/consent form): ported as
  (a) consent status in the session payload, (b) a global SPA middleware that
  redirects unconsented users to the consent-completion page (reusing the
  register form's completion mode), (c) a server-side `verifyUserConsent.api`
  middleware on authenticated v2 mutation routes returning 403
  `{reason:'consent_required'}`, and (d) `POST /api/v2/auth/consent` to record
  it. Nothing consent-gated today may become reachable without consent.

### 4.3 Discourse SSO + MediaWiki bridge

`GET /discourse/sso` (spinen package, web+auth middleware) and the MediaWiki
cookie listeners fundamentally need a Laravel **web session** during a top-level
browser navigation. Bridge design:

- `POST /api/v2/auth/sso-ticket` (Sanctum-authed) → one-time, 60-second ticket
  (random value, sha256 stored, single-use). The client navigates **top-level** to
  `GET /auth/bridge?ticket=…&redirect=…`. The bridge runs under a dedicated
  `bridge` middleware group (`EncryptCookies` + `AddQueuedCookiesToResponse` +
  `StartSession` — **no `VerifyCsrfToken`**, it's a GET with a one-time ticket):
  validates + consumes the ticket, `Auth::login()`s the web guard — firing the
  Login event, so `LogInToWiki` queues the `mw_*` cookies on this first-party
  response — then redirects onward. `redirect` is validated against an
  **allowlist** (the `/discourse/sso` path, `WIKI_URL`, `FRONTEND_URL`) — no open
  redirect.
- Links out to Talk/Wiki from the Nuxt navbar route through the bridge.
- When Discourse initiates SSO and there is no Laravel session: the login
  redirect for `/discourse/sso` is repointed at
  `FRONTEND_URL/login?redirect=<original>` **scoped to that route only** via
  `services.discourse.middleware` config (a dedicated authenticate middleware) —
  the global `auth` alias / `Authenticate::redirectTo()` is untouched until
  phase F, so legacy Blade routes keep their own /login redirect throughout B–E.
  After client-side login the user is sent back through the bridge.
- Test coverage: phpunit for ticket issue/consume/expiry/replay + bridge
  session establishment; Playwright asserts the SPA→bridge→`/discourse/sso`
  hop 302s into Discourse's `session/sso_login` (full round-trip stays in the
  Discourse-profile CI job, same `www.example.com` host mapping as today).

### 4.4 Client conventions

- `stores/auth.js`: `{token, user}`; persist `pick: ['token']`. `fetchUser()` = GET
  /api/v2/session. Logout clears store + aborts in-flight requests (Freegle's
  logout-abort pattern).
- Global route middleware reads `definePageMeta({ auth: true, role: 'Administrator' })`
  and redirects to `/login?redirect=…` / renders 403 page. **Conventional Nuxt
  middleware, not Freegle's forceLogin-modal** — matches the existing redirect UX
  that Playwright flows encode. The API stays the real enforcer (401/403 JSON).

## 5. API completion

The SPA can only be as complete as the API. Endpoint families to add (full
inventory: findings/api-surface.md; per-page prop shapes: findings/i18n-globals.md
inventory + the 17 `@json` Blade views):

1. **Auth + session** — §4.
2. **Dashboard** — `GET /api/v2/dashboard` (your groups, nearby groups, upcoming
   events, new groups/moderation counts), batched (listSummaryv2 `ids=` pattern),
   replacing `DashboardController` Blade props.
3. **Groups** — join/leave (`POST/DELETE /api/v2/groups/{id}/members/me`), invite +
   stateless accept, image upload/delete (proper verbs replacing GET-mutation
   routes), `DELETE /api/v2/groups/{id}` (archive semantics), group stats block for
   the group page (`/api/v2/groups/{id}/stats`), nearby groups.
4. **Events** — RSVP family (`POST/DELETE /api/v2/events/{id}/attendees/me`),
   `GET /api/v2/events/{id}/attendees` (list: confirmed/invited/volunteers —
   today only the v1 volunteers endpoint exists), a per-user `attending` boolean
   on the v2 event resource when authenticated (today computed only in the Blade
   `PartyController`), volunteer PATCH (quantity/role) and invite, event devices
   list, images, `DELETE /api/v2/events/{id}`, moderation approve.
   (create/edit/get exist.) The event mixin port is therefore two composables:
   `useEventComputed(event)` for resource-derivable fields and
   `useEventAttendance(id)` backed by the attendees endpoint.
5. **Devices** — paginated list/search v2 (replaces `/api/devices/{page}/{size}`),
   images via tus, barrier/spare-part option lists (`/api/v2/devices/options`).
6. **Users** — folded in: `/users/me/*` family (PR #868), admin list (PR #866);
   add public profile `GET /api/v2/users/{id}`, admin user edit/role endpoints.
7. **Admin CRUD** — folded in via PR #863 (brands, skills, group-tags, categories,
   roles, permissions); add admin stats endpoints as JSON.
8. **Reference data** — timezones (exists v1 → keep), items/categories (exist),
   countries, skills list for registration.
9. **Maps proxy** — port `/maps/autocomplete|place-details` → `/api/v2/maps/*`
   under `auth:sanctum,api` (Google key stays server-side).
10. **Talk** — existing topics endpoint; notifications endpoints kept.
11. **Uploads** — tus (`/tus`) + per-entity attach endpoints, extending PR #868's
    pattern from profile photos to group/event/device images.

Conventions: every new endpoint is v2, OpenAPI-annotated (l5-swagger response
validation is active in tests), returns `{data: …}` envelopes, uses API Resources,
enforces roles server-side (403 JSON; the in-controller
`Fixometer::hasRole(...)->redirect('/user/forbidden')` pattern does not carry over).
Existing **externally-consumed v1 endpoints are frozen** (Zapier/TRP/RepairTogether:
stats, changes, outbound/info, networks) — never removed or reshaped on this branch.

**Internal GET-mutation routes** (`group/delete/{id}`, `party/join/{id}`,
image-delete GETs, `markAsRead`, …) are replaced by proper verbs. **Emailed and
shared deep-links** (`party/accept-invite/{id}/{hash}`, `group/accept-invite/…`,
`group/invite/{code}`, `party/invite/{code}`, `/user/reset?recovery=…`) keep
their GET URLs: Laravel keeps tiny handlers that redirect into the SPA
(`FRONTEND_URL/…`) carrying the hash/code, where the claim happens via
`/api/v2/invites/claim` (§4.2), so old emails and previously-shared links keep
working for both new and already-registered users.

## 6. Client architecture

```
client/
  app/                      # Nuxt 4 srcDir
    app.vue                 # shell: <NuxtLayout><NuxtPage/>; session fetch gate
    layouts/  default.vue (navbar+footer), plain.vue (auth pages), bare.vue
    pages/                  # file-based routing mirroring today's URLs (§6.2)
    components/             # auto-imported; PascalCase; no registration map
    composables/            # useApi(), useAuth(), useRoles(), useToast(), …
    stores/                 # pinia: auth, session/config, groups, events,
                            # devices, users, networks, admin, notifications
    middleware/  auth.global.ts (reads definePageMeta auth/role)
    plugins/     api.ts (provides $api), i18n message loading
  api/          BaseAPI.js + GroupAPI.js, EventAPI.js, … (class per resource)
  assets/css/   restarters.scss (global brand sheet §6.3) + bootstrap overrides
  i18n/locales/ en.json, fr.json, fr-BE.json   # generated, committed
  tests/        vitest unit tests (components/, stores/, api/, composables/)
  e2e/          playwright specs (ported from tests/Integration)
  nuxt.config.ts            # ssr:false; runtimeConfig.public.apiBase; IS_APP flag
```

Key rules:

- `ssr: false` everywhere (pure SPA). bootstrap-vue-next doesn't support SSR;
  nothing user-facing needs crawler HTML that Laravel isn't already keeping (the
  partner-embedded widgets/iframes stay in Laravel). This also makes
  `nuxi generate` output directly Capacitor-usable later. Per-page `useHead()`
  still sets titles/meta.
- **URL compatibility**: pages keep today's paths (`/dashboard`, `/group/view/{id}`,
  `/party/edit/{id}`, `/user/register`, …) so bookmarks, emails and the Playwright
  flows survive. Optional segments (`/party/create/{group_id?}`) use Nuxt's
  `[[param]].vue` optional-catch naming. Legacy redirects (faultcat etc.) move to
  nginx, not Nuxt.
- **Data flow**: pages call stores; stores call `$api`; components receive props or
  read stores. No component fetches with axios directly; no `newToOld()` field
  adapter — components are written against the real v2 field names (`idgroups`-era
  names die with the Vuex store).
- **The mixin surface** (`mixins/event.js`, `group.js` computed names like
  `attending`, `upcoming`, `canedit`) is re-provided as composables
  (`useEventComputed(event)`, `useGroupComputed(group)`) so ported templates keep
  their vocabulary while the underlying fields are v2-native.
- `data-testid` attributes on every interactive element as components are built —
  the Playwright port migrates off brittle CSS-class selectors as it goes.

### 6.2 Page inventory

The ~27 Vue 2 root/"page" components + the Blade-only pages map to Nuxt pages;
findings/routes-pages.md is the authoritative route-by-route list. Sections, in
migration order (each section = deliverable slice with tests):

1. Shell: navbar/footer/session/login/logout/register/password (+ auth API)
2. Dashboard
3. Groups: list (mine/all/nearby), view, create/edit, join/invite, map (RES-1995 work)
4. Events: list, view, create/edit/duplicate, RSVP/volunteers, moderation
5. Devices/Fixometer: event device CRUD, fixometer home, device search, impact stats
6. Profile: all 5 tabs (PR #868 API) + public profile
7. Admin: users list (PR #866), reference-data CRUD (PR #863), admin stats, preview-deploy
8. Networks: index/show/edit, tags (grouptags Playwright suite)
9. Static/misc: about pages, cookie policy, onboarding modal, cantcreate (PR #867),
   notifications page, talk topics widget

### 6.3 Styles

- Global sheet `client/assets/css/restarters.scss` seeded from
  `resources/global/css` (**the umbrella brand layer**, not `resources/sass`
  variables): brand tokens ($brand #0394a6 family), Bootstrap 5 variable overrides,
  nav, footer, typography (Asap/Open Sans/Patua One — self-hosted via
  @fontsource, loaded once), buttons/forms/alerts/cards/tables/tabs base.
- `$blue: #007bff` pinned explicitly (today it silently inherits BS4's default;
  BS5 changed it — findings/styles.md).
- Page/component styles become `<style scoped>` in their component. The BS4→BS5
  rename table (badge→text-bg, data-toggle→data-bs-toggle, close→btn-close,
  sr-only→visually-hidden, input-group-append→input-group-text, b-btn→b-button,
  b-img-lazy→`loading="lazy"`) is applied during each component port, tracked in
  the plan.
- `resources/global/css` and `resources/wiki/**` **stay in Laravel untouched** —
  MediaWiki consumes `/css/wiki.css` and `/global/css/app.css` from this host
  (legacyassets Playwright test pins this). Laravel keeps a minimal Vite build for
  those two entries only; `resources/sass/**` and `resources/js/**` are deleted at
  cutover.

## 7. i18n

- `lang/{en,fr,fr-BE}` stay the source of truth (server needs them for emails,
  API-side strings, exports).
- New artisan command `translations:export-client` walks `lang/<locale>/*.php` +
  `lang/<locale>.json` and emits `client/i18n/locales/<locale>.json` (committed;
  CI check that it's in sync). The exporter converts Laravel syntax to vue-i18n
  message syntax: `:param` → `{param}`, `singular|plural` stays pipe-form,
  `{1} x|[0,*] y` range forms normalized to vue-i18n choice positions; it **fails
  loudly** on any string it can't convert. A Vitest suite asserts converted plural
  behaviour matches Laravel `trans_choice` semantics for every pluralised string.
- Keys keep their `file.key` shape so ported templates keep `$t('groups.…')` ≈
  today's `__('groups.…')`.
- Locale: `@nuxtjs/i18n` cookie/localStorage persistence; logged-in users PATCH
  /api/v2/session; every API call carries the locale header so `APISetLocale`
  (already built) localizes server-side strings. Only en/fr/fr-BE are selectable.

## 8. Testing

- **Vitest** (`client/tests`): every store, composable, API class and non-trivial
  component gets unit tests (Freegle patterns: `setActivePinia`, module-level
  `vi.mock('~/api')`, `global.stubs` for bootstrap-vue-next, happy-dom,
  console.warn-throws). The 14 legacy Jest component tests are re-expressed as
  behaviour specs for their Vue 3 successors, then the Jest suite is deleted with
  the Vue 2 code. Coverage gate on `client/` (v8 provider).
- **PHPUnit**: folded-in PR test suites (~25 new Feature files) + tests for every
  new endpoint (auth family, session, bridge, dashboard, RSVP, …); OpenAPI
  response validation stays on.
- **Playwright**: specs move to `client/e2e/`, flow-for-flow with today's
  `tests/Integration` suite (login, create group, unfollow, image upload, create
  event ×2, invite modal, device ×5, grouptags ×34, landing, legacy assets —
  the acceptance contract). Selectors move to `data-testid`; fixed sleeps become
  deterministic waits; login helper becomes UI-form-based against the Nuxt login
  page. `PLAYWRIGHT_BASE_URL=http://restarters_client:3000`; the 4 pure-API
  grouptags tests + legacyassets keep pointing at the API host. Same seeding
  (Taskfile tinker step). `workers` can rise >1 once session-CSRF is gone
  (verify, don't assume).
- Legacy suites (`tests/Integration` against Blade, Jest) stay runnable until
  cutover (§10 phase F), then are deleted in the same commit that deletes Blade.

## 9. What Laravel keeps serving (permanent)

| Surface | Why |
|---|---|
| `/api/**` | the product |
| `/export/**` CSV downloads | external consumers (therestartproject.org dataset links) |
| `/calendar/**` iCal feeds | calendar apps, hash-auth |
| `/outbound/info/**`, `/group/stats/**`, `/party/stats/wide`, `/group-tag/stats`, `/admin/stats/{1,2}` | partner iframes/widgets (TRP.org) — server-rendered on purpose |
| `/discourse/sso` + `/auth/bridge` | SSO provider handshake (§4.3) |
| `/tus`, `/tus/{any}` | resumable uploads |
| Email deep-link redirectors (invite accept, password reset) | old emails keep working |
| `/css/wiki.css`, `/global/css/app.css` + fonts/images they need | MediaWiki skin |
| webhooks/misc (`/set-cookie` dies; Matomo/GTM move into Nuxt) | |

Everything else in `routes/web.php` (~110 routes) is deleted; nginx gains a
redirect map for the 16 dead legacy prefixes (faultcat/mobifix/…) and `/workbench`.

## 10. Sequencing (strangler, single branch, CI-gated)

- **A. Foundations**: fold-in PRs (done); Sanctum + auth/session/consent/bridge
  endpoints + CORS rewrite (tighten the existing wildcard `config/cors.php`, not
  just delete AddCorsHeaders) + phpunit; Nuxt scaffold with stack, global
  stylesheet, BaseAPI, auth store, layouts, login/register pages; docker
  `restarters_client` service; Taskfile tasks; CircleCI: `build-client` job
  (lint + vitest + nuxt build, node docker executor) **and** a separate
  `e2e-client` machine-executor job (compose up core + client with an explicit
  client readiness poll, then Playwright against the Nuxt origin) — the existing
  monolithic `build` job is never extended (its 35-min startup + 45-min phpunit
  budget is already tight).
- **B–E. Section slices** in §6.2 order. Each slice: missing API endpoints (+
  phpunit) → pages/components/stores (+ vitest) → its Playwright specs ported →
  CI green. Legacy Blade pages remain live and untouched throughout.
- **F. Cutover**: the `e2e-client` suite becomes the primary regression gate;
  delete Blade views (minus §9), `resources/js`, `resources/sass`, jQuery/CDN
  layouts, dead auth controllers, `EnsureAPIToken`, web routes; global
  `Authenticate::redirectTo()` may now point at FRONTEND_URL; nginx API-only +
  redirect map; delete Jest + legacy Integration specs; `npm` deps prune.
  **Pre-deletion audit**: `resources/global/js/app.js` imports
  `resources/js/misc/notifications.js` — move it (and any other
  `global|wiki → resources/js|sass` cross-references; grep first) into
  `resources/global/` before deleting, or the kept wiki/global Vite build (and
  the Fly image build) breaks.
- **G. Hardening**: full CI runs, flake fixes, Playwright parallelism, coverage
  gates, OpenAPI regenerate, docs (`docs/local-development.md`, CLAUDE.md update).

Definition of done: CircleCI green (phpunit + vitest + client Playwright), Laravel
route list contains no view-returning routes outside §9, `resources/js` and
`resources/sass` gone, session log + plan file closed out.

## 11. Risks / mitigations

- **SSO bridge is novel** → dedicated Playwright spec + phpunit for ticket
  issue/consume; manual-equivalent flow test in CI via Discourse container profile.
- **Legacy token consumers** → `auth:sanctum,api` dual guard; v1 surface frozen;
  contract tests kept.
- **bootstrap-vue-next gaps** (b-form-datepicker, b-img-lazy, b-btn) → §2 table;
  mapping table lives in the plan file and is ticked per component.
- **Visual drift** (two BS4 systems merged into one BS5 system) → global sheet
  seeded only from `global/css`; `$blue` pinned; per-section screenshot spot-checks
  with chrome-devtools MCP during development.
- **Plural conversion** → exporter hard-fails on unknown syntax; parity Vitest suite.
- **Scale** (220 views) → strangler order above; Blade stays functional until F;
  every slice lands CI-green so the branch is always shippable-ish.
- **Prod deploy topology** (Fly) → explicitly out of scope; flagged in §3.
