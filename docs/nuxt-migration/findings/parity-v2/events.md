# Visual-parity gaps: events (24)

## 1. [high] /party (mine events landing)
Develop renders the event list as a scrollable data TABLE (b-table, GroupEventScrollTable) with columns for date, title, and per-event icon-labelled stat columns (invited/volunteers, or participants/volunteers/waste/co2/fixed/repairable/dead for past events). Nuxt instead renders a flat card list (EventCard) with a small text-label badge/chip row for the same stats. This is a fundamentally different UI paradigm, the same category of miss the user already flagged for group actions.
- nuxt: client/app/components/events/EventCard.vue:117-196; client/app/components/events/EventsList.vue:43-51
- develop: resources/js/components/GroupEventScrollTable.vue:14-166 (b-table + fields()); resources/js/components/GroupEventsTab.vue:1-14
- FIX: Rebuild EventsList/EventCard as a b-table-equivalent (BTable) with the same column set (date, title+group, invited/volunteers icons for upcoming; participants/volunteers/waste/co2/fixed/repairable/dead icons for past) instead of a card list.

## 2. [high] /party/view/[id] (single event)
Develop consolidates all event actions (Edit, Duplicate, Delete, Request review, Share event stats, Export data, Invite volunteers, RSVP, Follow group) into a single 'EVENT ACTIONS' dropdown button (b-dropdown). Nuxt instead renders every one of these as a separate, individually-visible button laid out inline in the header — the exact same anti-pattern already flagged for the group-view page's actions.
- nuxt: client/app/pages/party/view/[id].vue:372-453
- develop: resources/js/components/EventActions.vue:3-59 (single b-dropdown, text='EVENT ACTIONS'); resources/js/components/EventHeading.vue:35 (EventActions usage)
- FIX: Replace the row of individual buttons in the event header with a single 'Event actions' dropdown containing Edit/Duplicate/Delete/Request review/Share stats/Export/Invite/RSVP/Follow-group as menu items, matching EventActions.vue's item set and conditions.

## 3. [high] /party/view/[id] (single event)
'Share event stats' (opens a modal that generates/shows a shareable image/summary of the event's impact stats) exists in develop as both a dropdown action and a share button on the CO2 impact card, but is completely absent from the Nuxt event page — no share feature exists anywhere in the client.
- nuxt: client/app/pages/party/view/[id].vue:255-536 (no share control anywhere)
- develop: resources/js/components/EventActions.vue:21-23,44-46 (share-stats dropdown item); resources/js/components/StatsImpact.vue:32-38,104-106 (share button -> StatsShareModal)
- FIX: Add a 'Share event stats' action (dropdown item + CO2-card share button) that opens a share modal, matching StatsShareModal's role.

## 4. [high] /party/view/[id] (single event)
Develop lays the event out in a 2-column CSS grid on desktop: EventDetails+EventDescription in the left column, EventAttendance in the right column, side by side. Nuxt renders everything as a single full-width vertical stack (description, then attendance, then stats, then impact, then devices) with no side-by-side column layout anywhere on the page.
- nuxt: client/app/pages/party/view/[id].vue:456-531 (all sections full width, sequential)
- develop: resources/js/components/EventPage.vue:6-13,185-195 (.ep-layout 2-col grid at md+)
- FIX: Introduce a 2-column grid (details/description left, attendance right) at md+ breakpoints, matching EventPage.vue's .ep-layout.

## 5. [high] /party/view/[id] (single event)
Develop shows an 'Event photos' gallery section (collapsible, count badge) rendering every uploaded event image. Nuxt's view page has no photo gallery/display at all — event-photo upload exists on the edit page but uploaded photos are never shown on the view page.
- nuxt: client/app/pages/party/view/[id].vue:255-536 (no EventImages equivalent; contrast with the upload-only client/app/pages/party/edit/[id].vue:116)
- develop: resources/js/components/EventPage.vue:15 (EventImages usage); resources/js/components/EventImages.vue:2-11 (CollapsibleSection + EventImage grid)
- FIX: Add an event-photos gallery section to the view page rendering the event's uploaded images, matching EventImages.vue.

## 6. [medium] /party (mine events landing)
Legacy page shows a page-level h1 'Events' (generic) plus a decorative events-doodle SVG next to it; the actual 'Your events' heading only appears further down as a second, inner section heading. Nuxt collapses this into a single h1 'Your events' and drops the doodle SVG entirely.
- nuxt: client/app/pages/party/index.vue:172-198
- develop: resources/views/events/index.blade.php:27-40 (h1 + events-doodle include); resources/js/components/GroupEvents.vue:6-16,166-171 (translatedTitle)
- FIX: Add the page-level 'Events' h1 with the events-doodle decoration, and keep a distinct 'Your events' sub-heading (with count badge) for the upcoming/past section, matching the two-heading structure.

## 7. [medium] /party (mine events landing)
Each events block (mine, and other-events) is wrapped in develop's CollapsibleSection, which on mobile starts collapsed (heading + count badge + expand/collapse icon, content hidden until tapped) and always shows the count as a pill badge next to the heading. Nuxt's page has no collapse behaviour at all on any breakpoint and no heading-level count badge (the tab labels carry counts, but there's no section-level badge).
- nuxt: client/app/pages/party/index.vue:220-262
- develop: resources/js/components/GroupEvents.vue:4,38 (CollapsibleSection collapsed count-badge); resources/js/components/CollapsibleSection.vue:1-40
- FIX: Wrap each events section in a collapsible container matching CollapsibleSection's mobile collapsed-by-default / count-badge behaviour.

## 8. [medium] /party/view/[id] (single event)
Develop's EventHeading wraps the whole header in a 5px black border-top and a thin border-bottom, with a vertical divider (border-right) separating the date/title block from the group/actions block on desktop. Nuxt's header (a plain flex row) has none of this border/box treatment.
- nuxt: client/app/pages/party/view/[id].vue:300-370 (header, no border classes)
- develop: resources/js/components/EventHeading.vue:7-9,108-121 (.border-top-very-thick, .border-bottom-thin, .bord border-right)
- FIX: Add the 5px top border, thin bottom border, and the vertical divider between the date/title and group/actions blocks to the event header.

## 9. [medium] /party/view/[id] (single event)
Develop's EventDetails renders a bordered, icon-led row list (date icon, time icon, discourse-thread-link icon [if attending], per-event named hosts icon+list, external-link icon, location/map-marker icon), each row separated by a top border. Nuxt renders the same information as plain unadorned text lines with no icons and no row borders.
- nuxt: client/app/pages/party/view/[id].vue:300-369
- develop: resources/js/components/EventDetails.vue:22-79 (icon+border-top-thin rows for date/time/discourse/hosts/link/location)
- FIX: Add icons and bordered-row treatment to the event detail fields (date/time/link/location) matching EventDetails.vue's layout.

## 10. [medium] /party/view/[id] (single event)
Develop lists the individually-named hosts assigned to the event (icon + each host's name) as a distinct field in EventDetails, separate from the organising group. Nuxt only shows the organising GROUP (avatar+name); there is no per-event named-hosts list anywhere on the page.
- nuxt: client/app/pages/party/view/[id].vue:309-317 (group only, no hosts list)
- develop: resources/js/components/EventDetails.vue:41-48 (hosts icon + v-for host.volunteer.name)
- FIX: Add a named-hosts list (icon + host names) to the event details, sourced from the attendee list filtered to host role.

## 11. [medium] /party/view/[id] (single event)
Develop shows a 'Talk thread' link (with icon) to the event's Discourse discussion thread for attendees. Nuxt has no Discourse-thread link anywhere on the event page.
- nuxt: client/app/pages/party/view/[id].vue:255-536 (no discourse link)
- develop: resources/js/components/EventDetails.vue:33-39 (talk_ico.svg + discourseThread link, shown when isAttending)
- FIX: Add a Discourse talk-thread link for attendees when the event has an associated thread.

## 12. [medium] /party/view/[id] (single event) — description
Develop truncates the event description to 440 characters behind a 'Read more/Read less' toggle (ReadMore component) inside a headed CollapsibleSection. Nuxt dumps the full HTML description with no truncation, no read-more control, and no section heading.
- nuxt: client/app/pages/party/view/[id].vue:457-463
- develop: resources/js/components/EventDescription.vue:2,7 (CollapsibleSection hide-title + read-more :max-chars=440)
- FIX: Truncate long descriptions to ~440 characters with a Read more/Read less toggle, matching ReadMore's behaviour.

## 13. [medium] /party/view/[id] (single event) — attendance
Develop lets hosts edit participant/volunteer headcounts inline (EventAttendanceCount, editable number field posting to /party/update-quantity and /party/update-volunteerquantity) whenever canedit is true. Nuxt's EventAttendees.vue only ever displays these as read-only numbers — there is no way to edit headcounts from the event page at all.
- nuxt: client/app/components/events/EventAttendees.vue:123-132 (static numbers only)
- develop: resources/js/components/EventAttendance.vue:14-26 (EventAttendanceCount canedit, changeParticipants/changeVolunteers)
- FIX: Make the participant/volunteer counts inline-editable for canedit viewers, wired to the equivalent update-quantity/update-volunteerquantity endpoints.

## 14. [medium] /party/view/[id] (single event) — attendance
Develop's 'Confirmed' tab has an 'Add volunteer' link+modal (EventAddVolunteerModal) letting a host manually add a named participant who isn't a registered user. Nuxt's confirmed panel only supports removing attendees; there is no way to add one.
- nuxt: client/app/components/events/EventAttendees.vue:159-223 (no add-volunteer control)
- develop: resources/js/components/EventAttendance.vue:41-48 (add_volunteer_modal_heading button + EventAddVolunteerModal)
- FIX: Add an 'Add volunteer' button+modal to the confirmed-attendees panel for canedit viewers on past/in-progress events.

## 15. [medium] /party/view/[id] (single event) — environmental impact
Develop lays the 'items fixed' stats and the 'environmental impact' stats side by side in a 2-column grid (border divider between them) on desktop, both rendered as icon-led StatsValue cards; the CO2 card additionally shows a car-miles-equivalent description, an info popover explaining the calculation, and a 'Share this' button. Nuxt renders 'items fixed' as plain unstyled numbers and 'environmental impact' as separate neo-brutalist stat cards, stacked one below the other (not side by side), with no equivalent-description text, no info popover, and no share button.
- nuxt: client/app/pages/party/view/[id].vue:482-522
- develop: resources/js/components/EventStats.vue:3,5,34-40 (2-col grid); resources/js/components/StatsImpact.vue:3-8,28-38 (info popover, equivalent_consumer description, share button)
- FIX: Put the fixed-items and environmental-impact sections side by side in a 2-column grid on desktop, add the CO2-equivalent description text and info popover, and give the fixed-items stats the same icon-card treatment as the impact cards.

## 16. [low] /party (mine events landing)
Develop's calendar button is a filled btn-primary square containing only an icon image, positioned inside the 'Your events' CollapsibleSection title (next to the count badge). Nuxt's calendar button is an unfilled btn-link with an inline SVG, positioned in the page's own h1 row (a different heading level/section than develop's).
- nuxt: client/app/pages/party/index.vue:174-189
- develop: resources/js/components/GroupEvents.vue:6-16 (b-btn variant=primary with b-img-lazy subs_cal_ico.svg)
- FIX: Style the calendar button as a filled primary icon button and move it next to the 'Your events' section heading rather than the outer page h1.

## 17. [low] /party (mine events landing)
Develop highlights rows for events you're attending with a full-row grey background (and a black date cell), via GroupEventScrollTable's rowClass()/`.attending` CSS. Nuxt only shows a green 'Attending' badge on the card, no row/background highlight.
- nuxt: client/app/components/events/EventCard.vue:129-136
- develop: resources/js/components/GroupEventScrollTable.vue:378-382,436-450 (rowClass, .attending SCSS)
- FIX: Add a background highlight (or equivalent) for attended events in addition to (or instead of) the badge, to preserve the at-a-glance row highlighting.

## 18. [low] /party (mine events landing) — past events, zero devices
When a finished event has zero devices recorded, develop's waste column shows explanatory text 'No devices added' plus a direct 'Add a device' link to the event, instead of a stat number. Nuxt just tints the fixed/repairable/dead badges red with no explanatory text or link.
- nuxt: client/app/components/events/EventCard.vue:68-98 (pastStats danger flag only, no CTA)
- develop: resources/js/components/GroupEventScrollTable.vue:107-115 (noDevices/'partials.no_devices_added'/'partials.add_a_device' link)
- FIX: When a finished event has no devices recorded, show the 'No devices added / Add a device' text+link instead of (or alongside) the red stat badges.

## 19. [low] /party (mine events landing) — 'All' other-events tab
Develop's filter bar for the 'all' tab uses a b-form-datepicker pair and a vue-multiselect country dropdown in a responsive grid. Nuxt uses plain native <input type=date> and <select> controls (already called out as a deliberate build choice in the Nuxt code comment, but it is still a visible widget-class difference from develop).
- nuxt: client/app/components/events/EventFilters.vue:54-118
- develop: resources/js/components/GroupEventsScrollTableFilters.vue:1-30,84-118
- FIX: If exact parity is desired, swap the native date/select inputs for a date-picker and multiselect-style country control matching develop's widgets.

## 20. [low] /party (mine events landing)
Develop's AlertBanner is rendered inside the GroupEvents Vue component, appearing after the page h1/Add-event row and after the moderation queue. Nuxt's AlertsBanner is hoisted to the very top of the page, before the h1/Add-event row and before the moderation queue — a different page-order for the same element.
- nuxt: client/app/pages/party/index.vue:168-200
- develop: resources/views/events/index.blade.php:27-55 (h1 row then EventsRequiringModeration before .vue GroupEvents); resources/js/components/GroupEvents.vue:3 (AlertBanner is first thing inside GroupEvents, i.e. after moderation queue)
- FIX: Move AlertsBanner to render after the moderation queue (matching develop's order) rather than at the very top of the page.

## 21. [low] /party/view/[id] (single event) — attendance
Develop shows the participants/volunteers headcount block for any non-upcoming event (in-progress OR finished), laid out in a CSS grid to the LEFT of the confirmed/invited tabs (side by side). Nuxt only shows it once the event is `finished` (narrower condition, in-progress excluded) and stacks it ABOVE the tabs rather than beside them.
- nuxt: client/app/components/events/EventAttendees.vue:123-132; client/app/pages/party/view/[id].vue:475-476 (finished-only)
- develop: resources/js/components/EventAttendance.vue:9-27,170-201 (.attendance grid, !upcoming condition)
- FIX: Show headcounts for in-progress events too, and lay them out beside the tabs in a grid rather than stacked above.

## 22. [low] /party/view/[id] (single event) — attendee rows
Develop's attendee rows have a solid black bottom border, a 40px profile photo with a 1px black border, an uppercase colored text label for 'Host' (not a badge), a clickable skill-count with a hover popover listing skill names, and a red trash-icon delete button opening a proper ConfirmModal. Nuxt's rows use a light grey Bootstrap border, an unbordered avatar, a BBadge pill for 'Host', a plain skill-count with a title-attribute tooltip, and an outline-danger text 'Remove' button with an inline confirm/cancel row instead of a modal.
- nuxt: client/app/components/events/EventAttendees.vue:163-222
- develop: resources/js/components/EventAttendee.vue:3-45,159,179 (blackbord, .host text, star popover, delete_ico_red.svg, ConfirmModal)
- FIX: Match the attendee-row visual treatment: black divider, bordered avatar, plain uppercase 'Host' text instead of a badge, and a red icon delete button with a real confirm modal.

## 23. [low] /party/view/[id] (single event) — devices
Develop's devices section heading includes a TV icon and is wrapped in a collapsible section with a count badge (collapsed by default on mobile, with a separate mobile-only double-collapsible powered/unpowered layout). Nuxt's devices heading is a plain h2 with a parenthetical count, no icon, and is never collapsible.
- nuxt: client/app/components/devices/EventDevicesPanel.vue:69-73
- develop: resources/js/components/EventDevices.vue:2,5 (CollapsibleSection + tv.svg icon)
- FIX: Add the TV icon to the devices heading and wrap the section in the same collapsible container used elsewhere on the page.

## 24. [low] /party/view/[id] (single event) — calendar modal
Nuxt's 'copy calendar link' modal shows only a generic static title ('My events') with no description text and no 'Find out more' help link. Develop's equivalent CalendarAddModal shows a dynamic, group-aware title+description, an external 'Find out more' Restarters Talk help link, and an inline 'copied to clipboard' confirmation alert.
- nuxt: client/app/pages/party/index.vue:315-351
- develop: resources/js/components/CalendarAddModal.vue:9-11,29-35 (title/description slots, find-out-more link, copied alert); resources/js/components/GroupEvents.vue:166-179 (translatedCalendarTitle/Description)
- FIX: Add the description text, the 'Find out more' external help link, and an inline copy-confirmation message to the calendar modal.
