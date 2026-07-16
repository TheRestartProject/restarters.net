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

## Phase D

### D1: `GET /api/v2/users/{id}` (D2's task) doesn't exist yet, so `/profile/edit/[[id]]` has no way to show the target user's name when an Administrator edits someone else

The legacy `profile-edit.blade.php` gets `$user` (the target) server-rendered
straight from `UserController::getProfileEdit($id)`, so the page heading and
"View user profile" link always know who they're talking about. The Nuxt
page has no such prop - it only has the numeric id from the route - and the
only endpoint that would supply the name (`GET /api/v2/users/{id}`, this
task brief's own D2 row) isn't implemented server-side yet (confirmed by
reading `routes/api.php` directly: no `Route::get('{id}', ...)` under
`/users`). `stores/profile.js#fetchTargetUser` calls it anyway and swallows
the failure; the page falls back to a generic "Editing user #{id}" heading
(`client.profile.editing_user_fallback`) until D2 lands, at which point the
existing call starts succeeding with zero client changes needed.

### D1: the client deliberately does NOT reproduce PR #868's "me-only tabs stay visible (and silently edit the wrong account) when an Administrator opens someone else's edit URL" behaviour

Every `users/me/*` endpoint (email preferences, calendars, language, profile
info, skills, password, photo, delete-account - confirmed by reading
`app/Http/Controllers/API/UserController.php` directly) always operates on
`Auth::user()`; none of them take an id parameter. But the legacy Blade page
(`resources/views/user/profile-edit.blade.php`) shows the Profile/Account/
Email-preferences/Calendars tabs whenever `Fixometer::hasRole(Auth::user(),
'Administrator') || Auth::id() == $user->id` - so an Administrator who opens
`/profile/edit/{someone_else}` sees those tabs and, if they save one, ends up
silently editing their *own* account instead of the target's. This is a
pre-existing footgun in PR #868's client, not a documented feature, and this
port does not reproduce it: `ProfileTabs.vue` gates the Profile tab
(ProfileInfoTab/SkillsTab/ProfilePhotoTab) and the self-scoped parts of the
Account tab (PasswordTab/LanguageTab/DeleteAccountTab) plus the Email
preferences/Calendars tabs and the Notifications nav link on `isOwnProfile`
alone, not `isOwnProfile || isAdmin`. The two genuinely id-scoped families -
the Account tab's AdminSettingsTab section and the standalone Repair
Directory tab - keep exactly the legacy visibility rule, since they are safe
to use against someone else's id. No new endpoint is being requested here;
this is a client-side judgment call, recorded per this file's own convention
of flagging behaviour a future API/audit pass might trip over. See
`stores/profile.js`'s and `ProfileTabs.vue`'s doc comments for the same note
in context.

### D1: no session field says whether the acting user may act on the Repair Directory at all - the client derives it from `GET /users/{id}/repair-directory-options`'s per-option `disabled` flags instead

The legacy Blade page gates the Repair Directory tab with
`@can('viewRepairDirectorySettings', Auth::user())` - a check purely on the
*acting* user (`UserPolicy::viewRepairDirectorySettings`: is a Repair
Directory Regional or Super Admin), independent of which profile is being
edited. `GET /api/v2/session`'s user payload has no equivalent field
(`repairdir_role` isn't exposed there - confirmed by reading
`SessionController::userPayload` directly), so the client cannot make this
same decision without an extra round-trip. It reuses
`GET /api/v2/users/{id}/repair-directory-options` (needed anyway to render
the tab's own `<select>`) for this: `UserPolicy::changeRepairDirRole` only
ever returns `true` for a Regional/Super Admin, so "at least one returned
option has `disabled: false`" is a reliable, id-independent proxy for
"the acting user may see this tab at all" - see
`stores/profile.js`'s `repairDirectoryVisible` getter. Not requesting a new
session field - documenting the derivation for anyone auditing why the tab's
visibility check looks indirect. The same fetch also backs the page-level
access gate for the "neither the profile owner nor an Administrator" case,
mirroring `UserController::getProfileEdit`'s 404 guard
(Administrator/Repair-Directory-Regional-or-Super-Admin/self).

### D1: `AdminSettingsTab`'s "Choose role"/"Preferences:"/"Permissions:" labels were hardcoded English strings in the legacy component, not `__()` calls

`resources/js/components/AdminSettingsTab.vue` has `<option :value="null"
disabled>Choose role</option>` and bare `<label>Preferences:</label>` /
`<label>Permissions:</label>` - none run through the translation helper, so
non-English users saw them in English regardless of locale. Not an API gap,
but flagged here since it would otherwise look like an accidental omission:
the Nuxt port fixes it with real i18n keys
(`client.profile.preferences_label`/`permissions_label`; the role select's
placeholder was dropped rather than translated, since `BFormSelect` renders
its own first option from the `roles` list with nothing pre-selected,
matching every other select in this client rather than adding a synthetic
placeholder option the legacy markup didn't semantically need).

### D2: `GET /api/v2/users/{id}` still doesn't exist - `/profile/[id]` (and `/profile`) are built against a documented PII-safe shape, with graceful 404/error handling

Confirmed again by reading `routes/api.php` directly on this slice (no
`Route::get('{id}', ...)` under `/users`) - this is the same gap D1 already
flagged for the edit-page heading, now blocking the actual public profile
page (`resources/views/user/profile-new.blade.php` via
`UserController::index($id)` is the functional spec). The documented shape
`stores/users.js#fetchPublicProfile` / `components/profile/
PublicProfileView.vue` are built against, matching this task's own brief
("PII-safe resource: name, avatar, groups, skills, bio only") plus the two
extra fields the legacy view also displays publicly (role name, location):

```
GET /api/v2/users/{id}
200 {
  "data": {
    "id": 42,
    "name": "Jane Fixit",
    "avatar_url": "thumbnail_xyz.png" | null,
    "role_name": "Restarter",
    "location": "London" | null,
    "groups": [{ "id": 3, "name": "Chiswick Fixers" }, ...],
    "skills": [{ "id": 5, "name": "Electronics repair" }, ...],
    "biography": "..." | null
  }
}
404 { "message": "..." }   // user not found
```

`avatar_url` deliberately matches the bare-filename shape `GET /api/v2/
session`'s `user.avatar_url` already uses (`useUploadedImageUrl.js` handles
both that and an already-absolute URL, so either works). `role_name` and
`groups`/`skills` id+name pairs match `UserAdmin`'s and `getMySkillsv2`'s
existing field-naming conventions respectively, for consistency with the
rest of the v2 API rather than inventing a third convention. No
`can_edit`/permission field is requested: "am I allowed to edit this
profile" is `isOwnProfile || hasRole('Administrator')`, computable
client-side from the session alone, same as the legacy Blade's own
`$user->id == Auth::id() || Fixometer::hasRole(null, 'Administrator')`
check.

Until this lands, `PublicProfileView.vue` shows a real "not found" state on
a 404 and a generic load-error state on anything else (unlike
`stores/profile.js#fetchTargetUser`'s best-effort swallow for D1's heading -
a working profile page has no reasonable fallback content, so surfacing the
failure honestly is correct here, not a workaround to remove later).

### D2: `/profile` (own profile, no id) is a new page, not just `/profile/[id]`

`pages/profile/edit/[[id]].vue` (D1) already links "View profile" to the
bare `/profile` URL for one's own profile (mirroring `UserController::
index($id = null)` defaulting to `Auth::id()`, and `routes/web.php`'s
separate `Route::get('/', [UserController::class, 'index'])->name
('profile')` vs `Route::get('/{id}', ...)`) - so this slice adds
`pages/profile/index.vue` alongside `pages/profile/[id].vue`, both thin
wrappers around the new shared `components/profile/PublicProfileView.vue`
(same "orchestrator component behind thin page(s)" shape as `ProfileTabs.vue`
behind `[[id]].vue`), rather than only building the `{id}` route the task
brief names explicitly. Not an API gap - flagged so the D1-created dangling
link doesn't look like it was missed.

### D3: `GET /api/v2/roles` (already implemented server-side, `RoleController::listRolesv2`) is reused for the `/user/all` role filter dropdown; a matching `RoleAPI.js` is added with only `list()`

Confirmed working end-to-end (Administrator-only, matches this page's own
gating) by reading `app/Http/Controllers/API/RoleController.php` directly.
Not a gap - noted because it's a new API resource class file
(`client/app/api/RoleAPI.js`) touching a controller area (`RoleController`)
that design.md §6.2 Phase D task D4 (reference-data CRUD) also owns; only
`list()` is added here, deliberately leaving `get`/`updateRolePermissions`
for D4 to add to the same file without needing to restructure it.

### D3: the legacy "Create new user" modal (`includes/modals/create-user`, posting to the web-only `/user/create` route) is not ported

`UsersPage.vue`'s "Create new user" button opens a Blade-rendered modal
posting to a legacy web route with its own CSRF-protected form handling
(`UserController::create`) - there is no v2 API for it (confirmed by reading
`routes/api.php`: no `POST` route under `/users` besides the id-scoped
sub-resources). Building a v2 user-creation endpoint plus the client form is
a separate, non-trivial feature (password/consent/role assignment for a
user who never went through self-registration) and out of scope for this
slice's brief, which only asks for "filters/sort/pagination preserved; role
editor modal". Recorded here rather than silently dropped so a future slice
knows the button is intentionally missing, not forgotten.

### D4: all five PR-863 reference-data endpoint families (Brands/Skills/GroupTags/Categories/Roles+Permissions) already exist server-side and match the legacy Vue contract exactly

Confirmed by reading `routes/api.php` and every controller
(`API\BrandController`/`SkillController`/`GroupTagController`/
`CategoryController`/`RoleController`) directly - no server gap for this
slice. `client/app/api/{Brand,Skill,GroupTag,Category}API.js` (new) and
`RoleAPI.js` (extended with `get`/`listPermissions`/`updatePermissions` -
`list()` already existed from D3) are thin wrappers with no discovered
shape mismatches. Two things worth recording precisely because they are
*not* gaps, so a future audit doesn't waste time re-checking them:

- `API\CategoryController::listCategoriesv2`/`getCategoryv2`/
  `updateCategoryv2` already return a pre-joined `cluster_name` on every
  row. The legacy `CategoriesPage.vue` had to build its own
  `cluster -> name` lookup client-side because the old admin-only API
  didn't join it; `pages/category.vue`'s table column reads `cluster_name`
  directly instead, one fewer piece of client state.
- `Tag` (`/api/v2/group-tags` and `/api/v2/networks/{id}/tags`) already
  carries `groups_count`. The legacy `GroupTagsPage.vue`'s delete
  confirmation was static text only; `pages/tags.vue` uses this field to
  add a dynamic in-use warning to `AdminCrudTable.vue`'s delete modal
  (`labels.deleteWarning(item)`, a new optional callback beyond the legacy
  `AdminCrudPage.vue` prop contract) - a client-side UX improvement the
  field already made possible, not a gap.

### D4: `?editId=N` reproduces the legacy `/{resource}/edit/{id}` bookmark as a query param on the same page, not a separate route

`routes/web.php` gives each reference-data page its own path route for this
(`Route::get('/edit/{editId}', [BrandsController::class, 'index'])`, same
shape for skills/tags/category/role) - a server-rendered-Blade-era pattern
that doesn't map cleanly onto an SPA page built around one component
instance. `AdminCrudTable.vue`'s `editId` prop is instead read from
`route.query.editId` by each thin page (`/brands?editId=5`), which every
`pages/{brands,skills,tags,category,role}.vue` supports. The legacy path
routes (`/brands/edit/5`, `/brands/create`) are not built as separate Nuxt
pages/redirects - any such legacy bookmark would need a Laravel-side
redirect to `/brands?editId=5` if that matters post-cutover (design.md §10
pre-deletion audit territory, not this slice's).

### D4: the skill-category dropdown (1 = Organising, 2 = Technical) has no dedicated list endpoint - hardcoded client-side against `App\Helpers\Fixometer::skillCategories()`, confirmed identical

`SkillController::createSkillv2`/`updateSkillv2` validate `category`
against `Fixometer::skillCategories()`'s keys but there's no
`GET /api/v2/skill-categories` (or similar) to fetch the two labels from -
the legacy `SkillsController` (web) passed `Fixometer::skillCategories()`
straight from PHP as a Blade prop, which doesn't exist for an SPA page.
`pages/skills.vue` hardcodes the same two entries, reusing the exact
top-level i18n keys (`"Organising skills - please select at least one if
you'd like to host events"`, `"Technical skills"`) that
`GET /api/v2/users/me/skills` already returns as its category `label` field
(consumed the same way by `components/profile/SkillsTab.vue`, D1) - so
there is exactly one source of truth for this wording, not two independent
copies. Not requesting a new endpoint: the server's own validation is the
actual authority here, and the client hardcoding it wrong would just mean a
422, not silently-accepted bad data - but flagging it since it's the one
place in this slice where "the client knows something the API doesn't
expose."

### D4: `pages/role.vue` is bespoke, not built on `AdminCrudTable.vue`

Unlike its four siblings, `RoleController` exposes no create/rename/delete
route (confirmed by reading `routes/api.php`: only `GET /roles`,
`GET /roles/{id}`, `PUT /roles/{id}/permissions`, `GET /permissions`) and
the one editable thing - a role's granted permission set - is a checkbox
matrix against a separate `permissions` catalogue with its own payload
shape (`{permissions: [id, ...]}`), not a text-field-per-row form.
`AdminCrudTable.vue`'s `formFields` contract (one value per row, keyed by
field name, submitted as `{[key]: value}`) has no natural way to express
that without inventing a bespoke field `type` used by nothing else. This is
a page-shape design decision, not a missing generic capability or a server
gap - recorded here per this file's own convention of flagging anything
that might otherwise look like an oversight to a future auditor.

## Phase E

### E1: `GET /api/v2/networks/{id}` has no `coordinators` field

The legacy `NetworkController@show` (web) builds a `coordinators` array
(`{id, name, picture}` per coordinator, from `$network->coordinators`)
straight from Eloquent and passes it into `NetworkPage.vue` as part of
`networkData`. `App\Http\Resources\Network` (confirmed by reading the
resource directly) has no equivalent field - only `id/name/logo/
description/website/shortname/default_language/stats/timezone/full`. There
is no other v2 endpoint that lists a network's coordinators either. The
Nuxt show page (`pages/networks/[id].vue`) does not render a coordinators
section at all rather than build one against data that doesn't exist.
**Requested addition**: either a `coordinators` array on the `Network`
resource, or a dedicated `GET /api/v2/networks/{id}/coordinators`.

### E1: no v2 endpoint for associating groups with a network

The only existing route, `POST /networks/{network}/groups`
(`networks.associate-group`, `NetworkController::associateGroup`), is a
`web` middleware (session-cookie + CSRF) route returning a redirect
response - unusable from the SPA, which is pure Sanctum Bearer-token auth
with no Laravel session cookie (design.md §4.4). Confirmed by reading
`routes/api.php` directly: nothing under `/api/v2/networks/{id}/groups`
accepts `POST`. **Client stub**: `api/NetworkAPI.js#associateGroups(id,
groupIds)` calls the not-yet-existing `POST /api/v2/networks/{id}/groups
{groups: [...]}`; `stores/networks.js#associateGroups` and
`components/networks/AssociateGroupsModal.vue` are built against that
documented shape and will surface a clean inline error (not a broken page)
until the endpoint lands. **Requested addition**: `POST /api/v2/networks/
{id}/groups {groups: [id, ...]}` (auth: Administrator or
network-coordinator-for-this-network, mirroring `NetworkPolicy::
associateGroups`), success shape TBD (e.g. `{data: {added: n}}`).

### E1: no v2 endpoint for updating a network's own profile (logo/name/description/website)

The legacy `/networks/{id}/edit` page is a Blade file-upload form
(`NetworkController::update`, web route) for the network logo only, gated
by `NetworkPolicy::update` (Administrator or coordinator-of-this-network -
the same condition as `view`). There is no `PATCH`/`PUT /api/v2/networks/
{id}` at all (confirmed by reading `routes/api.php`: `/networks` only has
`GET` routes plus the tag CRUD trio). This task's brief describes
`/networks/{id}` as "show, with edit for coordinators/admins", which this
port interprets as the page's *management* capabilities (tags CRUD,
associate groups) rather than a separate network-profile-edit form, since
no API exists for the latter and the legacy edit page is logo-upload only
(a small, separate feature). Not building a dead page against a
non-existent endpoint. **Requested addition**: `PATCH /api/v2/networks/
{id}` (name/description/website + logo, mirroring the group-edit pattern)
if/when a Nuxt network-edit form is wanted.

### E1: the legacy "Export event list" link is a session-cookie-authenticated web route, unreachable from the pure-Bearer-token SPA

`NetworkPage.vue`'s dropdown links straight to `/export/networks/{id}/
events` (`ExportController::networkEvents`, inside the `web` middleware
group's `auth` gate - confirmed by reading `routes/web.php`: it sits under
the same `Route::middleware('auth', 'verifyUserConsent', 'ensureAPIToken')`
block as the rest of the authenticated Blade app, unlike the anonymous-
access `/export/devices/*` routes `party/view/[id].vue` already links to
directly). The SPA carries no Laravel session cookie (design.md §4.4:
Sanctum Bearer tokens in localStorage, explicitly *not* Sanctum SPA stateful
cookies), so a direct `<a href>` would just redirect to `/login`. Dropped
from the Nuxt show page rather than shipping a dead link. **Requested
addition**: a v2 CSV/export endpoint (or a signed, tokenised export URL)
network coordinators can hit from the SPA.

### E1: `GET /api/v2/networks/{id}/groups` honours `includeCounts` for hosts/restarters, but it isn't documented on that endpoint's own OA annotation

`GroupSummary#toArray` (confirmed by reading the resource directly) adds
`hosts`/`restarters` when `$request->get('includeCounts', false)` is
truthy, and reads it straight off the current request object rather than a
parameter the controller passes through explicitly - so it works on every
`GroupSummaryCollection`-returning endpoint, including `getNetworkGroupsv2`,
even though that endpoint's `@OA\Parameter` list only documents
`includeNextEvent`/`includeDetails`/`includeStats`/`includeArchived`/
`group_tag`. `stores/networks.js#fetchGroups` passes `includeCounts: true`
by default (verified against the resource's actual behaviour, not just its
docs) so the network's groups table (reusing `GroupsTable.vue`, same row
mapping as `pages/group/all.vue`) gets populated hosts/restarters columns.
Flagging so a documentation pass adds `includeCounts` to the OA annotation
rather than someone reading only the docs and concluding it's unsupported.

### E1: network coordinator "view a network" and "manage its tags / associate groups" are the exact same permission, so the show page has no separate reduced-permission state

`NetworkPolicy::view` and `NetworkPolicy::associateGroups` (confirmed by
reading the policy directly) are identical: Administrator, or a
NetworkCoordinator who coordinates *this* network - there is no "Host can
view a network read-only" case at all (unlike `/group/view/[id]`, where
Hosts get a real read-only page). Since `NetworkController@show`'s web
route enforces `view` before rendering anything, reaching `/networks/{id}`
in the Nuxt client already implies full management rights - `canManage` in
`pages/networks/[id].vue` is computed once and gates both the tags-CRUD
section and the "Add groups" action identically. Recorded so this doesn't
read as a missed permission check: it mirrors the legacy policy exactly,
just collapsed into one flag instead of two identical ones.

### E1: `/tags` (D4, global tags) and the network show page's tags-management section both configure the same generic `AdminCrudTable.vue`, with `testid-prefix="tag"` here (not `tag-item`/`create-tag`/`edit-tag`/`delete-tag` literally)

The task brief for this slice names `tag-item`/`create-tag`/`edit-tag`/
`delete-tag` as the semantic testids a future Playwright port of
`tests/Integration/grouptags.test.js` (currently CSS-class-selector based:
`.tag-item`, `.create-tag`, `.edit-tag-btn`, `.delete-tag-btn`) will need.
Rather than hand-build a bespoke tags UI to hit those exact strings, this
reuses D4's already-built, already-tested `AdminCrudTable.vue` (its
`groups_count`-driven in-use delete warning is exactly what
`NetworkPage.vue`'s "(N groups)" tag-delete warning needs, verbatim -
`App\Http\Resources\Tag` already returns `groups_count` on every tag, same
field the global `/tags` admin page's warning already reads). With
`testid-prefix="tag"`, the rendered testids are `tag-row-{id}`,
`tag-edit-link-{id}`, `tag-delete-{id}`, `tag-create-form`,
`tag-create-name`, `tag-create-submit`, `tag-edit-modal`, `tag-delete-modal`,
`tag-delete-warning`, `tag-delete-confirm` - semantically equivalent to the
brief's naming (tag-item ≈ tag-row, create-tag ≈ tag-create-*, edit-tag ≈
tag-edit-*, delete-tag ≈ tag-delete-*) and consistent with every other
admin CRUD surface in the app, rather than a one-off naming scheme for this
page alone. Flagging in case whoever ports the Playwright suite (E5) expects
the literal strings from the task brief instead.

### E1: Groups/Events "requiring moderation" panels and a groups map are not ported

`NetworkPage.vue` also renders `GroupsRequiringModeration`/
`EventsRequiringModeration` panels and a `GroupMapAndList` (Google Map +
list) for the network's groups. Neither has any Nuxt equivalent anywhere in
the client yet (confirmed: no `moderat*`-named component/page exists;
`grep`-ing the whole `app/` tree for `moderate` only turns up the group/
event *creation* form's approved/moderate flag, not a moderation queue
UI) - building a moderation queue is a standalone feature, out of scope for
"networks show page" and not called out in this task's own brief (which
lists tags/associate-groups/stats, not moderation). Likewise, no map
component exists in the client (`LocationPicker` only edits a single
point) - the network's groups are rendered as a sortable table
(`GroupsTable.vue`, reused) instead of a map, which the task brief's "reuse
Phase B/C stat components where shapes allow" note doesn't preclude. Neither
is a genuine API gap; recorded so a future moderation-queue phase knows
`/networks/{id}` is a natural home for a network-scoped view of it.

### E2/E3: the SSO bridge (design.md §4.3, built in A6) had never actually been wired into any client link-out before this slice

`AppNavbar.vue`'s Talk/Wiki `<a>` tags shipped in A11 as plain
`config.discourse_url`/`wiki_url` hrefs with a comment saying they'd
"route via the SSO bridge... once that lands" - A6 landed the server side
(`POST /api/v2/auth/sso-ticket` + `GET /auth/bridge`) in the same phase,
but no client component ever called `ssoTicket()` afterwards (confirmed by
grepping the whole `app/` tree pre-this-slice: only `AuthAPI.js` defined
it, nothing invoked it). Not a server gap - just flagging that "check how
AppNavbar handles bridge URLs already and reuse" (this task's own brief)
turned out to have nothing to reuse yet.

**Client build**: added `composables/useSsoBridge.js` (mint a ticket, then
top-level-navigate to `bridge_url?ticket=...&redirect=...`) and wired it
into `AppNavbar.vue`'s and `AppFooter.vue`'s Talk/Wiki links and the new
`AppNotifications.vue`'s Discourse badge - all four call sites now go
through the bridge instead of a plain href. The plain `href` attribute is
kept as a right-click/open-in-new-tab fallback, but a fresh ticket can only
be minted on a real click, so that fallback path lands unauthenticated.

### E3: no v2 endpoint lists or marks-as-read a user's Restarters notifications - only the count is available

`GET /api/users/{id}/notifications` (v1, `UserController::notifications`)
is the *only* notifications endpoint that exists anywhere in the API - it
returns `{success, restarters: <count>, discourse: <count>}`, nothing
per-notification. The legacy notifications list/mark-as-read UI
(`resources/views/user/notifications.blade.php`, `NotificationController`,
`route('markAsRead')`) and the navbar's `<aside id="notifications">`
10-item preview panel (`layouts/navbar.blade.php`) both render
server-side from `$user->notifications()->take(10)->get()` directly in
Blade - there is no API a client could call for that list at all, v1 or
v2. `resources/js/components/Notifications.vue` itself (the component this
task's brief names as the port target) never fetches that list either -
its own scope really is just the two count badges; the list panel is a
sibling Blade partial outside the component.

**Requested addition**: `GET /api/v2/users/me/notifications` (paginated
list) + a mark-as-read endpoint, if a full in-app notifications
list/page is ever wanted client-side (plan row E3 mentions "+ page", which
this slice deliberately did not build for exactly this reason).

**Client build**: `AppNotifications.vue` replicates
`Notifications.vue`'s actual scope - two polled count badges. The
Discourse badge routes through the SSO bridge to Discourse's own
notifications page (real content, real host). The Restarters badge opens a
small local panel showing only the count text (`client.notifications.unread`)
since there is nothing to list - deliberately not a dead link to the
Blade `/user/notifications` page, which requires a Laravel web session the
token-authenticated SPA doesn't have and isn't on the bridge's redirect
allowlist anyway (`BridgeController::safeRedirect` only allows
`/discourse/sso`, the wiki, Discourse, and `FRONTEND_URL`).

### E3: `GET /api/users/{id}/notifications` polling interval is a client judgment call, not a ported behaviour

The legacy widget (`Notifications.vue`) fetches counts **once**, after a
5-second `setTimeout` (to keep it off the critical page-load path) - it
never re-polls at all, which reads as an oversight rather than a
deliberate one-shot design given the whole point is showing you *new*
notifications without a page reload. `AppNotifications.vue` fetches
immediately on mount (no reason to delay - nothing else on the SPA route
is blocked on it) and then polls every 60s, per this task's own "count
polling" requirement. 60s is not sourced from anywhere in the legacy code
- picked as a reasonable balance between freshness and load; there's no
existing precedent in this codebase for a "correct" interval.

### E2: `/about/cookie-policy`'s content has no Laravel-side lang source to generate from

`resources/views/features/cookie-policy.blade.php` (routes/web.php: a
plain closure, no controller) has every string hardcoded English inline in
the Blade template - unlike every other ported page, there is no
`lang/en/*.php` file backing it, so nothing for
`translations:export-client` to have generated. Per this migration's i18n
convention (design.md §7: client-only content lives in hand-maintained
`client-<loc>.json`), this slice introduces `client.cookie_policy.*`
(en/fr/fr-BE, hand-translated) rather than leaving the page hardcoded
English or inventing a fake generated-file dependency.

**Requested addition**: none - this is working as intended for
client-only content; flagging only so a future contributor doesn't go
looking for a `lang/en/cookie-policy.php` that was never there.

**Client build**: the legacy page's "reopen cookie settings" link
(`.gdpr-cookie-notice-settings-button`, tied to `resources/js/gdpr-cookie-
notice`) is dropped rather than wired to nothing - no GDPR cookie-consent
banner has been ported to the Nuxt client yet at all (grepped the whole
`app/` tree: no consent-banner component exists outside the registration
form's one-time consent checkboxes), so there is nothing for that link to
reopen. Recorded here since a future consent-banner build should come back
and re-add this link.

### E2/E4: `/user/forbidden` (this task's own URL list) vs `/forbidden` (what the client actually built, in A11)

This task's brief lists `/user/forbidden` as an existing URL (matching
`routes/web.php`'s `GET /user/forbidden` closure), but the client's 403
page has lived at `app/pages/forbidden.vue` (route `/forbidden`) since A11,
and every role-gated page in the client (`middleware/auth.global.ts`,
`networks/index.vue`, `networks/[id].vue`, ...) already
`navigateTo('/forbidden')` consistently. Not a new decision made in this
slice - just confirmed-and-left-alone rather than silently mismatched:
renaming the route now would touch every one of those call sites for a
purely cosmetic URL difference, with no user-facing bookmark/link
depending on the old path yet (the SPA doesn't serve `/user/forbidden` at
all, so there's no compatibility to preserve, unlike the URLs design.md
§6.2 calls out specifically for bookmark/email/Playwright continuity).

### E4: the account "preferred language" (`users/me/language`, LanguageTab.vue) and the UI locale switcher write the exact same field

`SessionController::patchSessionv2` sets `$user->language` - the identical
column `UserController::getMyLanguagev2`/`updateMyLanguagev2`
(`LanguageTab.vue`'s profile-tab endpoint) reads and writes. There are not
two separate "UI locale" vs "account language" preferences server-side,
just one. Not a gap, just a non-obvious coupling worth recording: saving a
locale from the new navbar/footer `LocaleSwitcher.vue` also changes what
`LanguageTab.vue` shows next time the profile tab loads (and vice versa) -
this is almost certainly the intended behaviour (why maintain two
"language" settings?), but it means the two components are quietly
coupled through a shared field despite living in unrelated parts of the
UI, with no explicit indication of that on either screen.
