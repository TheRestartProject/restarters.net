# API security review

Careful review of the `/api/v2` (and residual `/api` v1) surface plus the
web-route surface, after the Nuxt cutover. Conducted controller-family by
family; every finding below was confirmed against the actual code, and the
severe ones cross-checked against the pre-migration Blade controllers to tell
genuine regressions from faithful legacy parity.

Legend: **status** = ✅ fixed here · ⚠️ needs a product/behaviour decision ·
📋 reported (hardening / lower priority) · ℹ️ by-design (verified, no action).

## Fixed in this pass

### C1 ✅ CRITICAL — IDOR: any host could hijack/overwrite any device
`PATCH /api/v2/devices/{id}` (`DeviceController::updateDevicev2`). The
permission check used the **body** `eventid` (an event the caller legitimately
hosts) while the device was loaded by the **URL** id with no check that the
device belonged to that event — then `update()` reassigned the device to the
caller's event and overwrote every field. Device ids are sequential/enumerable,
so a host of event A could `PATCH /devices/9999` (a stranger's device on event
B) with `eventid=A` and steal/vandalise it. `deleteDevicev2` already did this
correctly (derives the event from the device).
**Fix:** also require edit permission on the device's *current* owning event
before mutating. Test: `APIv2DeviceUpdateAuthTest`.

### H1 ✅ HIGH — Open redirect in the SSO bridge
`GET /auth/bridge?redirect=…` (`BridgeController::safeRedirect`). The allowlist
used `str_starts_with($target, $prefix)` against bare origin strings, so
`https://<frontend-origin>.attacker.com/phish` passed (it literally starts with
the frontend origin). Worse, the redirect fires immediately after establishing
a real web-login session — a strong phishing primitive.
**Fix:** match on an origin/path boundary (`=== prefix`, or continues with `/`
or `?`). Test: `SsoBridgeTest::testRedirectAllowlistBlocksSuffixBypass`.

### M1 ✅ MEDIUM — Password-reset token replayable within its 24h window
`POST /api/v2/auth/password/reset` (`AuthController::resetPasswordv2`) set the
new password but never rotated `recovery`/`recovery_expires`, so the emailed
link stayed valid for 24h — an intercepted/forwarded link could be replayed to
re-take an already-reset account. `updateMyPasswordv2` already rotates it.
**Fix:** rotate the recovery token on reset (single-use). Test:
`AuthEndpointsTest::testResetPasswordTokenCannotBeReplayed`.

### H2 ✅ HIGH — Event read endpoints leaked unmoderated-group events to anyone
`GET /api/v2/events/{id}`, `/attendees`, `/devices`
(`EventController::getEventv2` / `attendeesv2` / `devicesv2`) did only
`Party::findOrFail` with no visibility gate, so anonymous users could read full
venue / exact lat-long / online-meeting link / attendee names / device notes
for events on brand-new, un-vetted (spam-risk) groups. The legacy
`PartyController::view` gated this with `Fixometer::userHasViewPartyPermission`.
**Fix (approved):** each endpoint now gates on
`userHasViewPartyPermission($id, $request->user()?->id, $party)` → 404. Events
on *approved* groups stay fully public; only unapproved-group events are hidden
from non-host/-admin/-coordinator callers — exact legacy behaviour. Test:
`APIv2EventVisibilityTest`.

## Needs a product / behaviour decision (not changed)

### M2 ⚠️ MEDIUM — Public group directory leaks unapproved groups + PII
**Decision: left public for now** (product to confirm the moderation intent for
the public map before changing).
`GET /api/v2/groups/names`, `/summary`, `/{id}`
(`GroupController::listNamesv2` / `listSummaryv2` / `getGroupv2`) filter only on
`archived_at`, not `approved` — so not-yet-moderated groups (name, exact
lat/lng, and via `getGroupv2` phone/email/description/shareable_link) are public,
and `GroupMembershipController::joinv2` lets a user join one. Historically
`approved` mainly gated *external publishing* (Discourse/WordPress), so this may
be intentional-but-risky — confirm whether the public map should show
unapproved groups before changing.

## Reported — hardening / lower priority

- **M3 📋** `GET /api/v2/users/{id}/repair-directory-options`
  (`getRepairDirOptionsv2`) has no gate beyond being logged in, so any user can
  read anyone's Repair-Directory role; the legacy `getProfileEdit` restricted
  this to self / Administrator / RD admins. Low-sensitivity (one role int).
  Fix: add the self/admin/RD-admin gate. *(Write path is correctly gated.)*
- **M4 📋** `GET /api/groups/` (v1, `getGroupList`) dumps the entire raw groups
  table (all columns, approved or not) to any authenticated user; marked "not
  used but worth keeping". Restrict to Administrator + a Resource, or remove.
- **M5 📋** Dead draft-image path in `createDevicev2`: a client-supplied `id`
  copies images from an arbitrary device with no ownership check (IDOR). Dead
  from the official client (never sends `id`) but live server-side. Remove the
  block or verify ownership.
- **M6 📋** tus uploads (`/api/tus`, unauthenticated by design): (a) files are
  written under a flat dir keyed by the *client-declared* filename, so two
  sessions choosing the same common name (`photo.jpg`) collide — an attacker can
  race a victim's upload to swap image bytes (still MIME-valid, no RCE); (b) no
  expiry sweep is ever run → unbounded disk-fill DoS. Namespace the path by
  upload key; schedule `handleExpiration()`.
- **L1 📋** `/api/v2/alerts` PUT/PATCH lack route-level `auth:sanctum,api`
  (in-method `getUser()` + `hasRole` still protect them today — defence-in-depth
  gap only). Device/event *mutation* routes have the same structural gap.
- **L2 📋** No dedicated throttle on: `email-available` (user enumeration at
  300/min/IP — outside the stricter `throttle:auth` group its siblings use),
  the maps proxy (authed users can run up the Google API bill), and the
  mass-notification endpoints (`request-review`, `invites` — host-only, so
  abuse-of-trust rather than anonymous).

## Verified clean / by-design (no action)

- **Network logo upload** (`uploadLogov2`): per-network authz, image_upload flag,
  ≤2MB + MIME sniff; the tus `upload_key` is a **cache** lookup (not a path), so
  no traversal; output filename is server-generated — no overwrite/traversal.
- **web catch-all redirect** (`/{any?}`): target origin is a fixed config value;
  user input only ever appended as a subpath → not an open redirect; PHP rejects
  CR/LF header injection.
- **RoleController::updateRolePermissionsv2** (privilege-escalation-critical):
  admin-gated both ways, transactional, `exists:` validation, parameterised.
- **Reference-data CRUD** (brands/categories/skills/group-tags/roles): every
  write checks `hasRole('Administrator')` in-method *and* is route-gated.
- **Maps proxy / Discourse proxy**: hardcoded upstream host, params passed as
  query values — not SSRF.
- **`api_token`**: in `User::$hidden`; never returned in any response.
- **`getPublicProfilev2`** (the profile endpoint added in the G6 pass): returns
  name/avatar/role/location/groups/skills/bio only — **not** email; matches the
  always-public legacy Blade profile. No new leak.
- **Mass assignment**: all mutation paths use explicit validated field lists,
  never `$request->all()`; `role`/`api_token` excluded from `$fillable`.
- **SQL injection**: list/sort/filter params go through Eloquent bindings;
  `sort` whitelisted where raw-ish (`listUsersv2`). None found.
- **`admin/preview-deploy`**, **export/**, **calendar/**, **stats** web routes:
  admin-gated or aggregate-only / per-row visibility-checked. No PII leak.

## Systemic note

The `api` guard's `?api_token=` credential is a plaintext token compared with a
raw `WHERE api_token = ?` and passed in the query string (log/cache exposure).
This predates the migration (Zapier/legacy integrations) and no reviewed v2
endpoint introduces it, but it's worth tracking for eventual migration to
hashed Sanctum tokens.
