# Visual-parity gaps: group-view (16)

## 1. [high] /group/view/{id}
Develop consolidates all group-header actions (Edit, Add event, Invite volunteers, Share group stats, Export group data, Join/Leave, Delete, Archive) into a single 'GROUP ACTIONS' b-dropdown rendered once (desktop, right column) and once (mobile, top row). Nuxt instead renders separate always-visible buttons (Join/Leave, Edit, Invite, Archive) in a flat flex row, with no dropdown container at all.
- nuxt: client/app/pages/group/view/[id].vue:174-214
- develop: resources/js/components/GroupHeading.vue:3-8,22-30 + resources/js/components/GroupActions.vue:4-51
- FIX: Create a GroupActions.vue dropdown component (BDropdown, variant=primary, text='Group actions') and replace the individual-button flex row with it, rendered once in the desktop header position (and optionally duplicated above the h1 for mobile, matching GroupHeading.vue:3-8) so all actions live in one menu instead of loose buttons.

## 2. [high] /group/view/{id}
Three dropdown actions present in develop are entirely absent from Nuxt: 'Add event' (links to /party/create/{id}), 'Share group stats' (opens a share-stats modal), and 'Export group data' (CSV export link /export/devices/group/{id}). No equivalent controls, links or modals exist anywhere on the Nuxt page.
- nuxt: client/app/pages/group/view/[id].vue:174-214
- develop: resources/js/components/GroupActions.vue:8-9,14-19
- FIX: Add 'Add event' (NuxtLink to /party/create/{id}), 'Export group data' (plain link to /export/devices/group/{id}), and a 'Share group stats' action that opens a share-stats modal, as items inside the new GROUP ACTIONS dropdown (canedit-gated per GroupActions.vue's v-if rules).

## 3. [high] /group/view/{id}
Develop's volunteer rows expose Make Host / Remove Host role / Remove Volunteer via a single pencil edit-icon dropdown (edit_ico_green.svg, b-dropdown no-caret) at the right of each row. Nuxt instead renders these as separate inline outline buttons (Remove Host role / Make Host / Remove Volunteer) shown directly in the row.
- nuxt: client/app/components/groups/GroupVolunteers.vue:161-184
- develop: resources/js/components/GroupVolunteer.vue:36-44
- FIX: Replace the inline BButton group for canedit rows with a BDropdown using the edit_ico_green.svg pencil icon as its button-content (no-caret), containing 'Remove host role' (if host && candemote), 'Make host' (if !host), and 'Remove volunteer' as dropdown items.

## 4. [high] /group/view/{id}
Develop's About section shows a 'View group conversation' (talk_group) link with a talk icon to the group's Discourse page, when the group has a linked discourse_group. Nuxt's description section has no Discourse link at all.
- nuxt: client/app/pages/group/view/[id].vue:219-249
- develop: resources/js/components/GroupDescription.vue:32-39
- FIX: Add a discourseGroup computed (built from group.discourse_group + Discourse base URL, as Blade's view.blade.php does) and render an icon+link row ('View group conversation' / groups.talk_group) beneath the email row, matching GroupDescription.vue:32-39.

## 5. [high] /group/view/{id}
Develop's Events section is a rich, sortable/filterable data table (GroupEventScrollTable) with columns for date, title, invited/confirmed volunteers (upcoming) or participants/volunteers/waste/co2/fixed/repairable/dead-device counts (past), plus per-row action icons, an 'Add new event' button, an 'Export event list' button, and a calendar-subscribe button/modal. Nuxt's GroupEventsList is a bare two-tab (Upcoming/Past) list showing only event title + date + location, with no add/export/subscribe controls and no per-event stats.
- nuxt: client/app/components/groups/GroupEventsList.vue:1-129
- develop: resources/js/components/GroupEvents.vue:1-60 + resources/js/components/GroupEventScrollTable.vue:1-140
- FIX: Rebuild GroupEventsList as a table (BTable or equivalent) with the develop column set per tab (upcoming: date/title/invited/volunteers/actions; past: date/title/actions/participants/volunteers/waste/co2/fixed/repairable/dead), sortable headers, and add the 'Add new event', 'Export event list', and calendar-subscribe controls in the section title-right area.

## 6. [high] /group/view/{id}
Develop's 'Most repaired devices' section renders a 1st/2nd/3rd place PODIUM (GroupDeviceRepairPodium) with rosette medal icons and different-height boxes per rank. Nuxt renders a plain numbered <ol> list of 'name - count'.
- nuxt: client/app/components/groups/GroupStats.vue:148-157
- develop: resources/js/components/GroupDevicesMostRepaired.vue:1-30 + resources/js/components/GroupDeviceRepairPodium.vue:1-30
- FIX: Replace the <ol> top-devices list with a podium layout: three boxes (2nd, 1st, 3rd order on desktop) each with a rosette_{position}_ico.svg badge, the device count in large teal text, and the device name, with box height varying by rank (152/132/112px), matching GroupDeviceRepairPodium.vue.

## 7. [high] /group/view/{id}
Develop's device-category breakdown ('Computers and Home Office' / 'Electronic Gadgets' / 'Home Entertainment' / 'Kitchen and Household Items') is a TABBED interface on desktop (b-tabs, only one cluster visible at a time) and a collapsible accordion on mobile. Nuxt instead renders all four clusters simultaneously as a grid of bordered/shadowed boxes.
- nuxt: client/app/components/groups/GroupStats.vue:160-201
- develop: resources/js/components/GroupDevicesBreakdown.vue:1-70
- FIX: Replace the cluster-grid with a BTabs (justified) component showing one cluster's stats at a time on desktop, and a collapsible/accordion list (first expanded, rest collapsed) on mobile, per GroupDevicesBreakdown.vue.

## 8. [medium] /group/view/{id}
Within each cluster, develop shows most-seen/most-repaired/least-repaired as icon+count StatsValue widgets (most-seen_ico.svg, most-repaired_ico.svg, least-repaired_ico.svg) laid out in a grid alongside fixed/repairable/dead. Nuxt shows these as three plain muted text lines ('Most seen: X (Y)').
- nuxt: client/app/components/groups/GroupStats.vue:183-196
- develop: resources/js/components/GroupDevicesBreakdownCluster.vue:11-16
- FIX: Render most_seen/most_repaired/least_repaired as icon-topped stat items (using the most-seen_ico/most-repaired_ico/least-repaired_ico SVGs) alongside fixed/repairable/dead in the cluster panel, instead of plain text lines.

## 9. [medium] /group/view/{id}
Develop's Environmental impact heading includes an info icon (info_ico_green.svg) with a hover/click popover explaining the impact calculation, and the CO2 stat card has a 'Share this' link that opens a StatsShareModal for sharing the group's CO2 figure. Nuxt's group-stats-impact section has neither the info popover nor any share control.
- nuxt: client/app/components/groups/GroupStats.vue:105-124
- develop: resources/js/components/StatsImpact.vue:3-7,29-38
- FIX: Add an info-icon popover next to the 'Environmental impact' heading using the groups.impact_calculation copy, and add a 'Share this' link/button on the CO2 card that opens a share-stats modal, matching StatsImpact.vue.

## 10. [medium] /group/view/{id}
Develop's Environmental impact card shows a caveat line ('Not counting: N repairable, M dead, K no-weight devices') built from dead_devices/repairable_devices/no_weight counts when any are present. Nuxt's GroupStats.vue omits this text entirely.
- nuxt: client/app/components/groups/GroupStats.vue:105-124
- develop: resources/js/components/StatsImpact.vue:18-23,63-90
- FIX: Compute and render the 'not counting X/Y/Z devices' caveat text under the waste stat, using groups.not_counting + partials.to_be_recycled/to_be_repaired/partials.no_weight translation strings, as StatsImpact.vue does.

## 11. [medium] /group/view/{id}
Develop's header bar wraps image+name+tags (left, border-right on desktop) and location+website+GROUP ACTIONS (right) in a container with a 5px solid-black top border and 1px black bottom border, with location/website living in the RIGHT column next to the actions dropdown. Nuxt has no top/bottom border bar, no vertical divider between columns, and stacks location+website underneath the group name in the same left-hand block as the image.
- nuxt: client/app/pages/group/view/[id].vue:148-172
- develop: resources/js/components/GroupHeading.vue:9-33,99-120
- FIX: Wrap the header in a container with border-top: 5px solid black / border-bottom: 1px solid black, add a border-right divider between the image+name column and the location/actions column on desktop, and move location + website into the right-hand column alongside the GROUP ACTIONS dropdown instead of stacking them under the name.

## 12. [medium] /group/view/{id}
Develop wraps the About and Volunteers sections in a CollapsibleSection that defaults to collapsed on mobile (title + expand/collapse icon, with a count badge), while always fully expanding on desktop. Nuxt always renders both sections fully expanded regardless of viewport, with no collapse/expand affordance.
- nuxt: client/app/pages/group/view/[id].vue:219-249; client/app/components/groups/GroupVolunteers.vue:78-84
- develop: resources/js/components/GroupDescription.vue:2; resources/js/components/GroupVolunteers.vue:2 (develop)
- FIX: Wrap the About and Volunteers sections in a collapsible component that starts collapsed on mobile viewports (showing only the heading + count + expand icon) and is always expanded on desktop, matching CollapsibleSection.vue's collapsed behaviour.

## 13. [medium] /group/view/{id}
Section order differs: develop shows Header -> Description/Volunteers -> GroupStats(facts+impact only) -> hr -> Events -> Devices-worked-on/Most-repaired (side by side) -> Devices breakdown (clusters). Nuxt shows Header -> Description/Volunteers -> hr -> GroupStats (facts+impact+devices+top-devices+clusters all combined) -> hr -> Events (last).
- nuxt: client/app/pages/group/view/[id].vue:251-260 (GroupStats then GroupEventsList)
- develop: resources/js/components/GroupPage.vue:14-49
- FIX: Reorder the page so Events (with its add/export/subscribe controls) appears immediately after the Group facts/Environmental impact stats and before the device-worked-on/most-repaired/breakdown sections, splitting GroupStats.vue into a facts+impact component (before Events) and separate devices-worked-on/most-repaired/breakdown components (after Events), matching GroupPage.vue's order.

## 14. [low] /group/view/{id}
Develop gates a 'Delete group' (hard delete) action behind Administrator + group.canDelete(), shown disabled if not currently deletable; Nuxt only offers reversible Archive, with no hard-delete option anywhere (a code comment states no hard-delete endpoint currently exists).
- nuxt: client/app/pages/group/view/[id].vue:24-27,192-213
- develop: resources/js/components/GroupActions.vue:26-31
- FIX: Confirm with backend whether a hard-delete endpoint is intended to exist for the Nuxt migration; if so, add a canSeeDelete/canPerformDelete-gated 'Delete group' dropdown item (disabled when not performable) alongside Archive. If the endpoint is deliberately being retired, note that as an intentional, documented divergence rather than an oversight.

## 15. [low] /group/view/{id}
The 'archived group' badge is shown in develop inside the About/description section body (GroupDescription's badges-row, alongside tags), not in the page header. Nuxt shows the archived badge directly under the group name in the header, while tags correctly stay in the header per GroupHeading.vue.
- nuxt: client/app/pages/group/view/[id].vue:152-154
- develop: resources/js/components/GroupDescription.vue:7 (badges-row: GroupArchivedBadge + GroupTagsBadges)
- FIX: Move the archived-group BBadge out of the header block and into the About/description section (badges-row alongside tags), matching GroupDescription.vue; keep tags where they already are in the header per GroupHeading.vue.

## 16. [low] /group/view/{id}
Develop indicates a host volunteer with plain uppercase brand-teal text ('HOST', no background) next to their name. Nuxt renders a filled BBadge pill (variant=primary) instead.
- nuxt: client/app/components/groups/GroupVolunteers.vue:126-131
- develop: resources/js/components/GroupVolunteer.vue:16-18,131-135 (.host class)
- FIX: Replace the BBadge host indicator with plain uppercase text styled in the brand-teal colour (no pill/background), matching GroupVolunteer.vue's .host class.
