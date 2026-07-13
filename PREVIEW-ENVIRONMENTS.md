# PR Preview Environments

Label a PR with `preview` and approve the workflow run: you get a
self-contained copy of the site at `https://restarters-pr-<N>.fly.dev`,
running that PR's code (merged with current `develop`) against a fresh copy
of the live database. It costs pennies while idle and destroys itself after
the PR closes.

Technical detail lives in `docs/fly-deployment.md` → *PR Previews*. This file
is the operating guide plus the record of the concrete setup.

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
| GitHub environment `preview` | required reviewer: edwh; holds `FLY_ORG_TOKEN`, `FLY_PREVIEW_SECRETS` |
| GitHub environment `preview-cleanup` | no reviewers; deployment branches restricted to `develop`; holds `FLY_ORG_TOKEN` |
| Restore identity | `restarters-preview-restore@restarters-previews.iam.gserviceaccount.com` (GCP project `restarters-previews`, owned by edward@therestartproject.org; Drive API enabled). Needs **Viewer** on the backups folder — it must never be able to delete backups. |
| Tigris | previews use a **read-only** access key for `restarters-uploads` (create via `fly storage dashboard restarters-uploads` → Access Keys) |
| Label | `preview` |

Secrets template: `fly.pr-secrets.example.env`. To rotate or complete the
blob:

```bash
# edit ~/preview-secrets.env (or start from the template), then:
gh secret set FLY_PREVIEW_SECRETS --env preview < ~/preview-secrets.env
shred -u ~/preview-secrets.env
```

## Security model (short version)

- Previews run arbitrary PR code holding a full live-DB copy, so they get
  **no production-capable credentials**: fresh per-app `APP_KEY`, read-only
  Tigris, read-only Drive SA, no mail/Discourse/wiki/WordPress/Drip secrets.
- The `preview` environment's required-reviewer gate is what stands between
  a pushed commit and the credentials — hence the pre-approval glance at
  infra files.
- The sweep's token is unreachable from PR code: it only exists in
  `preview-cleanup`, which only `develop`-ref runs (i.e. the schedule) can
  use. Never wire cleanup to `pull_request` events.
- The Fly org token can manage every app in the org. If it leaks, revoke with
  `fly tokens list` / `fly tokens revoke` and re-set both environment
  secrets.
