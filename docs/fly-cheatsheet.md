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

### Log format

The nginx access log uses a custom `timed` format (the `X-Forwarded-For` real client IP is logged first, then the Fly internal proxy IP in parentheses):
```
<real-client-ip> (<fly-proxy-ip>) - <user> [timestamp] "REQUEST" status bytes "referer" "ua"
rt=<total_request_time> uct=<fpm_connect_time> uht=<time_to_first_header> urt=<fpm_response_time>
```

Key fields:
- `rt` = total time nginx held the request (includes FPM wait + processing + response send)
- `urt` = time PHP-FPM took to process the request (the PHP execution time)
- `uct` = time to connect to FPM socket (near 0 normally; high values indicate socket backlog)
- `uht` = time until FPM sent the first response header (FPM queue wait + PHP start time)
- `uht=-` = FPM never responded (504 / worker died)

### Find slow requests (> 2s upstream time)

```bash
flyctl ssh console --app restarters --command \
  'awk '"'"'{for(i=1;i<=NF;i++) if($i~/^urt=/) {v=substr($i,5); if(v+0>2) print v,$0}}'"'"' \
  /var/log/nginx/access.log | sort -rn | head -30'
```

### Identify queued requests (rt much larger than urt)

Queueing shows as `uct` (FPM connect time) being elevated. When FPM workers are all busy, the
socket accept is delayed, so `uct >> 0`. The window `uht - uct` is the PHP processing time.

```bash
# Show requests where total time is > 5s — look for uct > 0 to spot FPM saturation
flyctl ssh console --app restarters --command \
  'awk '"'"'{for(i=1;i<=NF;i++) if($i~/^rt=/) {v=substr($i,3); if(v+0>5) print v,$0}}'"'"' \
  /var/log/nginx/access.log | sort -rn | head -30'
```

### Summarise slowest URLs by average upstream time

```bash
flyctl ssh console --app restarters --command \
  'awk '"'"'{url="-"; urt=0; for(i=1;i<=NF;i++){if($i~/^"(GET|POST|PUT|DELETE|PATCH|HEAD)/) url=$i; if($i~/^urt=/) urt=substr($i,5)+0} if(urt>0) print urt, url}'"'"' \
  /var/log/nginx/access.log | sort -rn | head -40'
```

### Top calling hosts (real IP)

The first field in the timed log is the real client IP (from `X-Forwarded-For`). The Fly proxy IP is field 2 (inside parens, stripped on parsing).

```bash
flyctl ssh console --app restarters --command \
  'awk '"'"'{print $1}'"'"' /var/log/nginx/access.log | sort | uniq -c | sort -rn | head -20'
```

### Count 5xx errors by URL

```bash
# Fields: 1=real-ip 2=(fly-ip) 3=- 4=- 5=[timestamp] 6=timezone] 7="METHOD 8=PATH 9=PROTO" 10=status
flyctl ssh console --app restarters --command \
  'awk '"'"'$10~/^5/ {print $10, $8}'"'"' /var/log/nginx/access.log | sort | uniq -c | sort -rn | head -20'
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
