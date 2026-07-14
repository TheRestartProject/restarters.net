# Usage instrumentation → Loki → AI flow-coverage analysis

Status: **approved design** (2026-07-14). Implementation follows in phased PRs
(see Phasing). Decisions taken by Edward are marked ✔.

## Goal

Capture the flows real users actually perform (client actions + API calls),
store them in a self-hosted Loki, and provide tooling that lets an AI mine
those events into canonical flows, compare them against the existing test
estate (Playwright / PHPUnit API / Jest), and produce the missing golden-flow
tests. No third-party SaaS (no Hotjar/Clicktale-style tools).

This design was produced from three research strands (the Freegle
client→Loki pipeline, a full inventory of this codebase, and 2025–26 state of
the art for behavioural capture and trace-based test generation) followed by a
four-lens adversarial review (privacy/GDPR, signal quality, operations,
frontend feasibility — 27 findings incorporated).

## Ground truth this design rests on

- restarters.net is **not an SPA**: 221 blade views; Vue 2 (v2.7) islands —
  ~118 components mounted per-element with no vue-router. Whole flows are
  blade-only (all 14 user/profile views, registration, password reset).
  **Verified**: the main JS bundle IS loaded on those blade-only pages, so one
  DOM-level capture layer covers both worlds. (The 4 iframe/stat-embed views
  don't load it — explicitly out of scope, they are non-interactive.)
- Document capture-phase listeners see events before any jQuery
  `stopPropagation` (verified); `el.__vue__` is present in Vue 2.7 (verified).
- The existing consent flag (`analyticsCookieEnabled`) is currently read once
  at page parse, **does not actually gate Matomo** (only Sentry's beforeSend
  reads it), and nothing reacts to the banner's `gdprCookiesEnabled` event.
  Phase 1 fixes this rather than inheriting it.
- `api_token` travels in query strings → server-side logging must strip query
  strings everywhere.
- Freegle's proven pipeline shape is adopted (client batch → own-API relay →
  NDJSON file → Alloy tail → Loki; low-cardinality labels; per-stream
  retention) with its known gaps closed: no raw IPs, real consent handling,
  Loki never publicly reachable.

## Architecture

```
Browser (all pages; module in the main bundle, init at DOMContentLoaded —
         NOT inside the jQuery/Leaflet polling gate)
  flowcap module (neutral naming; "usage"/"analytics"/"track" attract adblockers)
    - document capture-phase listeners: click, submit, change
    - element identity, priority order:
        1. nearest [data-flow="area.step"]           ← PRIMARY (✔ committed)
        2. sanitized elements_chain descriptor (PostHog-style ancestor walk;
           strips data-v-* hashes, __BVID__*/__BV_* generated ids, noise classes)
        3. nearest meaningful Vue component via el.__vue__ walk, skipping a
           denylist of library internals — supplementary signal only
    - page_view on DOMContentLoaded + pageshow (bfcache restores flagged)
    - form fields: identity + emptiness/length only; NEVER values;
      password fields suppressed entirely
    - no mouse movements, no scroll, no keystrokes
      (optional later: rage-click derived event)
    - context: session_id (sessionStorage, mirrored into a SameSite=Lax cookie
      so native blade form POSTs and Dropzone uploads correlate),
      trace_id per interaction (JS-only), numeric user id,
      page id via <meta name="page">, page_load_phase state machine
    - queue ≤10 events / 5s; flush via navigator.sendBeacon on
      pagehide/visibilitychange (fetch keepalive fallback; byte-capped;
      never unload/beforeunload — they break bfcache)
    - axios + jQuery ajax interceptors add X-Session-ID / X-Trace-ID
        │ POST /api/session-events
        ▼
Laravel
  - SessionEventsController: loose validation, enrichment (user id, coarse UA
    class, NO raw IP), append NDJSON via a dedicated Monolog channel to
    /var/log/usage/*.log; always 204 (fire-and-forget)
  - RecordApiUsage middleware (web + api groups, EXCLUDING the ingestion route):
    method, route PATTERN (no raw URLs; query strings stripped), status,
    duration_ms, user id, session id (header, cookie fallback) → same stream
        │ logrotate: 3–7 days local retention (Loki holds the long copy)
        ▼
Grafana Alloy (new supervisord program on production and restarters-dev ONLY —
  per-PR preview apps are excluded: no /var/log volume, 2GB no-swap suspend
  contract, and their traffic is synthetic anyway; FEATURE__USAGE_TELEMETRY is
  forced off there)
  - Loki labels: {app, env, source, event_type} ONLY; session/user/trace ids go
    to structured metadata (never labels — cardinality)
  - WAL buffering rides out Loki outages
        ▼
Loki — new Fly app `restarters-loki` (✔ Loki is the single store of record)
  - private 6PN only, NO public IP; shared-cpu-1x; small volume; version pinned
  - retention_stream: usage streams 90d
  - volume not backed up — accepted risk (losing it restarts collection)
        ▼
flowmine — PHP artisan commands (✔) under the app (operator tooling)
  1. flowmine:export  — paginated pull from Loki HTTP API over ≤30-day windows
                        (✔ 30-day mining window accepted) into local SQLite
  2. flowmine:mine    — sessionize (session_id = case id) and build a
                        directly-follows graph; flow variants ranked by
                        frequency (DFG + variant counting is a few hundred
                        lines of PHP; no external mining library needed)
  3. flowmine:manifest— regenerate the test-coverage manifest (below)
  4. flowmine:report  — ranked gap list (flow frequency × missing coverage);
                        k-anonymity floor: a variant needs ≥5 distinct sessions
                        to appear in any export; rarer flows are flagged for
                        human-only review. The LLM-facing brief contains
                        aggregates only — no trace ids, nothing Sentry-joinable
        ▼
Claude consumes the brief and writes the missing Playwright / API / Jest tests
as ordinary human-reviewed PRs.
```

## The `data-flow` convention (✔ committed, with decay protection)

CSS-path descriptors fragment even within a single release (permission- and
viewport-dependent DOM, BootstrapVue's mount-order `__BVID__` ids,
scoped-style `data-v-*` hashes). Therefore:

- Phase 1 adds `data-flow="area.step"` (e.g. `group.create.save`,
  `event.add-device.submit`) to the ~30–40 highest-traffic interactive
  elements (group/event forms, EventActions, dashboard actions, auth +
  registration blade forms). Descriptor capture still runs everywhere as
  discovery for elements not yet annotated.
- Playwright specs standardise on `[data-flow=...]` selectors for
  flow-defining steps, so the coverage manifest and production events key off
  identical strings.
- **Decay protection (✔ requested)**: a checked-in `tests/flow-registry.json`
  lists every canonical `data-flow` value and the template file(s) expected to
  carry it. A CI check (`flowmine:lint`, run in the existing test workflow):
  - fails if a registry entry no longer appears in any template (attribute
    removed/renamed without updating the registry);
  - fails on malformed values (must match `^[a-z0-9-]+(\.[a-z0-9-]+)+$`);
  - fails if a Playwright spec references a `data-flow` value absent from the
    registry;
  - warns (not fails) on template `data-flow` values missing from the registry,
    so adding new annotations is frictionless but deleting tracked ones is loud.
  It scans both `.vue` templates and blade files (plain text scan — no fragile
  AST work), so the convention cannot silently rot in either world.

## Consent & privacy (✔ two-tier)

1. **Server-side operational logging** (RecordApiUsage): route pattern,
   status, duration, coarse UA class — runs for everyone under **legitimate
   interest** (equivalent to the nginx access logs that already exist, which
   record strictly more). Contains no behavioural detail. User/session
   correlation fields are populated ONLY when analytics consent exists — the
   middleware checks the consent cookie server-side; absent consent, those
   fields are omitted.
2. **Client behavioural capture** (flowcap): consent-gated and **reactive** —
   flowcap subscribes to the banner's `gdprCookiesEnabled` event; withdrawal
   stops queueing and flushing within the same page view. Phase 1 also fixes
   the pre-existing bug that Matomo ignores the consent flag.

Accepted consequence: pre-consent flows (registration, first visit) are
under-observed by client capture; they remain visible at page granularity in
tier-1 logs and stay covered by hand-written tests.

Additional controls:
- No raw IPs anywhere in the pipeline; numeric user ids only; 64-char string
  truncation; descriptor depth caps.
- **Erasure runbook** (GDPR Art. 17): raw NDJSON files are user-id-filterable
  for their short local window (documented rewrite script); Loki entries age
  out at ≤90d; LLM-facing exports are k≥5 aggregates with no identifiers.
- Privacy policy page updated in phase 1 (user-facing copy).
- The LLM step: only the aggregate gap brief leaves the machine; Anthropic
  API no-training/no-retention terms cited in the phase-3 docs.

## Ingestion endpoint hardening

- nginx: dedicated `location = /api/session-events` with
  `client_max_body_size 8k` and per-IP `limit_req` (same zero-FPM-cost pattern
  as the existing `/_login` limit) — abuse is rejected before PHP-FPM.
  Laravel throttle as a second layer.
- Origin/Sec-Fetch-Site same-site check (endpoint is CSRF-exempt but not
  cross-site-open). Client-supplied session_id is never trusted for rate
  limiting (per-IP only).
- Poisoning damping: the k≥5 distinct-session floor in flowmine means forged
  traffic must sustain many distinct sessions through nginx rate limits to
  influence the gap report; the report is also human-reviewed before any test
  is written.

## Test-coverage manifest

- **PHPUnit**: a test listener records route patterns hit per test,
  distinguishing scaffolding from subject (calls during setUp()/helpers are
  tagged via stack inspection — ~28% of Feature tests hit routes as
  scaffolding and must not count as coverage).
- **Playwright**: extractor reads `data-flow` selectors + `page.goto` targets;
  residual `hasText` locators resolved through the lang files.
- **Jest**: component-name map.
- Output: `tests/coverage-manifest.json`, regenerated in CI.

## Deployment & cost

- `restarters-loki`: ~$3–4/month. Alloy: ~50–100MB RSS on prod (4GB box) and
  restarters-dev. Previews: telemetry off, no Alloy.
- Volume: before committing Loki sizing, phase 1 measures a week of real
  traffic (nginx access-log counts approximate the server-event stream —
  the middleware logs every request sitewide, not just client events).
- Local dev: file-only, no Alloy; `flowmine:tail` helper; /var/log/usage
  bootstrap in the local entrypoint.
- Feature flag `FEATURE__USAGE_TELEMETRY` (default off): dev → production.

## Phasing (each phase a separate PR)

1. **Capture + ship**: flowcap module, /api/session-events + nginx hardening,
   RecordApiUsage, data-flow attributes on the top ~30–40 elements +
   flow-registry + flowmine:lint CI check, reactive consent (+ Matomo consent
   bugfix), Alloy + restarters-loki, logrotate, privacy-policy copy, docs.
   Exit: events flowing from dev; no measurable page-perf regression; real
   volume numbers collected.
2. **flowmine export/mine**: Loki export → SQLite → DFG mining → first real
   usage report over ≥2 weeks of production data.
3. **Coverage manifest + gap report**: PHPUnit listener, Playwright/Jest
   extraction, matcher, k≥5 floor, LLM brief format.
4. **First golden-flow tests** generated from the brief; the loop documented
   as a repeatable (e.g. quarterly) process.

## Decisions taken (Edward, 2026-07-14)

| Decision | Choice |
|---|---|
| Consent model | Two-tier: LI for server route logs, consent-gated client capture |
| Store of record | Loki only; 30-day mining windows accepted |
| Element identity | `data-flow` attributes primary, with registry + CI lint against decay |
| flowmine language | PHP artisan commands |
| Grafana UI | Not initially (analysis is CLI/AI-driven; revisit if needed) |

## Non-goals

Session replay / DOM recording; keystroke or mouse-path capture; replacing
Matomo or Sentry; real-time dashboards or alerting; instrumenting iframe stat
embeds; capture on per-PR preview apps; instrumenting legacy raw jQuery code
beyond what DOM-level capture already observes.
