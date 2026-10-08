# Phase C API contracts (events + devices slice)

Authoritative shapes for C1's server work and C2–C6's client work. Existing
endpoints (event get/create/patch, v1 volunteers list/add, device get/patch/
delete, items, brands, categories, category-clusters) are unchanged — see
l5-swagger for those.

All responses `{data: …}`; errors 401/403/404/422 as per design §5. Auth:
Bearer; roles enforced server-side. `getUser()` (sanctum→api fallback) is the
existing pattern in `API\EventController`/`API\DeviceController` — new
endpoints below follow it.

## C1a — Event resource additions

`GET/POST/PATCH /api/v2/events/*` (`Http\Resources\Party`) already returns
`id/start/end/timezone/title/location/online/lat/lng/group/description/stats/
updated_at/approved/network_data/full`. `stats` already has `participants`,
`volunteers`, `invited` (see `Party::getEventStats()`) — client reads
`event.stats.participants` etc, not the Blade `expandEvent()`'s separate
`participants_count`/`volunteers_count`/`allinvitedcount`, which are
redundant with it.

**EXTEND** — one new field, only when authenticated:

```json
{ "attending": true }   // EventsUsers.status === '1' for this user; omitted/null when logged out
```

Mirrors `expandEvent()`'s `Auth::user() && $event->isBeingAttendedBy(...)`
(strict status==='1', not the looser "confirmed-or-null" `isVolunteer()`
test — `isVolunteer` becomes redundant once C1b's attendees endpoint exists:
derive client-side as `attendees.confirmed.some(a => a.user === me.id)`).

Everything else `expandEvent()` adds (`upcoming`/`finished`/`inprogress`/
`startingsoon`/`*_local`) is derivable client-side from `start`/`end`/
`timezone` via `moment-timezone` — port into `useEventComputed(event)`, no
new fields. `canModerate` isn't event-specific at all (just
`hasRole('Administrator')||hasRole('NetworkCoordinator')`) — belongs in
`useRoles()`. `requiresModeration` is just `!approved`.

## C1b — GET /api/v2/events/{id}/attendees (NEW)

Replaces the Blade-only `PartyController::view()` computation
(`attended`/`invited`/`hosts` + `Party::expandVolunteers()`) and extends v1
`GET /api/events/{id}/volunteers` (confirmed-only). `Http\Resources\Volunteer`
has a standing comment — *"When we write the v2 event volunteer code we'll
need to change this"* — this is that code.

```json
{ "data": {
  "confirmed": [
    { "id": 501,                 // events_users.idevents_users
      "user": 12,                // null for a manually-added, unregistered volunteer
      "fullName": "Sam Jones",
      "role": 3,                 // Role: HOST=3, RESTARTER=4, GUEST=5 (guest = plain attendee/"participant")
      "confirmed": true,
      "profilePath": "/uploads/thumbnail_xyz.png",
      "volunteer": {             // present only when `user` is set
        "id": 12, "name": "Sam Jones",
        "email": "sam@example.com",   // only when caller has edit-party permission (mirrors listVolunteers' showEmails gate)
        "user_skills": [ { "skill_name": { "skill_name": "Soldering" } } ]
      }
    }
  ],
  "invited": [ /* same shape, confirmed:false; the raw status hash is not surfaced */ ]
} }
```

Builders: confirmed = `Party::allConfirmedVolunteers()` (status='1' OR NULL),
invited = `allInvited()` (status<>'1'). The event-view "hosts" block is just
`confirmed.filter(a => a.role === 3)` — no separate array needed. Read
`Party::expandVolunteers()` for exact per-row shaping (reused as-is; only the
caller changes).

Note: today's Blade view truncates confirmed/invited to 5–6 rows server-side
for perf (`attended_summary`/`invited_summary`). The v2 endpoint should
return full lists — a deliberate behaviour change, not a silent drop.

## C1c — RSVP: POST/DELETE /api/v2/events/{id}/attendees/me (NEW)

Replaces `GET /party/join/{id}` (`getJoinEvent`) and
`GET /party/cancel-invite/{id}` (`cancelInvite`).

- `POST` (auth) — `EventsUsers::updateOrCreate` with `status='1',
  role=Role::RESTARTER` (mirrors `getJoinEvent`, incl. already-attending
  no-op). Fires `notifyHostsOfRsvp()`.
  `{data:{attending:true, already_attending:bool, prompt_follow_group:bool}}`
  (the last replaces the legacy `prompt-follow-group` flash, shown when the
  user isn't in the hosting group).
- `DELETE` (auth) — loop-deletes the user's `EventsUsers` row(s) for this
  event (per `cancelInvite`, to keep observers firing).
  `{data:{left:true}}`.
- Pending-invite accept (hash-status row, not yet `'1'`): emailed links keep
  working via the frozen `GET /party/accept-invite/{id}/{hash}` redirect
  per design §5. But recommend `POST .../attendees/me` *also* flip a
  matching hash row to `'1'` for the logged-in caller (mirrors
  `confirmInvite`'s effect), so the in-app "confirm RSVP" banner doesn't
  need to know the hash at all — judgment call, flagged below.

## C1d — Volunteer management

- **Add volunteer** (EXISTS, v1, keep) — `PUT /api/events/{id}/volunteers`
  (`addVolunteer`): `{user, full_name, volunteer_email_address}`. Used by
  `EventAddVolunteerModal`. Not worth duplicating as v2.
- `PATCH /api/v2/events/{id}/volunteers/{iduser}` (NEW; host/NC/admin) —
  `{host: bool}`, mirroring `GroupController::patchVolunteerForGroupv2`
  exactly: sets `EventsUsers.role` to HOST/RESTARTER for that user's row(s).
  (The plan note's "quantity/role" is misleading here — there's no
  per-volunteer quantity field; see headcount counters below.)
- `DELETE /api/v2/events/{id}/volunteers/{iduser}` (NEW, for symmetry) — but
  the only removal path today, `POST /party/remove-volunteer
  {id: idevents_users}` (used by `EventAttendee.vue`), keys off the
  `events_users` row id because a row may have no `user` (manually-added
  volunteer). **Recommend the route param be `{idevents_users}`, not
  `{iduser}`**, so it still works for those rows — a naming deviation from
  the group pattern, flagged below.
- **Headcount counters** (`pax`/`volunteers` columns, the +/- control next to
  "Participants"/"Volunteers") — today two bespoke endpoints,
  `POST /party/update-quantity` / `update-volunteerquantity`
  (`{event_id, quantity}`, Host/NC/Admin gated). **Recommend folding into
  `PATCH /api/v2/events/{id}`** as optional `participants`/`volunteers`
  integers instead of two more one-field routes — judgment call, flagged
  below.
- **Invite** (NEW) — `POST /api/v2/events/{id}/invites` (host/NC/admin)
  `{emails:[…], message?}` → `{data:{invites_sent:int, invalid:[…]}}`,
  mirroring B2's `POST /api/v2/groups/{id}/invites` exactly. Replaces
  `postSendInvite`'s hidden-form POST to `/party/invite`. Preserve: known
  emails get an in-app invite (hash-status row + `JoinEvent` notification);
  unknown emails get an `Invite` row (type `event`) + registration email;
  already-confirmed users silently skipped; only malformed *addresses* go in
  `invalid`. The invite modal's "select group members" list already has its
  endpoint (`GET /api/v2/groups/{id}/volunteers?exclude_event={id}`, EXISTS).

## C1e — Event devices

- `GET /api/v2/events/{id}/devices` (NEW) — `{data: Device[]}`. Replaces the
  Blade `view()` controller's inline device-resolve loop. Not exposed as a
  callable endpoint today (Blade passes it as an initial prop) — the Nuxt
  client needs it as a real call since there's no server render.
- **Device CRUD** (EXISTS, v2) — `GET/POST/PATCH/DELETE /api/v2/devices[/{id}]`
  already cover add/edit/delete, keyed by `eventid` in the body. Read
  `API\DeviceController::validateDeviceParams()` for the exact field mapping
  (`repair_status`/`spare_parts`/`barrier` string enums ↔ int columns —
  mirror logic lives in `Http\Resources\Device` for reads). No gaps beyond
  the list endpoint above.

## C1f — Event + device images (NEW, tus)

Mirrors B2's group-images shape exactly (`Tus::buildCache()`, `upload_key`,
2MB/jpeg-png-gif validation) — read
`GroupMembershipController::uploadImagev2`/`deleteImagev2` for the builder,
`tests/Feature/Groups/APIv2GroupImagesTest.php` /
`tests/Feature/Users/APIv2TusUploadTest.php` for the tus flow.

- `POST /api/v2/events/{id}/images` `{upload_key}` → `{data:{image_url}}`;
  `DELETE /api/v2/events/{id}/images/{idimages}`. Permission: any attendee
  (any `EventsUsers` row) or Administrator — mirrors `deleteImage()`, looser
  than edit-party since any attendee can add repair photos.
- `POST /api/v2/devices/{id}/images` / `DELETE .../images/{idimages}` — same
  pair for devices (design §5 point 11: extend PR #868's tus pattern to
  device images too). Permission: `userHasEditEventsDevicesPermission`.

Replaces `POST /party/image-upload/{id}` + `GET /party/image/delete/...`
(event) and `POST /device/image-upload/{id}` + `GET /device/image/delete/...`
(device) — both raw-`$_FILES`/GET-mutation today.

## C1g — DELETE event + moderation approve

- `DELETE /api/v2/events/{id}` (NEW) — permission =
  `userHasEditPartyPermission || userIsHostOfGroup`, matching `deleteEvent()`.
  Preserve: deletes audits, hard-deletes the event's devices, loop-deletes
  `EventsUsers` (for observers), soft-deletes the event, fires
  `EventDeleted`. `{data:{deleted:true}}`. Note `Party::canDelete()`
  (zero devices) is only a **client-side** confirm-dialog rule today —
  `deleteEvent()` itself doesn't enforce it. Flagged below.
- **Moderation approve** — EXISTS, no new endpoint:
  `PATCH /api/v2/events/{id}` with `{moderate: "approve"}` +
  `userCanApproveEvent` gate ⇒ `$party->approve()`. C4 just needs the
  `.event-approve` `<select>` to keep posting on the same PATCH call
  (preserve `data-testid=event-approve`). Moderation queue is
  `GET /api/v2/moderate/events` (EXISTS, `moderateEventsv2`).

## C5 — GET /api/v2/devices/options (NEW, narrow)

The plan note lists "brands/barriers/spareparts/itemtypes" but three already
have v2 endpoints — reuse, don't duplicate:

- Item-type autocomplete + category suggestion: `GET /api/v2/items` (EXISTS
  → `{type, powered, idcategories, categoryname}[]`). This is the entire data
  source `DeviceType.vue`/`EventDevice.vue`'s `suggestedCategory` use today —
  there's **no fuse.js or fuzzy scoring in the current code**, just an exact
  case-insensitive match against category names, then this list. A fuzzy
  upgrade in the Nuxt port is a new design decision, not a legacy behaviour
  to preserve.
- Brands: `GET /api/v2/brands` (EXISTS).
- Categories-by-cluster (`DeviceCategorySelect.vue` needs
  `cluster.categories[]` nesting): no endpoint returns that shape, but
  `GET /api/v2/categories` (flat, with `cluster`/`cluster_name` per row) +
  `GET /api/v2/category-clusters` (`{id,name}` headers) both EXIST — group
  client-side rather than adding a third shape for the same data.

So the only real gap is the two hard-coded enum lists:

```json
{ "data": {
  "barriers": [ { "id": 1, "name": "Spare parts not available" }, /* …5 total, from Barrier::all() */ ],
  "spare_parts": [ "No", "Manufacturer", "Third party" ],
  "next_steps": [ "More time needed", "Professional help", "Do it yourself" ]
} }
```

`spare_parts`/`next_steps` have no table — hard-code them the same way
`Device::REPAIR_STATUS_*_STR` constants already do.

## C6a — GET /api/v2/devices (NEW v2, paginated + filtered)

Replaces `GET /api/devices/{page}/{size}` (v1, `ApiController::getDevices`,
keep — not worth removing). Same filters, `{data:{items,count}}`-wrapped:

```
GET /api/v2/devices?page=1&size=20&sortBy=event_start_utc&sortDesc=DESC
    &powered=true&category=16&brand=…&model=…&item_type=…&status=1
    &comments=…&wiki=true&group=…&from_date=…&to_date=…
```

Read `ApiController::getDevices()` for the exact query builder (joins
events+groups+categories, `LIKE` matches, batch-loaded images via
`findImagesForMany`). `FixometerRecordsTable.vue`'s `items()` method is the
client call to port — same param names. This is `APIv2DevicesListTest` per
the plan note.

## C6b — Fixometer home data (reuse, no new endpoint)

`GET /homepage_data` (EXISTS, v1, **unauthenticated**, 12h cache) already
returns everything `FixometerGlobalImpact.vue` needs: `participants,
hours_volunteered, items_fixed, waste_powered, waste_unpowered, waste_total,
co2_powered, co2_unpowered, co2_total, fixed_powered, fixed_unpowered,
total_powered, total_unpowered` (+ legacy aliases for therestartproject.org —
don't touch). No `{data:…}` envelope — it's frozen, not a v2-convention
endpoint; client reads the body directly.

`latestData` (the "most recent finished event with repairs" banner) has
**no existing endpoint** — `DeviceController::index()` builds it inline
(`Party::with('theGroup')->hasDevicesRepaired(1)->eventHasFinished()->
orderBy('event_start_utc','DESC')->first()` + computed `id_events`/
`waste_prevented`). Recommend `GET /api/v2/stats/latest-repaired-event`
(NEW, public) → `{data:{id, waste_prevented, group: GroupSummary}}`.

## C2/C3 — Events lists (`/party`, `/party/all`, `/party/all-past`)

`/party` (mine) and `/party/group/{group_id}` both route to
`PartyController::index()`, which returns `expandEvent()` per event (C1a) —
no API endpoint exists for it today, only the server-rendered prop. Needed:
`GET /api/v2/users/me/events` (NEW, auth) returning the union `index()`
builds: "my" events (`Party::forUser(null)`), nearby upcoming events if the
user has lat/lng (`upcomingEventsInUserArea()`), and other approved upcoming
events not already included — each tagged `nearby`/`all` as the Blade does.
Group's own events tab is unaffected — `GET /api/v2/groups/{id}/events`
(EXISTS) already covers it.

**`/party/all` and `/party/all-past` have no working legacy implementation.**
`routes/web.php:375-376` wires them to `PartyController::allUpcoming`/
`allPast`, but **neither method exists on the controller** — these routes
currently 500 if hit. Not a migration gap so much as a pre-existing dead
route; flagged below rather than guessed at.

## Judgment calls made in this doc

1. **RSVP-accept-without-hash** (C1c): `POST .../attendees/me` also resolves
   a pending hash-invite for the caller, so the in-app banner doesn't need
   the hash. Emailed links stay on the frozen hash-GET route per design §5.
2. **Volunteer-remove keyed by `{idevents_users}`, not `{iduser}`** (C1d):
   unregistered volunteers have no user id, so DELETE and PATCH end up keyed
   differently on "the same" sub-resource. Alternative: key both by
   `{idevents_users}` and have PATCH look up the user from the row.
3. **Headcount counters folded into `PATCH /api/v2/events/{id}`** (C1d):
   simpler surface than two new routes, but today's two counter endpoints
   have their own inline role check, subtly different from
   `userHasEditPartyPermission` used elsewhere in `updateEventv2` — re-verify
   this doesn't loosen who can bump the counters.
4. **`DELETE /api/v2/events/{id}` doesn't enforce `canDelete()`** (C1g),
   matching today's behaviour (only the Blade UI hides the button). Flagging
   in case C4 wants server-side enforcement added rather than the quirk kept.
5. **`/party/all`/`/party/all-past`** (C2): proposing they become filters
   over `GET /api/v2/users/me/events` (all = future approved events visible
   to the user; all-past = same, finished) since there's no working
   precedent to mirror — needs a product decision.
6. **Fuzzy category-suggestion** (C5): the plan note's "fuse.js scoring port
   of items.js" describes a *new* algorithm — today's matching is exact
   string only, still backed by `GET /api/v2/items` either way.

Where a page needs data with no endpoint listed here or in swagger, DO NOT
invent one: record it in `docs/nuxt-migration/api-gaps.md` and stub the UI.
