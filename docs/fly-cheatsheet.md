# Fly.io Cheat Sheet

Quick reference for common operations on the Fly.io environments.

---

## Setup

```bash
# Authenticate (token is in .env — uncomment it, or export inline)
export FLY_API_TOKEN="<token from .env>"

# Or log in interactively
flyctl auth login
```

---

## Run an Artisan command on a live instance

```bash
flyctl ssh console --app restarters-dev --command "php /var/www/artisan <command>"
```

**Examples:**

```bash
# Add a network coordinator
flyctl ssh console --app restarters-dev \
  --command "php /var/www/artisan network:coordinator:add 'some@email.com'"

# Run migrations
flyctl ssh console --app restarters-dev \
  --command "php /var/www/artisan migrate --force"

# Check migration status
flyctl ssh console --app restarters-dev \
  --command "php /var/www/artisan migrate:status"

# Clear caches
flyctl ssh console --app restarters-dev \
  --command "php /var/www/artisan cache:clear"

# Open a PHP REPL (tinker)
flyctl ssh console --app restarters-dev --command "php /var/www/artisan tinker"
```

For production (`restarters-prod`), replace `restarters-dev` with `restarters`.

---

## Open an interactive shell

```bash
flyctl ssh console --app restarters-dev
```

---

## Deploy

```bash
# Deploy dev (normally triggered by CI on develop branch)
flyctl deploy --config fly.dev.toml --remote-only

# Deploy production
flyctl deploy --config fly.toml --remote-only
```

---

## Logs

```bash
# Live log stream
flyctl logs --app restarters-dev

# Nginx access log (persistent — survives redeploys)
flyctl ssh console --app restarters-dev --command "tail -f /var/log/nginx/access.log"

# Laravel log (persistent — survives redeploys)
flyctl ssh console --app restarters-dev --command "tail -f /var/log/laravel/laravel.log"

# PHP-FPM slow log
flyctl ssh console --app restarters-dev --command "tail -f /var/log/php-fpm-slow.log"
```

---

## Secrets (environment variables)

```bash
# List secrets (names only — values never shown)
flyctl secrets list --app restarters-dev

# Set a secret
flyctl secrets set MY_VAR="value" --app restarters-dev

# Delete a secret
flyctl secrets unset MY_VAR --app restarters-dev
```

---

## Scale / machines

```bash
# List machines and their status
flyctl machine list --app restarters-dev

# Check app status
flyctl status --app restarters-dev
```

---

## Database access (phpMyAdmin)

```bash
# Proxy to phpMyAdmin (opens at http://localhost:8080)
flyctl proxy 8080:80 --app restarters-pma
```

---

## File system notes

- The container filesystem is **ephemeral** — files written outside `/var/log` are lost on redeploy.
- `/var/log` is a **persistent volume** (`app_logs_dev`) — logs and file cache survive redeploys.
- Uploaded images are written to `/var/www/public/uploads/` temporarily, then synced to **Tigris S3**.  
  Nginx proxies `/uploads/*` → Tigris, so images are served from S3, not local disk.
- `public/uploads/` is created at build time (Dockerfile.fly) and on each container start (startup.sh).
