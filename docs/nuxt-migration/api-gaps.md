# API gaps found during client work

Recorded when a client slice needs data with no documented endpoint/field
(design.md / api-contracts-phase-b.md). Each entry: what's missing, why the
client needs it, and how the client stubs around it in the meantime.

## B3 — /dashboard page

### Dashboard needs an explicit "has the user set a location" signal

`GET /api/v2/dashboard` (api-contracts-phase-b.md B1) returns `nearby_groups: []`
both when the user has no lat/lng set **and** when they have a location but
there simply are no groups nearby - the comment on the contract even says so
("[] when user has no lat/lng"). The legacy Blade dashboard could tell these
apart because `DashboardController` passed the raw `$user->location` string as
a separate prop (`location="{{ $user->location ?? '' }}"`) alongside
`$groups_near_you`; `DashboardNoGroups.vue` branches on that prop, not on the
groups array being empty.

The two states need different UI: "no location set" should show a CTA to set
one (`groups.no_groups_nearest_no_location`, which links to `/profile/edit`),
while "you have a location but nothing's nearby" should show the plain
"no groups near you" copy instead.

**Requested addition**: `GET /api/v2/dashboard` response gains a top-level
`has_location` boolean (or a `location` string, mirroring the session user
payload's shape), computed from `$user->latitude`/`$user->longitude` (or
`$user->location`) being set.

**Client stub**: `client/app/pages/dashboard.vue` reads
`dashboardStore.data?.has_location`, defaulting to `true` (i.e. treats an
absent field as "has a location", so an empty `nearby_groups` renders the
plain empty state rather than incorrectly prompting for a location) until the
field exists. `DashboardNearbyGroups.vue`'s `hasLocation` prop likewise
defaults to `true`.

### No v2 endpoint to dismiss the onboarding modal

The legacy onboarding modal (`resources/views/layouts/app.blade.php` +
`onboarding()` in `resources/js/app.js`) calls `GET /user/onboarding-complete`
(`UserController::getOnboardingComplete`) on modal close, which bumps
`$user->number_of_logins` from 1 to 2 so that `flags.onboarding` (computed in
`SessionController::indexv2` as `number_of_logins < 2`) is `false` on the next
`GET /api/v2/session` call - i.e. the modal shows once per user, on their
first proper session, and never again.

There is no v2 equivalent of this endpoint. The existing
`/api/v2/users/me/preferences` endpoints are specifically for email invite
preferences and are not a good fit for a one-off "mark onboarding seen" flag.

**Requested addition**: `POST /api/v2/users/me/onboarding-complete` (auth) -
increments `number_of_logins` the same way the legacy GET route did (or sets
a dedicated boolean, if number_of_logins gets repurposed later); response
`{data: {onboarding: false}}`.

**Client stub**: `client/app/api/UserAPI.js#dismissOnboarding()` calls the
not-yet-existing `POST /api/v2/users/me/onboarding-complete`.
`client/app/stores/session.js#dismissOnboarding()` flips
`this.flags.onboarding = false` locally *before* awaiting the API call and
swallows any failure, so the modal always dismisses for the current session
even though the "don't show it again next login" persistence doesn't yet
work server-side.

## B4 — /group (mine), /group/all, /group/nearby

### No endpoint lists all of the current user's group memberships

There is nothing equivalent to the legacy `Group::ofThisUser($user->id)` /
the Vuex `following` flag: `GET /api/v2/groups/names` returns
`{id, name, archived_at}` for *every* group with no per-group membership or
role field, `GET /api/v2/groups/{id}` (the Group resource) has UI
`permissions` flags but nothing like `is_member`, and the only place a
membership+role pair appears at all is `GET /api/v2/dashboard`'s
`your_groups`, capped at 5 (api-contracts-phase-b.md B1).

**Requested addition**: either a dedicated `GET /api/v2/users/me/groups`
(id/name/role/archived, uncapped) or a `role`/`is_member` field added to the
names index per-user.

**Client stub**: `client/app/stores/groups.js#fetchMine()` reuses
`GET /api/v2/dashboard`'s `your_groups` for `/group` (mine). Above 5 groups,
the page silently shows only the first 5 until this lands. The same gap
means `memberIds` (used to decide Join vs. Leave button state on
`/group/all` and `/group/nearby`) is only reliably populated for those same
≤5 groups plus whatever's been joined/left client-side this session - a user
in more than 5 groups may see "Join" on the All Groups tab for a group
they're actually already in.

### Names index carries no summary fields yet

`GET /api/v2/groups/names` is `{id, name, archived_at}` only (confirmed by
reading `GroupController::listNamesv2` on this branch); the
location/hosts/restarters/tags/next_event fields referenced by
api-contracts-phase-b.md's client notes ("PR #887") aren't on this branch.

**Client stub**: `client/app/pages/group/all.vue` paginates/searches/sorts
client-side over the bare names index, then hydrates full detail
(`GET /api/v2/groups/{id}`, cached in `groupsStore.details`) only for the
rows on the current page (`stores/groups.js#fetchDetails`) rather than
fetching every group. The legacy network/country/tag filters
(`GroupsTableFilters.vue`) are dropped entirely for now - that data isn't in
the names index at all - marked `TODO(PR #887)` in `pages/group/all.vue`.

### No generic user-preferences slot for the (unused) legacy column_preferences

`resources/views/group/index.blade.php` reads `session('column_preferences')`
into `$user_preferences`, but that variable is never actually passed to
`GroupsPage.vue`/`GroupsTable.vue` or read anywhere in `resources/js` -
grepping the whole legacy frontend for `column_preferences` only turns up
the one dead Blade line. There was never a real column-visibility feature to
port, and the closest existing endpoint,
`GET/PATCH /api/v2/users/me/preferences`
(`UserController::getMyEmailPreferencesv2`/`updateMyEmailPreferencesv2`), is
hardcoded to the single `invites` email-preference boolean - not a generic
key/value slot.

**Requested addition**: a generic per-user preferences endpoint (or a
`column_preferences` JSON field) if column visibility should sync across
devices.

**Client stub**: `client/app/composables/useColumnPreferences.js` persists
which optional `/group/all` table columns (location/hosts/restarters/
next_event) are shown in `localStorage`, per-browser only.

## B5 — /group/view/[id] page

### GET /api/v2/groups/{id} has no "am I a member" flag

The legacy Blade page computed `$in_group` server-side
(`UserGroups::where('group', ...)->where('user', Auth::id())->where('status',
1)->exists()`) and passed it into `GroupPage.vue` as the `ingroup` prop. The
v2 Group resource (`app/Http/Resources/Group.php`) carries `permissions` but
nothing like `is_member` - the same gap already noted for B4's `memberIds`
(there is no endpoint that maps "this user + this group" to membership at
all, aside from the capped `GET /api/v2/dashboard`).

**Requested addition**: an `is_member` (and, ideally, `role`) field on the
single-group response, gated on the authenticated user like `permissions`
already is.

**Client stub**: `stores/groups.js#fetchCurrent()` falls back to the same
session-local `memberIds` set B4 introduced, and best-effort calls
`fetchMine()` (the dashboard's capped `your_groups`) if `memberIds` is still
empty when a group is opened directly (e.g. via a shared link) rather than
via a groups list page. A user who is a member of more than 5 groups and
opens group #6 directly, without having visited `/group` or `/dashboard`
first this session, will incorrectly see a "Join" button.

### GET /api/v2/groups/{id}/stats does not exist

api-contracts-phase-b.md documents the shape
(`{group_stats, device_stats, cluster_stats, top_devices}`, mirroring
`GroupController@view`'s Blade props) but it isn't implemented server-side
yet (B2). Separately, `GET /api/v2/groups/{id}?includeStats=true` *does*
exist and returns a real (but differently-shaped) `stats` object
(`co2_total`, `fixed_devices`, `participants`, `hours_volunteered`, ... -
see `app/Http/Resources/Group.php`) - it has no `cluster_stats`/`top_devices`
equivalent at all, so it can't fully replace the documented endpoint.

**Client stub**: `GroupStats.vue` is built against the documented
`/stats` shape; `stores/groups.js#fetchStats()` fails gracefully (sets
`stats.error`, does not rethrow) so a missing endpoint shows a quiet "not
available" message instead of blocking the rest of the page.

### No shareable-link field on the Group resource

The legacy invite modal (`group-invite-to.blade.php`) has a second tab
showing `$group->shareable_link` (an accessor built from
`shareable_code`, `App\Group::getShareableLinkAttribute()`). Neither
`shareable_link` nor `shareable_code` appears on the v2 Group resource, and
there's no way to construct the URL client-side without one of them.

**Requested addition**: expose `shareable_link` (or the raw
`shareable_code`) on `GET /api/v2/groups/{id}`.

**Client stub**: `GroupInviteModal.vue` only implements the email-invite
half of the legacy modal (`POST /api/v2/groups/{id}/invites`, itself also a
B2 gap - not yet implemented server-side); the shareable-link tab is
dropped entirely rather than built against data that doesn't exist.

### No discourse_group field on the Group resource

The Blade page separately computed `$discourseGroup` from
`$group->discourse_group` (a raw model attribute, not part of the API
resource at all) and passed it into `GroupPage.vue`/`GroupDescription.vue`
as a prop to render a "Talk group" link. It isn't exposed on
`GET /api/v2/groups/{id}` either.

**Requested addition**: expose a `discourse_group` (or a ready-made
`talk_url`) field on the Group resource.

**Client stub**: the "Talk group" link is dropped from the group view page
description block until this field exists.

## B6 - /group/create, /group/edit/[id]

### GET/POST /api/v2/maps/autocomplete and .../place-details don't exist

api-contracts-phase-b.md B6 documents bearer-token v2 equivalents of the
existing session-auth `GET /maps/autocomplete` and `GET /maps/place-details`
(`MapsProxyController`, which keeps the Google API key server-side) as
"moves in this phase", but neither `/api/v2/maps/autocomplete` nor
`/api/v2/maps/place-details` exists in `routes/api.php` on this branch.

**Requested addition**: land the two v2 routes (thin passthrough to the
existing `MapsProxyController` methods, same response shape, just under
`auth:sanctum,api` instead of the web session guard).

**Client stub**: `client/app/api/MapsAPI.js` is written against the
documented v2 paths; `LocationPicker.vue` calls them for autocomplete
suggestions but catches failures silently and falls back to plain manual
text entry - which is a fully functional path anyway, since
`GroupController::createGroupv2`/`updateGroupv2` geocode the submitted
`location` string server-side (`App\Helpers\Geocoder`) rather than trusting
client-supplied coordinates. Until the v2 maps routes land, nobody sees
autocomplete suggestions while creating/editing a group, but typing a
location and submitting still works end-to-end.

### POST /api/v2/groups/{id}/images and DELETE .../images/{idimages} don't exist

Documented in api-contracts-phase-b.md B2 as `POST /api/v2/groups/{id}/images
{upload_key}` -> `{data:{image_url}}` and
`DELETE /api/v2/groups/{id}/images/{idimages}`, mirroring the tus-upload
pattern folded in from PR #868 for profile photos
(`POST /api/v2/users/me/photo {upload_key}`, already implemented and used
as the reference implementation for `TusImageUpload.vue`). Neither group
images route exists in `routes/api.php` on this branch.

**Requested addition**: land both routes, following the same
"tus uploads to the shared `/api/tus` endpoint, then a second authenticated
call attaches the upload by key" pattern as `updateMyPhotov2`.

**Client stub**: `GroupAPI#uploadImage()`/`#deleteImage()` and
`groupsStore#uploadGroupImage()` are written against the documented shape.
`pages/group/edit/[id].vue` still runs the full Uppy/tus upload flow (the
upload itself succeeds - it lands on the shared, endpoint-agnostic
`/api/tus`) but the final "attach this upload to the group" call 404s;
the page shows `client.groups.image_upload_error` inline rather than
failing silently or blocking the rest of the edit form.

### No endpoint at all for a group's audit log

`resources/views/group/edit.blade.php`'s admin-only "Group log" tab
(gated on `App\Helpers\Fixometer::hasRole($user,'Administrator')`) renders
`$group->audits` - fetched directly from the Eloquent model inside
`GroupController::edit()` (the Blade controller) and rendered via
`resources/views/partials/log-accordion.blade.php` - there is no API route,
v1 or v2, that exposes a group's audit trail at all.

**Requested addition**: `GET /api/v2/groups/{id}/audits` (auth; administrator
only), paginated, mirroring the `owen-it/laravel-auditing` records the Blade
partial already renders.

**Client stub**: none - `pages/group/edit/[id].vue` omits the audit log
entirely (design.md §6.2 B6 task brief explicitly calls this out as an
acceptable omission: "audit info OMITTED (admin-only legacy nicety)").

### createGroupv2/updateGroupv2 return a bare {id: ...}, not the usual {data: ...} envelope

Not a missing endpoint - both are already implemented and used as-is - but
worth flagging since it breaks the "all responses `{data: ...}`" convention
stated at the top of api-contracts-phase-b.md and every other v2 endpoint
this migration has touched so far (confirmed against
`tests/Feature/Groups/APIv2GroupTest.php`, which asserts
`array_key_exists('id', $json)` at the top level, not `$json['data']['id']`).

**Client stub**: `stores/groups.js#createGroup()`/`#updateGroup()`
destructure `{ id }` directly from the raw response rather than
`{ data: { id } }`, and say so in a comment so a future contributor doesn't
"fix" it to match the rest of the API by mistake.
