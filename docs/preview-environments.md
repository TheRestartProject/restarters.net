# PR preview environments

Label a pull request with `preview` and approve the workflow run: you get a
self-contained copy of the site at `https://restarters-pr-<N>.fly.dev`,
running that PR's code (merged with current `develop`) against a fresh copy
of the live database. An idle preview costs pennies a month (its stored disk
image only, roughly 20–30p); once the PR closes it is destroyed and costs
nothing.

Technical design detail lives in `fly-deployment.md` → *PR Previews*. This
file is the operating guide plus the record of the concrete setup.

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
  Deploy to restarters-pr-<N> ──► COLD BOOT (about 5–8 minutes)
        │   1. warming page binds :80 (seconds)     ◄─ visitors see this
        │   2. local MariaDB starts
        │   3. latest hourly backup restored from Drive (~98MB)
        │   4. jobs/failed_jobs truncated (never run production's queue)
        │   5. php artisan migrate --force  (PR schema vs real data)
        │   6. status → /_preview_status; real nginx takes over
        ▼
  READY at https://restarters-pr-<N>.fly.dev  (login gate shows the PR title)
        │
        ├─ ~5 min idle ──► SUSPEND (whole-VM snapshot, DB stays loaded)
        │                     └─ next request ──► resume, sub-second
        ├─ snapshot discarded by Fly ──► next request = COLD BOOT again
        ├─ push + approve ──► redeploy = COLD BOOT with NEWER backup
        └─ PR closed / label removed ──► 6-hourly sweep destroys the app
```

## Using a preview

Everything is driven from GitHub — the PR page and the Actions tab — not
from the command line.

1. **Start it from the PR page**: on
   `https://github.com/TheRestartProject/restarters.net/pull/<N>`, add the
   `preview` label (right-hand sidebar → Labels). Alternatively start it
   from the Actions tab: open
   <https://github.com/TheRestartProject/restarters.net/actions/workflows/pr-preview.yml>,
   click *Run workflow* and enter the PR number.
2. **Approve it in the Actions run**: the run appears at the same Actions
   URL above (and under the PR's checks) with a yellow "Waiting" badge;
   reviewers also get a "Deployment review required" email linking straight
   to it. Open the run, then *Review deployments → tick `preview` →
   Approve and deploy*. Before approving, glance at the PR's diff to
   `.github/workflows/`, `fly.pr.toml`, `Dockerfile.fly` and `docker/*.sh` —
   approval runs the PR's own code with the preview credentials.
3. **Wait for the PR comment**: around ten minutes later (build plus cold
   boot) the workflow posts/updates a comment on the PR with the preview URL
   (`https://restarters-pr-<N>.fly.dev`) and the restore/migration result.
   The login gate shows the PR title so you can tell which preview you are
   on; the password is the shared preview password (`BASIC_AUTH_PASSWORD`
   in the `FLY_PREVIEW_SECRETS` secret at
   <https://github.com/TheRestartProject/restarters.net/settings/environments>).
4. **Pushes queue redeploys**: every push to a labeled PR queues a new run
   needing an approval click; only the newest push's run survives. A
   redeploy restores a fresh copy of the latest hourly backup.
5. **Cleanup is automatic**: remove the label or close the PR and the sweep
   destroys the app within a few hours.

What to expect:

- **"Warming up" page**: cold boots (first deploy, redeploys, discarded
  snapshots) restore the database for a few minutes behind an auto-refreshing
  page. After idle periods the machine *resumes* instead — near-instant.
- **Data is disposable**: it resets to a newer live copy on every deploy and
  may reset at any time. Log in with real production credentials (the
  database is an exact copy of live, behind the login gate).
- **Emails** go to shared Mailpit (https://restarters-dev-mail.fly.dev),
  never to real recipients. **Image uploads are disabled** (image reads come
  from the live bucket). Discourse/wiki/WordPress integrations are off.
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

**Forcing a fresh restore** (for example after fixing backup access): a
suspended machine resumes its old memory state rather than re-running
startup, and `flyctl machine restart` refuses to act on a suspended machine.
Cold-boot it instead:

```bash
flyctl machine stop <machine-id> -a restarters-pr-<N>
# wait until `flyctl machine status` shows "stopped", then:
flyctl machine start <machine-id> -a restarters-pr-<N>
```

## Troubleshooting

- `/_preview_status` says **restore failed** and the site has no data: the
  restore account probably cannot read the backups. It needs BOTH Viewer
  access on the backups folder AND the shared drive setting that lets
  viewers download files. If downloads are blocked, the account can still
  list the backups but every download fails with a 403
  (`cannotDownloadFile`) in `/var/log/preview-restore.log` on the machine.
- Machine logs: `flyctl logs --app restarters-pr-<N>`, or
  `flyctl ssh console -a restarters-pr-<N> -C "tail -50 /var/log/preview-restore.log"`.

## Concrete setup

| Piece | Value |
|---|---|
| Fly organisation (`FLY_ORG` repo variable) | `personal` ("Restart Tech") |
| GitHub environment `preview` | required reviewers: edwh, ngm, restart-tech, restart-neil (any one approves); holds `FLY_ORG_TOKEN`, `FLY_PREVIEW_SECRETS` |
| GitHub environment `preview-cleanup` | no reviewers; deployment branches restricted to `develop`; holds `FLY_ORG_TOKEN` |
| Restore identity | `restarters-preview-restore@restarters-previews.iam.gserviceaccount.com` (GCP project `restarters-previews`, owned by edward@therestartproject.org; Drive API enabled). Needs **Viewer** on the backups folder — never anything stronger, so previews can never delete backups — and the shared drive must allow viewers to download (see Troubleshooting). |
| Tigris (image store) | a dedicated **read-only** access key for `restarters-uploads` — the real enforcement behind the disabled-uploads flag |
| Label | `preview` |

Secrets template: `fly.pr-secrets.example.env` in the repo root. To rotate or
complete the secrets:

```bash
# edit ~/preview-secrets.env (or start from the template), then:
gh secret set FLY_PREVIEW_SECRETS --env preview < ~/preview-secrets.env
shred -u ~/preview-secrets.env
```

## Security model

- Previews run whatever code is in the PR, next to a full copy of the live
  database. They therefore hold **no credentials that could touch
  production**: each preview app gets its own randomly generated `APP_KEY`,
  its keys for the image store and the backups folder are read-only, and it
  has none of the mail, Discourse, wiki, WordPress or Drip secrets.
- **Approval is the security boundary.** Nothing deploys until one of the
  listed reviewers approves the run, and approval executes the PR's own code
  with the preview credentials — that is why the pre-approval glance at the
  infrastructure files matters.
- **The cleanup job's Fly token is out of reach of PR code.** That token can
  create and destroy any app in the organisation, so it is stored only in
  the `preview-cleanup` environment, which GitHub restricts to runs of the
  workflow as it exists on `develop` — in practice, the scheduled cleanup.
  A pull request cannot get at it, because pull-request runs execute the
  PR's copy of the workflow and are not allowed to use that environment.
  For the same reason, cleanup must never be triggered by pull-request
  events.
- **If the Fly token leaks**, revoke it (`fly tokens list`,
  `fly tokens revoke`) and store a fresh one in both GitHub environments.
