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

## Phase C

### GET /api/v2/users/me/events (C2/C3) doesn't exist yet, and its exact per-event shape is undocumented

api-contracts-phase-c.md C2 describes the endpoint only in prose: the union
`PartyController::index()` builds today ("my" events via `Party::forUser`,
nearby upcoming events via `upcomingEventsInUserArea()` when the user has a
location, and other approved upcoming events not already included), each
event tagged `nearby`/`all` "as the Blade does" - it doesn't give a JSON
example. The route doesn't exist in `routes/api.php` on this branch.

**Requested addition**: land the route per the contract doc's description,
returning the v2 event resource (C1a) per row plus the two boolean tags
(absent/false on a "mine" row).

**Client stub**: `EventAPI#myEvents()` and `stores/events.js#fetchMyEvents()`
are written against the documented shape. `pages/party/index.vue` buckets
`myEvents.data` client-side into mine (upcoming/past, filtering out any row
with `nearby`/`all` truthy) and an "other events" section (nearby/all tabs);
until the endpoint exists, `myEvents.loading` never resolves and the page
shows its loading skeleton indefinitely rather than failing silently.

### POST/DELETE /api/v2/events/{id}/attendees/me (C1c) don't exist yet

Documented in api-contracts-phase-c.md C1c: `POST` RSVPs
(`{data:{attending, already_attending, prompt_follow_group}}`), `DELETE`
cancels (`{data:{left:true}}`). Neither route exists in `routes/api.php` on
this branch.

**Requested addition**: land both routes per the contract doc, including the
judgment call that `POST` also resolves a pending hash-invite for the caller
so the in-app RSVP button doesn't need to know about the hash.

**Client stub**: `EventAPI#attend()`/`#unattend()` and
`stores/events.js#attend()`/`#unattend()` are written against the documented
shape, with the optimistic-update-then-revert-on-failure pattern already
established by `stores/groups.js#join()`/`leave()`. `EventCard.vue`'s RSVP
button will 404 and revert (showing a toast) until the routes land.

### No field on the event resource (or `GET /api/v2/users/me/events`) says whether the current user hosts the organising group

Needed for the "Hosting" badge called for in the Phase C task brief
(EventCard.vue). api-contracts-phase-c.md C1a's `attending` addition is
per-user, but there's no equivalent per-user "my role in this event's
group" field, and `GroupSummary` (embedded as `event.group`) carries no
role either.

**Requested addition**: none proposed - this is a "nice to have" badge, not
worth a bespoke endpoint. If a per-event/group role ever gets added for
other reasons (e.g. C1d's volunteer-management work), the client should use
it here too.

**Client stub**: `pages/party/index.vue` derives `hostedGroupIds`
best-effort from `GET /api/v2/dashboard`'s `your_groups[].role` (the same
already-fetched, already-imperfect source `stores/groups.js` uses for "mine"
membership - see the B4 entry above), filtered to `role === 3` (Host, per
`app/Role.php`). This only catches groups in the user's `your_groups` list
(dashboard-capped), not every group they host - an acceptable approximation
for a badge, flagged the same way B4's membership gap is.

### /party/all and /party/all-past have no working legacy implementation to port

`routes/web.php` wires them to `PartyController::allUpcoming`/`allPast`,
neither of which exists on the controller - confirmed pre-existing dead
routes (api-contracts-phase-c.md C2, judgment call 5), not a migration gap.

**Client build**: built as new, minimal pages per the contract doc's
proposal - filters over the same `GET /api/v2/users/me/events` list
(deduped by id, since a row could in principle carry both a mine identity
and a stray `nearby`/`all` tag): `/party/all` = approved, not-yet-finished
events; `/party/all-past` = finished events. Each gets a single free-text
`EventFilters.vue` search box (title/location/group name substring match) -
deliberately not a port of `GroupEventsScrollTableFilters.vue`'s
title/country/date-range search, which needs fields and UI out of scope for
this minimal build.

### C3 (/party/view/[id]): the v2 Event resource has no `permissions` object, unlike Group's

`GET /api/v2/groups/{id}` returns `permissions: {can_edit, can_demote,
can_see_delete, can_perform_delete}`, computed server-side from
`Fixometer::userHasEditPartyPermission()`-equivalent group logic
(`GroupController`). The v2 Event resource (`Http\Resources\Party`) has no
equivalent, so the client can't exactly mirror
`Fixometer::userHasEditPartyPermission()`/`userHasDeletePartyPermission()`/
`userCanApproveEvent()`, which additionally check "is this user a
NetworkCoordinator for a network *this event's group* belongs to" - no
endpoint exposes network-coordinator-to-group membership at all today.

**Requested addition**: a `permissions` object on the v2 Event resource
(`{can_edit, can_delete, can_moderate}`), computed the same way Group's is -
this would let the client drop its approximation entirely and also fix the
false-negative described below.

**Client stub**: `pages/party/view/[id].vue` approximates canedit/candelete
as `hasRole('Administrator') || hostedGroupIds.includes(event.group.id)`
(the latter from `GET /api/v2/dashboard`'s `your_groups[].role === 3`, the
same capped-at-5 best-effort source `pages/party/index.vue` already uses).
This deliberately does NOT grant NetworkCoordinators edit/delete access for
groups in their network but which they don't host directly - a safe
false-negative (the Edit/Delete/Invite buttons stay hidden for some
legitimate NCs) rather than showing a control that would 403, but it is a
real behaviour gap versus the Blade page for that specific role.

### C3: invite-button visibility is now stricter than the legacy behaviour it replaces

`EventActions.vue` shows "Invite volunteers" to any attending user
(`isAttending && upcoming`, no host/edit check at all in the `!canedit`
branch), but api-contracts-phase-c.md C1d gates `POST
/api/v2/events/{id}/invites` to host/NC/admin. Showing the button to a
plain confirmed attendee would just produce a 403.

**Client build**: `pages/party/view/[id].vue` and
`components/events/EventAttendees.vue`'s invited-tab link both gate on
`canedit` (the same approximation above) instead of `isAttending`. This is
a deliberate behaviour change from the legacy page, not a bug - flagged in
case product wants attendees (not just hosts) to be able to invite others,
in which case the server gate would need loosening to match, not the
client tightened to match it.

### C3: no endpoint lists an event's existing photos

`Http\Resources\Party` has no `images` field, and api-contracts-phase-c.md
C1f only adds `POST`/`DELETE .../images` (upload/delete), not a `GET` list.
The Blade view page gets its gallery from `PartyController::view()`'s
inline `$images` query, which has no API equivalent.

**Requested addition**: none needed for upload/delete (C1f already covers
it), but nothing in the current API can render the *existing* photo
gallery client-side.

**Client build**: the event photo gallery (`EventImages.vue`/
`EventImage.vue`) is not built in C3 - deferred until either a list
endpoint lands or `image_url`s start appearing on another already-fetched
resource. Same treatment for the Discourse-thread link
(`EventDetails.vue`'s `discourseThread` prop) and the finished-event
"share stats" embed-code modal (`event-share-stats.blade.php`) - neither
field/route is in the v2 event resource or the contract doc, so both are
left for a later slice rather than guessed at.

### C3: `DELETE /api/v2/events/{id}/volunteers/{idevents_users}` (C1d) is written against a documented-but-unconfirmed shape

Unlike C1b/C1c (explicitly `NEW`), C1d's per-row volunteer removal is
described in prose alongside a naming judgment call (route param
`{idevents_users}`, not `{iduser}`, so manually-added volunteers with no
`user` id are still addressable) rather than pinned as committed.
`components/events/EventAttendees.vue`'s "Remove" button (shown when
`canedit`) and `stores/events.js#removeAttendee()` are wired against that
proposed shape; if the server lands a different param name, only
`EventAPI.js#removeVolunteer()`'s URL needs to change.

### C3: calendar links (google/yahoo/webOutlook/ics) are generated client-side, not fetched

No endpoint returns `generateAddToCalendarLinks()`'s output, and none is
needed - `composables/useCalendarLinks.js` ports the four
`spatie/calendar-links` generators directly from fields already on the v2
Event resource (`title`/`start`/`end`/`description`/`location`/`online`),
relying on `config/app.php`'s `timezone => 'UTC'` to skip timezone
conversion (the legacy generator's inputs are UTC `DateTime`s for the same
reason). One deliberate deviation: the `.ics` file's `UID` uses
`event-{id}-{start}` instead of porting `Ics::generateEventUid()`'s
PHP-`md5()`-based one, since there's no MD5 in the browser (Web Crypto only
offers SHA-*) and nothing asserts byte-identical UIDs across the two
systems - this UID only helps a calendar app deduplicate a re-added event.

### C4: no `auto_approve` field on the Group resource (or anywhere client-reachable)

`GET /api/v2/groups/{id}` (`Http\Resources\Group::toArray()`) does not
expose `Group::getAutoApproveAttribute()` (true iff every network the group
belongs to has `auto_approve_events` set) - confirmed by reading the
resource directly, no `auto_approve` key anywhere in its `$ret`.
`NetworkSummary` (the nested `event.group.networks[]`/`group.networks[]`
shape) doesn't expose each network's `auto_approve_events` flag either, so
it can't be recomputed client-side from the group's network list.

Legacy's create/edit form (`EventAddEdit.vue`) uses this to (a) decide
which of two "before you submit" notices to show
(`events.before_submit_text` vs `_autoapproved`) and (b) seed
`eventApproved` optimistically on create, before the server's first
response confirms it.

**Requested addition**: either `auto_approve` on the Group resource
(mirroring the `$appends` attribute that already exists on the Eloquent
model) or `auto_approve_events` on `NetworkSummary`.

**Client stub**: `components/events/EventForm.vue` always shows the
non-auto-approved copy (`events.before_submit_text`) and never optimistically
marks a freshly-created event as approved - a safe, conservative default
(the server's own `autoapprove` branch in `createEventv2` still approves
the event correctly regardless of what the client displayed; this is a
copy-accuracy gap, not a functional one).

### C4: `groupsInChargeOf()`'s NetworkCoordinator branch isn't reproducible client-side

`User::groupsInChargeOf()` (the group list `PartyController::create()`
passes to a non-admin) is: groups the user hosts directly
(`users_groups.role === HOST`, now available via `GET /api/v2/users/me/groups`
- see below) **plus**, for a NetworkCoordinator, every group belonging to
every network they coordinate. There's no endpoint that returns "the
networks I coordinate" or "groups in networks I coordinate" from the
caller's own identity.

**Requested addition**: `GET /api/v2/users/me/networks` (networks the
caller coordinates) or a `coordinated_network_ids` field on
`GET /api/v2/users/me`, either usable to then call the existing
`GET /api/v2/networks/{id}/groups`.

**Client stub**: `pages/party/create/[[group_id]].vue` and
`pages/party/duplicate/[id].vue` source the group picker from
`GET /api/v2/users/me/groups` filtered to `role === HOST` only, for any
non-Administrator. A NetworkCoordinator who doesn't personally host a group
will therefore see the `cantcreate` page (if their top-level role is also
`Host`) or an empty group picker (otherwise) on `/party/create`, even
though the legacy Blade page would show them every group in their
network(s) - a false-negative, same safe-direction trade-off as
`canedit`/`canApprove` elsewhere in this phase, documented rather than
worked around with a guess.

### C4: `GET /api/v2/users/me/groups` has landed since the B4/C2 notes above were written

Confirmed by reading `routes/api.php` (`Route::get('/groups',
[API\UserController::class, 'getMyGroupsv2'])` under `/users/me`) and
`UserController::getMyGroupsv2()` directly: `{data:[{id, name, role,
archived, image_url}]}`, uncapped, one row per group the user belongs to.
This is exactly the endpoint the "No endpoint lists every group a user
belongs to" gap above (B4/B5/C2) asked for - `stores/groups.js#fetchMine()`
and `pages/party/index.vue`'s `hostedGroupIds` (both still reading the
capped-at-5 `GET /api/v2/dashboard`'s `your_groups`) were out of scope for
this C4 slice to touch, but are now straightforward follow-ups.

**Client build**: `client/app/api/UserAPI.js#myGroups()` (new) and
`pages/party/create/[[group_id]].vue`/`pages/party/duplicate/[id].vue` use
it directly (filtered to `role === HOST`) for the event-create group
picker - the one place in this slice that needed an uncapped "groups I
belong to" list.

### C4: event photo upload (C1f) is write-only from the client, same as the C3 gap for the gallery

`POST /api/v2/events/{id}/images` (confirmed implemented -
`EventAttendanceController::uploadImagev2`) returns `{data:{image_url}}`
for the single newly-uploaded photo, but (per the C3 gap above) there is
still no endpoint listing an event's *existing* photos. `pages/party/edit/[id].vue`
therefore renders `TusImageUpload` without a `current-image-url` preview
and without a gallery below it - just a one-shot "photo added"/"upload
failed" confirmation message, unlike `group/edit/[id].vue`'s image field
(which can show the group's current single image because `GET
/api/v2/groups/{id}` returns it).

### C4: vue-datepicker-next's rendered `<input>` doesn't receive `class`/`data-testid` passthrough

`design.md` §2 picks `vue-datepicker-next` for the date field
(`b-form-datepicker` has no bootstrap-vue-next equivalent). Its default
export renders its own markup and does not forward arbitrary attributes
(`data-testid`, `class`) placed on the `<DatePicker>` tag down to its
internal `<input>` - confirmed by inspecting its rendered output directly
(neither attribute appears anywhere in the DOM). `EventForm.vue` therefore
only gets red-border (`is-invalid`) styling and a stable e2e hook on the
*native* `<input type="date">` fallback (shown on narrow viewports,
mirroring `EventDatePicker.vue`'s existing responsive dual-input pattern) -
the desktop `vue-datepicker-next` widget itself has no red-border state,
though the error text underneath is still shown regardless of viewport.
Not a server-side gap - recorded here because it constrains what a future
Playwright spec can assert against the desktop date field.

### C5: device photo upload can't happen before the device exists (add-mode has no photo tab, same as C4's event-create page)

`POST /api/v2/devices/{id}/images` requires a real, already-created device
id in the route - there's no equivalent of the legacy negative "draft id"
+ `FixometerFile::findImages()`/`Xref::copy()` linking that let a photo be
attached to a device that hadn't been saved yet
(`API\DeviceController::createDevicev2` does still support a client-sent
`id` field for this via `$request->input('id')`, but that's the *old*
`/device/image-upload/{id}` raw-`$_FILES` upload path, not the new
tus-based `POST /api/v2/devices/{id}/images` endpoint the client now uses
exclusively). `components/devices/DeviceForm.vue` therefore only renders
`DevicePhotos.vue` when editing an existing device, never while adding one
- mirrors C4's event-create form having no photo tab for the identical
reason.

### C5: `POST /api/v2/devices/{id}/images`'s response has no id/idxref for the new image, so the client can't optimistically add it

The response is `{data:{image_url}}` only (no `id`/`idxref` field), but
deleting a photo (`DELETE /api/v2/devices/{id}/images/{idxref}`) is keyed
by the `Xref` row id, which only a full device re-fetch would surface.
`stores/devices.js#uploadDeviceImage()`/`#deleteDeviceImage()` therefore
force-refetch the whole event's device list (`GET
/api/v2/events/{id}/devices`, `{force: true}`) after every upload/delete,
rather than patching a single device's `images` array in place - correct,
but one extra round-trip per photo action. Returning the created image's
`{id, idxref, path}` in the upload response (matching the `Image` schema
already used elsewhere) would let the client patch locally instead.

### C5: `DELETE /api/v2/devices/{id}/images/{idimages}`'s route param is the Xref row id, not the Image resource's own `id` field

`Http\Resources\Image::toArray()` returns two different id-shaped fields -
`id` (`$this->idimages`) and `idxref` (`$this->idxref`) - but
`deleteImagev2()` queries `Xref::where('idxref', $idimages)`, i.e. the
route's `{idimages}` placeholder is actually satisfied by the *xref* id,
not the image's own `id`. Not a bug (verified by reading the controller
directly), but a genuine trap for any future caller of this route: the
naming strongly suggests the wrong field. `DevicePhotos.vue` deletes by
`image.idxref`, matching the legacy Vue client's `DeviceImages.vue`
(`idxref: image.idxref`) exactly - flagging here in case a future API pass
wants to rename either the param or the resource field for clarity.

### C5: no endpoint returns categories pre-grouped by cluster; grouping happens client-side

Per the contract doc's own note, `GET /api/v2/categories` (flat, with
`cluster`/`cluster_name` joined per row) and `GET /api/v2/category-clusters`
(`{id, name}` headers) both exist but nothing returns the nested
`{id, name, categories: [...]}` shape `DeviceForm.vue`'s category
`<select>` (and the legacy `DeviceCategorySelect.vue`/
`useDeviceCategorySuggestion.js` it's ported from) needs.
`stores/devices.js`'s `clusters` getter builds it client-side from the two
existing endpoints (`buildClusters()`), same judgment call the contract doc
already made for B6's category-cluster admin pages. Not requesting a new
endpoint - documenting the client-side join for anyone auditing the
category `<select>`'s data flow.

### C6: `/fixometer`'s embedded records table split out into its own page, `/device/search`

`resources/views/fixometer/index.blade.php` +
`resources/js/components/FixometerPage.vue` render the impact stats and the
powered/unpowered `FixometerRecordsTable.vue` on one page, switched by tabs.
The C6 task brief splits these into two Nuxt pages (`/fixometer` for the
impact stats + latest-repairs banner, `/device/search` for the paginated
table) - a judgment call, not a contract-doc instruction. `/fixometer` links
to `/device/search` via a new "Browse repair records" button
(`client.fixometer.browse_records`) to bridge the gap where the legacy page
had tabs on one screen.

### C6: status filter's `status` query param is the numeric `Device::REPAIR_STATUS_*` code, not the `repair_status` resource string - the legacy filter passed the wrong one

`ApiController::getDevices()`/`API\DeviceController::listDevicesv2()` both
filter `WHERE repair_status = ?` against the raw DB column, which stores
`Device::REPAIR_STATUS_FIXED/REPAIRABLE/ENDOFLIFE` (ints 1/2/3). But
`resources/js/constants.js`'s `FIXED`/`REPAIRABLE`/`END_OF_LIFE` (the
*_STR values, e.g. `'Fixed'`) are what `FixometerFilters.vue`'s status
`<select>` actually feeds into that same int-typed filter - a pre-existing
mismatch that means the legacy status filter has never actually worked
(the SQL comparison never matches a string against an int column).
`components/fixometer/DevicesSearchTable.vue` uses the correct int codes
(1/2/3) instead of reproducing the bug. Not requesting an API change -
flagging for anyone auditing why the legacy status filter appeared to do
nothing.

### C6: `GET /api/v2/stats/latest-repaired-event`'s null case has no legacy precedent

The Blade prop (`$most_recent_finished_event`) was always populated on a
database with existing data, so `FixometerLatestData.vue` never had to
render an empty state. The v2 endpoint can legitimately return
`{data: null}` (a fresh/empty database), so
`components/fixometer/LatestRepairs.vue` adds a
`client.fixometer.no_latest_repairs` empty state the legacy component never
needed - not a gap, just a new state the API's honesty about "no data yet"
exposes.

### C6: `popover_consumer()` (the CO2-equivalent explanation popover) ported but not wired up

`useCo2Equivalent.js` ports both of `mixins/co2equivalent.js`'s methods
(`equivalent_consumer`/`popover_consumer`), but `ImpactStats.vue` only uses
the former - the legacy `<b-img v-b-popover.html="popover">` info-icon
popover pattern isn't part of this task's brief and there's no equivalent
already established elsewhere in the client for a hover/click popover
bubble. `popoverConsumer()` is exported and unit-tested, ready for a future
page to wire up.
