# Worked example: mined events → duplicate-avoidance → generated Playwright test

Companion to `usage-instrumentation-design.md`. This is an executed dry run of
the flowmine matching pipeline, not a thought experiment: a prototype matcher
was run against simulated capture data, with the manifest side extracted from
the **real** spec files in `tests/Integration/`. Two flows were mined — one the
suite already covers (group create), one it does not (account registration) —
to demonstrate both halves of the claim: *recognise existing coverage to avoid
duplication* and *generate a new test for a real gap*.

Honesty statement: the input events are hand-derived from the actual UI code
(GroupAddEdit.vue and children, auth/register-new.blade.php, the Vuex store's
API calls, the mounted()-hook ambient audit) because capture is not built yet —
that is exactly what the phase-3 round-trip gate replaces with real recorded
events. The manifest extraction and matching, however, ran against the real
spec files.

## Input

4 sessions, 55 events (`events.jsonl`): two users creating a group (one via
/group → create button, one landing directly on /group/create; different
optional-field orders), and two users registering an account (one filling all
optional fields + newsletter, one minimal). Server-side events include the
ambient mount-time calls (`GET api/timezones`, `GET api/v2/groups/names`,
`GET api/users/{id}/notifications`) precisely so the denylist has something
to prove.

## What the first run got wrong — and why that matters

The naive first run produced **exactly the failure the fitness review
predicted**:

1. Group-create matched to `device.test.js` (score 0.75), not
   `group.test.js`. Cause: `device.test.js` (and `event.test.js`) call the
   same `createGroup()` helper as *scaffolding*, so their manifests contain
   the full group-create footprint. Flat overlap scoring cannot tell a spec
   that tests a flow from a spec that merely performs it on the way to
   something else — the Playwright twin of the PHPUnit 58-call-site
   scaffolding problem.
2. The two group-create sessions fragmented into two variants (different
   entry pages), as did the two registrations (optional-field differences).

Fixes applied to the matcher (and now binding in the design):

- **Scaffolding weighting with a subject rule**: helper-derived manifest
  evidence counts at 0.25 weight unless a test title in the spec shares the
  flow's domain tokens ("Can create group" ↔ flow pages `group.*`), in which
  case the helper is the subject and counts fully.
- **Variant key = (flow page, action-route set)** after the ambient denylist —
  optional-field presence/order becomes an annotation on exemplars, not a new
  variant.

## Corrected run output (verbatim)

```
Sessions: 4  →  flow variants after normalization: 2

── Variant 1: entry page 'group.index', 2 session(s): ['s-A1', 's-A2']
   action routes: ['POST api/v2/groups']
   VERDICT: MATCHED → group.test.js  (score 0.74 ≥ 0.6)
   evidence: {"subject_match": true, "page_overlap": 0.67, "route_overlap": 1.0,
              "selector_overlap": 0.41, "shared_routes": ["POST api/v2/groups"],
              "titles": ["Can create group", "Can unfollow group",
                         "Group image upload persists on view page"]}
   ACTION: no test generated — duplicate avoided.

── Variant 2: entry page 'user.register', 2 session(s): ['s-B1', 's-B2']
   action routes: ['POST register/check-valid-email', 'POST user/register']
   VERDICT: UNCOVERED (best candidate grouptags.test.js scored 0.06 < 0.6)
   ACTION: generate Playwright spec from steps: [16 steps listed]
```

Registration is genuinely uncovered: no spec under tests/Integration touches
`/user/register`, `POST user/register`, or any `register*` selector.

## Generated test (from variant 2's steps + verification against the view)

The generation step is not blind templating: the mined steps give the skeleton
(pages, fields touched, API calls to await, terminal redirect), and the
generating LLM then verifies selectors against the actual view code. That
verification mattered here: the mined click descriptor for the final submit
(`button[type=submit]`) resolves in `register-new.blade.php` to
`#register-form-submit`, and the view revealed a **step-4 GDPR consent
checkbox (`#consent_gdpr`)** that the simulated sessions never recorded
(consent-checkbox interactions are plausible fast-path omissions in real data
too). Generated spec, flagged for human review:

```js
// tests/Integration/registration.test.js
// GENERATED from mined flow 'user.register'
// (2/2 observed sessions: multi-step wizard → POST user/register → /dashboard)
// Verified against resources/views/auth/register-new.blade.php before proposal.
const { test } = require('./fixtures')
const { expect } = require('@playwright/test')
const faker = require('faker')

test('Can register a new account', async ({page, baseURL}) => {
  test.slow()

  await page.goto(baseURL + '/user/register')

  // Step 1: skills are optional (1 of 2 observed sessions skipped them).
  await page.locator('#step-1 button.btn-next').click()

  // Step 2: identity + password. The email field triggers an async
  // validity check observed in both sessions — wait for it so the wizard
  // doesn't advance on a stale state.
  await page.fill('#registerName', faker.name.findName())
  const [emailCheck] = await Promise.all([
    page.waitForResponse(resp => resp.url().includes('/register/check-valid-email')),
    page.fill('#registeremail', faker.internet.email().toLowerCase()),
  ])
  expect(emailCheck.status()).toBe(200)
  await page.selectOption('#age', { index: 1 })
  await page.selectOption('#country', 'GB')
  await page.fill('#password', 'passw0rd!Reg')
  await page.fill('#password-confirm', 'passw0rd!Reg')
  await page.locator('#step-2 button.btn-next').click()

  // Step 3: preferences (both observed sessions proceeded without changes
  // being required; newsletter was ticked in 1 of 2).
  await page.locator('#step-3 button.btn-next').click()

  // Step 4: GDPR consent — NOT present in the mined events (see note above);
  // required by the form, added from the view code.
  await page.check('#consent_gdpr')

  const [registerResponse] = await Promise.all([
    page.waitForResponse(resp => resp.url().includes('/user/register')
      && resp.request().method() === 'POST'),
    page.locator('#register-form-submit').click(),
  ])
  expect([200, 302]).toContain(registerResponse.status())

  // Both observed sessions ended on the dashboard.
  await page.waitForSelector('section.dashboard')
})
```

Status: **proposed, not committed to tests/Integration** — per the design,
generated tests land only via a reviewed PR after running against a live dev
environment (and this one needs step-transition waits validated against the
wizard's JS).

## What this demonstrates, and what it does not

Demonstrated end-to-end: sessionization → ambient filtering → variant
normalization → manifest extraction from real specs → duplicate avoidance
with evidence → gap detection → generation grounded in both the mined steps
and the actual view code — including the pipeline catching its own
mis-attribution defect and the fix generalising (scaffolding weighting).

Not demonstrated (this is what phase 3's round-trip gate exists for): real
captured events (these were hand-derived), matching at production variant
diversity, the PHPUnit/Jest manifest sides, and thresholds tuned on more than
two flows. The prototype matcher (~150 lines of Python) is the specification
for the flowmine:match implementation, not the implementation itself.
