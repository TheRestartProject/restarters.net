# Phase B API contracts (dashboard + groups)

Authoritative shapes for B1/B2 server work and B3–B8 client work. Existing
endpoints (groups get/create/patch, volunteers, names, tags, events-for-group,
permissions flags) are unchanged — see l5-swagger for those.

All responses `{data: …}`; errors 401/403/404/422 as per design §5.
Auth: Bearer; roles enforced server-side.

## B1 — GET /api/v2/dashboard (auth)

Replaces DashboardController@index Blade props.

```json
{ "data": {
  "your_groups": [           // max 5, alphabetical (users_groups pivot)
    { "id": 1, "name": "…", "role": 3, "archived": false, "image_url": null }
  ],
  "nearby_groups": [         // user.groupsNearby(2); [] when user has no lat/lng
    { "id": 2, "name": "…", "distance": 12.3, "location": "…", "image_url": null }
  ],
  "new_nearby_groups": [     // groupsNearby(3, "1 month ago"), same shape as nearby
  ],
  "upcoming_events": [       // Party::futureForUser() take 5, expanded
    { "id": 9, "title": "…", "start": "ISO8601", "end": "ISO8601",
      "timezone": "Europe/London", "online": false, "location": "…",
      "attending": true, "group": { "id": 1, "name": "…" } }
  ]
} }
```

## B2 — group membership / lifecycle

- `POST /api/v2/groups/{id}/members/me` (auth) — self-join (replaces GET
  /group/join/{id}). 200 `{data:{joined:true,already_member:bool}}`.
- `DELETE /api/v2/groups/{id}/members/me` (auth) — leave (equivalent of the
  legacy DELETE /api/usersgroups/{id}, which stays). 200 `{data:{left:true}}`.
- `GET /api/v2/groups/nearby` (auth) — `{data:[nearby_groups shape]}` for the
  logged-in user's location.
- `POST /api/v2/groups/{id}/invites` (auth; host/coordinator/admin of group)
  `{emails:[…], message?}` → `{data:{invites_sent:int}}` (replaces POST
  /group/invite). Invalid addresses reported in `{data:{invalid:[…]}}`.
- `DELETE /api/v2/groups/{id}` (auth; per #892 can_perform_delete) — archive
  semantics like the Blade delete. 200 `{data:{archived:true}}`.
- `GET /api/v2/groups/{id}/stats` — public. The group-view Blade props:
  `{data:{group_stats:{…Party stats keys…}, device_stats:…, cluster_stats:…,
  top_devices:[…]}}` (shapes mirror what group/view.blade.php passes today —
  read GroupController@view for the exact builders).
- Group images: `POST /api/v2/groups/{id}/images` `{upload_key}` (tus, like
  users/me/photo) → `{data:{image_url}}`; `DELETE /api/v2/groups/{id}/images/{idimages}`.

## B6 — maps proxy (moves in this phase)

- `GET /api/v2/maps/autocomplete?q=…` (auth) and
  `GET /api/v2/maps/place-details?place_id=…` (auth): identical JSON to the
  session-auth `/maps/*` routes (MapsProxyController) they replace.

## Client notes

- Group lists/cards may also use the existing `GET /api/v2/groups/names` and
  per-group `GET /api/v2/groups/{id}` (+ `permissions`).
- The group map page (B7) needs PR #887's names-index/summary work — not yet
  on this branch; build the page behind the existing names endpoint and leave
  a TODO marker referenced in the plan.
- Where a page needs data with no endpoint listed here or in swagger, DO NOT
  invent one: record it in docs/nuxt-migration/api-gaps.md and stub the UI.
