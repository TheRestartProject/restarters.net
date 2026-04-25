# Fly.io Deployment

How the application is built, run, and deployed on Fly.io.

---

## Environments

| App | Config | URL | Branch |
|---|---|---|---|
| `restarters-dev` | `fly.dev.toml` | `restarters.dev` | `develop` |
| `restarters` | `fly.toml` | `restarters.net` | `production` |
| `restarters-db` | `fly-mysql.toml` | internal only | — |
| `restarters-pma` | `fly-pma.toml` | via `fly proxy` only | — |
| `restarters-yesterday` | `fly-yesterday.toml` | via `fly proxy` only | — |

All apps run in the `lhr` (London) region. The DB is on a private 6PN network (`restarters-db.internal`) — only reachable by other apps in the same Fly organisation, not from the public internet.

---

## Docker Build (`Dockerfile.fly`)

Two-stage build:

**Stage 1 — builder** (`php:8.2-cli`):
- `composer install --no-dev`
- `npm ci` + `npm run production` (Vite build)
- `php artisan lang:js` (JS translations)
- `php artisan l5-swagger:generate` (API docs)
- Output: compiled assets in `public/build/`, vendor autoload, swagger JSON

**Stage 2 — runtime** (`php:8.2-fpm`):
- Copies built assets from stage 1
- Installs nginx, supervisord, cron, sysstat
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

1. Creates `storage/` subdirectories with correct ownership
2. Symlinks `storage/logs` and `storage/framework/cache` to the persistent `/var/log` volume (so they survive redeploys)
3. Runs `envsubst` to inject the Tigris bucket URL into the nginx config
4. Spawns a background subshell that:
   - Waits up to 60s for MySQL to be reachable
   - Runs `php artisan migrate --force`
   - Runs `translations:import`
   - Caches config, routes, and views
   - Restarts the queue worker
5. Immediately starts supervisord — the health check can pass while the DB setup runs in the background

---

## Configuration

Non-secret config lives in `fly.toml` (production) and `fly.dev.toml` (dev). Secrets are stored in Fly's secret store and injected as environment variables at runtime.

```bash
fly secrets list -a restarters-dev   # see what's set
fly secrets set KEY=VALUE -a restarters-dev
fly secrets import -a restarters-dev < secrets.env
```

Secrets take precedence over `[env]` values in the toml file.

### Environment Isolation

Dev/staging apps must not reach real external services. `fly-migrate.sh --secrets` enforces this automatically:

**Shared secrets** (copied to any app): `APP_KEY`, `AWS_*` (Tigris), `SENTRY_LARAVEL_DSN`, `MAPBOX_TOKEN`, `GOOGLE_API_CONSOLE_KEY`, `CALENDAR_HASH`, `SUPPORT_EMAIL_ADDRESS`, `REPAIRDIRECTORY_URL`

**Production-only secrets** (only copied when `--app restarters`): all mail/Mailgun config, `DISCOURSE_*`, `WIKI_*`, `WP_XMLRPC_*`, `DRIP_*`, `GOOGLE_ANALYTICS_TRACKING_ID`, `GOOGLE_TAG_MANAGER_ID`, `SEND_COMMAND_LOGS_TO`

When `--secrets` runs against a non-production app it also **unsets** any production-only secrets that are already present, cleaning up any that were set by older script runs.

The `fly.dev.toml` sets `MAIL_MAILER = "log"` (emails go to container stdout, visible via `fly logs`). This is safe only while no `MAIL_MAILER` Fly secret overrides it — the isolation above guarantees that.

**Email in dev — Mailpit:** Each non-production app has a paired Mailpit instance named `${FLY_APP}-mail` (e.g. `restarters-dev-mail`). `fly.dev.toml` points SMTP at `restarters-dev-mail.internal:1025`. Mailpit's SMTP port is only reachable from other apps in the same Fly org via private 6PN networking — it is not publicly exposed. The web UI runs on port 8025.

```bash
# Deploy Mailpit for dev
fly deploy --config fly-mailpit.toml

# Deploy for a PR branch
fly deploy --config fly-mailpit.toml --app restarters-pr-123-mail

# Open the web UI
fly proxy 8025:8025 -a restarters-dev-mail
# then http://localhost:8025
```

Email sent from dev or a PR branch lands in that branch's Mailpit inbox, not in real users' inboxes.

---

## File Storage (Tigris)

Uploaded files are stored in a shared Tigris S3-compatible bucket. All Fly apps (`restarters`, `restarters-dev`, etc.) read from and write to the same bucket — there is no per-environment image storage. nginx proxies all `/uploads/*` requests to the bucket; the PHP app never serves these files directly.

- **Reads:** nginx → Tigris (30-day cache headers)
- **Writes:** Laravel S3 filesystem driver (`FILESYSTEM_DISK=s3`)
- **Secrets required:** `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT_URL_S3`, `AWS_REGION`

---

## Deployment

### Manual

```bash
fly deploy -a restarters-dev    # deploy to dev
fly deploy -a restarters        # deploy to production
```

This triggers a remote build on Fly's infrastructure and deploys with zero downtime (rolling replacement of machines).

### Automated (CircleCI) — TODO

Auto-deploy is not yet active. The target setup:

- **`develop` branch** → tests pass → `fly deploy -a restarters-dev`
- **`production` branch** → tests pass → `fly deploy -a restarters`

To activate, add a deploy job to `.circleci/config.yml` after the existing `build` job:

```yaml
deploy-fly-dev:
  docker:
    - image: cimg/base:current
  steps:
    - checkout
    - run: curl -L https://fly.io/install.sh | sh
    - run: ~/.fly/bin/flyctl deploy --remote-only -a restarters-dev
  environment:
    FLY_API_TOKEN: $FLY_API_TOKEN   # set in CircleCI project settings

workflows:
  build-and-deploy:
    jobs:
      - build
      - deploy-fly-dev:
          requires:
            - build
          filters:
            branches:
              only: develop
```

`FLY_API_TOKEN` must be added to the CircleCI project's environment variables (Project Settings → Environment Variables). Generate a token with `fly tokens create deploy -a restarters-dev`.

A separate `deploy-fly-prod` job with `only: production` follows the same pattern for the production app.

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
fly logs -a restarters-dev        # live tail
fly logs -a restarters-dev --no-tail  # recent output
```

Laravel application logs (`LOG_CHANNEL=daily`) write to `/var/log/laravel/` on the persistent volume, surviving redeploys.

Nginx access/error logs write to `/var/log/nginx/` on the same volume.

---

## Database Access

The MySQL DB is not publicly accessible. To connect:

```bash
fly proxy 13306:3306 -a restarters-db   # open tunnel in one terminal
mysql -h 127.0.0.1 -P 13306 -u restarters -p restarters  # connect in another
```

phpMyAdmin (not publicly exposed):

```bash
fly machine start -a restarters-pma
fly proxy 8080:80 -a restarters-pma
# open http://localhost:8080 in browser
# Host: restarters-db.internal, user/pass from fly secrets
fly machine stop -a restarters-pma  # stop when done
```

---

## Common Operations

```bash
# Run an artisan command
fly ssh console -a restarters-dev -C "php artisan migrate:status"

# Open a shell
fly ssh console -a restarters-dev

# Check process status
fly ssh console -a restarters-dev -C "supervisorctl status"

# Restart the queue worker
fly ssh console -a restarters-dev -C "supervisorctl restart queue-worker"

# Check failed jobs
fly ssh console -a restarters-dev -C "php artisan queue:failed"

# Retry all failed jobs
fly ssh console -a restarters-dev -C "php artisan queue:retry all"

# Check app status and machine health
fly status -a restarters-dev

# Scale VM size
fly scale vm shared-cpu-2x -a restarters-dev
fly scale memory 2048 -a restarters-dev
```
