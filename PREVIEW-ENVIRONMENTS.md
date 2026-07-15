# PR Preview Environments

Label a PR with `preview` and approve the workflow run: you get a
self-contained copy of the site at `https://restarters-pr-<N>.fly.dev`,
running that PR's code (merged with current `develop`) against a fresh copy
of the live database. It costs pennies while idle and destroys itself after
the PR closes.

Technical detail lives in `docs/fly-deployment.md` → *PR Previews*. This file
is the operating guide plus the record of the concrete setup.

## Architecture at a glance

```
  PR labeled `preview` ─── or push to labeled PR ─── or manual dispatch
        │
        ▼
  GitHub Actions "PR preview" run
        │  waits at: Review deployments → preview → Approve
        │  (approver checks the PR's diff to .github/workflows/, fly.pr.toml,
        │   Dockerfile.fly, docker/*.sh — the run executes the PR's own code)
        ▼
  Build: PR merged with base (refs/pull/N/merge), Dockerfile.fly, remote builder
        │  first deploy also: create app, stage secrets, fresh APP_KEY
        ▼
  Deploy to restarters-pr-<N> ──► COLD BOOT
        │   1. warming page binds :80 (seconds)     ◄─ visitors see this
        │   2. local MariaDB starts
        │   3. latest hourly backup restored from Drive (~98MB, minutes)
        │   4. jobs/failed_jobs truncated (never run production's queue)
        │   5. php artisan migrate --force  (PR schema vs real data)
        │   6. status → /_preview_status; real nginx takes over
        ▼
  READY at https://restarters-pr-<N>.fly.dev  (cookie gate; PR title shown)
        │
        ├─ ~5 min idle ──► SUSPEND (whole-VM snapshot, DB stays loaded)
        │                     └─ next request ──► resume, sub-second
        ├─ snapshot discarded by Fly ──► next request = COLD BOOT again
        ├─ push + approve ──► redeploy = COLD BOOT with NEWER backup
        └─ PR closed / label removed ──► 6-hourly sweep destroys the app
```

## Using a preview

1. Add the `preview` label to the PR (or run the **PR preview** workflow via
   *Actions → PR preview → Run workflow* with the PR number).
2. The run pauses at **Waiting for review**. Before approving, glance at the
   PR's diff to `.github/workflows/`, `fly.pr.toml`, `Dockerfile.fly` and
   `docker/*.sh` — approval runs the PR's own code with the preview
   credentials. Then *Review deployments → preview → Approve*.
3. ~10 minutes later the workflow comments on the PR with the URL and the
   restore/migration result. Enter the gate password (in the
   `FLY_PREVIEW_SECRETS` blob as `BASIC_AUTH_PASSWORD`) to browse.
4. Every push to a labeled PR redeploys (each needs an approval click) with a
   fresh restore of the latest hourly backup.
5. Remove the label or close the PR and the sweep destroys the app within a
   few hours.

What to expect:

- **"Warming up" page**: cold boots (first deploy, redeploys, discarded
  snapshots) restore the DB for a few minutes behind an auto-refreshing page.
  After idle periods the machine *resumes* instead — near-instant.
- **Data is disposable**: it resets to a newer live copy on every deploy and
  may reset at any time. Log in with real production credentials (the DB is
  an unscrubbed live copy behind the gate).
- **Emails** go to shared Mailpit (https://restarters-dev-mail.fly.dev),
  never real recipients. **Image uploads are disabled** (reads come from the
  live bucket). Discourse/wiki/WordPress integrations are off.
- A red **PR-\<N\>** banner marks the preview, linking to Mailpit.
- ❌ *migrate failed* in the PR comment means the PR's migrations broke
  against real data — usually a signal to rebase; check
  `flyctl logs --app restarters-pr-<N>`.

## Handy commands

```bash
flyctl logs --app restarters-pr-<N>                 # live logs
flyctl ssh console --app restarters-pr-<N>          # shell on the preview
curl https://restarters-pr-<N>.fly.dev/_preview_status   # boot status JSON
flyctl apps destroy restarters-pr-<N> --yes         # manual teardown
```

The cleanup sweep also runs on demand: *Actions → PR preview → Run workflow*
with the PR number left **empty**.

## Concrete setup (configured 2026-07-13)

| Piece | Value |
|---|---|
| Fly organisation (`FLY_ORG` repo variable) | `personal` ("Restart Tech") |
| GitHub environment `preview` | required reviewers: edwh, ngm, restart-tech, restart-neil (any one approves); holds `FLY_ORG_TOKEN`, `FLY_PREVIEW_SECRETS` |
| GitHub environment `preview-cleanup` | no reviewers; deployment branches restricted to `develop`; holds `FLY_ORG_TOKEN` |
| Restore identity | `restarters-preview-restore@restarters-previews.iam.gserviceaccount.com` (GCP project `restarters-previews`, owned by edward@therestartproject.org; Drive API enabled). Needs **Viewer** on the backups folder — it must never be able to delete backups — AND the shared drive must allow viewers to download (see Operational learnings). |
| Tigris | dedicated **read-only** access key for `restarters-uploads` (created 2026-07-13 via the Tigris console) — the real enforcement behind the disabled-uploads flag |
| Label | `preview` |

Secrets template: `fly.pr-secrets.example.env`. To rotate or complete the
blob:

```bash
# edit ~/preview-secrets.env (or start from the template), then:
gh secret set FLY_PREVIEW_SECRETS --env preview < ~/preview-secrets.env
shred -u ~/preview-secrets.env
```


## Operational learnings

- **Drive sharing needs two things** (2026-07-14): Viewer access for the
  restore service account AND the shared drive setting "viewers and
  commenters can download". Without the latter, the SA can list backups but
  every download 403s (`cannotDownloadFile`) — the preview boots, reports
  `restore failed` at /_preview_status, and serves an empty database.
- **Resume does not re-run startup**: starting a *suspended* machine resumes
  its old memory state. To force a fresh restore (e.g. after fixing
  credentials), cold-boot it: `flyctl machine stop <id> -a restarters-pr-<N>`,
  wait for state `stopped`, then `flyctl machine start <id>`. Note
  `flyctl machine restart` fails on a suspended machine ("not currently
  started or stopped") — stop/start is the reliable sequence.
- **The gate login page shows the PR title** (PR #897): the workflow stages a
  `PREVIEW_PR_TITLE` secret each deploy; `public/_auth_login.php` displays it.
- **Measured on the first real preview** (PR #887, 2026-07-14): rootfs ≈1.2GB
  used → idle storage cost ≈ $0.20–0.30/month, matching the design estimate.
  Cold boot to ready ≈ 5–8 minutes end to end; suspend/resume confirmed
  working in production.

## Security model (short version)

- Previews run arbitrary PR code holding a full live-DB copy, so they get
  **no production-capable credentials**: fresh per-app `APP_KEY`, read-only
  Tigris key, read-only Drive SA, no mail/Discourse/wiki/WordPress/Drip
  secrets.
- The `preview` environment's required-reviewer gate is what stands between
  a pushed commit and the credentials — hence the pre-approval glance at
  infra files.
- The sweep's token is unreachable from PR code: it only exists in
  `preview-cleanup`, which only `develop`-ref runs (i.e. the schedule) can
  use. Never wire cleanup to `pull_request` events.
- The Fly org token can manage every app in the org. If it leaks, revoke with
  `fly tokens list` / `fly tokens revoke` and re-set both environment
  secrets.
