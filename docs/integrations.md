# External Integrations

How Restarters.net integrates with Discourse, MediaWiki, and WordPress.

---

## Authentication Architecture

Laravel is the primary authentication system. The other platforms delegate to it:

- **Discourse** — logs in via SSO against Laravel. When a user authenticates on Discourse, it redirects to Laravel's SSO endpoint, which issues a signed payload back to Discourse.
- **MediaWiki** — receives login credentials via a MediaWiki API call made during the Laravel login process. The `LogInToWiki` listener fires on login and creates (or updates) the corresponding wiki user account.
- **map.restarters.net** — shares the main Laravel session cookie.

### Session Cookies

Each platform maintains its own session cookie:

| Domain | Cookie |
|---|---|
| restarters.net | `restarters_session` (duration configurable via `SESSION_LIFETIME`) |
| talk.restarters.net (Discourse) | set during SSO authentication |
| wiki.restarters.net (MediaWiki) | retrieved from MediaWiki API during login |

On logout, the Laravel and MediaWiki session cookies are cleared. The Discourse session cookie behaviour at logout is uncertain — it may persist.

---

## Discourse

### Feature flag

```
FEATURE__DISCOURSE_INTEGRATION=true
```

When false, all Discourse sync is skipped silently.

### API access

Three modes of calling the Discourse API:

1. **Admin access** — use an API token with an admin username; grants access to all endpoints.
2. **User-specific access** — use the "All Users" API token with the logged-in user's username; returns only data relevant to that user.
3. **Public access** — call without authentication for publicly visible data.

Relevant env vars: `DISCOURSE_URL`, `DISCOURSE_APIKEY`, `DISCOURSE_APIUSER`, `DISCOURSE_SECRET`.

### SSO

Discourse SSO is handled by a controller that validates the nonce, signs the payload with `DISCOURSE_SECRET`, and redirects back to Discourse. The `DISCOURSE_SECRET` must match the value set in Discourse's admin settings.

---

## MediaWiki

### Feature flag

```
FEATURE__WIKI_INTEGRATION=true
```

### Environment

```
WIKI_URL=https://wiki.restarters.net
WIKI_APIUSER=Wiki-api-restarters     # MediaWiki admin account
WIKI_APIPASSWORD=...
```

The wiki domain must be a subdomain of the main app domain (`wiki.restarters.net`) so that session cookies work correctly for automatic login.

### SSO listeners

Two listeners in `app/Listeners/` handle wiki account sync:

- **`LogInToWiki`** — creates a wiki user account for each Restarters user on first login; subsequent logins keep credentials in sync.
- **`ChangeWikiPassword`** — fires when a user changes their Restarters password and syncs the change to MediaWiki.

### Troubleshooting SSO

When wiki SSO breaks for a specific user (common causes: account existed before integration, or MediaWiki password restrictions rejected the sync):

**Reset via Restarters (Tinker):**
```bash
php artisan tinker
# Find user, hash new password, save
$u = User::where('email', 'user@example.com')->first();
$u->password = Hash::make('newpassword');
$u->save();
```

**Reset via MediaWiki CLI:**
```bash
php /path/to/wiki/maintenance/changePassword.php --user=WikiUsername --password=newpassword
```

Users can also log in directly via `/Special:UserLogin` on the wiki when SSO isn't working.

---

## WordPress

The project publishes event and group data to therestartproject.org via WordPress XML-RPC.

Relevant env vars: `WP_XMLRPC_ENDPOINT`, `WP_XMLRPC_USER`, `WP_XMLRPC_PSWD`.

When deleting a group that is integrated with WordPress, a WordPress administrator must also remove the corresponding content from therestartproject.org separately — there is no automatic cleanup.

---

## Enabling/disabling integrations

For dev/staging environments, `fly-migrate.sh --secrets` automatically excludes production-only secrets (Discourse, Wiki, WordPress) so these integrations are safely disabled. See `docs/fly-deployment.md` for details.
