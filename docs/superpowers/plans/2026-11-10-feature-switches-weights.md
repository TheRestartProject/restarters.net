# Feature switches, item weights and custom item fields: implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Prototype per-network and per-group feature switches (RES-2063, RESDISCO-9), with two switches: item weights and custom item fields, plus one impact rule for entered weights.

**Architecture:** A feature registry and resolver in `App\Services\GroupFeatures` reads JSON settings on networks and groups. A single impact rule in `App\Services\ItemImpact` (PHP and SQL forms) replaces the copies in `Device`, `Party::getEventStats` and `LcaStats`. The Nuxt client reads resolved switches from the event and group resources and shows or hides the weight box and custom fields.

**Tech stack:** Laravel 10 (PHP 8.2 in the test container), MySQL, Nuxt 4 / Vue 3 / bootstrap-vue-next, Vitest, PHPUnit.

**Story (spec):** https://therestartproject.atlassian.net/browse/RES-2063

---

## How to run tests in this worktree

The dev containers mount the main checkout, so this worktree uses throwaway containers and its own database `restarters_thought9_test` (never the shared `restarters_db_test`).

- PHP: `/tmp/claude-1000/-home-edward-restarters-net/fc3c84a5-3538-4328-9589-798417dcabe0/scratchpad/t9php.sh './vendor/bin/phpunit --configuration ./phpunit.xml -d error_reporting=8191 --no-coverage tests/Feature/Stats/'`
- Client: `/tmp/claude-1000/-home-edward-restarters-net/fc3c84a5-3538-4328-9589-798417dcabe0/scratchpad/t9node.sh 'npx vitest run tests/components/devices/'`
- Never run two PHPUnit runs at once: they share the worktree test DB.
- After adding migrations: `t9php.sh 'php artisan migrate --database=mysql_testing --force'`.

## API contract (backend owns, client consumes)

### Feature registry

| key | default | meaning |
|---|---|---|
| `item_weight` | on | Every item has a weight box. Off: only Misc items have one. |
| `custom_fields` | off | The group's custom item fields appear in the item form. |

Network values per key: `on` (always on), `off` (always off), `default_on`, `default_off`, or absent/null (not set).
Group values per key: `true`, `false`, or absent/null (use the default).

Resolution, in order: any network `on`/`off` (on wins if they disagree; `locked: true`), then the group's own value, then any network `default_on`/`default_off` (on wins), then the registry default.

### `GET /api/v2/groups/{id}` (Group resource) adds

```json
"features": {
  "item_weight": {"enabled": true, "source": "default", "locked": false, "group_setting": null, "inherited": true, "set_by": []},
  "custom_fields": {"enabled": false, "source": "network_default", "locked": false, "group_setting": null, "inherited": false, "set_by": ["Repair Together"]}
},
"custom_fields": [
  {"key": "town", "label": "Town the visitor came from", "type": "text", "options": []},
  {"key": "visit", "label": "First visit?", "type": "choice", "options": ["Yes", "No"]}
]
```

- `source`: `network` (locked by a network), `group`, `network_default`, `default`.
- `inherited`: what applies if the group has no setting of its own (network default, else registry default). The client shows it as "Use the default (On)".
- `set_by`: names of the networks behind `network` or `network_default`.
- `custom_fields` lists active fields only; types are `text`, `number`, `choice`.

### `PATCH /api/v2/groups/{id}/options` (JSON)

Body (both keys optional):
```json
{"features": {"item_weight": false, "custom_fields": null}, "custom_fields": [{"key": "town", "label": "Town", "type": "text"}, {"label": "Visit", "type": "choice", "options": ["Yes", "No"]}]}
```
- Who: admins, hosts of the group, coordinators of one of its networks. Others 403.
- `features` values: `true`, `false`, `null` (clear). Unknown key or other value: 422.
- `custom_fields`: the full list of active fields, at most 10. `label` 1-50 chars, `type` in text/number/choice, `options` 1-20 distinct strings of 1-50 chars for choice. A field without a known `key` gets a new key from its label. Fields left out are marked removed (values kept, key never reused).
- Response 200: `{"data": {"features": {...as above}, "custom_fields": [...]}}`.

### `GET /api/v2/networks/{id}` (Network resource) adds

```json
"features": {"item_weight": null, "custom_fields": "default_on"},
"feature_defaults": {"item_weight": true, "custom_fields": false}
```

### `PATCH /api/v2/networks/{id}/options` (JSON)

Body: `{"features": {"item_weight": "on", "custom_fields": null}}`. Who: admins and coordinators of this network; others 403. Unknown key or value: 422. Response 200: `{"data": {"features": {...}, "feature_defaults": {...}}}`.

### `GET /api/v2/events/{id}` (Party resource) adds

```json
"group_features": {"item_weight": true, "custom_fields": true},
"group_custom_fields": [{"key": "town", "label": "Town", "type": "text", "options": []}]
```

### Categories (Category resource, used by the item form) add

```json
"typical_weight": 1.28,
"weight_min": 0.0128,
"weight_max": 12.8
```
For Misc (category weight 0): `typical_weight` is the median of Misc weights entered in the last 12 months (powered or unpowered Misc separately), `weight_min` is 0.001 and `weight_max` is 20 times the typical weight, or null when there is no typical weight yet.

### Devices

- `estimate`: nullable. Create/update accept `null`, `""`, a number, or a string with a decimal comma (`"1,5"`). Blank is stored as NULL. The resource returns `null` for no weight.
- `custom_values`: object `{key: value}`. Create/update accept it as an object (or a JSON string). Keys must be active fields of the event's group (422 otherwise); number fields take numbers, choice fields one of the options, text fields at most 255 characters. `null` or `""` removes a value. Values not sent are kept. The resource returns `custom_values` (an object, `{}` when none).

### Impact rule (`App\Services\ItemImpact`)

- Entered weight counts if `> 0` and between category weight / 100 and category weight x 10. Misc (category weight 0): counts if `> 0` and at most 20 x the typical Misc weight (no cap when there is no typical weight).
- Waste: entered weight if it counts, else category weight, else (Misc) the typical Misc weight.
- CO2e before displacement: entered x (footprint / weight) if it counts; Misc uses the general ratio (`EMISSION_RATIO_POWERED` / `EMISSION_RATIO_UNPOWERED`). Otherwise the footprint; Misc: typical weight x general ratio.
- Then x `DISPLACEMENT_VALUE`, as before.

---

## Backend tasks (inline, TDD)

### Task B1: estimate nullable, footprint correction
**Files:** Create `database/migrations/2026_11_10_000001_make_device_estimate_nullable.php`, `database/migrations/2026_11_10_000002_correct_jewellery_and_hand_tool_footprints.php`; modify `app/Http/Controllers/API/DeviceController.php` (validation + `$estimate`), `app/Http/Resources/Device.php`; test `tests/Feature/Devices/NullEstimateProblemTest.php`.
- [ ] Rewrite `NullEstimateProblemTest`: creating with `estimate: null` stores NULL and returns `estimate: null`; `""` stores NULL; `"1,5"` stores 1.5; editing to null stores NULL. Run: fails (ValidationException).
- [ ] Migration: `ALTER TABLE devices MODIFY estimate DECIMAL(6,3) UNSIGNED NULL DEFAULT NULL`, then `UPDATE devices SET estimate = NULL WHERE estimate = 0`. Down: zeros back, NOT NULL DEFAULT 0.
- [ ] Controller: normalise comma before validation; rule `['nullable', 'numeric', 'min:0', 'max:999.999']`; blank to null. Resource returns null when null.
- [ ] Footprint migration: categories 52 footprint 3.334, 51 footprint 4.349 (down: 59.190, 4.670).
- [ ] Run tests, commit.

### Task B2: one impact rule
**Files:** Create `app/Services/ItemImpact.php`, `app/Services/TypicalMiscWeights.php` (container singleton, memoises per app instance, cached 1 day); modify `app/Device.php` (four methods delegate), `app/Party.php::getEventStats`, `app/Helpers/LcaStats.php::getWasteStats`, `app/Http/Resources/Category.php`, `app/Providers/AppServiceProvider.php`; tests `tests/Unit/ItemImpactTest.php` (new), `tests/Feature/Stats/*` (expectations to the new rule).
- [ ] Unit tests for `counts`, `waste`, `co2`, `weightRange` with a fake typical weight; feature test that `TypicalMiscWeights` takes the median of the last 12 months only; test that PHP and SQL agree on a mixed set of devices.
- [ ] Implement; update stats tests where the rule changes (powered non-Misc weights now count; unpowered weights use the category ratio; blank Misc uses the typical weight).
- [ ] Category resource adds `typical_weight`, `weight_min`, `weight_max`.
- [ ] Run `tests/Feature/Stats/`, `tests/Unit/`, `tests/Feature/Devices/`; commit.

### Task B3: feature switches
**Files:** Create `database/migrations/2026_11_10_000003_add_feature_settings.php` (groups.features JSON, groups.custom_fields JSON, networks.features JSON, devices.custom_values JSON), `app/Services/GroupFeatures.php`, `app/Services/CustomFields.php`; modify `app/Group.php`, `app/Network.php`, `app/Device.php` (casts), `app/Http/Resources/Group.php`, `app/Http/Resources/Network.php`, `app/Http/Resources/Party.php`, `app/Http/Controllers/API/GroupController.php` (`updateGroupOptionsv2`), `app/Http/Controllers/API/NetworkController.php` (`updateNetworkOptionsv2`), `routes/api.php`; tests `tests/Unit/GroupFeaturesTest.php`, `tests/Feature/Groups/GroupOptionsTest.php`, `tests/Feature/Networks/NetworkOptionsTest.php`.
- [ ] Resolution tests: registry default; group value; network default; network always beats group; on wins between networks; `set_by` names.
- [ ] Endpoint tests: permissions (host, coordinator, admin, other user 403), 422 on unknown keys/values, custom field keys generated and kept, removed fields hidden and keys not reused, 10-field limit.
- [ ] Resource tests: group, network and event resources carry the new fields.
- [ ] `#[UserStory]` on the two new controller methods; `php artisan specs:extract`.

### Task B4: custom values on devices
**Files:** modify `app/Http/Controllers/API/DeviceController.php`, `app/Http/Resources/Device.php`; test `tests/Feature/Devices/DeviceCustomValuesTest.php`.
- [ ] Tests: valid values saved and returned; unknown key 422; bad number 422; choice not in list 422; null removes; values not sent are kept; values for removed fields kept but not accepted anew.

## Client tasks (subagent, TDD with Vitest)

### Task C1: weight box and custom fields in `DeviceForm.vue`
- Weight box for every item when `group_features.item_weight`, else Misc only. Never pre-filled; blank sends `null`. Hint from the category's `typical_weight`. Accept a comma. Warning (does not block saving) when outside `weight_min`..`weight_max`, with "Did you mean X kg?" when value / 1000 is inside the range.
- Custom fields after the standard fields when `group_features.custom_fields`; text, number and choice inputs; sends `custom_values`.
- Item list shows custom values on the item.

### Task C2: "Options" on the group edit page
- New card on `/group/edit/[id]` for anyone who can edit the group: each feature as On / Off / Use the default (On|Off); locked features disabled with "Set by {network}". Custom fields editor (add, rename, reorder not needed, remove; type select; options for choice) shown when custom fields are enabled. Saves with `PATCH /api/v2/groups/{id}/options`.

### Task C3: "Options for groups in this network" on the network edit page
- Per feature a select: Not set / Always on / Always off / On by default / Off by default. Saves with `PATCH /api/v2/networks/{id}/options`. Visible to admins and the network's coordinators.

### Task C4: strings
- en, fr and fr-BE strings in `lang/*/`, exported with `php artisan translations:export-client`.

## Finish
- [ ] Full PHP suite dirs touched, Vitest full run, `specs:extract --check`.
- [ ] Push branch, open a draft PR into `nuxt-client` (not for merge), end-state description.
