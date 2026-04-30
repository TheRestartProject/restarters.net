# Fly.io Deployment

How the application is built, run, and deployed on Fly.io.

---

## Environments

| App | Config | URL | Branch |
|---|---|---|---|
| `restarters-dev` | `fly.dev.toml` | `restarters-dev.fly.dev` | `develop` |
| `restarters-dev-mail` | `fly-mailpit.toml` | `restarters-dev-mail.fly.dev` | `develop` |
| `restarters` | `fly.toml` | `restarters.net` | `production` |
| `restarters-db` | `fly-mysql.toml` | internal only | — |
| `restarters-pma` | `fly-pma.toml` | via `flyctl proxy` only | — |
| `restarters-yesterday` | `fly-yesterday.toml` | `restarters-yesterday.fly.dev` (stopped when idle) | — |

All apps run in the `lhr` (London) region. The DB is on a private 6PN network (`restarters-db.internal`) — only reachable by other apps in the same Fly organisation, not from the public internet.

---

## Docker Build (`Dockerfile.fly`)

Two-stage build:

**Stage 1 — builder** (`php:8.2-cli`):
- `composer install --no-dev`
- `npm ci` + `npm run production` (Vite build)
- `php artisan lang:js` (JS translations)
- `php artisan l5-swagger:generate` (API docs)
- PHP extensions: `pdo_mysql bcmath zip intl gd exif` (exif required by Intervention Image's `orientate()`)
- Output: compiled assets in `public/build/`, vendor autoload, swagger JSON

**Stage 2 — runtime** (`php:8.2-fpm`):
- Copies built assets from stage 1
- Installs nginx, supervisord, cron, sysstat
- PHP extensions: `pdo_mysql bcmath zip intl gd exif`
- Creates `public/uploads/` directory (gitignored, so not present in source)
- No Node or Composer in the final image

The build runs on Fly's remote builders (`fly deploy --remote-only`). No local Docker required.

---

## Runtime Processes

All processes are managed by supervisord (`docker/supervisord-fly.conf`). supervisord itself is PID 1 — the container stays alive as long as supervisord is running.

| Process | Command | Restarts |
|---|---|---|
| `nginx` | `nginx -g "daemon off;"` | automatic |
| `php-fpm` | `php-fpm --nodaemonize` | automatic |
| `cron` | `cron -f` | automatic |
| `queue-worker` | `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600` | automatic |
| `sysstat` | sa1 collector (60s interval) | automatic |

nginx serves static files and proxies PHP requests to php-fpm via Unix socket (`/var/run/php-fpm.sock`). The Laravel scheduler runs via cron (`php artisan schedule:run` every minute).

The queue worker restarts automatically if it crashes (supervisord `autorestart=true`), but there is currently **no alerting** if it falls behind or fails silently. See [Monitoring](#monitoring) below.

---

## Startup (`/.fly/scripts/startup.sh`)

Runs as root before supervisord starts:

1. Creates `storage/` subdirectories, `bootstrap/cache`, and `public/uploads/` with correct ownership
2. Symlinks `storage/logs` and `storage/framework/cache` to the persistent `/var/log` volume (so they survive redeploys)
3. Computes the cookie-gate HMAC and writes nginx map files for the `BASIC_AUTH_ENABLED` gate
4. Runs `envsubst` to inject the Tigris bucket URL into the nginx config
5. Spawns a background subshell that:
   - Waits up to 60s for MySQL to be reachable
   - Runs `php artisan migrate --force`
   - Runs `translations:import`
   - Caches config, routes, and views
   - Restarts the queue worker
6. Immediately starts supervisord — the health check can pass while the DB setup runs in the background

---

## Configuration

Non-secret config lives in `fly.toml` (production) and `fly.dev.toml` (dev). Secrets are stored in Fly's secret store and injected as environment variables at runtime.

```bash
flyctl secrets list --app restarters-dev   # see what's set
flyctl secrets set KEY=VALUE --app restarters-dev
flyctl secrets import --app restarters-dev < secrets.env
```

Secrets take precedence over `[env]` values in the toml file.

### Environment Isolation

Dev/staging apps must not reach real external services. `fly-migrate.sh --secrets` enforces this automatically:

**Shared secrets** (copied to any app): `APP_KEY`, `AWS_*` (Tigris), `SENTRY_LARAVEL_DSN`, `MAPBOX_TOKEN`, `GOOGLE_API_CONSOLE_KEY`, `CALENDAR_HASH`, `SUPPORT_EMAIL_ADDRESS`, `REPAIRDIRECTORY_URL`

**Production-only secrets** (only copied when `--app restarters`): all mail/Mailgun config, `DISCOURSE_*`, `WIKI_*`, `WP_XMLRPC_*`, `DRIP_*`, `GOOGLE_ANALYTICS_TRACKING_ID`, `GOOGLE_TAG_MANAGER_ID`, `SEND_COMMAND_LOGS_TO`

When `--secrets` runs against a non-production app it also **unsets** any production-only secrets that are already present, cleaning up any that were set by older script runs.

The `fly.dev.toml` sets `MAIL_MAILER = "smtp"` pointing at the paired Mailpit instance (`restarters-dev-mail.internal:1025`). Emails land in Mailpit's inbox, not real inboxes. This is safe only while no `MAIL_MAILER` Fly secret overrides it — the isolation above guarantees that.

**Email in dev — Mailpit:** Each non-production app has a paired Mailpit instance named `${FLY_APP}-mail` (e.g. `restarters-dev-mail`). `fly.dev.toml` points SMTP at `restarters-dev-mail.internal:1025`. Mailpit's SMTP port is only reachable from other apps in the same Fly org via private 6PN networking — it is not publicly exposed. The web UI runs on port 8025.

```bash
# Deploy Mailpit for dev
flyctl deploy --config fly-mailpit.toml --remote-only

# Deploy for a PR branch
flyctl deploy --config fly-mailpit.toml --app restarters-pr-123-mail --remote-only

# Open the web UI
flyctl proxy 8025:8025 --app restarters-dev-mail
# then http://localhost:8025
```

Email sent from dev or a PR branch lands in that branch's Mailpit inbox, not in real users' inboxes.

---

## File Storage (Tigris)

Uploaded files are stored in a shared Tigris S3-compatible bucket. All Fly apps (`restarters`, `restarters-dev`, etc.) read from and write to the same bucket — there is no per-environment image storage. nginx proxies all `/uploads/*` requests to the bucket; the PHP app never serves these files directly.

- **Reads:** nginx → Tigris (30-day cache headers)
- **Writes:** Laravel S3 filesystem driver (`FILESYSTEM_DISK=s3`)
- **Secrets required:** `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` (endpoint and region are set in the toml: `AWS_ENDPOINT = "https://fly.storage.tigris.dev"`, `AWS_DEFAULT_REGION = "auto"`)

---

## Deployment

### Manual

```bash
flyctl deploy --config fly.dev.toml --remote-only   # deploy to dev
flyctl deploy --config fly.toml --remote-only        # deploy to production
```

This triggers a remote build on Fly's infrastructure and deploys with zero downtime (rolling replacement of machines).

### Automated (CircleCI)

Auto-deploy is active for the `develop` branch:

- **`develop` branch** → tests pass → `flyctl deploy --config fly.dev.toml --remote-only` → `restarters-dev`

This is wired up in `.circleci/config.yml` as the `deploy-fly-dev` job, which runs after the `build` job and only on the `develop` branch. `FLY_API_TOKEN` is set in CircleCI project settings (Project Settings → Environment Variables).

Production auto-deploy is not yet configured. When ready, add a `deploy-fly-prod` job using `fly.toml` with `filters: branches: only: production`.

---

## Monitoring

### App health

Fly's built-in health check polls `GET /robots.txt` every 15s (configured in `fly.dev.toml` / `fly.toml`). A machine is replaced if the check fails repeatedly. This covers the case where nginx or php-fpm has died.

**TODO:** Set up an external uptime monitor (e.g. Better Uptime, UptimeRobot) that alerts on HTTP failures from outside Fly's network.

### Queue worker

supervisord automatically restarts the queue worker if the process exits. However, it does not alert if:
- Jobs are piling up (worker running but slow/stuck)
- Jobs are consistently failing (going to the `failed_jobs` table)

**TODO:** Add monitoring for the queue. Options:
- A scheduled artisan command that checks `failed_jobs` count and `jobs` queue depth, and sends an alert if either exceeds a threshold
- Horizon (Laravel's queue dashboard) — adds visibility but is heavier
- A simple cron check: `php artisan queue:monitor database:10` (built into Laravel — sends a notification if queue depth exceeds threshold)

The simplest path: add `php artisan queue:monitor database:10` to the scheduler in `app/Providers/ScheduleServiceProvider.php`, which will fire a `QueueBusy` event that can be routed to a Slack notification or email.

---

## Logs

All processes log to stdout/stderr, which Fly captures:

```bash
flyctl logs --app restarters-dev          # live tail
flyctl logs --app restarters-dev --no-tail  # recent output
```

Laravel application logs (`LOG_CHANNEL=daily`) write to `/var/log/laravel/` on the persistent volume, surviving redeploys.

Nginx access/error logs write to `/var/log/nginx/` on the same volume.

---

## Database Access

The MySQL DB is not publicly accessible. To connect:

```bash
flyctl proxy 13306:3306 --app restarters-db   # open tunnel in one terminal
mysql -h 127.0.0.1 -P 13306 -u restarters -p restarters  # connect in another
```

phpMyAdmin (not publicly exposed):

```bash
flyctl machine start --app restarters-pma
flyctl proxy 8080:80 --app restarters-pma
# open http://localhost:8080 in browser
# Host: restarters-db.internal, user/pass from fly secrets
flyctl machine stop --app restarters-pma  # stop when done
```

---

## Common Operations

```bash
# Run an artisan command
flyctl ssh console --app restarters-dev --command "php /var/www/artisan migrate:status"

# Open a shell
flyctl ssh console --app restarters-dev

# Check process status
flyctl ssh console --app restarters-dev --command "supervisorctl status"

# Restart the queue worker
flyctl ssh console --app restarters-dev --command "supervisorctl restart queue-worker"

# Check failed jobs
flyctl ssh console --app restarters-dev --command "php /var/www/artisan queue:failed"

# Retry all failed jobs
flyctl ssh console --app restarters-dev --command "php /var/www/artisan queue:retry all"

# Check app status and machine health
flyctl status --app restarters-dev

# Scale VM size
flyctl scale vm shared-cpu-2x --app restarters-dev
flyctl scale memory 2048 --app restarters-dev
```
