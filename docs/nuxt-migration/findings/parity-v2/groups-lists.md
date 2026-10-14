# Visual-parity gaps: groups-lists (20)

## 1. [high] /group, /group/nearby, /group/all, /group/map (shared tab bar)
Legacy's group tabs are a bordered/box-shadowed 'ourtabs' strip (1px black border + 5px black drop-shadow around the whole tab region, uppercase justified/equal-width tabs, active tab gets a thick 5px top border) built from b-tabs. Nuxt's GroupsTabsNav is a plain minimal underline nav (small gap, thin 2px bottom border, teal active underline) with no box/shadow chrome at all, and it is left-aligned rather than justified/full-width. It also adds a 4th 'Map' tab that has no equivalent in legacy's 3-tab (Mine/Nearby/All) b-tabs.
- nuxt: client/app/components/groups/GroupsTabsNav.vue:27-86
- develop: resources/js/components/GroupsPage.vue:21 (b-tabs class="ourtabs w-100 mt-4" justified); resources/sass/_events.scss:326-368 (.ourtabs box/shadow/border rules)
- FIX: Restyle GroupsTabsNav to reproduce the .ourtabs look (bordered box, 5px drop-shadow, justified/equal-width tabs, uppercase bold labels, thick top border on the active tab, no bottom border on active) instead of the current plain underline nav.

## 2. [high] /group (mine), /group/nearby, /group/all
Legacy renders the 'Groups requiring moderation' panel (GroupsRequiringModeration, a full GroupsTable of pending groups) above the tabs on the single /group page for Administrators/NetworkCoordinators, so it is visible regardless of which tab (mine/nearby/all) is selected. In Nuxt, ModerationQueue is only wired into /group/map.vue - it is completely absent from /group, /group/nearby and /group/all.
- nuxt: client/app/pages/group/index.vue (no ModerationQueue import/usage); client/app/pages/group/nearby.vue (none); client/app/pages/group/all.vue (none) - contrast client/app/pages/group/map.vue:9,114
- develop: resources/views/group/index.blade.php:37-55 (GroupsRequiringModeration rendered once, above the shared b-tabs, for all three tabs)
- FIX: Render <ModerationQueue type="groups" /> (role-gated to Administrator/NetworkCoordinator) above the h1/tabs on /group, /group/nearby and /group/all as well, not only /group/map.

## 3. [high] /group (mine), /group/all, /group/map
Legacy's GroupsTable always includes a leading group-photo column (bordered square image with a default-profile fallback). Nuxt's GroupsTable has no image/avatar column at all - it starts straight at Name.
- nuxt: client/app/components/groups/GroupsTable.vue:148-197
- develop: resources/js/components/GroupsTable.vue:184 (fields: 'group_image'); :40-46 (head/cell(group_image) template, b-img-lazy + defaultProfile fallback)
- FIX: Add a leading photo/avatar column to GroupsTable (bordered image, falling back to the default profile placeholder) matching legacy's group_image column.

## 4. [high] /group (mine), /group/all, /group/map
Legacy's table column headers are icon images with no text at all (group name icon, map-marker icon, user icon for hosts, volunteer icon for restarters, events icon for next event; Group Image and Follow headers are blank). Nuxt renders plain text labels ('Location', 'Hosts', 'Restarters', 'Next event') plus a literal unicode sort glyph (↕/▲/▼) appended inside each header button - a completely different, non-iconic look.
- nuxt: client/app/components/groups/GroupsTable.vue:151-196 (text + sortIndicator()) and :136-142
- develop: resources/js/components/GroupsTable.vue:47-49,62-63,72-73,75-76,78-79 (head(group_name)/head(location)/head(all_confirmed_hosts_count)/head(all_confirmed_restarters_count)/head(next_event) icon-only templates)
- FIX: Replace the text column headers with the legacy icon assets (group_name_ico.svg, map_marker_ico.svg, user_ico.svg, volunteer_ico-thick.svg, events_ico.svg) and rely on the table's native sortable-column caret rather than embedding a unicode ↕/▲/▼ character in the label.

## 5. [high] /group/nearby
Legacy's 'Other groups nearby' tab uses the exact same GroupsTable component as Mine/All (photo, name, location, hosts, restarters, next-event, follow columns, sortable). Nuxt's /group/nearby instead renders a list of GroupCard rows (circular avatar, name, location+distance text, join button only) - a structurally different, much less detailed layout with no hosts/restarters/next-event data and no sortable headers.
- nuxt: client/app/pages/group/nearby.vue:76-83; client/app/components/groups/GroupCard.vue:1-53
- develop: resources/js/components/GroupsPage.vue:40-57 (nearby b-tab renders <GroupsTable :groups="nearbyGroups" .../>)
- FIX: Replace the GroupCard list on /group/nearby with GroupsTable (the same component used on /group and /group/all) so the photo/location/hosts/restarters/next-event columns and sorting appear there too, matching legacy.

## 6. [high] /group/all
Legacy's All Groups tab has a rich, always-visible-on-desktop filter bar (name, tags multiselect, location, country multiselect, network multiselect) via GroupsTableFilters. Nuxt's /group/all only offers a single name-only search input directly on the page; GroupsTable's showFilters prop (which would render the ported GroupsTableFilters with location/country/tags) is never passed true from this page (it's only used on /networks/[id].vue).
- nuxt: client/app/pages/group/all.vue:135-156 (only a name search + archived checkbox); client/app/components/groups/GroupsTable.vue:147 (`v-if="showFilters"`, never true here)
- develop: resources/js/components/GroupsTable.vue:4-17 (GroupsTableFilters always shown ≥md, search prop true from GroupsPage.vue:71-72); resources/js/components/GroupsTableFilters.vue:1-64 (name/tags/location/country/network fields)
- FIX: Pass show-filters="true" on the All Groups GroupsTable (or otherwise surface the location/country/tags filters) so name/location/country/tags filtering is available, matching legacy's All Groups filter bar.

## 7. [high] /group/all
Nuxt paginates the All Groups list with Previous/Next buttons and a 'Page X of Y' indicator, 20 rows per page. Legacy has no pagination UI at all - GroupsTable progressively auto-loads more rows (`show += 10` on a timer loop) until the entire filtered list is rendered, with no page-number controls the user has to click through.
- nuxt: client/app/pages/group/all.vue:88-94,186-206
- develop: resources/js/components/GroupsTable.vue:265-267 (`itemsToShow` slices to `show`), :278-280 & :265-267's `loadMore()` implementation (progressive auto-load, no pager)
- FIX: Remove the Previous/Next/Page-of-page pager; instead render the full filtered list (optionally progressively, matching legacy's auto-load-more) with no manual pagination controls.

## 8. [high] /group (mine)
Nuxt's Mine page (via optional-columns all set to false) suppresses the Location, Hosts, Restarters and Next Event columns entirely, showing only Name + Join/Leave. Legacy's Mine tab uses the identical always-all-columns GroupsTable as every other tab, so location/hosts/restarters/next-event are shown there too.
- nuxt: client/app/pages/group/index.vue:22-30,75
- develop: resources/js/components/GroupsPage.vue:29-35 (`<GroupsTable :groups="yourGroups" .../>`, no column-suppression prop exists); resources/js/components/GroupsTable.vue:183-190 (static fields array, always all columns)
- FIX: Once the /api/v2 'mine' groups source carries location/hosts/restarters/next-event, restore all standard GroupsTable columns on /group; this is currently an acknowledged API gap in the code comments but remains a visible structural gap versus legacy.

## 9. [high] /group, /group/nearby, /group/all, /group/map (GroupJoinButton, used everywhere Join/Leave appears)
Clicking 'Leave'/'Unfollow' in Nuxt immediately calls groupsStore.leave() with no confirmation. Legacy opens a ConfirmModal ('Please confirm that you want to unfollow this group.') and only unfollows once the user confirms.
- nuxt: client/app/components/groups/GroupJoinButton.vue:32-46
- develop: resources/js/components/GroupsTable.vue:106 (`@click="leaveGroup"` opens ConfirmModal), :113 (ConfirmModal with `leave_group_confirm` message); lang/en/groups.php 'leave_group_confirm' => 'Please confirm that you want to unfollow this group.'
- FIX: Show a confirmation dialog ('groups.leave_group_confirm') before calling groupsStore.leave(), matching legacy's ConfirmModal gate on the Leave/Unfollow action.

## 10. [medium] /group/map (and wherever moderation is added per above)
Nuxt's ModerationQueue renders pending groups as a bare bullet list of name links under a heading. Legacy's GroupsRequiringModeration renders the same full GroupsTable used elsewhere (photo, location, hosts, restarters, next event columns) with the 'follow' cell replaced by an orange/warning cell containing a 'Group requires moderation' link to /group/edit/{id}.
- nuxt: client/app/components/moderation/ModerationQueue.vue:86-102
- develop: resources/js/components/GroupsRequiringModeration.vue:1-7 (renders <GroupsTable approve>); resources/js/components/GroupsTable.vue:94-97 (cell-warning + 'group_requires_moderation' link to /group/edit/{id})
- FIX: Render the groups-moderation queue as a GroupsTable with a warning-styled cell linking to /group/edit/{id} and the 'groups.group_requires_moderation' label, instead of a plain link list.

## 11. [medium] /group/all (Admin/NetworkCoordinator view)
Legacy shows group tag badges beneath the group name for Administrators/NetworkCoordinators (showTags). Nuxt's GroupsTable never renders tags in any row.
- nuxt: client/app/components/groups/GroupsTable.vue:213-235 (name cell has no tags rendering)
- develop: resources/js/components/GroupsTable.vue:53-60 (tag badges under the name, gated on showTags/visibleTags)
- FIX: Render group tag badges under the name for Admin/NC users once the v2 summary endpoint carries tags (tracked as a backend gap, but currently a visible parity gap on /group/all).

## 12. [medium] /group/nearby
When nearby groups exist, legacy shows a heading line above the table: 'These are the groups that are within 50 km of {location} (change)', where '(change)' links to /profile/edit. Nuxt's populated nearby view has no equivalent heading/link at all - the location and change-link only ever appear inside the (mutually exclusive) empty-state message.
- nuxt: client/app/pages/group/nearby.vue:71-84 (no heading text in the populated branch)
- develop: resources/js/components/GroupsPage.vue:45-49 (`{{ nearestGroups }} <a href="/profile/edit">{{ __('groups.nearest_groups_change') }}</a>.`)
- FIX: Add the 'These are the groups that are within 50 km of {location} (change)' line above the list when groups exist, using t('groups.nearest_groups', {location}) plus a t('groups.nearest_groups_change') link to /profile/edit.

## 13. [medium] /group/all (and /networks/[id] wherever GroupsTableFilters is used)
Legacy only hides the filter bar behind a 'show/hide filters' toggle on mobile (<md); at md and above the filters are always visible with no toggle at all. Nuxt's GroupsTableFilters is collapsed-by-default behind a toggle at every breakpoint, including desktop.
- nuxt: client/app/components/groups/GroupsTableFilters.vue:13,26-34 (expanded ref defaults false, no breakpoint distinction)
- develop: resources/js/components/GroupsTable.vue:4-17 (`d-none d-md-block` - filters always rendered, no toggle) vs :18-36 (`d-block d-md-none` show/hide toggle, mobile only)
- FIX: Show the filter fields unconditionally at md+ widths; restrict the show/hide toggle button to below md, matching legacy's breakpoint split.

## 14. [medium] /group/all
Nuxt adds an 'Include archived groups' checkbox that hides archived groups from the All Groups list by default (unticked). Legacy has no such toggle anywhere - archived groups are always listed in the All Groups tab, just marked with the GroupArchivedBadge; there is no code path that filters them out.
- nuxt: client/app/pages/group/all.vue:38,45,144-155
- develop: resources/js/components/GroupsTable.vue (no archived-exclusion filter anywhere in filteredGroups/items); resources/js/components/GroupArchivedBadge.vue:1-10 (badges only, never hides)
- FIX: Default archived groups to visible (as legacy does) rather than hidden-until-ticked; keep only the archived badge to mark them, or default the checkbox to checked.

## 15. [medium] /group/all
The shared 'groups.group_count' translation string was edited (on this branch) to append 'Zoom out to see more' - text only sensible for a map view. Because /group/all's count paragraph reuses the same key, it now nonsensically tells users to 'zoom out' on a page that has no map, whereas legacy's plain count text is just 'There are X groups.'
- nuxt: client/app/pages/group/all.vue:179-182 (`t('groups.group_count', ...)`); lang/en/groups.php:88 (`'group_count' => '... Zoom out to see more.|... Zoom out to see more.'`)
- develop: origin/develop lang/en/groups.php:116 (`'group_count' => 'There is <b>:count group</b>.|There are <b>:count groups</b>.'`, no 'zoom out' phrase)
- FIX: Split the count copy into two keys - a plain 'groups.group_count' for the list count (matching legacy) and a separate map-specific key with the 'zoom out to see more' phrasing used only by /group/map.

## 16. [low] /group/all
Nuxt adds a 'Columns' fieldset with checkboxes to toggle Location/Hosts/Restarters/Next event column visibility. Legacy has no column-visibility control anywhere - all columns are always shown; the only related trace in legacy is an unused `$user_preferences = session('column_preferences')` variable that is computed but never rendered in the Blade template.
- nuxt: client/app/pages/group/all.vue:158-177
- develop: resources/views/group/index.blade.php:76 (dead `$user_preferences` variable, never used in markup); resources/js/components/GroupsTable.vue:183-190 (static, always-shown fields array)
- FIX: Remove the column-visibility fieldset (legacy always shows every column) or clearly document it as an intentional new feature if it is being kept.

## 17. [low] /group (mine)
Nuxt shows a role badge (Host/Volunteer/etc.) per row on the Mine page via GroupsTable's show-role prop. Legacy's GroupsTable has no concept of a per-row role badge on any tab - fields are limited to photo/name/location/hosts/restarters/next-event/follow.
- nuxt: client/app/components/groups/GroupsTable.vue:218-225; client/app/pages/group/index.vue:74
- develop: resources/js/components/GroupsTable.vue:183-190 (fields array has no role/status column)
- FIX: Drop the role badge from the Mine table (or confirm it is a deliberate enhancement) since legacy shows no per-row role indicator on any groups list.

## 18. [low] /group, /group/nearby, /group/all, /group/map (Join/Leave buttons)
Legacy's Join/Leave buttons show a shorter label on mobile ('Follow'/'Unfollow' below md) and the full label at md+ ('Follow group'/'Unfollow group'). Nuxt's GroupJoinButton always renders only the full-length label regardless of viewport.
- nuxt: client/app/components/groups/GroupJoinButton.vue:50-58
- develop: resources/js/components/GroupsTable.vue:98-105 (join button, `d-block d-md-none` mobile span vs `d-none d-md-block` desktop span), :106-113 (same pattern for leave)
- FIX: Add the shorter mobile label variants ('groups.join_group_button_mobile' / 'groups.leave_group_button_mobile') below md, matching legacy's responsive text swap.

## 19. [low] /group, /group/nearby, /group/all, /group/map (page header CTA)
Legacy's 'Add a new group' button shows a shorter label on small/medium screens ('Add new', below lg) and the full text at lg+. Nuxt's create-group button always renders only the full 'groups.create_groups' text at every breakpoint.
- nuxt: client/app/pages/group/index.vue:48-50 (same pattern in all.vue:114-116, nearby.vue:46-48, map.vue:109-111)
- develop: resources/js/components/GroupsPage.vue:11-18 (`d-block d-lg-none` -> 'create_groups_mobile2'; `d-none d-lg-block` -> 'create_groups')
- FIX: Show t('groups.create_groups_mobile2') ('Add new') below lg and the full t('groups.create_groups') text at lg+, on all four group-listing pages.

## 20. [low] /group, /group/nearby, /group/all, /group/map (page header doodle icon)
Legacy always shows the group doodle icon next to the 'Groups' title, at 76px tall, on every screen size. Nuxt hides it below md (`d-none d-md-inline-block`) and shrinks it to 40px tall.
- nuxt: client/app/pages/group/index.vue:46 (and all.vue:112, nearby.vue:44, map.vue:107)
- develop: resources/js/components/GroupsPage.vue:8 (`<b-img class="height ml-4" .../>`, always rendered); :216-218 (`.height { height: 76px; }`)
- FIX: Remove `d-none d-md-inline-block` so the doodle icon shows at all breakpoints, and size it at 76px instead of 40px to match legacy.
