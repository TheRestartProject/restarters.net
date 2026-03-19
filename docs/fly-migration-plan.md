# Fly.io Migration Plan for Restarters.net

**Branch:** `RES-2060_fly_io_deployment`
**Date prepared:** 2026-03-19
**Target region:** `lhr` (London Heathrow)

---

## Table of Contents

1. [What Changes](#1-what-changes)
2. [What Stays the Same](#2-what-stays-the-same)
3. [Known Risks](#3-known-risks) — FixometerFile (blocker), DMARC, Metabase, map subdomain
4. [Pre-migration Checklist](#4-pre-migration-checklist)
5. [Service Dependencies](#5-service-dependencies-and-reconfiguration) — Secrets status, Mailgun, Discourse, Wiki, WordPress
6. [Data Migration](#6-data-migration) — Database, images, Croppa
7. [DNS Cutover Strategy](#7-dns-cutover-strategy) — Pre-cutover setup, maintenance window steps
8. [Smoke Tests](#8-smoke-tests)
9. [Rollback Plan](#9-rollback-plan)
10. [Post-Cutover Monitoring](#10-post-cutover-monitoring)
11. [Timeline](#11-timeline)
12. [Reference: Fly.io Architecture](#12-reference-flyio-architecture)

---

<details>
<summary><strong>1. What Changes</strong></summary>

| Area | Current (ServerPilot) | Fly.io | Risk |
|---|---|---|---|
| **Server** | `restart-sp`, ServerPilot-managed VPS | Docker container on Fly.io shared-cpu-1x, 1024MB, `lhr` region | Different resource limits; need to monitor |
| **File storage** | Local filesystem `public/uploads/` | Tigris S3-compatible storage, served via nginx proxy | **Highest risk.** `FixometerFile` helper writes directly to local disk in 15+ places (user photos, event images, group logos). These writes will go to ephemeral container storage and be lost on redeploy. Reads work (nginx proxies `/uploads/` to Tigris) but new uploads via `FixometerFile` will not persist. |
| **Database** | MySQL on same server (fast local connection) | MySQL on separate Fly app (`restarters-db`), connected via Fly internal network (`restarters-db.internal:3306`) | Adds network latency between app and DB. Monitor query performance. |
| **SSL/TLS** | ServerPilot manages Let's Encrypt | Fly.io manages Let's Encrypt at the edge. Auto-renewal, no certbot. | Different renewal mechanism — need to add custom domain via `fly certs add` before cutover |
| **Deployment** | ServerPilot / manual | `fly deploy` builds Docker image remotely, rolls out new container | Different deploy process. GitHub Actions workflow prepared but not yet activated. |
| **Process management** | ServerPilot manages nginx/PHP-FPM separately; cron via system crontab; queue worker likely via supervisor or systemd | Single container runs nginx + PHP-FPM + cron + queue worker via supervisord | All processes in one container — if container restarts, everything restarts |
| **Persistent filesystem** | Full persistent disk | **Ephemeral.** Container filesystem is lost on redeploy. Only the DB volume persists. | Any code writing to local disk (logs, cache, uploads) loses data on redeploy. Laravel cache/sessions use DB so that's fine, but `FixometerFile` is a problem. |
| **IP address** | Dedicated: `139.59.184.196` | Shared IPv4: `66.241.124.187`, Dedicated IPv6: `2a09:8280:1::ce:b85f:0` | Shared IPv4 — if IP reputation matters, consider dedicated ($2/mo) |
| **Scaling** | Vertical (upgrade VPS) | Can scale VM size or add machines | More flexible but currently single machine |
| **Backups** | Daily backups (existing process) | Fly.io daily volume snapshots for DB | Different backup mechanism — verify snapshots are working |
| **`map.restarters.net`** | Served from same server | Not on Fly.io | Needs separate migration or DNS kept pointing to old server |

</details>

<details>
<summary><strong>2. What Stays the Same</strong></summary>

- **Domain** — `restarters.net` stays the same, DNS just points to new IP
- **External services** — all connect outbound, unaffected by server change:
  - Discourse (`talk.restarters.net`) — confirmed no IP matching, SSO works via domain
  - MediaWiki (`wiki.restarters.net`) — separate server at `165.22.123.158`
  - WordPress XML-RPC (`therestartproject.org/fxm.php`) — verified reachable from Fly (HTTP 200)
  - Mailgun — sends via API (`MAIL_MAILER=mailgun`), domain `mg.restarters.net`
  - Mapbox, Google APIs, Drip — all outbound API calls
- **Email sending** — same Mailgun config, same from address. Tested and working from Fly.
- **Database schema** — migrated via mysqldump, Laravel migrations run on startup
- **Application code** — same Laravel app, same PHP 8.2
- **Queue/cron** — same jobs, same schedule, just different process manager

### Key Domains

- `restarters.net` — main app → **DNS changes to Fly.io**
- `www.restarters.net` — CNAME to `restarters.net` → follows main domain
- `map.restarters.net` — currently same server → **needs separate plan**
- `repairtogether.restarters.net` — network subdomain (Repair Together, Belgium, `fr-BE`) → **DNS must move to Fly** (covered by wildcard cert)
- `repairshare.restarters.net` — network subdomain (Repair Share) → **DNS must move to Fly** (covered by wildcard cert)
- `hauts-de-france.restarters.net` — network subdomain (Hauts-de-France) → **DNS must move to Fly** (covered by wildcard cert)
- `talk.restarters.net` — Discourse (external) → no change
- `wiki.restarters.net` — MediaWiki (external) → no change
- `therestartproject.org` — WordPress (external) → no change
- `mg.restarters.net` — Mailgun EU sending domain → **do not change DNS**
- `mg.rstrt.org` — from address domain → **⚠️ DMARC misalignment** (see section 5)

</details>

<details open>
<summary><strong>3. Known Risks</strong></summary>

### 3.1 FixometerFile — Local Disk Writes (HIGH RISK — BLOCKER)

**Investigated 2026-03-19.** The `FixometerFile::upload()` method (`app/Helpers/FixometerFile.php`) does three things that interact badly with Fly.io:

1. **Writes original file** to `$_SERVER['DOCUMENT_ROOT'].'/uploads/'.$filename` via `move_uploaded_file()` (line 72)
2. **Processes with Intervention Image** — orientate, resize, crop — reading from and saving back to local disk (lines 81, 130-131)
3. **Saves thumbnails** (`thumbnail_` and `mid_` prefixed) to the same local directory

On Fly.io, nginx proxies ALL `/uploads/` requests **directly to Tigris** (see `docker/nginx-fly.conf` line 37-61). It does NOT try local disk first. So:

- Upload runs → file written to local container disk ✓
- Image processing runs → thumbnails created on local disk ✓
- User sees broken image immediately → nginx asks Tigris, file isn't there ✗
- Files are also lost on next deploy (ephemeral filesystem)

This is used across `UserController`, `PartyController`, `DeviceController`, `EventController`, and `GroupController` (15+ call sites) for user photos, event images, and group logos.

**Fix:** Add S3 upload after local file processing. The local write is still needed as a staging area for Intervention Image, but after processing, the original + thumbnails must be uploaded to Tigris. Add to the end of `FixometerFile::upload()`:

```php
// After local processing, upload to S3/Tigris
$disk = Storage::disk('s3');
$disk->put($filename, file_get_contents($lpath));
$disk->put('thumbnail_'.$filename, file_get_contents($_SERVER['DOCUMENT_ROOT'].'/uploads/thumbnail_'.$filename));
$disk->put('mid_'.$filename, file_get_contents($_SERVER['DOCUMENT_ROOT'].'/uploads/mid_'.$filename));
```

**This must be resolved before production cutover.**

### 3.2 DMARC Email Alignment (LOW RISK — pre-existing)

`MAIL_FROM_ADDRESS` is `noreply@mg.rstrt.org` but `MAILGUN_DOMAIN` is `mg.restarters.net`. DKIM is signed by `mg.restarters.net` but the From header says `mg.rstrt.org`, causing DMARC alignment failure. This is the same on the current production server — not caused by migration. Emails deliver but may be flagged by strict receivers.

**Options:**
1. Change `MAIL_FROM_ADDRESS` to `noreply@mg.restarters.net` (simplest)
2. Add `mg.rstrt.org` as a verified domain on EU Mailgun and update DNS
3. Leave as-is

### 3.3 Metabase — Direct Database Access (MEDIUM RISK — BLOCKER)

Metabase connects directly to the production MySQL database (referenced in `app/Device.php:521`). On Fly.io, MySQL is on `restarters-db.internal:3306` — only accessible via Fly's internal 6PN network. It is **not publicly exposed**.

**Options:**
1. **Fly WireGuard tunnel** — Metabase server connects to Fly's private network via WireGuard. Set up with `fly wireguard create`, then Metabase connects to `restarters-db.internal:3306` through the tunnel.
2. **Persistent `fly proxy`** — run `fly proxy 3306:3306 -a restarters-db` on the Metabase server. Fragile, needs process supervision.
3. **Expose MySQL publicly** — add a public IP to `restarters-db` and configure firewall rules. Least secure option.

**Are there other external systems with direct DB access?** This needs to be confirmed before cutover.

### 3.4 map.restarters.net (MEDIUM RISK)

Currently served from the same server (`139.59.184.196`). If DNS for `restarters.net` changes to Fly.io but `map.restarters.net` still needs the old server, the old server must stay running. If the Repair Directory app is also on the old server, it needs its own migration plan or DNS must be kept separate.

---

</details>

<details>
<summary><strong>4. Pre-migration Checklist</strong></summary>

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

</details>

<details>
<summary><strong>5. Service Dependencies and Reconfiguration</strong></summary>

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

</details>

<details>
<summary><strong>6. Data Migration</strong></summary>

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
- **Writing:** ⚠️ `FixometerFile` writes to local disk, NOT to Tigris. See section 3.1 — this is a blocker that must be fixed before cutover.

### 5.3 Croppa (Image Thumbnailing)

**Verified on 2026-03-19:** `Croppa::url()` is **not called anywhere** in the application code or templates. Dynamic Croppa resizing is NOT actively used. All thumbnails are pre-generated at upload time (prefixed `thumbnail_*`, `mid_*`).

This means:
- Pre-generated thumbnails are uploaded to Tigris alongside the originals by the sync script — no special handling needed
- Croppa config exists but is effectively unused for runtime image processing
- **No action required** for Croppa during migration

---

</details>

<details>
<summary><strong>7. DNS Cutover Strategy</strong></summary>

### Prerequisites

- All data migration phases complete (secrets, database, images)
- Staging verification complete on `restarters.fly.dev`
- DNS TTL already lowered to 300s (done 1 week prior)

### Pre-Cutover Setup (days/weeks before — verify everything works on Fly before the maintenance window)

#### Set production config on Fly.io now

These can be set immediately. The app runs on `restarters.fly.dev` regardless — setting production values won't break anything since DNS still points to the old server.

```bash
# Set production env vars as secrets (override fly.toml staging values)
fly secrets set \
  APP_ENV=production \
  APP_URL=https://restarters.net \
  SENTRY_ENVIRONMENT=production \
  SESSION_DOMAIN=.restarters.net \
  -a restarters

# Deploy and verify on restarters.fly.dev
fly deploy -a restarters
```

**Verify on `restarters.fly.dev`:** The site will show `APP_URL=https://restarters.net` in generated URLs, but that's fine — we're verifying the app boots, connects to DB, and runs correctly with production config.

#### Set up automated deploy via CircleCI (optional but recommended)

Adding a deploy step to CircleCI means deploys happen automatically after tests pass on the production branch. This can be set up and tested before cutover — pushes to the production branch will deploy to Fly.io even though DNS isn't pointed there yet.

Add `FLY_API_TOKEN` to CircleCI project settings as an environment variable, then add to `.circleci/config.yml`:

```yaml
  deploy-fly:
    machine:
      image: ubuntu-2204:current
    steps:
      - checkout
      - run:
          name: Install flyctl
          command: curl -L https://fly.io/install.sh | sh
      - run:
          name: Deploy to Fly.io
          command: ~/.fly/bin/flyctl deploy --remote-only

workflows:
  build-and-deploy:
    jobs:
      - build
      - deploy-fly:
          requires:
            - build
          filters:
            branches:
              only: production
```

This can be tested by merging to the production branch before DNS cutover — the deploy will go to Fly.io and be accessible on `restarters.fly.dev`.

#### Set up wildcard TLS certificate (before cutover — do this now)

A wildcard cert covers `*.restarters.net` — all network subdomains (`repairtogether`, `repairshare`, `hauts-de-france`) plus `www`, without needing individual `fly certs add` for each.

**Already done (2026-03-19):**
```bash
fly certs add "*.restarters.net" -a restarters   # ✅ Done
fly certs add restarters.net -a restarters        # Also needed for the apex domain
```

**DNS validation required** — add this CNAME at iwantmyname.com now (does not affect current production):
```
_acme-challenge.restarters.net.  CNAME  restarters.net.369kyp0.flydns.net.
```

Once the CNAME is in place, Fly.io will issue the wildcard cert via DNS-01 challenge. Unlike per-domain HTTP-01 validation, this works **before** DNS cutover, so the cert can be ready and waiting.

Check progress: `fly certs check "*.restarters.net" -a restarters`

### Cutover Steps (maintenance window — aim for minimal duration)

By this point, all config, secrets, deploy pipeline, and domain setup should already be done and verified. The maintenance window is only for final data sync and DNS change.

#### Step 1: Final Data Sync

```bash
# Put production into maintenance mode
ssh restart-sp
cd /srv/users/serverpilot/apps/restarters
php artisan down --message="We're upgrading our infrastructure. Back shortly."

# Final incremental image sync
./scripts/fly-migrate.sh --images-only

# Final database sync (this is the point of no return for data)
./scripts/fly-migrate.sh --db-only
```

#### Step 2: Update DNS Records

DNS is hosted at **iwantmyname.com**. Since iwantmyname does not support CNAME flattening at the apex, use A/AAAA records.

**Allocated Fly.io IPs:**
- IPv4 (shared): `66.241.124.187`
- IPv6 (dedicated): `2a09:8280:1::ce:b85f:0`

```
restarters.net.                   300    A        66.241.124.187
restarters.net.                   300    AAAA     2a09:8280:1::ce:b85f:0
www.restarters.net.               300    CNAME    restarters.net.
repairtogether.restarters.net.    300    A        66.241.124.187
repairtogether.restarters.net.    300    AAAA     2a09:8280:1::ce:b85f:0
repairshare.restarters.net.       300    A        66.241.124.187
repairshare.restarters.net.       300    AAAA     2a09:8280:1::ce:b85f:0
hauts-de-france.restarters.net.   300    A        66.241.124.187
hauts-de-france.restarters.net.   300    AAAA     2a09:8280:1::ce:b85f:0
```

Or, if iwantmyname supports wildcard DNS records:
```
*.restarters.net.    300    A        66.241.124.187
*.restarters.net.    300    AAAA     2a09:8280:1::ce:b85f:0
```

**Note:** Do NOT wildcard DNS if `map.restarters.net` still needs the old server. In that case, use individual records above.

If a dedicated IPv4 is needed later: `fly ips allocate-v4 -a restarters` (~$2/month).

#### Step 3: Verify TLS Certificate

```bash
# Wildcard cert (should already be issued via DNS-01 validation done pre-cutover)
fly certs check "*.restarters.net" -a restarters

# Apex domain
fly certs check restarters.net -a restarters

# Verify in browser
curl -I https://restarters.net
curl -I https://repairtogether.restarters.net
```

**Certificate Renewal:** Fly.io auto-renews Let's Encrypt certificates. The wildcard cert renews via DNS-01 challenge using the `_acme-challenge` CNAME — this must remain in place permanently. No certbot or cron needed.

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

</details>

<details>
<summary><strong>8. Smoke Tests</strong></summary>

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

</details>

<details>
<summary><strong>9. Rollback Plan</strong></summary>

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

</details>

<details>
<summary><strong>10. Post-Cutover Monitoring</strong></summary>

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

</details>

<details>
<summary><strong>11. Timeline</strong></summary>

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

</details>

<details>
<summary><strong>12. Reference: Fly.io Architecture</strong></summary>

See `Dockerfile.fly`, `docker/supervisord-fly.conf`, `docker/nginx-fly.conf`, `.fly/scripts/startup.sh` for implementation details.

Fly apps in `lhr` region:

| Fly App | Purpose | Status |
|---|---|---|
| `restarters` | Main app (nginx + PHP-FPM + cron + queue worker via supervisord) | Deployed |
| `restarters-db` | MySQL 8.0 with persistent volume | Deployed |
| `restarters-pma` | phpMyAdmin (access via `fly proxy`) | Suspended |
| `restarters-yesterday` | Yesterday's DB restore for debugging | Suspended |

</details>

<details>
<summary><strong>Appendix A: Key File References</strong></summary>

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

</details>

<details>
<summary><strong>Appendix B: Environment Variables in fly.toml</strong></summary>

The following are set as non-secret env vars in `fly.toml` and will need updating for production:

```toml
[env]
  APP_ENV = "production"              # Change from "staging"
  APP_URL = "https://restarters.net"  # Change from "https://restarters.fly.dev"
  FEATURE__DISCOURSE_INTEGRATION = "true"  # Change from "false" when ready
  SENTRY_ENVIRONMENT = "production"   # Change from "staging"
  SESSION_DOMAIN = ".restarters.net"  # Set for production domain (currently empty)
```

</details>

<details>
<summary><strong>Appendix C: Fly CLI Quick Reference</strong></summary>

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

</details>
