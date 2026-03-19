# Fly.io Migration Plan for Restarters.net

**Branch:** `RES-2060_fly_io_deployment`
**Date prepared:** 2026-03-19
**Target region:** `lhr` (London Heathrow)

---

## Table of Contents

1. [Current State Summary](#1-current-state-summary)
2. [Fly.io Architecture](#2-flyio-architecture)
3. [Pre-migration Checklist](#3-pre-migration-checklist)
4. [Service Dependencies and Reconfiguration](#4-service-dependencies-and-reconfiguration)
5. [Data Migration](#5-data-migration)
6. [DNS Cutover Strategy](#6-dns-cutover-strategy)
7. [Smoke Tests](#7-smoke-tests)
8. [Rollback Plan](#8-rollback-plan)
9. [Post-Cutover Monitoring](#9-post-cutover-monitoring)
10. [Timeline](#10-timeline)

---

## 1. Current State Summary

### Current Hosting

- **Server:** `restart-sp` (ServerPilot-managed, app root at `/srv/users/serverpilot/apps/restarters`)
- **Web server:** Nginx + PHP-FPM
- **Database:** MySQL (local or managed, on the same server)
- **File storage:** Local filesystem at `public/uploads/`
- **SSL:** Managed by ServerPilot / Let's Encrypt
- **CI/CD:** CircleCI runs tests; deployment mechanism is separate from Fly.io

### Key Domains

- `restarters.net` - main application
- `talk.restarters.net` - Discourse (Restarters Talk) -- externally hosted, not migrated
- `wiki.restarters.net` - MediaWiki (Restarters Wiki) -- externally hosted, not migrated
- `map.restarters.net` - Repair Directory -- same server as main app, may need separate migration
- `therestartproject.org` - WordPress site -- separate, not migrated
- `mg.restarters.net` - Mailgun sending domain (EU, `MAILGUN_DOMAIN`) -- DNS records must not change
- `mg.rstrt.org` - from address domain (`MAIL_FROM_ADDRESS=noreply@mg.rstrt.org`) -- **⚠️ DMARC misalignment**: this domain is on US Mailgun but mail is sent via EU Mailgun domain `mg.restarters.net` (see section 4.2)

---

## 2. Fly.io Architecture

The Fly.io setup consists of four apps, all in the `lhr` region:

| Fly App | Config File | Purpose | VM Size |
|---|---|---|---|
| `restarters` | `fly.toml` | Main Laravel app (Nginx + PHP-FPM + cron + queue worker via supervisord) | shared-cpu-1x, 1024 MB |
| `restarters-db` | `fly-mysql.toml` | MySQL 8.0 with persistent volume (`mysqldata`) | shared-cpu-1x, 2048 MB |
| `restarters-pma` | `fly-pma.toml` | phpMyAdmin (no public endpoint; access via `fly proxy`) | shared-cpu-1x, 256 MB | **Suspended** |
| `restarters-yesterday` | `fly-yesterday.toml` | Yesterday's DB restore for debugging (auto-stop enabled) | shared-cpu-1x, 1024 MB | **Suspended** (+ its DB `restarters-db-yesterday` also stopped) |

### Container Architecture (`Dockerfile.fly`)

Two-stage build:
1. **Builder stage** (php:8.2-cli): Composer install, npm install, Vite production build, Swagger generation
2. **Runtime stage** (php:8.2-fpm): Nginx + PHP-FPM + cron + supervisord queue worker

Key runtime processes managed by `supervisord-fly.conf`:
- **php-fpm** -- serves PHP requests via Unix socket
- **nginx** -- reverse proxy, static files, Tigris proxy for `/uploads/`
- **cron** -- Laravel scheduler (`php artisan schedule:run` every minute)
- **queue-worker** -- `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600`

### Startup Flow (`/.fly/scripts/startup.sh`)

1. Ensure storage/cache directories exist with correct ownership
2. Substitute Tigris bucket URL into nginx config via `envsubst`
3. Background subshell: wait for MySQL (up to 60s), run migrations, cache config/routes/views
4. Immediately start supervisord (so health check passes while DB setup runs)

### Image Storage

- **Current:** Local filesystem at `public/uploads/`
- **Fly.io:** Tigris S3-compatible storage (`https://fly.storage.tigris.dev`)
- **Serving:** Nginx proxies `/uploads/*` requests to Tigris bucket, with 30-day cache headers
- **Application writes:** Via Laravel's S3 filesystem driver (`FILESYSTEM_DISK=s3`)
- **Note:** The legacy `FixometerFile` helper uses `$_SERVER['DOCUMENT_ROOT'].'/uploads/'` for direct file writes. This code path will need to work with Tigris. Since `FILESYSTEM_DISK=s3` is set and the nginx proxy handles reads, new uploads via the S3 driver will work. However, `FixometerFile::upload()` writes directly to the local filesystem -- this is a known issue that needs a separate code migration or may already be handled by the S3 disk being the default.

### Deploy Workflow

The GitHub Actions workflow (`.github/workflows/fly-deploy.yml`) is prepared but **not yet activated**. It triggers on the disabled branch name `DISABLED-fly-deploy`. To activate: change the branch filter to `production` (or `main`/`develop` for staging) and add `FLY_API_TOKEN` to GitHub repo secrets.

**Current CI:** CircleCI runs tests on all branches. Deploy to Fly.io is manual via `fly deploy`. The workflow file also contains commented instructions for adding a CircleCI deploy job that runs after tests pass.

---

## 3. Pre-migration Checklist

### 2-3 Weeks Before Migration

- [x] **Confirm Fly.io apps are created and working** on staging *(DONE — all 4 apps exist)*
  - `restarters` — deployed, shared-cpu-1x/1024MB
  - `restarters-db` — deployed, shared-cpu-1x/2048MB, MySQL 8.0 with persistent volume
  - `restarters-pma` — suspended (start on demand via `fly machine start`)
  - `restarters-yesterday` — suspended (start on demand)
- [ ] **Run `scripts/fly-migrate.sh --dry-run`** on the production server to validate the script
- [x] **Provision Tigris bucket** *(DONE — `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, plus `BUCKET_NAME`, `AWS_ENDPOINT_URL_S3`, `AWS_REGION` all set)*
- [ ] **Test image upload/download** on staging: upload an image via the app, confirm it appears via the Tigris-proxied `/uploads/` path
- [ ] **Verify Discourse SSO** works on staging (if `FEATURE__DISCOURSE_INTEGRATION` is to be enabled)
- [x] **Set up Sentry** *(DONE — `SENTRY_LARAVEL_DSN` set as Fly secret)*
- [x] **Review Fly secrets** are complete (see section 4) *(DONE — see status below)*

### 1 Week Before Migration

- [ ] **Lower DNS TTL** for `restarters.net` and relevant subdomains to **300 seconds** (5 minutes)
  - DNS is hosted at **iwantmyname.com** (nameservers: `dns1/2/3.iwantmyname.com`)
  - Current TTL is **3600s** for all records
  - Records that need TTL lowered:
    - `restarters.net` — A record → `139.59.184.196` (current production server)
    - `www.restarters.net` — CNAME → `restarters.net`
    - `map.restarters.net` — A record → `139.59.184.196` (same server, may need separate migration)
  - Records that do NOT need changing (external services):
    - `talk.restarters.net` — CNAME → `restarters.discoursehosting.net` (Discourse, externally hosted)
    - `wiki.restarters.net` — A record → `165.22.123.158` (MediaWiki, separate server)
    - `mg.restarters.net` — Mailgun subdomain (SPF + MX + DKIM set, do not change)
- [ ] **Document current DNS records** for rollback purposes
  - `restarters.net` A → `139.59.184.196` (current production)
  - `www.restarters.net` CNAME → `restarters.net`
  - `map.restarters.net` A → `139.59.184.196`
  - `talk.restarters.net` CNAME → `restarters.discoursehosting.net`
  - `wiki.restarters.net` A → `165.22.123.158`
  - `mg.restarters.net` — Mailgun (TXT SPF, MX, DKIM records)
- [ ] **Final staging test** with production data
  - Run `fly-migrate.sh --db-only` to load a recent production DB snapshot
  - Run `fly-migrate.sh --images-only` to sync all images to Tigris
  - Verify the staging site with real data

### Day Before Migration

- [x] **Daily production database backups** *(DONE — already running daily backups)*
- [ ] **Verify DNS TTL is at 300s** (check with `dig restarters.net`)
- [ ] **Notify users** of planned maintenance window if appropriate
- [ ] **Verify Fly.io deploy workflow** is ready to activate (secrets set in GitHub, branch filter prepared)

---

## 4. Service Dependencies and Reconfiguration

### 4.1 Secrets Required on Fly.io

These are set via `fly secrets set` or `fly secrets import` (the `fly-migrate.sh` script handles this).

**Status as of 2026-03-19:** 39 secrets are set on the `restarters` app. 2 secrets are set on `restarters-db`.

#### Secrets already set (✓ = confirmed on Fly.io)

| Secret | Purpose | Status |
|---|---|---|
| `APP_KEY` | Laravel encryption key | ✓ Set |
| `DB_USERNAME` / `DB_PASSWORD` | Fly MySQL credentials | ✓ Set (also `MYSQL_PASSWORD` / `MYSQL_ROOT_PASSWORD` on `restarters-db`) |
| `DISCOURSE_URL` | Discourse base URL | ✓ Set |
| `DISCOURSE_SECRET` | SSO shared secret | ✓ Set |
| `DISCOURSE_APIKEY` / `DISCOURSE_APIUSER` | API credentials | ✓ Set |
| `WIKI_URL`, `WIKI_DB`, `WIKI_APIUSER`, `WIKI_APIPASSWORD` | MediaWiki integration | ✓ Set (integration currently disabled) |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | SMTP config | ✓ Set |
| `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_ENDPOINT` | Mailgun API | ✓ Set |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` | Tigris S3 storage | ✓ Set |
| `AWS_ENDPOINT_URL_S3`, `AWS_REGION`, `BUCKET_NAME` | Tigris additional config | ✓ Set (extra, not in plan originally) |
| `MAPBOX_TOKEN` | Map rendering | ✓ Set |
| `GOOGLE_API_CONSOLE_KEY` | Geocoding | ✓ Set |
| `GOOGLE_ANALYTICS_TRACKING_ID`, `GOOGLE_TAG_MANAGER_ID` | Analytics | ✓ Set |
| `SENTRY_LARAVEL_DSN` | Error monitoring | ✓ Set |
| `DRIP_API_TOKEN`, `DRIP_ACCOUNT_ID` | Drip email marketing | ✓ Set |
| `CALENDAR_HASH` | Calendar feed | ✓ Set |
| `REPAIRDIRECTORY_URL` | Repair Directory link | ✓ Set |
| `SUPPORT_EMAIL_ADDRESS` | Support contact | ✓ Set |
| `SEND_COMMAND_LOGS_TO` | Command log recipient | ✓ Set |
| `FEATURE__DISCOURSE_INTEGRATION` | Feature flag (currently `false`) | ✓ Set as secret (overrides fly.toml) |
| `FEATURE__WIKI_INTEGRATION` | Feature flag (currently `false`) | ✓ Set as secret (overrides fly.toml) |

#### Secrets NOT yet set (need action before production cutover)

| Secret | Purpose | Action Required |
|---|---|---|
| `MAIL_FROM_ADDRESS` | Email "from" address | **Critical.** Currently defaults to `hello@example.com`. Set from production `.env` |
| `MAIL_FROM_NAME` | Email "from" name | **Critical.** Currently defaults to `Example`. Set from production `.env` |
| `WP_XMLRPC_ENDPOINT` | WordPress XML-RPC URL | Set from production `.env`. Used by event/group sync commands. |
| `WP_XMLRPC_USER` | WordPress XML-RPC username | Set from production `.env` |
| `WP_XMLRPC_PSWD` | WordPress XML-RPC password | Set from production `.env` |
| `DRIP_CAMPAIGN_ID` | Drip campaign ID | Set from production `.env` (may be empty if not used) |

### 4.2 Mailgun

**Reconfiguration needed:** Yes — `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` are missing from Fly secrets.

**Verified on 2026-03-19:**
- ✅ **Test email sent successfully** from Fly.io to `edward@therestartproject.org`
- ✅ **Mail driver:** `MAIL_MAILER=mailgun` (uses Mailgun HTTP API, not SMTP)
- ✅ **Mailgun domain:** `mg.restarters.net` (EU endpoint: `api.eu.mailgun.net`)
- ✅ **SPF:** `mg.restarters.net` has SPF record: `v=spf1 include:eu.mailgun.org ~all`
- ✅ **DKIM:** `mta._domainkey.mg.restarters.net` has RSA key set
- ⚠️ **No DMARC record** for `mg.restarters.net` (not strictly required but recommended)
- ❌ **`MAIL_FROM_ADDRESS` not set** — defaults to `hello@example.com`. Must be set from production `.env`
- ❌ **`MAIL_FROM_NAME` not set** — defaults to `Example`. Must be set from production `.env`

**No DNS changes needed for email** — Mailgun uses `mg.restarters.net` subdomain which has its own SPF/DKIM/MX records independent of the main A record.

**⚠️ Pre-existing DMARC alignment issue (not caused by migration):**
The from address (`noreply@mg.rstrt.org`) is on a different domain to the Mailgun sending domain (`mg.restarters.net`). DKIM is signed by `mg.restarters.net` but the From header says `mg.rstrt.org`, so DMARC alignment fails. This is the same on the current production server. Test email to `edward@ehibbert.org.uk` arrived but showed DMARC failure. Options to fix:
1. Change `MAIL_FROM_ADDRESS` to `noreply@mg.restarters.net` (simplest — keeps EU Mailgun, aligns domains)
2. Add `mg.rstrt.org` as a verified domain on the EU Mailgun account and update its DNS to point to EU Mailgun
3. Leave as-is — emails deliver but may be flagged by strict receivers

### 4.3 Discourse Integration

**Reconfiguration needed:** No — domain stays the same, confirmed no IP matching.

**Verified on 2026-03-19:**
- ✅ **Feature flag fixed:** `FEATURE__DISCOURSE_INTEGRATION=true` now set as Fly secret (was incorrectly `false`)
- ✅ **Community Tech confirmed** they do not do any IP matching, so the Discourse SSO will work as long as the domain stays the same
- The Restarters app acts as an SSO provider for Discourse. The Discourse instance is configured with a `sso_url` pointing to `https://restarters.net/discourse/sso`.
- After DNS cutover, this URL will resolve to Fly.io. **No Discourse config change is needed.**
- If testing on `restarters.fly.dev` before DNS cutover, Discourse SSO will not work (URL mismatch). This is expected.
- The `DISCOURSE_URL` secret points Restarters to Discourse -- this does not change.
- **Scheduled tasks** that sync with Discourse (`discourse:syncgroups` every 15 minutes) will run via cron on the Fly.io container.

### 4.4 MediaWiki / Wiki Integration

**Reconfiguration needed:** No.

**Verified on 2026-03-19:**
- ✅ **Feature flag fixed:** `FEATURE__WIKI_INTEGRATION=true` now set as Fly secret (was incorrectly `false`)
- ✅ Wiki secrets are all set (`WIKI_URL`, `WIKI_DB`, `WIKI_APIUSER`, `WIKI_APIPASSWORD`)
- Wiki is hosted separately at `wiki.restarters.net` (165.22.123.158) — not affected by migration
- Wiki-related event listeners (password change, login/logout sync) will work from Fly.io as they connect outbound

### 4.5 WordPress XML-RPC

**Reconfiguration needed:** Secrets need to be set.

**Verified on 2026-03-19:**
- ✅ **Endpoint reachable from Fly.io:** `https://therestartproject.org/fxm.php` returns HTTP 200 (POST)
- ❌ **Secrets not yet set:** `WP_XMLRPC_ENDPOINT`, `WP_XMLRPC_USER`, `WP_XMLRPC_PSWD` must be copied from production `.env`
- WordPress event/group sync happens via queue jobs (listeners like `CreateWordpressPostForEvent`, `EditWordpressPostForGroup`, etc.).
- The queue worker on Fly.io will process these jobs.
- No IP-based access restrictions on the WordPress side.

### 4.6 Geocoding (Google Maps / Mapbox)

**Reconfiguration needed:** No.

- Both `GOOGLE_API_CONSOLE_KEY` and `MAPBOX_TOKEN` are API keys used for outbound requests. They are domain-restricted or unrestricted.
- **If domain-restricted:** Ensure `restarters.net` is in the allowed origins (not the old server's IP). Since the domain stays the same, this should already be correct.

### 4.7 Drip Email Marketing

**Reconfiguration needed:** No.

- Outbound API calls only. Credentials copied as Fly secrets.

### 4.8 Repair Directory

**Reconfiguration needed:** No.

- `REPAIRDIRECTORY_URL` is used for linking to the repair directory. Outbound only.

---

## 5. Data Migration

### 5.1 Database Migration

The `scripts/fly-migrate.sh` script handles this in three phases. For the database:

**Approach:** mysqldump from production, import into Fly MySQL via `fly proxy`.

```bash
# On the production server (restart-sp):

# Phase 1 - Set secrets (do this first, well before cutover)
./scripts/fly-migrate.sh --secrets-only

# Phase 2 - Database (do this during maintenance window)
./scripts/fly-migrate.sh --db-only
```

**Detailed steps performed by the script:**

1. `mysqldump` the production database with `--single-transaction` (non-blocking)
2. Strip `DEFINER` clauses from the dump (production MySQL users don't exist on Fly)
3. Start `fly proxy` to tunnel to `restarters-db.internal:3306`
4. Drop and recreate the target database for idempotent re-runs
5. Import the dump via `mysql` through the proxy
6. Grant privileges to the `restarters` user
7. Kill the proxy

**Estimated dump size:** Depends on production data. Test with `--dry-run` first.

**Important considerations:**
- The proxy approach means the import speed depends on network bandwidth between the production server and Fly.io (London region). For large databases, consider compressing the dump or using a faster import method.
- Migrations will run automatically on Fly.io container startup (via `startup.sh`), so any pending migrations will be applied after the import.
- The production `sessions` table will be imported, but sessions from the old server won't be valid on Fly.io (different `APP_KEY` usage context). Users will need to log in again.

### 5.2 File/Image Storage Migration

**Approach:** `aws s3 sync` from production `public/uploads/` to Tigris bucket.

```bash
# On the production server:

# Phase 3 - Images (can be done incrementally before cutover)
./scripts/fly-migrate.sh --images-only
```

**Detailed steps:**

1. Read `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, and `AWS_BUCKET` from `.env`
2. `aws s3 sync` from `public/uploads/` to `s3://${BUCKET}/` using `--endpoint-url https://fly.storage.tigris.dev --size-only`

**Key notes:**
- **Incremental sync:** `--size-only` means only new/changed files are uploaded. This can be run multiple times before cutover to keep Tigris in sync.
- **Run a full sync days before cutover**, then a final incremental sync during the maintenance window.
- **Serving:** On Fly.io, nginx proxies `/uploads/*` to the Tigris bucket (configured in `docker/nginx-fly.conf` and substituted at startup). The app code does not need changes for reading images.
- **Writing:** New uploads will go to Tigris via Laravel's S3 filesystem driver. The legacy `FixometerFile` helper that writes to local disk will need attention (it uses `$_SERVER['DOCUMENT_ROOT'].'/uploads/'`). Verify whether this code path is still active in production.

### 5.3 Croppa (Image Thumbnailing)

**Verified on 2026-03-19:** `Croppa::url()` is **not called anywhere** in the application code or templates. Dynamic Croppa resizing is NOT actively used. All thumbnails are pre-generated at upload time (prefixed `thumbnail_*`, `mid_*`).

This means:
- Pre-generated thumbnails are uploaded to Tigris alongside the originals by the sync script — no special handling needed
- Croppa config exists but is effectively unused for runtime image processing
- **No action required** for Croppa during migration

---

## 6. DNS Cutover Strategy

### Prerequisites

- All data migration phases complete (secrets, database, images)
- Staging verification complete on `restarters.fly.dev`
- DNS TTL already lowered to 300s (done 1 week prior)

### Step-by-Step Cutover

#### Step 1: Final Data Sync (Maintenance Window Start)

```bash
# Put production into maintenance mode (optional but recommended)
ssh restart-sp
cd /srv/users/serverpilot/apps/restarters
php artisan down --message="We're upgrading our infrastructure. Back shortly."

# Final incremental image sync
./scripts/fly-migrate.sh --images-only

# Final database sync (this is the point of no return for data)
./scripts/fly-migrate.sh --db-only
```

#### Step 2: Update Fly.io Configuration for Production

Before pointing DNS, update the Fly.io app configuration:

```bash
# Update fly.toml env vars for production
# APP_ENV=production
# APP_URL=https://restarters.net
# FEATURE__DISCOURSE_INTEGRATION=true (if ready)
# SENTRY_ENVIRONMENT=production

# Set the production APP_URL as a Fly secret (overrides fly.toml)
fly secrets set APP_URL=https://restarters.net APP_ENV=production -a restarters

# Deploy with production config
fly deploy -a restarters
```

#### Step 3: Add Custom Domain and TLS Certificate on Fly.io

**Current state (2026-03-19):** No custom domains or certificates configured yet. The app is only accessible via `restarters.fly.dev`.

**Allocated IPs:**
- IPv4 (shared): `66.241.124.187`
- IPv6 (dedicated): `2a09:8280:1::ce:b85f:0`

```bash
# Add the custom domain
fly certs add restarters.net -a restarters

# If using www subdomain:
fly certs add www.restarters.net -a restarters

# Verify certificate status
fly certs show restarters.net -a restarters
```

Fly.io will automatically provision a TLS certificate via Let's Encrypt. This requires DNS to be pointed to Fly.io, so steps 3 and 4 happen in close succession.

**Certificate Renewal:** Fly.io handles TLS certificate renewal automatically. Certificates are issued via Let's Encrypt and renewed before expiry (typically 30 days before the 90-day expiry). No manual intervention or cron jobs are needed. However:

- The custom domain must remain configured in Fly.io (`fly certs list -a restarters`)
- DNS must continue pointing to Fly.io for the ACME HTTP-01 challenge to succeed
- If certificate renewal fails, Fly.io will retry and send notifications
- Monitor with: `fly certs check restarters.net -a restarters`
- Unlike ServerPilot (which also auto-renews via Let's Encrypt), Fly.io manages certs at the edge/proxy layer, not on the application container

#### Step 4: Update DNS Records

Change the DNS A/AAAA records for `restarters.net`:

```
# Option A: CNAME (if apex domain supports it, e.g., Cloudflare CNAME flattening)
restarters.net.    300    CNAME    restarters.fly.dev.

# Option B: A/AAAA records (if CNAME not possible at apex)
# Current Fly.io IPs for restarters:
restarters.net.    300    A        66.241.124.187
restarters.net.    300    AAAA     2a09:8280:1::ce:b85f:0
```

**Note:** The IPv4 address is a **shared** IP. If using Cloudflare as the DNS provider, you can use CNAME flattening at the apex (recommended). Otherwise, use A/AAAA records. If a dedicated IPv4 is needed later: `fly ips allocate-v4 -a restarters` (costs ~$2/month).

#### Step 5: Verify TLS Certificate

```bash
# Wait for certificate to be issued (usually 1-5 minutes after DNS propagation)
fly certs check restarters.net -a restarters

# Verify in browser
curl -I https://restarters.net
```

#### Step 6: Verify Application

Run the full smoke test suite (see section 7).

#### Step 7: Restore DNS TTL

Once everything is confirmed working (24-48 hours after cutover):

```
restarters.net.    3600    CNAME    restarters.fly.dev.
```

#### Step 8: Decommission Old Server

After 1-2 weeks of stable operation:

- [ ] Stop services on the old server
- [ ] Keep the server available (but not serving traffic) for 30 days as a safety net
- [ ] Archive the final production backup
- [ ] Terminate the old server

---

## 7. Smoke Tests

### Immediate (within 5 minutes of DNS cutover)

- [ ] **Homepage loads** -- `https://restarters.net` returns 200 with expected content
- [ ] **HTTPS works** -- certificate is valid, HSTS header present
- [ ] **Static assets load** -- CSS, JS, images from `/build/` path
- [ ] **Uploaded images load** -- any existing image at `/uploads/...` renders correctly (verifies Tigris proxy)
- [ ] **Health check passes** -- `fly status -a restarters` shows healthy machines

### Authentication (within 15 minutes)

- [ ] **Login works** -- log in with a known test account
- [ ] **Registration works** -- register a new account (if self-registration is enabled)
- [ ] **Password reset** -- trigger a password reset email and verify it arrives (tests Mailgun)
- [ ] **Session persistence** -- navigate between pages, confirm session is maintained

### Core Functionality (within 30 minutes)

- [ ] **Event creation** -- create a new repair event with date, location, and description
- [ ] **Device logging** -- add a device to an event with category, status (fixed/repairable/end-of-life)
- [ ] **Image upload** -- upload a device photo, verify it appears and persists after page reload
- [ ] **Group management** -- view group page, verify member list and event history load
- [ ] **Statistics** -- verify stats/impact calculations display correctly on group/event pages
- [ ] **Search** -- search for groups and events
- [ ] **Locale switching** -- switch to French (`/set-lang/fr`) and verify translated content

### External Integrations (within 1 hour)

- [ ] **Email sending** -- trigger a notification (e.g., event invitation) and verify email arrives via Mailgun
- [ ] **Discourse SSO** -- if enabled, click "Talk" / Discourse link and verify SSO login works
  - User should be redirected to Restarters login, then back to Discourse authenticated
- [ ] **Discourse group sync** -- verify `discourse:syncgroups` runs without errors
  - `fly ssh console -a restarters -C "php artisan discourse:syncgroups"`
- [ ] **WordPress sync** -- if enabled, create/edit an event and verify it appears on the WordPress site
- [ ] **Geocoding** -- create/edit a group with an address and verify location is geocoded correctly

### API Endpoints (within 1 hour)

- [ ] **Public API** -- `GET /api/homepage_data` returns valid JSON
- [ ] **Event stats** -- `GET /api/party/{id}/stats` returns valid stats (used by therestartproject.org)
- [ ] **Group stats** -- `GET /api/group/{id}/stats` returns valid stats
- [ ] **iFrame embeds** -- `GET /outbound/info/{type}/{id}` renders the embed widget
  - Test both the web route and API route variants
- [ ] **API v2** -- `GET /api/v2/groups/names` returns group names

### Background Jobs (within 2 hours)

- [ ] **Queue worker running** -- `fly ssh console -a restarters -C "ps aux | grep queue:work"`
- [ ] **Cron running** -- `fly ssh console -a restarters -C "crontab -l"` shows the scheduler entry
- [ ] **Scheduler output** -- check that `language:sync` and `discourse:syncgroups` have run
  - `fly logs -a restarters | grep -i "schedule\|language:sync\|discourse"`
- [ ] **Failed jobs** -- `fly ssh console -a restarters -C "php artisan queue:failed"` shows no unexpected failures

### Performance

- [ ] **Response time** -- homepage loads in under 2 seconds
- [ ] **No 500 errors** -- check Sentry and `fly logs` for errors
- [ ] **Database connectivity** -- no connection timeouts in logs

---

## 8. Rollback Plan

### Rollback Decision Criteria

Trigger rollback if any of the following occur within the first 24 hours:

- Application is unreachable for more than 10 minutes and not quickly fixable
- Database corruption or data loss detected
- Critical functionality broken (login, event creation, device logging)
- Email delivery completely failed
- Sustained error rate above 5% in Sentry

### Rollback Procedure

**Time to rollback:** ~10 minutes (thanks to low DNS TTL)

#### Step 1: Revert DNS

Change DNS back to the old server's IP address:

```
restarters.net.    300    A    <old-server-ip>
```

With 300s TTL, most clients will see the change within 5 minutes.

#### Step 2: Bring Old Server Back Up

```bash
ssh restart-sp
cd /srv/users/serverpilot/apps/restarters
php artisan up  # Remove maintenance mode if it was set
```

#### Step 3: Sync Data Back (if needed)

If the Fly.io site was live long enough for users to create data:

```bash
# Export the Fly database
fly proxy 13306:3306 -a restarters-db &
mysqldump -h 127.0.0.1 -P 13306 -u restarters -p restarters > fly-data-$(date +%Y%m%d-%H%M%S).sql
kill %1

# Analyze the dump for new data since cutover
# Manually apply only the new records to the old production database
# This requires careful judgment -- automated merge is not safe
```

#### Step 4: Notify Team

- Post in team channel that rollback was performed
- Document what went wrong
- Plan the next attempt

### Preventing Data Split-Brain

To minimize the risk of needing to merge data from two databases:

- Keep the maintenance window as short as possible
- Have smoke tests scripted/automated so verification is fast
- Monitor Sentry actively during the first hour
- Be ready to rollback within the first 30 minutes if issues appear

---

## 9. Post-Cutover Monitoring

### First 24 Hours

- [ ] **Sentry dashboard** -- watch for new errors, especially:
  - Database connection errors
  - Mailgun delivery failures (the `before_send` filter in `config/sentry.php` suppresses transient Mailgun errors, so persistent ones will still appear)
  - S3/Tigris storage errors
  - Discourse API errors
- [ ] **Fly.io logs** -- `fly logs -a restarters` (keep a terminal with this running)
  - Watch for PHP-FPM errors, nginx 502/504, OOM kills
- [ ] **Fly.io metrics** -- `fly dashboard` or Grafana
  - CPU and memory usage (VM is shared-cpu-1x, 1024 MB)
  - Request latency
  - Health check status
- [ ] **Queue health** -- periodically check for failed jobs
  ```bash
  fly ssh console -a restarters -C "php artisan queue:failed"
  ```
- [ ] **Database health**
  ```bash
  fly ssh console -a restarters-db -C "mysqladmin status"
  fly ssh console -a restarters-db -C "mysql -u root -e 'SHOW PROCESSLIST'"
  ```

### First Week

- [ ] **Volume snapshots** -- verify Fly.io is taking daily snapshots of the MySQL volume
  ```bash
  fly volumes snapshots list <vol-id> -a restarters-db
  ```
- [ ] **Yesterday restore** -- run `scripts/restore-yesterday.sh` to verify the backup/restore pipeline works
- [ ] **Discourse sync** -- verify the 15-minute `discourse:syncgroups` cron has been running consistently
- [ ] **Language sync** -- verify `language:sync` runs every 5 minutes without errors
- [ ] **Email deliverability** -- check Mailgun dashboard for bounce rate and delivery stats
- [ ] **Image uploads** -- verify several images uploaded during the week are accessible
- [ ] **Performance baseline** -- establish baseline response times for comparison

### Ongoing

- [ ] **TLS certificate renewal** -- Fly.io auto-renews Let's Encrypt certificates. Verify periodically:
  ```bash
  fly certs show restarters.net -a restarters
  ```
  If renewal fails (e.g., DNS misconfiguration), Fly.io will send notifications. No cron or certbot needed.
- [ ] **Activate CI/CD** -- update `.github/workflows/fly-deploy.yml`:
  - Change branch filter from `DISABLED-fly-deploy` to `production` (or `develop` for staging)
  - Ensure `FLY_API_TOKEN` is set in GitHub repo secrets
  - Alternatively, add a Fly deploy job to `.circleci/config.yml` (instructions are in the workflow file comments)
- [ ] **Scaling** -- monitor if shared-cpu-1x with 1024 MB is sufficient under production load
  - If needed: `fly scale vm shared-cpu-2x -a restarters` or increase memory
- [ ] **Database volume size** -- monitor disk usage and resize if needed
  - `fly ssh console -a restarters-db -C "df -h /data"`
- [ ] **Restore DNS TTL** to 3600s after 48 hours of stable operation
- [ ] **Re-enable Wiki integration** if needed (set `FEATURE__WIKI_INTEGRATION=true` in fly.toml and ensure Wiki secrets are set)

---

## 10. Timeline

### Suggested Schedule

| When | Action | Duration |
|---|---|---|
| D-14 | Lower DNS TTL to 300s | 5 min |
| D-14 | Run `fly-migrate.sh --dry-run` on production server | 10 min |
| D-7 | Full image sync to Tigris (`--images-only`) | 1-4 hours (depends on volume) |
| D-7 | Test database migration to staging (`--db-only`) | 30-60 min |
| D-7 | Full staging verification with production data | 2 hours |
| D-1 | Final incremental image sync | 10-30 min |
| D-1 | Production database backup (off-server copy) | 30 min |
| **D-0** | **Maintenance window start** | |
| D-0 + 0:00 | Put old server in maintenance mode | 2 min |
| D-0 + 0:05 | Final database migration (`--db-only`) | 15-30 min |
| D-0 + 0:05 | Final incremental image sync (parallel) | 5-10 min |
| D-0 + 0:35 | Update Fly.io config for production | 5 min |
| D-0 + 0:40 | Deploy to Fly.io | 5-10 min |
| D-0 + 0:50 | Add custom domain + TLS cert on Fly.io | 5 min |
| D-0 + 0:55 | **DNS cutover** | 2 min |
| D-0 + 1:00 | Wait for DNS propagation | 5-10 min |
| D-0 + 1:10 | Run smoke tests | 30 min |
| D-0 + 1:40 | **Maintenance window end** (or rollback) | |
| D+1 | Verify yesterday restore pipeline | 30 min |
| D+2 | Restore DNS TTL to 3600s | 5 min |
| D+7 | Activate CI/CD pipeline | 30 min |
| D+14 | Decommission old server | 1 hour |

**Total estimated maintenance window:** ~1.5-2 hours

---

## Appendix A: Key File References

| File | Purpose |
|---|---|
| `fly.toml` | Main app Fly.io config |
| `fly-mysql.toml` | MySQL Fly.io config |
| `fly-pma.toml` | phpMyAdmin Fly.io config |
| `fly-yesterday.toml` | Yesterday restore app config |
| `Dockerfile.fly` | Multi-stage Docker build for Fly.io |
| `docker/supervisord-fly.conf` | Supervisord config (nginx, php-fpm, cron, queue worker) |
| `docker/nginx-fly.conf` | Nginx config with Tigris proxy |
| `.fly/scripts/startup.sh` | Container startup (migrations, caching, supervisord) |
| `scripts/fly-migrate.sh` | Data migration script (secrets, DB, images) |
| `scripts/restore-yesterday.sh` | Yesterday DB snapshot restore |
| `.github/workflows/fly-deploy.yml` | GitHub Actions deploy workflow (inactive) |
| `.circleci/config.yml` | Current CI pipeline (tests only, no deploy) |
| `config/filesystems.php` | S3/Tigris disk configuration |
| `config/services.php` | Mailgun, Discourse SSO config |
| `config/discourse-api.php` | Discourse API config |
| `config/mail.php` | Mail driver config |
| `config/sentry.php` | Sentry error reporting config |
| `config/croppa.php` | Image thumbnailing config |
| `app/Providers/ScheduleServiceProvider.php` | Cron schedule (language sync, Discourse sync) |

## Appendix B: Environment Variables in fly.toml

The following are set as non-secret env vars in `fly.toml` and will need updating for production:

```toml
[env]
  APP_ENV = "production"              # Change from "staging"
  APP_URL = "https://restarters.net"  # Change from "https://restarters.fly.dev"
  FEATURE__DISCOURSE_INTEGRATION = "true"  # Change from "false" when ready
  SENTRY_ENVIRONMENT = "production"   # Change from "staging"
  SESSION_DOMAIN = ".restarters.net"  # Set for production domain (currently empty)
```

## Appendix C: Fly CLI Quick Reference

```bash
# Deploy
fly deploy -a restarters

# Logs
fly logs -a restarters
fly logs -a restarters-db

# SSH into app container
fly ssh console -a restarters

# Run artisan command
fly ssh console -a restarters -C "php artisan migrate:status"

# Proxy MySQL for local access
fly proxy 13306:3306 -a restarters-db

# Proxy phpMyAdmin for browser access
fly proxy 8080:80 -a restarters-pma

# View app status
fly status -a restarters

# Scale VM
fly scale vm shared-cpu-2x -a restarters
fly scale memory 2048 -a restarters

# View secrets
fly secrets list -a restarters

# Set secrets
fly secrets set KEY=VALUE -a restarters

# View volume snapshots
fly volumes list -a restarters-db
fly volumes snapshots list <vol-id> -a restarters-db

# Custom domain management
fly certs add restarters.net -a restarters
fly certs show restarters.net -a restarters
fly certs check restarters.net -a restarters
fly ips list -a restarters
```
