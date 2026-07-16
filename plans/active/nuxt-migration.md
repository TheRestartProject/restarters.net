# Nuxt 4 client migration — master plan / progress tracker

Design: `docs/nuxt-migration/design.md` (read it first).
Findings/inventories: `docs/nuxt-migration/findings/*.md`.
Branch: `nuxt-client` (off origin/develop; PRs #863/#866/#867/#868/#892 merged in).
Rules: work ONE task at a time; update status markers here after each; every slice
lands with tests (phpunit for API, vitest for client, playwright where flows exist);
add new files to git; keep CI green-able at every phase boundary.
Status: ⬜ pending · 🔄 in progress · ✅ done · ❌ blocked

## Phase A — Foundations

| # | Task | Status | Notes |
|---|------|--------|-------|
| A1 | Fold in Vue PRs #863 #866 #867 #892 #868 | ✅ | union-merged routes/api.php, UserController, app.js; stub-locale lang conflicts → develop side |
| A2 | Design doc + this plan committed | ✅ | |
| A3 | Sanctum install + `auth:sanctum,api` dual guard; keep legacy TokenGuard for Zapier/TRP | ✅ | config/auth.php guard swap on api routes; phpunit AuthDualGuardTest |
| A4 | Auth endpoints: login/logout/register/password-forgot/password-reset/email-available + `POST /api/v2/invites/claim` (stateless shareable-code + invite-hash accept, on login AND register too) + phpunit | ✅ | plain api group — NO session/CSRF (phpunit asserts login works w/o CSRF token); do NOT fire Login event on XHR login (no wasted LogInToWiki round-trip; audit path invoked directly); honeypot ported |
| A5 | `GET/PATCH /api/v2/session` (user+roles+networks+consent+config+flags) + consent gate: SPA middleware + verifyUserConsent.api 403 on v2 mutations + `POST /api/v2/auth/consent` + phpunit | ✅ | SessionController; replaces navbar/window globals; consent parity with VerifyUserConsent middleware is mandatory (GDPR) |
| A6 | SSO bridge: `POST /api/v2/auth/sso-ticket` + `GET /auth/bridge` (dedicated bridge middleware group: EncryptCookies+QueuedCookies+StartSession, no CSRF; redirect ALLOWLIST: /discourse/sso, WIKI_URL, FRONTEND_URL) + repoint /discourse/sso login redirect via services.discourse.middleware ONLY (global auth alias untouched until F) + phpunit | ✅ | one-time 60s ticket (sha256 stored, single-use); bridge Auth::login(web) fires Login event → wiki cookies land first-party |
| A7 | CORS: HandleCors global + config/cors.php (wildcard origins kept deliberately for partner-site browser consumers; supports_credentials false); AddCorsHeaders deleted | ✅ | CorsTest pins preflight/simple/no-credentials |
| A8 | Nuxt 4 scaffold in `client/`: nuxt.config (ssr:false, runtimeConfig), bootstrap-vue-next + @bootstrap-vue-next/nuxt (css:false), pinia+persist, @nuxtjs/i18n (lazy JSON), vitest config, eslint | ✅ | nuxt 4.4.8, vue 3.5.39, bootstrap-vue-next 0.45.8, @pinia/nuxt 1.0.0, pinia-plugin-persistedstate 4.7.1, @nuxtjs/i18n 10.4.1, vitest 4.1.10, eslint 10.7.0+@nuxt/eslint 1.16.0. PIN: typescript MUST be 6.0.3 not latest 7.0.2 (@typescript-eslint 8.64.0 requires <6.1.0, else typescript-estree crashes). api/ placed at client/app/api/ (inside srcDir, resolves via `~`) not client/api/ — matches A9/A10's own `client/app/assets/css`/`plugins/api.ts` paths, superseding design.md §6's flat-layout diagram. vitest 15/15, nuxi build exit 0, eslint clean — all verified from a wiped .nuxt/.output/cache state. |
| A9 | Global stylesheet `client/app/assets/css/restarters.scss` seeded from resources/global/css; fonts self-hosted; $blue pinned | ✅ | @fontsource asap/open-sans/patua-one; BS5 var overrides + navbar/footer/buttons/forms/type; _variables/_bootstrap/_navigation-bar/_footer/_type partials; visual parity vs global/css |
| A10 | BaseAPI + api/index + plugins/api.ts + stores/auth + stores/session + composables (useAuth) + vitest | ✅ | $fetch-based BaseAPI (ofetch), APIError typing, Authorization header, locale param injection; SessionAPI/AuthAPI/GroupAPI...; auth store persist pick:['token'] |
| A11 | Layouts (default/plain) + navbar + footer components + login/register/forgot/reset pages + route middleware (auth.global) + vitest | ✅ | navbar per session.config/user role flags (data-testid rich); definePageMeta({auth, role, layout}); redirect /login?redirect=; 403 page. Register incl. invite_hash, consent checkboxes, honeypot fields, geocoded location via /api/v2/maps proxy TBD in C-slice (plain text field for now) |
| A12 | docker-compose `restarters_client` service (node:22, nuxi dev, port 3000) + Taskfile tasks (docker:client, docker:test:vitest, docker:test:playwright:client) | ✅ | client/Dockerfile (dev target); NUXT_PUBLIC_API_BASE=http://localhost:8001; profiles core/debug/discourse; anonymous node_modules volume |
| A13 | CircleCI: `build-client` job (node executor: npm ci, lint, vitest junit, nuxt build) AND separate `e2e-client` machine job (compose up-core + client, explicit client readiness poll, playwright vs Nuxt origin) | ✅ | never extend the monolithic `build` job; deploy jobs require all three |
| A14 | Playwright: client/e2e scaffold + login/register/logout spec against Nuxt (fixtures port: maps-abort generalized, X-Playwright-Test header) + seeded users | ✅ | client/e2e/{fixtures,utils}.js + auth.test.js (10 tests); playwright.client.config.js at repo root; workers:1 for now (revisit G3) |
| A15 | i18n exporter `translations:export-client` + committed en/fr/fr-BE JSON + parity vitest for plurals + CI sync check | ✅ | ExportClientTranslations command; converts :param→{param}, range plurals normalized; hard-fails on unconvertible; 27 files/locale; parity tests in client/tests/i18n; sync check step in build-client CI job |

## Phase B — Dashboard + Groups slice

| # | Task | Status | Notes |
|---|------|--------|-------|
| B1 | API: `GET /api/v2/dashboard` (your/nearby groups, upcoming events, new groups, moderation counts) + phpunit | ✅ | DashboardController@indexv2; batched queries (no N+1: withCount + single stats pass); OA annotated |
| B2 | API: group join/leave (`POST/DELETE /groups/{id}/members/me`), invite + stateless accept, image upload/delete verbs, `DELETE /groups/{id}` archive, `GET /groups/{id}/stats`, nearby | ✅ | GroupMembershipController + image/archive endpoints on GroupController; invite-accept email deep-link redirector kept (web.php → FRONTEND_URL); 5 new phpunit files |
| B3 | Pages: /dashboard (+ stores/dashboard, components DashboardGroups/Events cards) + vitest | ✅ | |
| B4 | Pages: /group (mine), /group/all, /group/nearby lists + stores/groups + GroupsTable/GroupCard + vitest | ✅ | column_preferences → user preference API not session |
| B5 | Pages: /group/view/{id} (stats, events, volunteers, permissions from #892) + vitest | ✅ | |
| B6 | Pages: /group/create + /group/edit/{id} (geocode via /api/v2/maps proxy ported here; Quill wrapper; tus image upload) + vitest | ✅ | MapsProxy → /api/v2/maps/* done here; RichTextEditor (Quill 2) + LocationPicker + TusImageUpload components |
| B7 | Group map page (port RES-1995 map work) | ⬜ | @vue-leaflet/vue-leaflet + leaflet.markercluster + leaflet-control-geocoder (Photon) + CARTO tiles — port GroupMap.vue as-is (NOT MapLibre; design §2); names-index + summary?ids= split fetch |
| B8 | Playwright: group.test.js flows ported (create, unfollow, image upload) + dashboard smoke | ✅ | client/e2e/group.test.js (3 flows + smoke); data-testid selectors; deterministic waits (waitForResponse) |

## Phase C — Events + Devices slice

| # | Task | Status | Notes |
|---|------|--------|-------|
| C1 | API: RSVP family + `GET /events/{id}/attendees` + per-user `attending` flag on v2 event resource, volunteer PATCH/invite, event devices list, images, DELETE event, moderation approve + phpunit | 🔄 | useEventComputed(event) + useEventAttendance(id) composable pair depends on these |
| C2 | Pages: /party (list mine), /party/all + /party/all-past (DEAD legacy routes — build minimal per contracts doc over GET users/me/events), group events tab + stores/events + vitest | ✅ | EventsTable/EventCard/EventFilters; joined/hosted badges |
| C3 | Pages: /party/view/{id} (RSVP, volunteers, devices readonly, calendar links, share) + vitest | ✅ | ics links stay Laravel /calendar/* |
| C4 | Pages: /party/create /party/edit/{id} /party/duplicate/{id} (b-calendar→vue-datepicker-next, venue/group-location picker, moderation approve UI) + vitest | ✅ | .event-approve select preserved as data-testid=event-approve |
| C5 | API+pages: device CRUD on event page (item type autocomplete/category suggestion port of items store, spare parts, barriers, photos via tus) + vitest | 🔄 | /api/v2/devices/options endpoint (brands/barriers/spareparts/itemtypes); DeviceForm + DeviceRow + useCategorySuggestion (exact-match port of items.js — legacy has NO fuzzy matching; keep parity); addDevice/updateDevice/deleteDevice + photo attach via tus; multiselect keyboard UX preserved |
| C6 | Pages: /fixometer (home), device search/list, impact stats + vitest | ✅ | fixometer dashboard page + /device/search page + ImpactStats components; GET /api/v2/devices paginated+filters endpoint added (APIv2DevicesListTest) |
| C7 | Playwright: event.test.js + device.test.js flows ported | ⬜ | client/e2e/event.test.js (create future/past, invite modal), device.test.js (5 flows incl. photo + category suggestion excluded-slow) |

## Phase D — Profile + Admin slice

| # | Task | Status | Notes |
|---|------|--------|-------|
| D1 | Pages: /profile/edit 5 tabs against PR #868 API (Uppy dashboard for photo) + vitest | ⬜ | ProfileTabs + 10 tab components; Uppy@tus wired to /tus; delete-account flow with confirm modal |
| D2 | Pages: public /profile/{id} + API `GET /api/v2/users/{id}` + phpunit/vitest | ⬜ | PII-safe resource (name, avatar, groups, skills, bio only) |
| D3 | Pages: /user/all admin list against PR #866 API + vitest | ⬜ | filters/sort/pagination preserved; role editor modal → PATCH /users/{id}/admin-settings |
| D4 | Pages: admin reference-data CRUD (brands/skills/categories/group-tags/roles) against PR #863 API; AdminCrudPage → AdminCrudTable component + vitest | ⬜ | one generic component + 5 thin pages, per PR-863 prop contract |
| D5 | API+page: admin stats (JSON versions of /admin/stats views) + preview-deploy page | ⬜ | admin stats widgets stay Laravel-served iframes (§9); preview-deploy = simple page on GET/POST /api/v2/admin/preview-deploy |
| D6 | Playwright: admin-users + admin-reference-data specs ported | ⬜ | client/e2e/admin.test.js |

## Phase E — Networks + static slice

| # | Task | Status | Notes |
|---|------|--------|-------|
| E1 | Pages: /networks, /networks/{id} (tags mgmt, associate groups, stats) + vitest | ⬜ | grouptags UI: create/edit/delete/assign flows with NC/Admin/Host permission gating from session roles |
| E2 | Pages: static (about/cookie-policy/visualisations link-outs), onboarding modal, cantcreate (PR #867 keys), /user/forbidden 403 | ⬜ | onboarding shown post-register from session flag |
| E3 | Notifications dropdown + page (existing endpoints) + talk topics widget | ⬜ | discourse links route via /auth/bridge |
| E4 | Locale switcher + PATCH session locale + APISetLocale header on every call | ⬜ | en/fr/fr-BE only |
| E5 | Playwright: grouptags.test.js ported (34 tests, deterministic waits) + landingpage equivalent (/ redirects to /dashboard or marketing landing page) | ⬜ | landing page rebuilt in Nuxt (marketing content from landing.php lang keys) |

## Phase F — Cutover (Laravel stops serving frontend)

| # | Task | Status | Notes |
|---|------|--------|-------|
| F1 | Point compose + CI Playwright at Nuxt as primary suite; legacy Integration suite still green | ⬜ | build job now runs client e2e; legacy suite retired in F3 same-commit |
| F2 | Delete: Blade views (minus §9 surface), resources/js, resources/sass, laravel-ui auth controllers, EnsureAPIToken + restarters_apitoken, dead legacy-redirect blocks, vite entries trimmed to wiki/global only | ⬜ | FIRST move resources/js/misc/notifications.js → resources/global/js (global/js/app.js imports it) + grep all global|wiki→js/sass cross-refs; then delete; global Authenticate::redirectTo may now point at FRONTEND_URL |
| F3 | Delete Jest suite + tests/Integration Blade specs; remove jest/vue2 deps from root package.json; nginx redirect map for legacy prefixes | ⬜ | root package.json now build-tooling only (vite + wiki/global css); docker/nginx.conf + nginx-fly.conf: 16 legacy prefixes → Discourse thread redirect |
| F4 | routes/web.php final audit: nothing view-returning outside §9; route:list snapshot test | ⬜ | tests/Feature/ApiOnlyRouteSurfaceTest pins the allowed web-route list |
| F5 | Docs: local-development.md, CLAUDE.md (client dev commands), README | ⬜ | |

## Phase G — Hardening / done criteria

| # | Task | Status | Notes |
|---|------|--------|-------|
| G1 | Full CircleCI green: build (phpunit+legacy asset test) + build-client (lint+vitest+build) + playwright-client | ⬜ | pushed; runs monitored via ci-status |
| G2 | Vitest coverage ≥ existing jest coverage on ported logic; coverage artifact in CI | ⬜ | v8 coverage, summary in CI artifacts; stores/api/composables >90%, components >80% |
| G3 | Playwright workers >1 trial (post-CSRF removal); flake pass (3 consecutive green runs) | ⬜ | |
| G4 | l5-swagger regenerate + OpenAPI response validation green; translations sync check green | ⬜ | |
| G5 | Session log + this plan closed out; PR opened (against develop) with folded-PR closure notes | ⬜ | |

## Conventions cheat-sheet (for resumed sessions)

- API: v2, OpenAPI-annotated, `{data:…}` envelope, roles enforced server-side 403.
  External v1 surface (Zapier/TRP/RepairTogether) frozen — see design §5.
- Client: pages mirror legacy URLs; stores call $api classes; components auto-import;
  `data-testid` on interactive elements; scoped styles; global brand sheet only for
  brand/nav/footer/type/bootstrap-overrides.
- Auth: Sanctum bearer in auth store (persist token only); `auth:sanctum,api` server-side;
  SSO/wiki via one-time-ticket bridge (design §4.3).
- BS4→BS5 renames + component swaps table: design §2 + §6.3.
- Test seeding: Taskfile tinker step (jane@bloggs.net etc.) unchanged.
- npm quirks: `npm install --legacy-peer-deps` in Laravel root; client/ is clean npm.
- Local phpunit: `task docker:up-core` then `task docker:test:phpunit -- --filter=X -d error_reporting=8191 --no-coverage`.
