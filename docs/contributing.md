# Contributing

Branching strategy and development workflow for restarters.net.

---

## Branch Structure

| Branch | Purpose |
|---|---|
| `develop` | Integration branch — all feature work targets this |
| `master` | Production-ready; always deployable |
| `production` | CI deploy trigger — merge `master → production` to ship |

---

## Feature Work

1. Branch off `develop`:
   ```bash
   git checkout develop
   git pull
   git checkout -b DOT-1234_short-description
   ```
2. Keep the branch up to date: merge `develop` in regularly to avoid large divergence.
3. Push and open a pull request against `develop`.
4. Code review before merge.

**Branch naming:** use the issue ID (GitHub or Jira) followed by an underscore and a short description, e.g. `DOT-1346_safari-timepicker`.

**External contributors** should fork the repository and open a pull request from their fork.

---

## Hotfixes (urgent production bugs)

1. Branch off `master` (not `develop`):
   ```bash
   git checkout master
   git pull
   git checkout -b hotfix/short-description
   ```
2. Fix, test, PR against `master`.
3. After merge, immediately merge `master → develop` to keep branches in sync.
4. Deploy: merge `master → production` and push (see `docs/fly-deployment.md`).

---

## Releases / Deployment

See `docs/fly-deployment.md` for full deployment instructions.

The short version:
```bash
git checkout production
git merge master
git push
# CircleCI auto-deploys to restarters.net
```

> **Never deploy `develop` or `master` directly to the `restarters` Fly app.** Always go via the `production` branch.
