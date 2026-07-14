# Usage instrumentation → flow mining → AI test-coverage analysis

Status: **approved design v2** (2026-07-14). Implementation follows in phased PRs.
Decisions by Edward marked ✔.

An executed matching dry-run accompanies this design: see
`usage-instrumentation-worked-example.md` and the runnable prototype in
`flowmine-demo/` (mined group-create correctly matched to group.test.js with
duplicate avoided; uncovered registration flow detected and a candidate spec
generated).

Two adversarial review rounds shaped this document. Round 1 (privacy/GDPR,
signal quality, operations, frontend feasibility — 27 findings) hardened the
original bespoke pipeline. Round 2 attacked the build-vs-buy premise and the
flow→test matching feasibility with a worked example from the real codebase;
it split the design: the client capture layer now rides the already-deployed
Matomo tracker (✔), while the server-side stream, mining, and coverage
matching remain custom because no off-the-shelf product addresses them
(verified against Matomo, PostHog, OpenReplay, Countly, Snowplow, Jitsu,
RudderStack, Faro, Umami, Plausible, Highlight.io; "ClickTail" does not exist
as a maintained 2026 project).

## Goal

Capture the flows real users actually perform (client actions + API calls),
mine them into canonical flows, compare against the existing test estate
(Playwright / PHPUnit API / Jest), and have an AI produce the missing
golden-flow tests — ranked by real usage, validated by a round-trip gate
before any generated test is trusted.

## Ground truth (corrected in round 2)

- **Matomo Cloud — not self-hosted — is already wired into every page**
  (`restartproject.matomo.cloud`, AWS Frankfurt, InnoCraft DPA). Google Tag
  Manager's noscript iframe is also present. The original "no third-party
  SaaS" framing was therefore already breached by the status quo; the
  operative non-goal is **no NEW SaaS dependencies** (✔ continued Matomo
  Cloud use accepted; migrating to self-hosted Matomo remains a separately
  costed option that would not change this design's shape).
- The site is not an SPA: 221 blade views, Vue 2.7 islands, no vue-router;
  whole flows are blade-only (user/profile ×14, registration, password
  reset). The main JS bundle IS loaded on those pages (verified), so one
  client capture layer covers both worlds. The 4 iframe/stat embeds don't
  load it — out of scope.
- The consent flag (`analyticsCookieEnabled`) is read once at page parse and
  **does not currently gate Matomo** (only Sentry's beforeSend consumes it);
  nothing reacts to the banner's `gdprCookiesEnabled` event. Fixed in
  phase 1 via Matomo's native consent API.
- `api_token` travels in query strings → server-side logging strips query
  strings everywhere.
- Laravel's method override (`_method=PATCH` in POSTed forms) is applied
  before any middleware runs, so wire method ≠ effective method for exactly
  the save calls that matter (verified: Kernel.php `enableHttpMethodParameterOverride`,
  and the Vuex store's edit action posts `_method: PATCH`).

## Architecture

```
Browser (all pages)
  flow-delegator module (small; init at DOMContentLoaded, NOT inside the
  jQuery/Leaflet polling gate)
    - document capture-phase listeners: click, submit, change
    - TAGGED events: nearest [data-flow="area.step"] →
        _paq.push(['trackEvent', 'flow', '<area.step>', <page-id>])
    - DISCOVERY events (✔ always-on): untagged interactive elements →
        sanitized elements_chain descriptor (strips data-v-* hashes,
        __BVID__*/__BV_* generated ids, noise classes; supplementary
        el.__vue__ walk skipping library internals) →
        _paq.push(['trackEvent', 'discovery', '<descriptor>', <page-id>])
      Volume note: discovery events count as Matomo Cloud hits — phase 1
      checks plan headroom and includes a sampling knob (default 100%).
    - form fields: identity + emptiness only, never values; password fields
      suppressed entirely. No mouse paths, scroll, or keystrokes.
    - session_id: sessionStorage, mirrored into a SameSite=Lax cookie AND
      pushed to Matomo as a custom dimension — the join key across streams.
      (Native blade form POSTs and Dropzone uploads correlate via the cookie.)
    - page identity: <meta name="page"> added to layouts (does not exist yet
      — phase 1 deliverable; URL prefixes are NOT a usable discriminator:
      /group/create, /group/edit/{id}, /group/view/{id} share a prefix but
      are different flows)
    - consent: Matomo native requireConsent()/setConsentGiven()/
      forgetConsentGiven(), wired to the existing gdprCookiesEnabled event —
      withdrawal stops tracking within the page view. This REPLACES the
      currently-broken arrangement where Matomo fires regardless of consent.
    - transport: Matomo tracker (alwaysUseSendBeacon). There is NO bespoke
      ingestion endpoint — /api/session-events and all its hardening are
      deleted from the design.
        ▼
Matomo Cloud (existing)                    Laravel RecordApiUsage middleware
  events queryable via                       (web+api): route PATTERN, status,
  Live.getLastVisitsDetails                  duration_ms, BOTH wire method
  (per-visit action sequences,               (getRealMethod) and effective
  paginated, 200 req/min —                   method, user id, session id
  ample at this volume)                      (header, cookie fallback);
        │                                    NDJSON → /var/log/usage/*.log
        │                                    (logrotate 3–7d) → Alloy tail →
        │                                    Loki (restarters-loki Fly app,
        │                                    private 6PN, labels {app,env,
        │                                    source,event_type} only, 90d)
        │                                    Consent tier: runs for everyone
        │                                    (legitimate interest, route-level
        │                                    only ✔); session/user correlation
        │                                    fields ONLY with analytics consent
        └──────────────┬─────────────────────┘
                       ▼
flowmine — PHP artisan commands (✔)
  flowmine:export   pulls BOTH streams (Matomo Live API + Loki HTTP API,
                    ≤30-day windows ✔) into local SQLite, joined on
                    session_id custom dimension + time
  flowmine:mine     sessionize → directly-follows graph → variant ranking,
                    with the noise controls listed below
  flowmine:manifest test-coverage manifest (below)
  flowmine:report   ranked gaps (frequency × missing coverage), k≥5 distinct
                    sessions per exported variant; LLM brief is aggregate-only
  flowmine:lint     data-flow registry CI check (below)
                       ▼
Claude writes missing Playwright / API / Jest tests → human-reviewed PRs
```

## Mining correctness (round-2 fitness findings, all binding)

The paper simulation of the group-create flow showed naive matching fails
(route-set Jaccard ≈ 0.17 vs its own covering test). These rules are part of
the design, not optional tuning:

1. **Ambient-route denylist**: routes fired unconditionally from mounted()/
   created() hooks (audit found e.g. `api/timezones`, `api/v2/groups/names`,
   `api/users/{id}/notifications` on the create page alone) are excluded from
   flow identity. Seeded by a one-off audit of Vue mounted() hooks;
   maintained in a checked-in list.
2. **Dual HTTP method fields**: server events record wire + effective method;
   matching uses effective (aligns with route patterns), wire retained for
   client-side reconciliation.
3. **Flow segmentation rule**: a flow ends at the first state-changing API
   response after a page_view; a navigation that is the direct redirect
   target of that response (e.g. create → /group/edit/{id}) continues the
   same flow, otherwise a new candidate begins. Auth is segmented out as its
   own sub-flow prefix (sessionStorage session ids make login a near-always
   prefix in test-driven sessions but not production ones).
4. **Variant normalization**: optional-field interactions collapse into
   field-group abstractions before variant counting; role-gated UI
   (canApprove/canNetwork etc.) is a labelled dimension, not separate flows.
5. **Known-unmatchable classes**: bare positional selectors
   (`nth-child`-only) and third-party-widget-generated classes are scored as
   unmatchable in the manifest, not silently counted against similarity.
   The Google Places autocomplete step is a documented capture blind spot
   (Google-injected DOM + custom events) — the gap report must not claim
   coverage judgement over it.

## Test-coverage manifest

- **PHPUnit**: a listener records route patterns per test. Simple stack
  inspection is NOT sufficient — 58 call sites across 23 files share the
  identical `TestCase::createGroup()` helper, including the one real
  create-group test. The disambiguation rule (first state-changing call in
  the test body of a test whose class/method name matches the route's domain
  noun, plus explicit override annotations where needed) is specified and
  unit-tested against that exact corpus before its output is trusted.
- **Playwright**: extractor reads `[data-flow]` selectors + page.goto
  targets, resolving shared helpers (utils.js) into their page/selector/route
  effects. Helper-derived evidence is weighted as SCAFFOLDING (0.25) unless a
  test title in the spec shares the flow's domain tokens — the worked example
  (docs/usage-instrumentation-worked-example.md) showed flat scoring
  mis-attributes group-create to device.test.js, which uses the same
  createGroup() helper as setup. Precondition (phase 3): audit-and-rewrite
  pass converting `page.evaluate()`-driven interactions (the existing group
  spec's submit is one) to locator-based calls; a lint flags evaluate()
  blocks containing `.click()` as unextractable.
- **Jest**: component-name map.
- Output: `tests/coverage-manifest.json`, regenerated in CI.

## The data-flow convention (✔, with decay protection)

- Phase 1 adds `data-flow="area.step"` to the ~30–40 highest-traffic
  interactive elements; the same attributes become the Playwright selector
  convention (zero `data-flow` exists today anywhere, including the 6 specs —
  the retrofit is a hard precondition of the round-trip gate).
- `tests/flow-registry.json` + `flowmine:lint` in CI: removing/renaming a
  registered attribute fails; malformed values fail; Playwright references
  to unregistered flows fail; new unregistered template attributes warn.
  Scans .vue AND blade templates (plain text scan).

## Round-trip validation gate (phase 3 exit criterion)

Run the 6 existing Playwright spec files (51 test blocks; each test = one
isolated session) against a locally instrumented build with capture on;
flowmine mines ONLY those events against a hand-authored, checked-in
`(spec, test title) → expected flow` ground-truth map. Pass requires:
≥90% of test-driven sessions attributed to the correct flow name, zero
cross-matches between different flows, and the create/edit ambiguity
(group.test.js deliberately straddles it) resolved by the segmentation rule.
Production mining output is not trusted until this gate passes.

## Privacy posture

- Client capture (tagged + discovery): consent-gated via Matomo's native
  consent API, reactive to withdrawal (✔ two-tier model).
- Server stream: route-level operational logging for everyone under
  legitimate interest (no behavioural detail; strictly less than existing
  nginx access logs); session/user correlation fields only with consent.
- No raw IPs in the server stream; numeric user ids only; 64-char
  truncation; descriptor depth caps. Matomo Cloud's own IP handling follows
  the existing DPA (anonymization settings reviewed in phase 1).
- Erasure: Matomo Cloud provides GDPR deletion tooling for its side; the
  server-side raw NDJSON window is short (3–7d, user-id filterable,
  documented script); Loki entries age out ≤90d; flowmine exports are k≥5
  aggregates with no identifiers, and the LLM-facing brief contains nothing
  Sentry-joinable.
- Privacy policy page updated in phase 1.

## Deployment & cost

- New infra: `restarters-loki` only (~$3–4/month, private, version pinned,
  volume explicitly unbacked-up — losing it restarts collection). Alloy on
  prod + restarters-dev; per-PR previews excluded (no volume, no-swap
  suspend contract, synthetic traffic) — FEATURE__USAGE_TELEMETRY forced off.
- Matomo Cloud: phase 1 verifies plan hit-volume headroom for always-on
  discovery events; sampling knob available if needed.
- Volume gate: a week of nginx access-log counts sizes the server stream
  before Loki sizing is committed.
- Local dev: file-only server stream + `flowmine:tail`; Matomo events to a
  dev site id or logged locally via a stub.

## Phasing (each phase its own PR)

1. **Capture**: data-flow attributes + registry + flowmine:lint; the
   flow-delegator module (tagged + discovery via Matomo); Matomo consent fix
   (native API, reactive); `<meta name="page">` in layouts; session-id
   custom dimension + cookie; RecordApiUsage (dual methods, query
   stripping); NDJSON + Alloy + restarters-loki + logrotate; mounted()-hook
   ambient-route audit; Matomo plan headroom check; privacy-policy copy.
2. **flowmine export/mine**: Matomo Live API + Loki export → SQLite; DFG
   mining with denylist/segmentation/normalization; first usage report over
   ≥2 weeks of production data.
3. **Manifest + matching + round-trip gate**: PHPUnit listener (tested
   against the 58-call-site corpus), Playwright spec rewrite + extractor,
   Jest map, matcher, k≥5 floor, the round-trip gate. Gate passes before any
   production gap report is issued.
4. **Golden-flow tests**: Claude consumes the gap brief; top uncovered flows
   land as reviewed tests; the loop documented as repeatable.

## Decisions taken (Edward)

| Decision | Choice |
|---|---|
| Consent model | Two-tier: LI server route logs; consent-gated client capture |
| Client transport | Matomo (existing tracker; native consent API; Live API export) |
| Discovery capture | Always-on via Matomo discovery events (plan-volume checked) |
| Store of record | Loki (server stream) + Matomo (client stream), joined in flowmine's SQLite; 30-day windows |
| Element identity | data-flow primary + registry + CI lint against decay |
| flowmine language | PHP artisan |
| Grafana UI | Not initially |
| Matomo Cloud | Continued use accepted; self-hosting it = separate future decision |

## Non-goals

Session replay / DOM recording; keystroke or mouse-path capture; a new
ingestion endpoint or any NEW SaaS dependency; replacing Sentry; real-time
dashboards; instrumenting iframe stat embeds; capture on per-PR preview
apps.
