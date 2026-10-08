# G6 — Visual parity review: live restarters.net vs the Nuxt client

Read-only comparison of the live (Blade + Vue 2) site against the new Nuxt
SPA client, page by page, to catch behaviour and styling that the cutover
dropped.

## Method

- Headless Chromium (GPU disabled) via Playwright in the `restarters_playwright`
  container.
- Live site https://restarters.net viewed **read-only** with an existing admin
  login (view-only navigation + screenshots only; no mutations). Local client
  at the dev server, logged in as the seeded `jane@bloggs.net`.
- Matching pages captured on both frontends: `/login` (unauthenticated),
  `/dashboard`, `/group`, `/party` (events), `/fixometer`, `/profile`.
- Screenshots were compared for layout, styling and missing/broken elements.
  No production data is reproduced here — findings describe structure only.

## Findings

Classified as: **F** functional regression (fixed), **B** clear render/CSS bug
(fixed), **D** design-parity gap (reported for a product decision — the client
is a deliberate re-implementation, not a pixel clone).

### F1 — `/profile` (own) and `/profile/{id}` were completely broken  ✅ fixed
The client fetches `GET /api/v2/users/{id}`, but that endpoint did not exist,
so every profile view (own profile from the navbar avatar, and any user's
public profile) rendered the client's 404 state: *"That profile couldn't be
found."* On the live site the same page shows the user's card, biography,
skills and groups.
- Fix: added `UserController::getPublicProfilev2` + route
  `GET /api/v2/users/{id}` returning the documented PII-safe shape (id, name,
  avatar_url, role_name, location, groups, skills, biography), gated only by
  auth (any logged-in user may view any profile, matching the legacy Blade).
  OpenAPI spec regenerated. Covered by `tests/Feature/Users/APIv2PublicProfileTest`.

### F2 — `/group/all` tab label showed a raw i18n key  ✅ fixed
The "All groups" tab used `t('groups.all_groups')`, which does not exist, so it
rendered the raw key — CSS-uppercased to `GROUPS.ALL_GROUPS` in the tab bar.
- Fix: added the client-only key `client.groups.all_tab` (en + fr + fr-BE) and
  pointed `GroupsTabsNav.vue` at it (same pattern as the map tab). Added a
  label-resolution assertion to `GroupsTabsNav.spec.js` (the existing test only
  checked hrefs, which is why this slipped through).

### B1 — All `/images/*` fallback assets were 404 (broken images)  ✅ fixed
`client/public/images/` had never been created, so every placeholder that falls
back to `/images/placeholder-avatar.*` (navbar avatar, group cards, volunteers,
attendees, profile, group/event views) and the icon SVGs referenced from SCSS
(dropdown arrows, ticks, cross) were broken images.
- Fix: populated `client/public/images/` from the Laravel-side assets. As part
  of this the asset set was rationalised (see "Asset rationalisation" below).

### B2 — Cramped nav/footer links (Bootstrap 4 utility classes)  ✅ fixed
The footer links ran together ("Help & FeedbackFAQsThe Restart Project…") and
the login-layout nav showed "Sign inJoin Restarters". Cause: leftover
Bootstrap **4** spacing utilities (`mr-*`, `ml-*`, `pl-*`, `pr-*`) that do
nothing under bootstrap-vue-next's Bootstrap **5** (renamed to `me-*`, `ms-*`,
`ps-*`, `pe-*`).
- Fix: `AppFooter.vue` (`mr-3`→`me-3`), `plain.vue` (`ml-3`→`ms-3`),
  `AppNavbar.vue` (`pl-0 pr-0`→`ps-0 pe-0`, `pr-md-3`→`pe-md-3`).

### D1 — Login page welcome panel styling
Live: the "Welcome to the Restarters community" panel has the brand amber
background, both cards carry the signature offset drop-shadow, and there is a
"Find out more" link plus a top impact-stats banner. Local: plain bordered
boxes, no amber, no shadow, no "Find out more"/stats banner.

### D2 — Dashboard brand chrome
Live: hand-drawn doodles (arrows, coffee cup), a "Welcome to Restarters" hero,
a repair photo, and an orange "Getting started" call-out panel. Local: a flat
text layout with none of the decorative brand chrome.

### D3 — Fixometer page
Live `/fixometer` is the "Our Global Impact" page: a grid of teal outlined
stat cards (participants, years volunteered, powered/unpowered items, "planting
X hectares") **and** an inline searchable POWERED/UNPOWERED repair-records table
with status badges and pagination. Local shows a plain per-group summary plus a
"Browse repair records" button (the table lives elsewhere). The styled stat-card
grid and the inline table are not reproduced.

D1–D3 are visual/scope differences, not broken behaviour, and are left for a
product/design decision rather than changed here.

## Not-a-bug (verified)

- The grey box first seen on `/group` and `/party` list areas was the
  `placeholder-glow` **loading skeleton**, not a broken table — with a proper
  wait the real sortable table/list renders. (The initial screenshots were
  taken mid-load.) No change needed; noted so it isn't re-flagged.

## Asset rationalisation

The copied assets were a mishmash (SVG + PNG + JPG, mixed CRLF/LF, mixed
naming). Standardised as:
- **Raster → WebP**: `placeholder-avatar.png`→`.webp` (8K→4K) and
  `onboarding_{1,2,3}.jpg`→`onboarding-{1,2,3}.webp` (~280K→~38K each), via GD.
- **Icons stay SVG**: the six UI icons are vector CSS `url()` backgrounds where
  raster would be a regression (blurry on hi-DPI, larger) — the deliberate
  "unless there's a reason not to" exception. Their line endings were
  normalised to LF.
- Naming standardised on kebab-case; references updated across the client.
