# Public Events API

This project exposes a read-only public events API behind the `FEATURE__PUBLIC_EVENTS_API` feature flag.

## Endpoints

- `GET /api/public/v2/events`
- `GET /api/public/v2/events/{id}`
- `GET /api/public/v2/groups/{id}/events`

## Authentication

Send a bearer token in the `Authorization` header:

`Authorization: Bearer <token>`

Query-string API tokens are not accepted for this API.

## Managing clients

Administrators can create, rotate, and revoke public API clients from `/admin/api-clients`.

CLI fallbacks are also available:

- `php artisan api-clients:create --name="Example integration"`
- `php artisan api-clients:rotate 123`
- `php artisan api-clients:revoke 123`

Tokens are stored only as hashes. The plaintext token is shown exactly once when created or rotated.

## Filters

`GET /api/public/v2/events` and `GET /api/public/v2/groups/{id}/events` support:

- `start`
- `end`
- `updated_start`
- `updated_end`
- `page`
- `per_page`

If `start` is omitted, the API defaults to upcoming and in-progress events.
