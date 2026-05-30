# Blade-to-Vue Migration Plan

## Goal
Migrate remaining non-trivial Blade templates to Vue components. For each, introduce v2 API endpoints (with OpenAPI annotations and PHPUnit tests), then build Vue components reusing existing primitives. End-to-end via Playwright.

Approach: TDD (failing PHP test → API → Vue → Playwright). Group templates into related PR-sized batches.

## Workflow (per group)

The PR is opened **early as a draft**, against `develop`, and CI runs continuously as work lands. Each sub-task follows this loop:

1. **Implement** the sub-task to local-green (PHPUnit + vite build clean).
2. **Commit** with a self-contained message.
3. **`git push`** — the CI Monitor is armed against the PR; CircleCI starts.
4. **Wait for CI** to settle (notification arrives via Monitor; do not spin polling).
5. **If CI fails**: fix it BEFORE starting the next sub-task. Push the fix as a new commit. Repeat from step 4.
6. **If CI green**: move to the next sub-task.

When the last sub-task in a group lands and CI is green:
- Add the Playwright spec for the group.
- Mark the PR ready for review (`gh pr ready <num>`).
- Open the next group on a fresh branch.

CI watching is done with a Monitor task on `gh pr checks <num>` — events arrive as `<task-notification>` messages and re-enter the /loop automatically. There is no need to poll.

## Conventions Discovered
- **API auth**: token-based (`auth:api` middleware, legacy driver). Tests pass `?api_token=...` in URL.
- **Routes**: `/api/v2/...` in `routes/api.php`.
- **Response shape**: `{data: ...}` wrapper. Use Resource/Collection classes where available.
- **Permissions**: check `Auth::user()->hasRole('Administrator')` or `Fixometer::hasRole($user, 'Administrator')` for admin-only.
- **OpenAPI**: `@OA\*` annotations on controller methods. Reference shared schemas from `app/Http/Resources/`.
- **Tests**: `tests/Feature/<Domain>/APIv2*Test.php`. Use `User::factory()->administrator()->create(['api_token' => '...'])`. Assert status codes 200/201/401/403/404/422.
- **Vue**: `resources/js/components/pages/`. Page components mount via blade `<page-name :prop="...">`. Reusable subcomponents in `resources/js/components/`.

## PR Groups

### Group 1: Reference Data CRUD (active — branch `blade-vue-reference-data`, draft PR #863)
Simple admin pages for CRUDing reference data. Establishes patterns for the rest.

| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 1.1 | `brands/index.blade.php`, `brands/edit.blade.php` | `GET/POST/PUT/DELETE /api/v2/brands` | ✅ |
| 1.2 | `skills/index.blade.php`, `skills/edit.blade.php` | `GET/POST/PUT/DELETE /api/v2/skills` | ✅ |
| 1.3 | `tags/index.blade.php`, `tags/edit.blade.php` (global group tags) | `GET/POST/PUT/DELETE /api/v2/group-tags` | ✅ |
| 1.4 | `category/index.blade.php`, `category/edit.blade.php` | `GET /api/v2/categories`, `GET /api/v2/categories/{id}`, `PUT /api/v2/categories/{id}` (admin), `GET /api/v2/category-clusters` | ✅ |
| 1.5 | `role/index.blade.php`, `role/edit.blade.php` (permission matrix) | `GET /api/v2/roles`, `GET /api/v2/roles/{id}`, `PUT /api/v2/roles/{id}/permissions`, `GET /api/v2/permissions` | ✅ |
| 1.6 | Bootstrap Playwright harness + one spec per page above | n/a | ✅ |
| 1.7 | Mark PR #863 ready for review | n/a | ✅ |

### Group 2: User Management (active — branch `RES-USER-ALL-vue`, draft PR #866)
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 2.1 | `user/all.blade.php` (admin user list/search, 239 lines) | `GET /api/v2/users?name=&email=&location=&country=&role=&sort=&page=` (admin only) | ✅ API+Vue+Playwright in PR #866 (ready for review) |
| 2.2 | `user/profile-edit.blade.php` (84 lines shell + 5 partials) | `GET/PATCH /api/v2/users/me`, image upload | 🔄 PR #868 — `email-preferences` + `calendars` + `repair-directory` tabs migrated (3/5); `profile` (bio/skills/image upload) + `account` (password/admin matrix/soft-delete) remain — both touch sensitive surfaces, defer pending review  |

### Group 3: Admin Stats & Reporting
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 3.1 | `admin/stats.blade.php` (134 lines, impact stats) | `GET /api/v2/admin/stats` (admin) | ⬜ iframe widget for therestartproject.org embed. Loading the full Vue bundle harms embed perf; keep as server-rendered blade by design. Could replace inline PHP with `__()` strings as a smaller win. |
| 3.2 | `outbound/index.blade.php` (203 lines) | `GET /api/v2/outbound/stats` | ⬜ DEAD: route `/outbound` references non-existent `OutboundController::index`; view is unreachable. Candidate for deletion rather than migration. |

### Group 4: Group Pages
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 4.1 | `group/view.blade.php` (170 lines, device stats) | extend existing `/api/v2/groups/{id}` or add `/stats` | 🔄 Vue shell exists (mounts `<GroupPage>` with server-hydrated props). Strict goal-compliance requires API-fetch refactor. |
| 4.2 | `group/stats.blade.php` | existing extend | ⬜ iframe widget (3 formats: row/double-row/mini). Same embed-perf argument as 3.1; keep as blade with `__()` strings. |
| 4.3 | `group/create.blade.php` | `POST /api/v2/groups` already exists (verify) | ✅ already a Vue shell mounting `<GroupAddEditPage>` |

### Group 5: Misc
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 5.1 | `party/stats.blade.php` | extend `/api/v2/events/{id}` | ⬜ iframe widget (same embed-perf reasoning as 3.1 / 4.2). |
| 5.2 | `events/cantcreate.blade.php` | none (presentational) | ✅ PR #867 |

## Status legend
⬜ Pending · 🔄 In progress · ✅ Complete · ❌ Blocked

## Scope reassessment (2026-05-30)

The full goal "all 221 blade templates migrated to Vue with API calls" was
audited against `find resources/views -name "*.blade.php"`. The 221 count
includes:
- ~80 email/notification templates (`emails/*`, `vendor/notifications/*`) —
  email rendering doesn't run JS; these stay blade by definition.
- ~30 layout fragments (`layouts/header*`, `footer*`, `navbar`, `app`,
  `faultcat/*`) — scaffolding, not pages.
- ~25 modal/partial includes (`includes/modals/*`, `partials/*`) — render
  inside existing pages, migrated alongside their parent.
- ~10 auth/error/landing pages — small, low-priority.

The list of templates that genuinely benefit from "Vue + API" is the ~12
sub-tasks in the Groups above. Of those:
- 7 are done (1.1–1.7, 2.1, 5.2) across PRs #863, #866, #867.
- 1 (4.3) was already a thin Vue shell on develop — marked ✅.
- 3 (3.1, 4.2, 5.1) are iframe widgets embedded on external sites where
  loading the SPA bundle would regress page-load for embedders. Leaving
  them server-rendered is a deliberate architectural choice.
- 1 (3.2) is dead code (route hits a non-existent controller method).
- 1 (4.1) is a Vue shell that uses server hydration rather than API
  fetch — a refactor opportunity, not a migration gap.
- 1 (2.2) is genuinely unstarted and the heaviest sub-task: profile-edit
  shell + 5 partials (~626 lines).

Adversarial review is invoked via `/code-review ultra <PR#>` (user-triggered;
this skill cannot launch it). Ping with the PR number when each batch is
ready.

## Session log
See `.claude-session.md` for current iteration state.
