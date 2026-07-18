# SPA-vs-live parity audit

Status: IN PROGRESS. Findings below are **verified** (each was independently
re-checked by a second agent that defaulted to rejecting the claim).

## Why this exists

Page-by-page eyeballing repeatedly failed: it "validated" pages that were in
fact wrong, it cannot detect a wrong auth model at all (a mis-gated page looks
fine in isolation), and it trusted in-code comments as evidence. The parity
process is now mechanical and repeatable.

## The method (repeatable)

1. **Capture** — `task parity:capture`
   Drives BOTH systems through the SAME page list, at desktop (1440x900) and
   mobile (390x844), logged-out and logged-in, dismissing live's cookie banner,
   writing matched pairs to `parity-shots/<viewport>/<slug>__{dev,live}.png`.
   - Harness: `client/parity/capture.spec.js`, config `playwright.parity.config.js`
     (separate testDir so CI never runs it; it asserts nothing).
   - Live is READ-ONLY: log in, navigate, screenshot. Never submits a form.
   - Live credentials come from `PARITY_LIVE_EMAIL` / `PARITY_LIVE_PASSWORD`
     in the environment and are never written to disk or committed:
     `PARITY_LIVE_EMAIL=... PARITY_LIVE_PASSWORD=... task parity:capture`
     Without them, live logged-in pages are skipped.
2. **Diff** — a workflow with one agent per pair, plus a second agent that
   independently verifies every claimed difference and rejects data-only
   differences (production vs seeded data) and the Nuxt DevTools badge.
3. **Auth model** — a workflow that diffs every SPA page's `definePageMeta`
   against the LEGACY route's middleware group (ground truth) and verifies each
   verdict empirically against live with a logged-out request
   (302 -> /login means gated).

Legacy source of truth: `git show 07e6abd7cc^:<path>` (last commit with the
Blade + Vue2 frontend).

## CONFIRMED: auth model is wrong (systemic)

Live, logged out: `/fixometer` -> 302 `/login`; `/group/all` -> 302 `/login`.
Legacy `routes/web.php` puts `Route::prefix('fixometer')` (line 333) INSIDE
`Route::middleware('auth','verifyUserConsent','ensureAPIToken')` (line 273).

`client/app/pages/fixometer.vue` carries a comment asserting *"Public, no auth
gate"* — this is factually wrong. Pages are rendering to logged-out users that
live gates behind login. Full per-page results pending from the auth workflow.

## CONFIRMED visual differences (28, from 6 logged-out pairs)

Grouped by ROOT CAUSE — these are global-layer defects, which is why "the
styles are completely wrong" on every page at once.

### A. Link colour token (every page)
Live: inline links are **black + underlined**. Dev: **teal `rgb(3,148,166)`**
(`$brand`). The SPA has no link-colour override, so the Bootstrap 5 themed
default applies. NB legacy BS4 also sets `primary: $brand`, so live's black
links come from a component-level override that has not been identified yet —
find it before "fixing" the token.

### B. Wrong header on logged-out pages (landing, login, register; both viewports)
Live: centred logo + **impact-stats bar** (Items fixed / CO2e / Waste / Events
held), no auth links. Dev: generic navbar, logo left + "Sign in"/"Join
Restarters" right, no stats bar. The stats bar is `includes/info.blade.php`
(`#logostats-header`, `d-none d-md-block` so desktop-only).

### C. Container width (login, register; desktop)
Live cards are inset ~185px and centred; dev ~74px, near edge-to-edge.

### D. Form-control styling (all forms)
Live inputs: white fill + thick black border (legacy `_forms.scss`:
`border: 1px solid $black !important`, 3px on focus). Dev password field: fill
equals the page background (245,247,250), thinner grey border, and inconsistent
with its own email field.

### E. Cookie-consent banner MISSING (every page)
Live shows a fixed GDPR cookie bar ("Cookie settings" / "OK"). Dev shows none.
Potential legal/compliance gap — confirm whether the SPA has any consent
mechanism at all.

### F. Footer language selector missing (landing, both viewports)
Live ends with a language selector ("English" + globe). Dev has no footer.

### G. Register is a 4-step wizard; dev is one long form (HIGH)
Live: "Step 1 of 4" + NEXT STEP, one card per step, skills as **chip/button**
tap targets, plus helper text "please select at least one if you'd like to host
events". Dev: all four steps flattened onto one page, skills as plain
checkboxes, "COMPLETE MY PROFILE". This is a different product, not a restyle.

### H. Login welcome panel missing its "Find out more" CTA link.

### I. Mobile header does not collapse (login mobile) — dev renders logo + both
auth links inline where live shows only the centred logo.

## Backlog (fix by class, not by page)

1. Auth gating — apply the legacy middleware map to every page (await workflow).
2. Global layer: link colour, form-control, container width.
3. Logged-out header (stats bar; needs a global-stats API) + footer language selector.
4. Cookie-consent banner.
5. Register wizard + chip skill picker.
6. Re-run `task parity:capture` and re-diff to prove each fix landed.

## Not yet covered
- Live **logged-in** pairs (dashboard, fixometer, group/all, party/all,
  group/nearby): dev shots captured, live shots need credentials.
- Detail pages (`group/view/{id}`, `party/view/{id}`) — need per-system ids.
