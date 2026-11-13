# Visual-parity gaps: networks (11)

## 1. [high] /networks/{id}
Legacy renders a single 'Network Actions' b-dropdown (View groups / Add groups / Export event list) top-right of the header; Nuxt replaces it with two separate always-visible controls (an 'Export event list' outline button and an 'Add groups' primary button) and drops the 'View groups' item entirely. This is the exact anti-pattern already flagged on the group-view page (dropdown replaced by individual buttons).
- nuxt: client/app/pages/networks/[id].vue:217-227
- develop: resources/js/components/NetworkPage.vue:13-19 (+ lang/en/networks.php:12 'actions' => 'Network Actions')
- FIX: Replace the two loose buttons with a single BDropdown (variant=primary, right-aligned) labelled t('networks.general.actions'), containing dropdown-items for Add groups (opens AssociateGroupsModal) and Export event list (external link); decide whether to restore the 'View groups' item (legacy's /group/network/{id} target currently has no matching web route on develop, so confirm before porting verbatim).

## 2. [high] /networks/{id}
Legacy's moderation queues render the full rich GroupsTable (logo, name+archived badge+tags, location, host/restarter counts, next-event, a 'Group requires moderation' link) and a full GroupEventScrollTable (date, title+group, invited/volunteer counts, actions) for events. Nuxt's ModerationQueue renders a bare <ul> of plain NuxtLink names with no other columns, images, or badges at all.
- nuxt: client/app/components/moderation/ModerationQueue.vue:86-102
- develop: resources/js/components/GroupsRequiringModeration.vue:1-7 (GroupsTable approve) + resources/js/components/EventsRequiringModeration.vue:1-14 (GroupEventScrollTable)
- FIX: Reuse the existing GroupsTable component (with a trailing 'requires moderation' column/link) for the groups queue, and an events table with date/title/group/invited/volunteers/actions columns for the events queue, instead of the current plain link list.

## 3. [high] /networks/{id}
Legacy's 'Groups' section is just a heading + a bordered info box with a count sentence and a 'View groups' link out to a separate page - it never lists groups inline. Nuxt embeds a full GroupsTable with a tag-filter <select> directly on the network page, plus a filter control with no legacy equivalent on this page at all.
- nuxt: client/app/pages/networks/[id].vue:288-314
- develop: resources/js/components/NetworkPage.vue:79-86
- FIX: Match legacy: replace the inline GroupsTable + tag-filter select with the summary info box ('{count} groups in {name}. View groups') linking to a groups-by-network destination, or explicitly sign off on keeping the richer inline listing as an intentional improvement rather than an unreviewed divergence.

## 4. [high] /networks/{id}
Legacy's tag list is bordered 'card' rows (name + count on one line, tag description shown inline below in muted text, pencil/trash icon buttons) plus an always-visible inline create form (name+description inputs, Create button) below the list. Nuxt's AdminCrudTable renders a plain <table> (Name, Groups count columns only - description is never shown anywhere in the list), makes the tag NAME itself the edit trigger (no pencil icon), uses a text 'Delete' button instead of a trash icon, and moves tag creation into a modal behind a top 'Create' button, plus renders its own <h1> (a second h1 on a page that already has one for the network name).
- nuxt: client/app/components/admin/AdminCrudTable.vue:353-363,401-420 (client/app/pages/networks/[id].vue:316-331)
- develop: resources/js/components/NetworkPage.vue:90-140
- FIX: Show each tag's description inline in the list (currently dropped entirely), use icon edit/delete buttons instead of a clickable name + text button, and replace the modal 'Create' flow with an always-visible inline create form under the list, matching NetworkPage.vue; also change AdminCrudTable's title element to h2 for this usage so the page keeps a single h1.

## 5. [high] /networks/{id}
Legacy's Impact section is one unified 4-tile grid (Groups, Events, Waste diverted, CO2 prevented; 2px solid black border, no shadow, uppercase labels). Nuxt splits this into a 2-tile grid (Groups/Events, 1px border + 4px offset shadow, non-uppercase labels) followed by the Fixometer-wide <ImpactStats> component, which adds unrelated participants/years-volunteered/powered-unpowered tiles AND a 'Latest Repairs' hero banner that - since no latestData prop is passed - always renders a visible 'no repairs yet' placeholder box that has zero legacy equivalent on this page.
- nuxt: client/app/components/networks/NetworkStats.vue:64-79 (client/app/components/fixometer/LatestRepairs.vue:59-61 empty state)
- develop: resources/js/components/NetworkPage.vue:22-43 (.stats-grid/.stat-box, lines 371-394)
- FIX: Build a network-scoped 4-tile stats grid (groups/events/waste/CO2 only) matching NetworkPage.vue's .stats-grid/.stat-box styling exactly, and stop reusing the Fixometer ImpactStats/LatestRepairs components here - they pull in metrics and a 'latest repair' banner with no place in the legacy network page.

## 6. [medium] /networks/{id}
Section order differs: legacy is Header -> Impact -> About -> Coordinators -> Groups-requiring-moderation -> Events-requiring-moderation -> Groups -> Tags. Nuxt moves the moderation queues (and a brand-new logo-upload section) to immediately after the header, ahead of Impact/About/Coordinators, so the 'above the fold' content on first load is completely different between the two.
- nuxt: client/app/pages/networks/[id].vue:234-255
- develop: resources/js/components/NetworkPage.vue:22-77
- FIX: Reorder the page so ModerationQueue (and the logo-management section, if kept) come after Impact/About/Coordinators and before the Groups section, matching NetworkPage.vue's actual flow.

## 7. [medium] /networks/{id}
Legacy's coordinator pill cards have explicit styling (2px solid black border, white background, hover state that changes border-color to brand teal and adds a box-shadow). Nuxt's coordinator cards use only bare Bootstrap utility classes (border rounded-pill) with no scoped CSS at all in the file, so they render with a thin default grey border and no hover effect.
- nuxt: client/app/pages/networks/[id].vue:268-285 (no <style> block in file)
- develop: resources/js/components/NetworkPage.vue:57-62,396-431
- FIX: Add a scoped .coordinator-card style matching NetworkPage.vue's SCSS (2px solid black border, pill radius, hover border-color/box-shadow) instead of relying on plain Bootstrap border utilities.

## 8. [medium] /networks/{id}
Nuxt adds a whole new 'Network logo' section with an inline TusImageUpload control directly on the show page. Legacy has no such section on the network page at all - logo upload is a completely separate page (/networks/{id}/edit, a simple file-input form) reached via a different flow, not shown inline here.
- nuxt: client/app/pages/networks/[id].vue:239-250
- develop: resources/views/networks/edit.blade.php:1-44 (separate page; not part of NetworkPage.vue/show.blade.php)
- FIX: Either drop the inline logo-upload section and reinstate a separate /networks/{id}/edit page matching edit.blade.php, or, if consolidating onto the show page is intentional, confirm that decision explicitly and reposition it to not disrupt the section order noted above.

## 9. [low] /networks/{id}
The 'Add groups' modal's candidate list in Nuxt filters out archived groups (!g.archived_at) client-side. Legacy's server-side Network::groupsNotIn() returns every group not already in the network regardless of archived status, so archived groups are selectable there but not in Nuxt.
- nuxt: client/app/pages/networks/[id].vue:106-109
- develop: app/Network.php:80-89 (groupsNotIn)
- FIX: Drop the !g.archived_at filter from candidateGroups (or explicitly confirm excluding archived groups from the associate-groups picker is an intended behaviour change) to match groupsNotIn()'s full candidate set.

## 10. [low] /networks
Legacy wraps both the 'Your networks' and 'All networks' tables in a .table-responsive div (enabling horizontal scroll instead of overflow/breakage on narrow viewports). Neither Nuxt table has this wrapper.
- nuxt: client/app/pages/networks/index.vue:96-117,127-146
- develop: resources/views/networks/index.blade.php:22-23,69-70
- FIX: Wrap both <table> elements in a div class="table-responsive" to match index.blade.php.

## 11. [low] /networks/{id}
Moderation queue heading/empty-state copy differs from legacy: Nuxt shows 'Groups requiring moderation' / 'Events requiring moderation' / 'Nothing is awaiting moderation.' where legacy shows 'Groups to moderate' / 'Events to moderate' / 'None'. Nuxt also wraps each queue in a coloured 'panel panel__orange' box that legacy doesn't have (legacy is a bare h2 + table, no card/panel styling).
- nuxt: client/app/components/moderation/ModerationQueue.vue:66,86-94
- develop: lang/en/groups.php:161 (groups_title_admin), lang/en/events.php:8 (events_title_admin), lang/en/networks.php:58 ('none' => 'None')
- FIX: Reuse the legacy translation keys ('Groups to moderate' / 'Events to moderate' / 'None') for this usage, and drop the panel__orange card wrapper in favour of a bare heading, to match NetworkPage.vue's plain presentation - or confirm the reworded copy/styling is an intentional change.
