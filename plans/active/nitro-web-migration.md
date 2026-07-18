# Make Laravel API-only: move the web surface to the Nuxt/Nitro Node server

Goal: eliminate Laravel's remaining non-API web routes by moving the browser-
facing glue to Nitro server routes (`client/server/**`), so `restarters.net`
is served by the Nuxt Node server and Laravel is reached only at `/api/v2`.

## DEPLOY FIX IMPLEMENTED (2026-07-18) — pending preview validation
Done (commit on nuxt-client): the deploy image now builds AND serves the Nuxt
SPA; nginx is the split point (`/api` + retained widgets → Laravel, everything
else → Nitro). Files changed:
- `Dockerfile.fly`: builder runs `client/ npm ci && nuxt build` (drops
  client/node_modules after — .output is standalone); final image installs Node
  18 (runtime) to run the Nitro server.
- `docker/supervisord-fly.conf`: `[program:nuxt]` → `node client/.output/server/index.mjs` on 127.0.0.1:3000, inherits Fly env.
- `docker/nginx-fly.conf`: `^~ /api|/auth/bridge|/discourse|/export|/calendar|/outbound|/{group,admin,party}/stats` → php-fpm; `location / { try_files $uri @nuxt }` + `@nuxt` proxy to :3000; `/images/` now falls back to @nuxt (SPA-vs-widget image overlap). Redirectors dropped — the SPA owns user/reset, user/register, party|group/invite natively.
- `fly.pr.toml` / `fly.toml` / `fly.dev.toml`: `FRONTEND_URL` + `NUXT_PUBLIC_API_BASE` = the app's own origin (apiBase carries /api/v2).
CI unaffected (compose/CI use docker/nginx.conf + the client container, not these
Fly files). **Validate on a preview deploy — checklist:**
1. `/` loads the SPA (not a localhost redirect); deep link e.g. `/party/view/<id>` returns the SPA (200), client-router resolves.
2. `/_nuxt/*` assets 200 from Nitro; `/images/<spa-only>.svg` 200; a widget image (`/images/broken-toaster.png`) still 200 from Laravel disk.
3. `/api/v2/session` 200 from Laravel; login → dashboard works end-to-end.
4. A stats widget (`/group/stats/<id>`) + an export still render from Laravel.
5. Memory: 2GB VM now also runs Node — watch for OOM (php-fpm pm.max_children=200 is the risk); tune down if it bites.

## CRITICAL FINDING (2026-07-18): the branch has NO working deployment yet
The PR-preview "redirects to localhost" because the deploy image only runs
Laravel, and post-cutover Laravel's catch-all redirects `/` to
`config('restarters.frontend_url')` which **defaults to `http://localhost:3000`**
(`config/restarters.php:33`) and is unset in `fly.pr.toml`. Root causes:
- `Dockerfile.fly` builds only Laravel (`npm run production` = widget/wiki
  vite); it never builds `client/` (the Nuxt SPA).
- `docker/nginx-fly.conf` `location /` is `try_files … /index.php` → all
  requests hit Laravel; there is no Nitro upstream.
- `fly.pr.toml`/`fly.toml` set no `FRONTEND_URL` / `NUXT_PUBLIC_API_BASE`.
So neither preview NOR prod serves the SPA. Deploy fix (prerequisite for
everything, do FIRST):
1. `Dockerfile.fly`: build `client/` (`nuxt build`), and install Node in the
   FINAL image (currently only in the builder) to run the Nitro server.
2. `docker/supervisord-fly.conf`: add a `nuxt` program → `node .output/server/index.mjs` on 127.0.0.1:3000.
3. `docker/nginx-fly.conf`: route `location ^~ /api/` → php-fpm (Laravel);
   `location /` → `proxy_pass http://127.0.0.1:3000` (Nitro), keeping the auth
   gate + static asset handling.
4. `fly.pr.toml` (+ `fly.toml`) `[env]`: `FRONTEND_URL` and
   `NUXT_PUBLIC_API_BASE` = the app's own `https://…` URL.
Can only be validated on a preview deploy.

## Feasibility (confirmed)
- Client prod container runs `node .output/server/index.mjs` (Nitro Node
  server), so `client/server/routes/**` + `client/server/api/**` run in prod
  even with `ssr: false`. Server routes work regardless of SSR mode.

## Current web surface (routes/web.php) and disposition
| Route | Move to |
|---|---|
| `/{any?}` catch-all → SPA | **Delete.** Once Nitro is the entry point, Nuxt serves every path natively. |
| `user/reset`, `user/recover`, `user/register/{hash}`, `group|party/accept-invite`, `party|group/invite/{code}` | **Delete.** These 302 to the SPA today; when Nitro is the origin, Nuxt's own pages handle them (they already exist as SPA routes). |
| `/auth/bridge` (BridgeController) | **Nitro** `server/routes/auth/bridge.get.ts`. |
| `/discourse/sso` (package route, HMAC via `DiscourseService`, `DISCOURSE_SECRET`) | **Nitro** `server/routes/discourse/sso.get.ts`. |
| MediaWiki login (`LogInToWiki` listener, `mw_*` cookies) | Nitro sets the cookies returned by the thin-proxy. |
| `export/*`, `calendar/*`, stats widgets (`outbound/info`, `group/stats`, `admin/stats/*`, `party/stats`) | **Keep** (external consumers/partners use these URLs). Nitro reverse-proxies them to Laravel, OR they stay Laravel-served behind the same nginx. Product call. |

## Thin-proxy contract (keep secrets in Laravel)
New `/api/v2` endpoints the Nitro routes call (DISCOURSE_SECRET + MW creds stay
server-side in Laravel):
- `POST /api/v2/auth/sso-session {ticket}` → `{ user, mw_cookies: [...] }` —
  consumes the SsoTicket (already `SsoTicket::consume`), runs the wiki login,
  returns the user + the `mw_*` cookies to set. (Bridge.)
- `POST /api/v2/auth/discourse-sso {sso, sig, user_id}` → `{ redirect }` —
  validates the incoming sig, builds + HMAC-signs the response payload
  (port of `DiscourseService`), returns the Discourse return URL. (SSO provider.)

## Session
The bridge's job is to give a top-level navigation an identity cookie the SPA's
in-memory bearer token can't provide. Nitro sets a signed, httpOnly session
cookie (nitro `useSession` / `setCookie`) carrying the user id after validating
the ticket; `discourse/sso.get.ts` reads it to know the user before calling the
thin-proxy to sign.

## Entry-point flip (infra — needs a deploy)
nginx / Fly routing so `/api/*` → Laravel and everything else → the Nuxt Node
server. This is the enabling infra change; it is outward-facing and I can't
deploy it. Once flipped, the redirectors + catch-all are dead.

## Steps
1. Laravel: add the two thin-proxy `/api/v2/auth/*` endpoints (+ tests, mocking
   the MW/Discourse calls). Port the `DiscourseService` signing verbatim.
2. Nitro: `server/routes/auth/bridge.get.ts` + `server/routes/discourse/sso.get.ts`
   + session cookie; unit-test with Nitro's test utils.
3. Config: nginx/Fly entry-point flip; run the Nuxt Node server as the origin.
4. Delete the Laravel web routes (bridge, redirectors, catch-all); rewrite
   `ApiOnlyRouteSurfaceTest` to assert an (almost) empty web surface.
5. Update the SPA's SSO links (`useSsoBridge`) to the new same-origin paths.

## Validation limit (important)
Unlike the rest of this session, the SSO + wiki flow can't be end-to-end tested
locally (no Discourse/MediaWiki + needs the routing flip). Unit-test the pieces
(HMAC correctness, ticket→user, cookie shaping), then **validate on a preview
deploy** (restarters-dev) before merging. This is why it's tracked separately
rather than folded into the G6 PR.
