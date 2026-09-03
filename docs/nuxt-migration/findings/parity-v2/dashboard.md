# Visual-parity gaps: dashboard (10)

## 1. [high] /dashboard
[text] The 'Newly added: N group in your area!' highlight link renders with EMPTY text — the pluralised i18n message fails to resolve at runtime even though the vue-i18n call shape (t(key, {count}, count)) is correct and passes in isolation (tests/i18n-plurals.spec.js). The en.json string has spaces around the plural pipe ('...area! | Newly...') where the Laravel source used a bare '|' with no leading/trailing space before 'Newly'; the build-time message compiler used by the live Nuxt app (unplugin-vue-i18n, distinct from the raw runtime createI18n() used by the passing unit test) appears to mis-parse it, producing an empty string.
- nuxt: client/i18n/locales/en.json:517 (consumed by client/app/components/dashboard/DashboardYourGroups.vue:84)
- develop: lang/en/dashboard.php:40 ('newly_added' => 'Newly added: :count group in your area!|Newly added: :count groups in your area!')
- FIX: Fix the exported plural string so it survives the production message compiler (verify with a real Nuxt build/e2e check, not just the raw-runtime vitest test) — e.g. normalize translations:export-client's pipe-joining to not introduce the extra space, or otherwise confirm the compiled form vue-i18n's SFC/message compiler produces for this string. Add a component/e2e regression test that renders DashboardYourGroups through the actual Nuxt i18n module (not a bare createI18n instance) so this class of bug is caught.

## 2. [high] /dashboard
[layout] The 'Newly added' highlight banner is confined to the left ('Your Groups') column instead of spanning full width above both the Groups and Events columns. Root cause: develop renders Groups+Events as ONE CollapsibleSection card with a single full-width title bar (the banner lives in that title bar); Nuxt splits Groups and Events into two separate components each with their own <h2> title, so the banner (inside DashboardYourGroups's own title slot) only spans the left half.
- nuxt: client/app/pages/dashboard.vue:94-102 (DashboardYourGroups and DashboardUpcomingEvents rendered as two side-by-side components) + client/app/components/dashboard/DashboardYourGroups.vue:70-87 (banner inside this component's own title slot, width = left grid column only)
- develop: resources/js/components/DashboardYourGroups.vue:1-16 (banner inside the single CollapsibleSection's full-width title slot) + lines 26-84 (dyg-layout two-column content sits INSIDE that same card, under the one title bar)
- FIX: Merge DashboardYourGroups + DashboardUpcomingEvents content into a single CollapsibleSection panel (one outer title bar 'Your Groups' + doodle + newly-added banner spanning full card width), with an internal two-column grid for Groups (left) / Upcoming events (right), matching develop's dyg-layout structure.

## 3. [high] /dashboard
[missing-element] The dashboard's 'Upcoming events' section has no 'Add event' button for hosts. Develop shows a primary 'Add' button next to the Upcoming events heading, visible only when the user is a Host on at least one of their groups, linking to /party/create. The Nuxt component has no such button and no host-role check at all; the translation key (dashboard.add_event) exists in the exported locale file but is completely unused.
- nuxt: client/app/components/dashboard/DashboardUpcomingEvents.vue (whole file — no button, no amAHost-equivalent check); unused key at client/i18n/locales/en.json:685 ('add_event': 'Add event' — note this exported text also differs from develop's literal 'Add')
- develop: resources/js/components/DashboardYourGroups.vue:63-68 (<b-btn variant="primary" href="/party/create" v-if="amAHost">{{ __('dashboard.add_event') }}</b-btn>) — lang/en/dashboard.php:36 ('add_event' => 'Add')
- FIX: Add a primary 'Add' button next to the 'Upcoming events' heading, shown only when the current user has a Host role on at least one of their dashboard groups, linking to /party/create. Also correct the exported string to 'Add' (not 'Add event') to match develop.

## 4. [medium] /dashboard
[missing-element] The left ('Groups') column is missing its 'Groups' sub-heading (an <h3> shown above the group list, distinct from the panel-level 'Your Groups' title) — and the translation key itself (dashboard.groups_heading) was never exported to the client, so it isn't even available to use.
- nuxt: client/app/components/dashboard/DashboardYourGroups.vue:94-97 (only renders the 'catch_up' <p>, no <h3>); key absent from client/i18n/locales/en.json
- develop: resources/js/components/DashboardYourGroups.vue:27-30 (<h3>{{ __('dashboard.groups_heading') }}</h3>) — lang/en/dashboard.php:22 ('groups_heading' => 'Groups')
- FIX: Export 'dashboard.groups_heading' via translations:export-client and add an <h3>{{ t('dashboard.groups_heading') }}</h3> above the catch_up paragraph in DashboardYourGroups.vue's has-groups branch.

## 5. [medium] /dashboard
[missing-element] The 'See all events' link disappears entirely when the user has groups but no upcoming events; develop always shows this link once the user has groups, regardless of whether there are any upcoming events (only the intro paragraph text changes between 'Your groups' upcoming events:' and 'no upcoming events for your groups').
- nuxt: client/app/components/dashboard/DashboardUpcomingEvents.vue:45-51 (empty branch has no see-all link) vs 92-96 (see-all link only exists inside the has-events branch)
- develop: resources/js/components/DashboardYourGroups.vue:56-62 (v-if/v-else only toggles the subtitle text) and 75-81 (event-seeall block is unconditional, always rendered whenever the outer v-else "has groups" branch is active)
- FIX: Move the 'See all events' link out of the v-if(events.length)/v-else split so it always renders alongside the empty-state message too, matching develop.

## 6. [medium] /dashboard
[missing-element/styling] Role badges (Restarter/Host/Administrator/etc.) are shown next to each group in the 'Your Groups' list; develop's equivalent (DashboardGroup.vue) never shows a role badge there at all — it only shows a 'Join Group' button (not applicable here since these are groups the user is already in) and the archived-group badge.
- nuxt: client/app/components/dashboard/DashboardYourGroups.vue:7-24 (ROLE_LABELS/ROLE_VARIANTS maps) and 117-124 (role badge rendered in the list item)
- develop: resources/js/components/DashboardGroup.vue:1-20 (renders only name, GroupArchivedBadge, and a conditional 'Join Group' button — no role badge)
- FIX: Remove the per-group role badge from the your-groups list to match develop; role isn't surfaced in this view.

## 7. [medium] /dashboard
[styling] The 'Add Data' panel is a plain white-background div (global .panel class) instead of develop's distinctly tinted card, and it isn't wrapped in a CollapsibleSection so it loses the mobile collapse ('+'/'−') affordance every other dashboard panel has.
- nuxt: client/app/components/dashboard/DashboardAddData.vue:67-87 (plain <div class="panel">, no CollapsibleSection wrapper)
- develop: resources/js/components/DashboardAddData.vue (wrapped in <CollapsibleSection>; outer <div class="bg"> with .bg { background-color: $brand-light; box-shadow: 5px 5px $black; border: 1px solid $black; })
- FIX: Wrap DashboardAddData's content in <CollapsibleSection> (as the other panels do) and give the outer container the $brand-light tinted background (client/app/assets/css/_variables.scss already defines $brand-light: #4aaebc — currently only .panel__blue and .panel__orange variants exist; add an equivalent light/teal variant or a local override) so it visually matches develop's teal-tinted, collapsible Add Data card instead of a plain white one.

## 8. [medium] /dashboard
[missing-element] The upcoming-events list omits the 'Online' pill badge shown for online events, and omits the event's end time (develop shows start-end on desktop); it also adds an 'Attending' badge and an inline group-name link in the details row that develop's dashboard event card doesn't show (group there is represented only by the avatar image).
- nuxt: client/app/components/dashboard/DashboardUpcomingEvents.vue:64-80 (no online badge, no end time, plus an extra Attending badge and group-name NuxtLink not present in develop)
- develop: resources/js/components/EventTitle.vue:1-4 (online pill badge) + resources/js/components/DashboardEvent.vue:12-17 ({{ date }} {{ start }} <span class="d-none d-md-inline">- {{ end }}</span>, no attending badge, no group-name text)
- FIX: Add the 'Online' badge (events.online) next to the event title for online events and show the event end time on desktop ('- HH:MM'), matching develop. Reconcile the added Attending badge / group-name link with design — either remove to match develop exactly or confirm as an intentional enhancement.

## 9. [low] /dashboard
[layout] The 'What's happening' Talk topics table (with its comment-count/clock header icons) disappears completely when there are zero topics; develop always renders the table with its header row, leaving only the body empty.
- nuxt: client/app/components/dashboard/DashboardWhatsHappening.vue:53 (<table v-if="topics.length" ...> gates the ENTIRE table, including the header)
- develop: resources/js/components/DiscourseDiscussion.vue (b-table-simple and b-thead have no v-if; only <b-tbody v-if="topics"> gates rows, and topics defaults to [] which is truthy, so the header/table chrome always renders)
- FIX: Remove the v-if="topics.length" guard from the outer <table> (keep only an empty <tbody> when there are no topics) so the header icons/table chrome always show, matching develop's empty-state look.

## 10. [low] /dashboard
[styling] The archived-group badge is missing its tooltip (develop shows a title attribute with the archive date via GroupArchivedBadge's 'archived_group_title' text); the translation key was never exported to the client either.
- nuxt: client/app/components/dashboard/DashboardYourGroups.vue:125-132 (BBadge has no :title)
- develop: resources/js/components/GroupArchivedBadge.vue:1-3 (:title="title" where title = __('groups.archived_group_title', {date: archived_at}))
- FIX: Export 'groups.archived_group_title' and add a :title tooltip with the archive date to the archived badge, matching develop.
