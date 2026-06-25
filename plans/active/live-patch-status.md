# Live Container Patch Status
Last updated: 2026-05-12 (bootstrap/app.php all-env putenv patch + pcntl fix)

## Monkey-Patched Files — ALL VERIFIED LIVE ✅

| File | Change | Live Status |
|------|--------|-------------|
| `app/Listeners/LogInToWiki.php` | Fix CookieJar for Guzzle 7: explicit `CookieJar` + `ActionApi` constructor injection | ✅ Confirmed live |
| `app/Providers/MediawikiServiceProvider.php` | `Addwiki\Mediawiki\Api\` namespace + v3.x `MediaWiki::newFromEndpoint()` | ✅ Confirmed live |
| `app/Listeners/ChangeWikiPassword.php` | `Addwiki\` namespace + `ActionRequest::simplePost()` | ✅ Confirmed live |
| `app/Http/Middleware/AddCorsHeaders.php` | New CORS middleware for `/api/` routes | ✅ Confirmed live |
| `app/Http/Kernel.php` | `AddCorsHeaders` in api middleware group | ✅ Confirmed live |
| `docker/nginx-fly.conf` (as `/etc/nginx/nginx.conf`) | `map $request_uri $x_frame_options` block | ✅ Confirmed live |
| `resources/views/layouts/navbar.blade.php` | `@php($navbarNotifications = ...)` — must use parenthesised form; block `@php...@endphp` is NOT compiled by this Blade version (leaves `@php` as literal text, only compiles `@endphp` → `?>`) | ✅ Confirmed live |
| `public/index.php` | `putenv()` overrides for DISCOURSE_SECRET and WP_XMLRPC_PSWD — `Dotenv::createImmutable()` lets system env WIN over .env, so broken fly secrets (D$ truncated at #, trailing dot) need putenv before bootstrap | ✅ Confirmed live |
| `bootstrap/app.php` | Sets `$_ENV`, `$_SERVER`, and `putenv()` for ALL broken/missing fly secrets — runs for web, CLI, queue, AND config:cache. PHP-FPM clears env so web was fine; CLI/queue inherit fly's `$_ENV` directly so putenv() alone isn't enough. Container-only (NOT committed — contains secrets) | ✅ Confirmed live |
| `app/Listeners/AddUserToDiscourseThreadForEvent.php` | `function_exists('pcntl_signal')` guard before `pcntl_signal()`/`pcntl_alarm()` calls — PHP throws fatal error on hosts without pcntl extension; caused 146 failed Discourse jobs (retried ✅) | ✅ Confirmed live |
| `vendor/spinen/laravel-discourse-sso/src/Controllers/SsoController.php` | `castBooleansToString(string\|bool\|null $property): ?string` — PHP 8 TypeError when null bio resolves; vendor patch accepts null (permanent fix is User.php accessor) | ✅ Confirmed live |
| `app/User.php` | `getBiographyAttribute` returns `''` not null — proper fix for SSO TypeError; biography is nullable but castBooleansToString requires string\|bool | ✅ Confirmed live |

All patches also committed to local master branch (not pushed).

## `/var/www/.env` Monkey-Patch (re-applied after each container restart)

These secrets are NOT in fly.toml or fly secrets — must be re-written to `/var/www/.env` after every container restart.

```
FEATURE__DISCOURSE_INTEGRATION=true
DISCOURSE_SECRET="D$#9WK38Kku%TiOVB6b*oZ*#%Q3GZ%"
DISCOURSE_URL=https://talk.restarters.net
DISCOURSE_APIKEY=3d37c119ddc4beaaee5b532ba7c209a55131468e0abd1950ba45c38a337e6b7d
DISCOURSE_APIUSER=neil
PLATFORM_COMMUNITY_URL=https://talk.restarters.net
FEATURE__WIKI_INTEGRATION=true
WIKI_URL=https://wiki.restarters.net
WIKI_APIUSER=Wiki-api-restarters
WIKI_APIPASSWORD=cf2QA9asR3jp4jCmr5UT2RLixQ7KSh55
WIKI_DB=wiki_db
WIKI_DB_USER=4ff6e4a1b49d
WIKI_DB_PASSWORD=b0b86c8ee688020b
WP_XMLRPC_ENDPOINT=https://therestartproject.org/fxm.php
WP_XMLRPC_USER=fixometer
WP_XMLRPC_PSWD=giannutri15Stone$87
```

**After writing `.env`**: run `php artisan config:clear && php artisan queue:restart`

## Staged Secrets (to be set permanently via `fly secrets set`)

| Secret | Value | Notes |
|--------|-------|-------|
| `DISCOURSE_SECRET` | `D$#9WK38Kku%TiOVB6b*oZ*#%Q3GZ%` | Not in fly.toml |
| `DISCOURSE_APIKEY` | `3d37c119...` | Not in fly.toml |
| `WIKI_APIPASSWORD` | `cf2QA9asR3jp4jCmr5UT2RLixQ7KSh55` | Not in fly.toml |
| `WIKI_DB_USER` | `4ff6e4a1b49d` | Not in fly.toml |
| `WIKI_DB_PASSWORD` | `b0b86c8ee688020b` | Not in fly.toml |
| `WP_XMLRPC_PSWD` | `giannutri15Stone$87` | fly.toml had trailing dot |

## Live Env Vars (key ones)

| Var | Value | Notes |
|-----|-------|-------|
| `WP_XMLRPC_ENDPOINT` | `https://therestartproject.org/fxm.php` | Custom alias — both this and `/xmlrpc.php` return 405 (XML-RPC disabled globally) |
| `WP_XMLRPC_USER` | `fixometer` | |
| `DISCOURSE_URL` | `https://talk.restarters.net` | |
| `DISCOURSE_APIUSER` | `neil` | |
| `WIKI_URL` | `https://wiki.restarters.net` | |
| `WIKI_APIUSER` | `Wiki-api-restarters` | |
| `FEATURE__WIKI_INTEGRATION` | `true` | |
| `FEATURE__DISCOURSE_INTEGRATION` | `true` | |

## Git: master commits NOT yet on production branch

Production branch is ~17 commits behind master. Includes:
- `36188742dc` — Wiki CookieJar fix + correct WP XMLRPC endpoint (fxm.php)
- `e315a144e0` — Wiki namespace fix (3 files)
- nginx X-Frame-Options map, AddCorsHeaders, Kernel wiring
- N+1 / performance fixes

**Deploy command** (when ready — merges everything + applies staged secrets):
```bash
git checkout production
git merge master -m "Merge production fixes from master"
git push origin production
```

## Pending Actions

| # | Action | Owner | Status |
|---|--------|-------|--------|
| 1 | Enable XML-RPC on therestartproject.org WordPress | WP admin | ❌ Blocked — XML-RPC disabled globally (both /xmlrpc.php and /fxm.php return 405) |
| 2 | Retry WP push for event 21076 | Dev | ⏳ Waiting on #1 |
| 3 | Deploy production branch | Dev | ⏳ Awaiting decision |
| 4 | MySQL DB deploy (`fly deploy -c fly-mysql.toml`) | Dev | ⏳ Pending |
| 5 | Discourse queue check | Dev | ⏳ Pending |

## Fly IPs (for Wordfence allowlist if needed)

```
205.234.240.77                      (IPv4, LHR)
2605:4c40:94:1dad:0:42c:2cfb:1      (IPv6, LHR)
```

## Notes

- `fly ssh console --command` needs `bash -c '...'` for pipes/redirects
- Fly token expires quickly — pass as `FLY_API_TOKEN` env var, not config file
- Patches survive PHP-FPM reloads but NOT container restarts/deploys
- `/tmp/wp-push-21076.php` exists on container (updated to use fxm.php)
