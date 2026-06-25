# Queue and Email

How background jobs and email notifications are processed.

---

## Queue System

Email notifications (and other background work) are queued rather than processed inline, to avoid blocking web requests and to allow retries on failure.

- **Driver:** `database` — jobs are stored in the `jobs` table, which means they survive process restarts without loss.
- **Worker:** `php artisan queue:work database` — runs continuously under supervisord (see `docs/fly-deployment.md`).
- **Retries:** jobs are retried for up to **4 days** before being moved to the `failed_jobs` table.
- **Failed jobs:** trigger a Sentry alert. Investigate via:
  ```bash
  flyctl ssh console --app restarters --command "php /var/www/artisan queue:failed"
  flyctl ssh console --app restarters --command "php /var/www/artisan queue:retry all"
  ```

---

## Email

Laravel's mail system is used for all outbound email. In production, mail is sent via Mailgun (`MAIL_MAILER=mailgun`).

In development, email is routed to a local Mailpit instance instead of real inboxes. See `docs/fly-deployment.md` (Email in dev section) for how to view captured emails.

Relevant env vars: `MAIL_MAILER`, `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAIL_FROM_ADDRESS`.

---

## In-app notifications

In-app notifications (the notification bell) operate through a separate path from email and are not queued via the `jobs` table. They are written directly to the `notifications` table via Laravel's notification system.

---

## Queue monitoring

supervisord restarts the worker automatically if it crashes, but does not alert on slow/stuck workers or consistently-failing jobs.

**TODO:** add `php artisan queue:monitor database:10` to the scheduler (`app/Providers/ScheduleServiceProvider.php`) — this fires a `QueueBusy` event when the queue depth exceeds 10, which can be routed to Slack or email.

See also the Monitoring section in `docs/fly-deployment.md`.
