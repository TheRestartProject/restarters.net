# Frontend Architecture

How the frontend is structured, and patterns for Vue components.

---

## Stack

- **Vue 2** — interactive components
- **Vite** — build tool (production) and dev server (HMR)
- **Bootstrap 4** — layout and base styles
- **SCSS** — styles in `resources/sass/`
- Build output in `public/build/`

---

## Blade + Vue integration

The app migrates gradually from server-rendered Blade to Vue. The pattern:

1. Blade renders a container `<div class="vue" id="my-component">` with data as JSON props
2. The Vue component is registered in `resources/js/app.js`
3. Vue mounts to that container on page load
4. A loading placeholder (spinner or skeleton) is shown until Vue hydrates

This means the page renders immediately with server-side content where possible, and Vue enhances it.

---

## Component guidelines

**When to extract a sub-component:** early and often. Refactoring a large component into sub-components later is significantly harder than doing it upfront. Components inside a `v-for` should always be their own component.

**Props vs Vuex store:**
- Pass data through props to direct children and grandchildren
- Use the Vuex store for data that arbitrary components might need to access
- Store objects indexed by ID (not arrays) — `{ [id]: item }` — for O(1) access
- Don't use custom Map implementations; plain indexed objects work fine

**Mixins:** use for shared JavaScript logic across components. Extending components is rare and discouraged.

---

## Performance for large datasets

When rendering large lists, two techniques help:

**`vue-infinite-loading`** — loads items progressively as the user scrolls. Data is driven by a counter-based computed property that slices the full array.

**Background timers** — for expensive processing (e.g. filtering/sorting a large array), run the computation off the main thread using `setTimeout(..., 0)` to avoid blocking the UI.

Note: Bootstrap's `b-table` component requires all data in memory even when displaying a subset, because filtering operates on the full dataset. Plan for this when working with large tables.

---

## API calls

The v2 REST API is documented at `/apiv2/documentation` (Swagger UI, auto-generated from annotations).

Read-only endpoints are currently open without authentication. Endpoints that modify data require an API token, passed as:
- Query param: `?api_token=...`
- Request body field: `api_token`
- Header: `Authorization: Bearer ...`

Tokens are per-user and stored in the `users` table (`api_token` column). Users can request access via the project contact.
