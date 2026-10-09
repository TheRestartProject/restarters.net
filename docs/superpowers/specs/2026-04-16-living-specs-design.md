# Living Specifications: Browsable Feature & Persona Documentation

## Problem

Restarters.net has extensive functionality across events, groups, devices, users, and networks, with multiple personas (Host, Admin, Restarter, NetworkCoordinator). There is no single place where a human can understand what the system does, organised by feature area or by persona. Test suites verify behaviour but don't communicate it at a readable level. User stories exist in Jira but aren't connected to the code. CLAUDE.md describes conventions, not capabilities.

## Solution

Embed structured annotations in PHP code that declare what each method enables and for whom. Extract these into a manifest. Use Claude to generate human-readable narrative summaries per feature area. Build a browsable static site with dual navigation (by feature, by persona) and deploy to GitHub Pages.

## Design Decisions

- **Source of truth:** PHP 8 attributes in the code, not external files or Jira
- **Maintenance model:** Claude updates annotations automatically during development
- **Narrative layer:** AI-generated markdown files, committed to the repo, human-editable
- **Browsable output:** VitePress static site on GitHub Pages, built via GitHub Actions
- **Jira integration:** None. This is a standalone documentation system
- **Validation:** CI warnings for drift, with option to tighten to hard-fail later
- **Test linking:** Tests reference user stories via `@story:ClassName::method` annotations; coverage shown on the site

## 1. PHP Attributes

Three attribute classes in `app/Attributes/`:

### Feature

Marks a class as belonging to a feature area.

```php
#[Attribute(Attribute::TARGET_CLASS)]
class Feature
{
    public function __construct(
        public string $name,
        public string $description = '',
    ) {}
}
```

Usage:

```php
#[Feature('Events', description: 'Community repair event management')]
class PartyController extends Controller { ... }
```

### UserStory

Marks a method with what it enables and for which persona.

```php
#[Attribute(Attribute::TARGET_METHOD)]
class UserStory
{
    public function __construct(
        public string $story,
        public string $persona,
        public string $feature = '',
    ) {}
}
```

Usage:

```php
#[UserStory(
    'As a Host, I can create a new repair event for my group',
    persona: 'Host',
    feature: 'Events'
)]
public function create(Request $request) { ... }
```

The `feature` parameter is optional — if omitted, the feature is inherited from the class-level `#[Feature]` attribute.

### NoStory

Explicitly marks a method as intentionally unannotated.

```php
#[Attribute(Attribute::TARGET_METHOD)]
class NoStory
{
    public function __construct(
        public string $reason = '',
    ) {}
}
```

Usage:

```php
#[NoStory(reason: 'Internal middleware hook')]
public function middleware() { ... }
```

## 2. Extraction Pipeline

An artisan command `php artisan specs:extract` that:

1. Scans all PHP files under `app/` using `nikic/php-parser` to find `#[Feature]` and `#[UserStory]` attributes
2. Builds a structured manifest
3. Scans test files under `tests/` and `resources/js/` for `@story:` references and matches them to stories
4. Writes `docs/specs/manifest.json`

### Manifest Format

```json
{
  "generatedAt": "2026-04-16T12:00:00Z",
  "features": {
    "Events": {
      "description": "Community repair event management",
      "sources": ["app/Http/Controllers/PartyController.php"],
      "stories": [
        {
          "story": "As a Host, I can create a new repair event for my group",
          "persona": "Host",
          "method": "PartyController::create",
          "file": "app/Http/Controllers/PartyController.php",
          "tests": [
            {
              "file": "tests/Feature/EventTest.php",
              "test": "test_host_can_create_event"
            },
            {
              "file": "tests/playwright/events.spec.ts",
              "test": "Host can create event"
            }
          ]
        }
      ],
      "storyCount": 16,
      "personas": ["Host", "Admin", "Restarter"]
    }
  },
  "personas": {
    "Host": {
      "features": ["Events", "Groups"],
      "storyCount": 24
    },
    "Admin": {
      "features": ["Events", "Groups", "Users", "Networks"],
      "storyCount": 31
    }
  },
  "coverage": {
    "annotatedMethods": 87,
    "noStoryMethods": 12,
    "unannotatedMethods": 23
  }
}
```

The manifest is deterministic (same code always produces the same output) and committed to the repo. The site build only needs Node.js, not PHP.

### Static parsing, not reflection

The command uses `nikic/php-parser` for static analysis rather than PHP reflection. This means it does not need to boot the Laravel application, load the database, or resolve dependencies. It is fast and safe to run in CI.

## 3. AI-Generated Narratives

Narrative summary files at `docs/specs/narratives/{feature}.md`. These are the "how Events work" layer that makes the documentation readable.

### Format

```markdown
<!-- specs:hash abc123 (16 stories) -->
# Events

Community repair events are the core activity of Restarters. Groups
organise events where volunteers repair broken items brought in by
the public.

## What Hosts can do

Hosts manage the full lifecycle of events for their groups -- creating
events with a date, time and location, editing details, inviting
volunteers, and recording the devices brought for repair.

## What Admins can do

Admins have oversight across all events -- they can moderate, delete,
or reassign events across any group.

## What Restarters can do

Restarters can browse upcoming events, RSVP to attend, and log the
devices they've repaired.
```

### Maintenance rules

- Claude generates these during development, committed alongside code changes
- Organised by feature, with persona subsections
- Human-written prose is preserved -- Claude adds/removes persona sections and updates story counts but does not rewrite paragraphs a human has edited
- The `specs:hash` comment tracks the story count for staleness detection

## 4. Static Site

A VitePress site in `specs-site/` that consumes `manifest.json` and narrative markdown files.

### Navigation

Two switchable views:

**Feature view** (default):

```
Events
  Overview (narrative)
  Host stories (8)
  Admin stories (3)
  Restarter stories (5)
Groups
  Overview (narrative)
  Host stories (12)
  NetworkCoordinator stories (6)
```

**Persona view** (toggle):

```
Host
  Events (8 stories)
  Groups (12 stories)
  Devices (4 stories)
Admin
  Events (3 stories)
  Groups (5 stories)
  Users (7 stories)
```

### Story display

Each user story entry shows:
- The story text
- The method it's attached to (e.g., `PartyController::create`)
- A link to the source file on GitHub
- Test coverage indicator: **Covered** (at least one test), **Multi-layer** (PHPUnit + Playwright), or **Uncovered**

Feature and persona overview pages show aggregate coverage (e.g., "Events: 14/16 stories covered (87%)").

### Build process

A prebuild script reads `manifest.json` and narratives, generates VitePress-compatible markdown pages for each feature and persona view, then VitePress builds the HTML.

### File structure

```
specs-site/
  .vitepress/
    config.ts
    theme/
  index.md
  features/
  personas/
  package.json
```

## 5. Deployment

### Quick preview (development / demos)

For showing the site to someone quickly during development:

```bash
cd specs-site
npm run build
npx surge dist/ my-restarters-specs.surge.sh
```

Surge gives a public URL instantly with no setup. Use this for feature branch previews and demos.

### Production (GitHub Pages)

A workflow at `.github/workflows/specs-site.yml`:

1. Triggers on push to `develop`
2. Checks out the repo
3. Installs Node.js dependencies for `specs-site/`
4. Runs the prebuild script (generates pages from manifest + narratives)
5. Builds VitePress
6. Deploys to GitHub Pages

No PHP needed in CI -- the manifest is already committed.

## 6. CI Validation

A separate CI step (can be in the same workflow or the existing test workflow):

### Manifest drift detection

Runs `php artisan specs:extract` and compares output against committed `manifest.json`. Fails if they differ.

### Orphan detection (warnings)

- Public methods on `#[Feature]` classes that have neither `#[UserStory]` nor `#[NoStory]` -- warns "PartyController::update has no story"
- Narrative files referencing personas or story counts that don't match the manifest

### Narrative staleness (warnings)

Compares the `specs:hash` comment in each narrative file against the current manifest. If story count has changed, warns "Events narrative may be stale (was 16 stories, now 18)."

### PR comment

When annotations change, the action posts a PR comment: "This PR modifies 3 user stories in Events. [View changes →]"

### Test coverage warnings

Stories with no `@story:` reference in any test file generate a warning: "UserStory PartyController::create has no test coverage." Not a hard failure -- some stories may be tested indirectly.

### Future tightening

The orphan detection can be promoted from warning to hard-fail for controller classes specifically. This is a configuration flag in the extraction command, not a code change.

## 7. Test Coverage Linking

Tests reference user stories via a `@story:` annotation pointing to the annotated method.

### PHPUnit

```php
/**
 * @story PartyController::create
 */
public function test_host_can_create_event(): void
{
    // ...
}
```

### Playwright

```js
test('Host can create event @story:PartyController::create', async ({ page }) => {
    // ...
});
```

### Jest

```js
test('Host can create event @story:PartyController::create', () => {
    // ...
});
```

### How it works

The `specs:extract` command scans test files for `@story:ClassName::method` patterns and matches them to user stories in the manifest. Each story gains a `tests` array listing the test file and test name.

The browsable site shows coverage at every level:
- Per story: covered / uncovered / multi-layer indicator
- Per feature: "Events: 14/16 stories covered (87%)"
- Per persona: "Host: 20/24 stories covered (83%)"

## 8. CLAUDE.md Integration

Add to the project CLAUDE.md:

```markdown
## Living Specifications

When modifying PHP controller or service methods:
- Maintain `#[UserStory]` and `#[Feature]` attributes (in `app/Attributes/`)
- Add `#[UserStory]` to new public methods that represent user-facing functionality
- Add `#[NoStory]` to methods that intentionally have no user story
- Update the story text if you change what a method does
- When adding or modifying tests, include `@story:ClassName::method` references
- Run `php artisan specs:extract` after annotation changes and commit the updated manifest
- Update the narrative in `docs/specs/narratives/` if feature coverage has changed
- Preserve human-written prose in narratives -- update structure and counts, not wording
```

## 9. File Structure Summary

```
app/
  Attributes/
    Feature.php
    UserStory.php
    NoStory.php
  Console/Commands/
    SpecsExtract.php

docs/
  specs/
    manifest.json
    narratives/
      events.md
      groups.md
      devices.md
      users.md
      networks.md

specs-site/
  .vitepress/
    config.ts
    theme/
  index.md
  features/
  personas/
  package.json

.github/
  workflows/
    specs-site.yml
```

## Personas (known)

From the existing codebase:
- **Admin** -- full platform oversight
- **Host** -- manages events and groups
- **Restarter** -- attends events, logs repairs
- **NetworkCoordinator** -- regional oversight across groups in a network

Additional personas will emerge naturally from the annotations.

## Open Questions

None -- all design decisions have been made. Ready for implementation planning.
