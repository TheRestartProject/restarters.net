# Frontend Architecture

The frontend is a standalone Nuxt 4 single-page application in `client/`,
fully separated from the Laravel backend. Laravel serves `/api/v2` plus a
small pinned web surface (see `tests/Feature/ApiOnlyRouteSurfaceTest.php`);
it renders no user-facing pages.

---

## Stack (client/)

- **Nuxt 4 / Vue 3** — `ssr: false` (SPA); pages under `client/app/pages`
- **Pinia** (+ persistedstate for the auth token) — stores under `client/app/stores`
- **bootstrap-vue-next / Bootstrap 5** — UI components and styling
- **@nuxtjs/i18n** — locales generated from `lang/*.php` by
  `php artisan translations:export-client` (en, fr, fr-BE), plus
  hand-maintained `client-{locale}.json` for client-only keys
- **Vitest** — unit tests under `client/tests`
- **Playwright** — e2e specs under `client/e2e`
  (`task docker:test:playwright:client`)

Key patterns:

- **API access** — one class per resource extending `BaseAPI`
  (`client/app/api/`), exposed through the `$api` plugin. Auth is a Sanctum
  bearer token; no cookies, no CSRF.
- **Stores** — index objects by ID (`{ [id]: item }`) for O(1) access; keep
  loading/error state alongside data. Mutate state only through the reactive
  proxy (assign fallbacks into state first, then read back — see
  `stores/devices.js`).
- **Session bootstrap** — `client/app/plugins/session.ts` awaits
  `GET /api/v2/session` on cold boot; `middleware/auth.global.ts` gates
  auth/role/consent.
- **SSO into Discourse/MediaWiki** — the SPA requests a one-time ticket from
  the API and sends the browser through `GET /auth/bridge` (Laravel) to
  establish the web session those services need.

## What Laravel still builds (`vite.config.js`)

Only the assets for surfaces Laravel still serves:

- `resources/global/{js,css}` — the embeddable stats widgets partners iframe
  (`/outbound/info/...`, `/group/stats/...`, `/party/stats/...`,
  `/admin/stats/1|2`) and shared wiki styling. jQuery + Bootstrap 4 only —
  no Vue.
- `resources/wiki/{js,css}` — the MediaWiki skin assets (also copied to the
  unhashed legacy URLs `/css/wiki.css`, `/global/css/app.css` that the wiki
  references).

The old Blade + Vue 2 frontend (`resources/js`, `resources/sass`, ~200 Blade
views) was removed at the Phase F cutover of the Nuxt migration; see
`docs/nuxt-migration/` for the design and cutover records.

---

## API calls

The v2 REST API is documented at `/apiv2/documentation` (Swagger UI,
auto-generated from annotations).

The SPA authenticates with a Sanctum bearer token from
`POST /api/v2/auth/login`. The legacy per-user `api_token` column is still
accepted (`Authorization: Bearer ...`, `?api_token=`, or body field) for
external integrations (Zapier, TRP, RepairTogether); users can request
access via the project contact.
