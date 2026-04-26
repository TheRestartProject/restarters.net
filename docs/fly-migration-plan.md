# Fly.io Go-Live Plan

**Go-live:** Tuesday 5 May 2026, 1pm–3pm BST
**Scope:** Laravel 10 + group tags deployed together to `restarters.net`

---

## Immediate Next Actions

### Edward
- [x] Get outstanding PRs merged into `develop`
- [x] Stand up `restarters.dev` from `develop`
- [x] **Set up auto-deploy for `develop` → `restarters-dev`** — CircleCI deploy job added; add `FLY_API_TOKEN` to CircleCI project env vars to activate (token already generated)
- [ ] **Set up queue and app monitoring** — supervisord keeps processes up, but need alerting if queue backs up or app goes unhealthy
- [ ] Tidy `production` branch — remove committed `node_modules` / build artefacts
- [ ] Activate auto-deploy for `production` → `restarters` (same CircleCI pattern as develop, once develop is working)
- [ ] Rebuild "yesterday" restore system
- [x] Deploy Mailpit for dev: `restarters-dev-mail` live at `https://restarters-dev-mail.fly.dev` — navbar on `restarters-dev` links to it
- [ ] Set remaining Fly secrets from production `.env` (see below)
- [ ] Final staging test with production data (`fly-migrate.sh --app restarters-dev --db --images`)
- [x] API compatibility check — no breaking changes for known consumers. All v1 stats endpoints, RepairTogether, Zapier triggers, and TRP.org widgets are unchanged. Tag visibility changes are intentional and fine.
- [ ] Check and renew `restarters.net` domain at iwantmyname (noted as due soon)
- [ ] Write Fly.io ops crib for Neil

### Neil
- [ ] Re-check known-issues list (some may be fixed since Laravel 10 / group tags work)
- [ ] Tell network coordinators about 5 May 1–3pm window
- [ ] Put banner on site once Big Give finishes (~29 April)

---

## Secrets Still to Set

```bash
fly secrets set \
  MAIL_FROM_ADDRESS=noreply@mg.restarters.net \
  MAIL_FROM_NAME="..." \
  WP_XMLRPC_ENDPOINT="..." \
  WP_XMLRPC_USER="..." \
  WP_XMLRPC_PSWD="..." \
  DRIP_CAMPAIGN_ID="..." \
  -a restarters
```

Values from the production `.env` on `restart-sp`. `MAIL_FROM_ADDRESS` is changed (not copied) — fixes DMARC alignment.

---

## Migration Day — 5 May

| When | Action |
|---|---|
| 1:00pm | Tell network coordinators not to log devices |
| 1:02pm | Put old server in maintenance mode: `php artisan down` |
| 1:04pm | Run final image sync and DB migration in parallel: `fly-migrate.sh --images` + `fly-migrate.sh --db` |
| ~1:35pm | Update Fly secrets for production and deploy: `fly secrets set APP_URL=https://restarters.net APP_ENV=production SENTRY_ENVIRONMENT=production -a restarters && fly deploy -a restarters` |
| ~1:45pm | Add custom domain and TLS: `fly certs add restarters.net -a restarters` |
| ~1:50pm | **Switch DNS** — point `restarters.net` A record to `66.241.124.187` (AAAA: `2a09:8280:1::ce:b85f:0`) |
| ~2:00pm | Run smoke tests (see below) |
| 3:00pm | Done, or roll back |

**New server is NOT put in maintenance mode** — it goes live immediately when DNS switches.

Also update `fly.toml` env before deploying:
```toml
APP_ENV = "production"
APP_URL = "https://restarters.net"
FEATURE__DISCOURSE_INTEGRATION = "true"
SENTRY_ENVIRONMENT = "production"
SESSION_DOMAIN = ".restarters.net"
```

---

## Smoke Tests

- [ ] Homepage loads, HTTPS works, HSTS header present
- [ ] Static assets and uploaded images load (verifies Tigris proxy)
- [ ] Login, session persistence
- [ ] Password reset email arrives (tests Mailgun)
- [ ] Create a repair event and log a device
- [ ] Upload an image — verify it persists after page reload
- [ ] Locale switch to French
- [ ] Discourse SSO — click Talk link, verify login works
- [ ] WordPress XML-RPC — create/edit event, verify it appears on therestartproject.org
- [ ] Network subdomains resolve correctly
- [ ] `GET /api/homepage_data`, `/api/party/{id}/stats`, `/api/group/{id}/stats` return valid data
- [ ] Queue worker running: `fly ssh console -a restarters -C "ps aux | grep queue:work"`
- [ ] No unexpected failed jobs: `fly ssh console -a restarters -C "php artisan queue:failed"`

---

## Rollback

Switch DNS back to `139.59.184.196` and run `php artisan up` on `restart-sp`. With 1-hour TTL, propagation is fast. Any writes that landed on the new server during the window can be salvaged manually.

**Roll back if** (within the 2-hour window): site unreachable > 10 min, data loss, login/device-logging broken, email completely failed, or sustained error rate > 5% in Sentry.

---

## Post-Go-Live (First Week)

- [ ] Verify hourly Google Drive backups are running
- [ ] Run "yesterday" restore to confirm backup/restore pipeline works
- [ ] Verify Discourse sync (`discourse:syncgroups`) and language sync are running on schedule
- [ ] Check Mailgun dashboard for delivery stats
- [ ] Scale down VMs if usage data supports it
- [ ] Restore DNS TTL to 3600s after 48 hours of stable operation
- [ ] Move Repair Directory (`map.restarters.net`) to Fly as a separate container in the same project, accessing the same DB

---

## Reference

**DNS records to change on go-live:**
- `restarters.net` A → `66.241.124.187`
- `restarters.net` AAAA → `2a09:8280:1::ce:b85f:0`

**Do not touch:** `talk.restarters.net`, `wiki.restarters.net`, `mg.restarters.net`, `map.restarters.net`
