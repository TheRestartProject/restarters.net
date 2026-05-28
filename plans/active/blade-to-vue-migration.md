# Blade-to-Vue Migration Plan

## Goal
Migrate remaining non-trivial Blade templates to Vue components. For each, introduce v2 API endpoints (with OpenAPI annotations and PHPUnit tests), then build Vue components reusing existing primitives. End-to-end via Playwright.

Approach: TDD (failing PHP test → API → Vue → Playwright). Group templates into related PR-sized batches.

## Conventions Discovered
- **API auth**: token-based (`auth:api` middleware, legacy driver). Tests pass `?api_token=...` in URL.
- **Routes**: `/api/v2/...` in `routes/api.php`.
- **Response shape**: `{data: ...}` wrapper. Use Resource/Collection classes where available.
- **Permissions**: check `Auth::user()->hasRole('Administrator')` or `Fixometer::hasRole($user, 'Administrator')` for admin-only.
- **OpenAPI**: `@OA\*` annotations on controller methods. Reference shared schemas from `app/Http/Resources/`.
- **Tests**: `tests/Feature/<Domain>/APIv2*Test.php`. Use `User::factory()->administrator()->create(['api_token' => '...'])`. Assert status codes 200/201/401/403/404/422.
- **Vue**: `resources/js/components/pages/`. Page components mount via blade `<page-name :prop="...">`. Reusable subcomponents in `resources/js/components/`.

## PR Groups

### Group 1: Reference Data CRUD (active — branch `blade-vue-reference-data`)
Simple admin pages for CRUDing reference data. Establishes patterns for the rest.

| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 1.1 | `brands/index.blade.php`, `brands/edit.blade.php` | `GET/POST/PUT/DELETE /api/v2/brands` | ⬜ |
| 1.2 | `skills/index.blade.php`, `skills/edit.blade.php` | `GET/POST/PUT/DELETE /api/v2/skills` | ⬜ |
| 1.3 | `tags/index.blade.php`, `tags/edit.blade.php` (global group tags) | `GET/POST/PUT/DELETE /api/v2/group-tags` | ⬜ |
| 1.4 | `category/index.blade.php`, `category/edit.blade.php` | `GET/POST/PUT/DELETE /api/v2/categories` | ⬜ |
| 1.5 | `role/index.blade.php`, `role/edit.blade.php` (permission matrix) | `GET/POST/PUT/DELETE /api/v2/roles` + permission update | ⬜ |
| 1.6 | Bootstrap Playwright harness + one spec per page above | n/a | ⬜ |
| 1.7 | Open PR | n/a | ⬜ |

### Group 2: User Management
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 2.1 | `user/all.blade.php` (admin user list/search, 239 lines) | `GET /api/v2/users?q=&country=&role=&page=` (admin only) | ⬜ |
| 2.2 | `user/profile-edit.blade.php` (84 lines) | `GET/PATCH /api/v2/users/me`, image upload | ⬜ |

### Group 3: Admin Stats & Reporting
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 3.1 | `admin/stats.blade.php` (134 lines, impact stats) | `GET /api/v2/admin/stats` (admin) | ⬜ |
| 3.2 | `outbound/index.blade.php` (203 lines) | `GET /api/v2/outbound/stats` | ⬜ |

### Group 4: Group Pages
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 4.1 | `group/view.blade.php` (170 lines, device stats) | extend existing `/api/v2/groups/{id}` or add `/stats` | ⬜ |
| 4.2 | `group/stats.blade.php` | existing extend | ⬜ |
| 4.3 | `group/create.blade.php` | `POST /api/v2/groups` already exists (verify) | ⬜ |

### Group 5: Misc
| # | Template(s) | API endpoints | Status |
|---|---|---|---|
| 5.1 | `party/stats.blade.php` | extend `/api/v2/events/{id}` | ⬜ |
| 5.2 | `events/cantcreate.blade.php` | none (presentational) | ⬜ |

## Status legend
⬜ Pending · 🔄 In progress · ✅ Complete · ❌ Blocked

## Session log
See `.claude-session.md` for current iteration state.
