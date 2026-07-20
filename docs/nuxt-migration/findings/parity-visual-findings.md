# Visual parity findings: new-dev SPA vs old-dev legacy

From `task parity:capture` + the `parity-diff-new-vs-old` workflow. 16 matched pairs (8 pages x desktop/mobile, logged-out and logged-in).

Claimed 106, **confirmed 105** (each independently re-verified against both screenshots).

Both systems ran locally against the SAME seeded database as the same user, so data differences are genuine defects.

By severity: critical=5, high=12, medium=31, low=36, unspecified=21

By kind: missing-feature=25, styling=27, missing-content=13, layout=31, data=9


## CRITICAL

### 01-landing--loggedout (mobile) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A dark GDPR cookie-consent banner is shown overlaying the bottom of the orange 'Learn and share repair skills' panel, with text 'We use cookies to help our website function, and analytical cookies to improve our website. Please click Cookie Settings to amend cookie settings or click OK to accept all.' plus 'Cookie settings' and orange 'OK' buttons.
- **NEW (SPA):** No cookie consent banner appears anywhere on the page for a logged-out first visit.

### 02-login--loggedout (desktop) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A fixed dark banner spans the bottom of the viewport: 'We use cookies to help our website function, and analytical cookies to improve our website. Please click Cookie Settings to amend cookie settings or click OK to accept all.' with a 'Cookie settings' link and an orange 'OK' button.
- **NEW (SPA):** No cookie consent banner is present anywhere on the page (confirmed on the full-page capture, which instead shows empty whitespace down to the bottom).

### 02-login--loggedout (desktop) — Heading typography (Sign in / Welcome panel)  [styling]

- **OLD (correct):** 'Sign in' and 'Welcome to the Restarters community' headings are set at a moderate size (heading text width for 'Welcome to the Restarters community' is ~392px).
- **NEW (SPA):** Same headings render noticeably larger (the same heading text spans ~539px), giving both panels a visually heavier/bigger title than old.

### 03-register--loggedout (desktop) — Skills step copy  [missing-content]

- **OLD (correct):** Sub-heading reads 'Organising skills - please select at least one if you'd like to host events'.
- **NEW (SPA):** Sub-heading reads only 'Organising skills' — the trailing guidance text ('please select at least one if you'd like to host events') is missing.
- **Likely cause:** Helper/guidance copy for the organising-skills field group was dropped when the skills step markup was ported to the new form.

### 05-fixometer (desktop) — Repair records data table  [missing-feature]

- **OLD (correct):** Below the POWERED/UNPOWERED tabs, a full data table is rendered with sortable column headers: Item, Category, Brand, Assessment, Group, Status, Date (each with a sort-direction icon), ready to display repair rows.
- **NEW (SPA):** No table or column headers are rendered at all. Only plain text "0 items found" / "No items match your search." appears where the table should be.
- **Likely cause:** The Nuxt repair-records results component likely doesn't render a table/column-header structure when the result set is empty, or the table component itself is missing/not wired up (only an empty-state message was implemented).


## HIGH

### 03-register--loggedout (mobile) — Registration flow structure  [layout]

- **OLD (correct):** Multi-step wizard: one step shown per screen with a 'Step 1 of 4' progress indicator in the top-right of the card, and a 'NEXT STEP' button that advances through steps one at a time.
- **NEW (SPA):** Single continuous scrolling page: all steps (skills, personal details, contact preferences, consent) are concatenated into one long form with no step/progress indicator anywhere, ending in a single 'COMPLETE MY PROFILE' submit button.
- **Likely cause:** The Nuxt SPA port collapsed the legacy stepped registration wizard into one long form instead of reproducing the step-by-step UX with per-step navigation and progress counter.

### 03-register--loggedout (desktop) — Registration flow structure  [layout]

- **OLD (correct):** Registration is a paginated wizard: a single bordered panel shows only the current step's content ('Step 1 of 4' indicator, top right) with a 'NEXT STEP' button to advance. Only the skills step is visible in this capture.
- **NEW (SPA):** All registration content is flattened into one continuously-scrolling page containing four stacked bordered panels (Skills, Personal info, Keep-in-touch preferences, Consent) with a single 'COMPLETE MY PROFILE' button at the very end. There is no step indicator and no per-step navigation.
- **Likely cause:** New SPA implements registration as one long form component instead of porting the legacy multi-step wizard (step state machine, progress indicator, per-step validation/navigation).

### 03-register--loggedout (desktop) — Skills step options  [data]

- **OLD (correct):** No checkbox options are visible under the 'Organising skills' or 'Technical skills' headings in this capture — only the section headers with blank space beneath.
- **NEW (SPA):** Full checkbox grids are rendered: Event organising / Volunteer coordination / Marketing / Community outreach under Organising skills; Electronics repair / Soldering / Sewing / Bicycle repair under Technical skills.
- **Likely cause:** Uncertain — may indicate the legacy page's skill options load asynchronously and had not finished rendering when the OLD screenshot was captured (a capture-timing artifact), rather than a genuine app difference. Worth re-capturing OLD with an explicit wait to confirm before treating as a real defect.

### 03-register--loggedout (desktop) — Panel width / centering  [layout]

- **OLD (correct):** Content panel is narrower and more centered, with larger side margins relative to the 1440px viewport (~165px each side).
- **NEW (SPA):** Content panel spans nearly the full viewport width with much smaller side margins (~73px each side).
- **Likely cause:** Container max-width/padding CSS in the new SPA form doesn't match the legacy Bootstrap container sizing.

### 03-register--loggedout (mobile) — Skill selection controls  [styling]

- **OLD (correct):** No selectable skill options (chips/checkboxes) are visible under 'Organising skills' or 'Technical skills' in this step - the card ends immediately after the headings with the Next Step button, suggesting a different (e.g. icon/chip-based) selector rather than plain checkboxes.
- **NEW (SPA):** Skill lists render as plain HTML checkbox rows with labels: Event organising, Volunteer coordination, Marketing, Community outreach (organising); Electronics repair, Soldering, Sewing, Bicycle repair (technical).
- **Likely cause:** Possible mismatch between a chip/icon-tile skill selector component in the legacy app and a plain checkbox-list component in the new build; could also reflect an icon-loading gap in the legacy capture, so worth confirming against the live legacy page directly.

### 04-dashboard (desktop) — Left column layout — Your Groups panel structure  [layout]

- **OLD (correct):** A single "Your Groups" panel contains everything: the no-town-set message, the 'view all groups' link, and the 'start your own group' copy/links, all in one box.
- **NEW (SPA):** Content is split across two separate panels: a top box with two columns "Your Groups" ("You're not following any groups yet.") and "Upcoming events" ("There are currently no upcoming events for your groups."), then a second, distinct "Groups near you" panel below containing the no-town-set message, a stock photo, and the start-a-group copy.
- **Likely cause:** Dashboard was restructured into new panels (Upcoming events, Groups near you) that don't match the legacy single-panel layout or copy.

### 04-dashboard (mobile) — Page section order  [layout]

- **OLD (correct):** Order is: Welcome header -> 'Getting started' (orange panel) -> 'Your Groups' panel -> 'What's happening' section (with 'see all' link) -> footer/language selector.
- **NEW (SPA):** Order is: Welcome header -> 'Your Groups' panel (with an 'Upcoming events' sub-section) -> 'Getting started' (orange panel) -> 'Groups near you' panel (with photo) -> full footer. The whole page flow/section ordering has been rearranged compared to OLD.
- **Likely cause:** Dashboard page component in the Nuxt SPA renders its panel list in a different order/composition than the legacy Blade dashboard view.

### 04-dashboard (mobile) — 'Your Groups' panel content  [missing-content]

- **OLD (correct):** Single 'Your Groups' panel contains: 'You do not currently have a town/city set. You can set one in your profile. You can also view all groups.' plus a 'Want to start your own community repair group?' block with event planning kit / resources for schools / Groups page / Talk links — all in one panel.
- **NEW (SPA):** The 'Your Groups' panel only shows 'You're not following any groups yet.' plus a broken link and an 'Upcoming events' sub-heading saying 'There are currently no upcoming events for your groups.' The town/city + 'start your own group' messaging has been moved into a separate, new 'Groups near you' panel further down the page, which also adds a photo not present in OLD.
- **Likely cause:** Dashboard content was split into two components (Your Groups / Groups near you) in the SPA rewrite, changing what appears in each panel versus the legacy single-panel layout.

### 05-fixometer (desktop) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A fixed cookie-consent banner ("We use cookies...", Cookie settings / OK) is visible overlaying the bottom of the viewport.
- **NEW (SPA):** No cookie-consent banner is visible anywhere on the page.
- **Likely cause:** Could be a genuine missing/differently-triggered cookie banner in the SPA, or simply that cookies were already accepted earlier in this browser session before this screenshot was captured (page order in the parity run).

### 06-groups-all (mobile) — Fixed bottom mobile navigation bar  [layout]

> **RETRACTED — this entry is wrong. Do not act on it.**
> develop DOES have the fixed bottom nav. Verified in source:
> `resources/global/css/components/_navigation-bar.scss` puts `.nav-left` at
> `position: fixed; bottom: 0` under `media-breakpoint-down(md)`;
> `resources/views/layouts/navbar.blade.php` renders the same five items
> (Talk / Fixometer / Events / Groups / Wiki); `header.blade.php` loads that
> SCSS and includes that navbar. Both current mobile captures show the bar on
> BOTH sides.
> Acting on this entry would have deleted a real feature — it was nearly
> removed on the strength of it.
> The capture batch behind this entry is unreliable: the very next entry
> (07-events-all) records that its OLD screenshot was a 500 error page, and
> the NEW side came from a dev server with a stale Vite build.
> The overlap it describes is also an artefact — Playwright freezes
> `position: fixed` elements at one spot when stitching a full-page shot.
>
> One REAL gap did come out of re-examining this: develop's `app.js` toggles
> `.nav-left--hidden` on scroll (hide down / show up). We ported the CSS class
> but never apply it, so our bar never hides. That is a MISSING feature, the
> opposite of what this entry claimed.

- ~~**OLD (correct):** No bottom navigation bar is present.~~
- ~~**NEW (SPA):** A fixed bottom bar with 5 icons is shown, overlapping the pagination controls above it.~~
- ~~**Likely cause:** New sitewide mobile nav chrome added in the SPA rebuild.~~

### 07-events-all (mobile) — Page load / whole page  [data]

- **OLD (correct):** The OLD (legacy) screenshot is not the events listing page at all — it shows the legacy app's generic 500 error page ('Unfortunately, an error has occurred.' with the broken-toaster image, a cookie consent banner, and debug details listing User: Jane Bloggs, Error: 500, URL: http://restarters_legacy_nginx/party/all, Previous URL: http://restarters_legacy_nginx/group/all).
- **NEW (SPA):** The NEW (Nuxt SPA) screenshot shows a normal-looking 'All upcoming events' page: header with restarters logo, English language selector, chat/bell/people icon badges (all showing 0), a search box ('Search by title or venue'), body text 'There are currently no other upcoming events.', and a standard footer with logo, bottom icon nav (chat, tools, events - active, people, archive), language link and copyright.

### 08-groups-nearby (desktop) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A fixed bottom cookie-consent banner ("We use cookies to help our website function..." with Cookie Settings / OK buttons) is visible, and the page's visible footer content is not reached.
- **NEW (SPA):** No cookie-consent banner is shown; instead the full site footer (logo, Talk/Wiki/Help & Feedback/FAQs/The Restart Project/Cookie Policy links, language, copyright) is visible.
- **Likely cause:** Likely just differing cookie-acceptance state between the two captured browser sessions rather than a code defect, but flagged in case the NEW SPA's cookie-consent banner is not wired up at all.


## MEDIUM

### 01-landing--loggedout (mobile) — Footer language selector  [missing-feature]

- **OLD (correct):** Below the 'Empower your network' panel there is a footer row with a chat/globe icon and an 'English' language dropdown selector, followed by a decorative striped divider.
- **NEW (SPA):** No language selector or footer divider is present after the 'Empower your network' panel; the page simply ends with blank background.

### 01-landing--loggedout (desktop) — Header impact-stats bar  [missing-feature]

- **OLD (correct):** Header shows a 4-column stats bar next to the logo: '0 Items fixed | 0 kg CO2e emissions prevented | 0 kg Waste prevented | 0 Events held', separated by vertical dividers.
- **NEW (SPA):** Header contains only the 'restarters' logo on the left and 'Sign in' / 'Join Restarters' text links on the right. The entire impact-stats bar (Items fixed / CO2e prevented / Waste prevented / Events held) is absent.
- **Likely cause:** Header component in the Nuxt SPA was not ported with the global impact-stats widget/API call that the legacy header includes.

### 01-landing--loggedout (desktop) — Header layout (contained vs full-bleed)  [layout]

- **OLD (correct):** Header content (logo + stats bar) is boxed/contained to the same centered column width as the body content below it (~732px of the 1440px viewport, roughly centered with equal side margins).
- **NEW (SPA):** Header spans the full viewport width edge-to-edge: logo sits ~73px from the left edge and the nav links sit near the right edge, decoupled from the narrower centered content column (~851px) used by the sections below.
- **Likely cause:** Header markup uses a full-width container/row while the page body uses a separate, narrower max-width wrapper, whereas the legacy Blade layout applies one consistent contained wrapper to both.

### 01-landing--loggedout (desktop) — Top-right auth links  [layout]

- **OLD (correct):** No 'Sign in' / 'Join Restarters' links appear in the header; the only sign-up/log-in CTAs are the boxed 'JOIN US' and 'LOG IN' buttons further down inside the hero section.
- **NEW (SPA):** Header additionally shows plain-text 'Sign in' and 'Join Restarters' links at the top right, duplicating the 'LOG IN' / 'JOIN US' buttons that also still appear in the hero section below.
- **Likely cause:** New Nuxt layout adds a global top-nav auth affordance that doesn't exist in the legacy landing page, creating duplicate CTAs for the same actions.

### 01-landing--loggedout (desktop) — Content column width/centering  [layout]

- **OLD (correct):** Main content column (colour panels, hero text) is ~732px wide out of the 1440px viewport, centered with roughly equal ~350px margins on each side.
- **NEW (SPA):** Main content column is noticeably wider, ~851px out of 1440px, with smaller ~293px side margins, causing panels and hero text to reflow differently (e.g. the intro paragraph wraps after a different word).
- **Likely cause:** Different max-width/padding value used for the content wrapper in the Nuxt page's CSS/layout compared to the legacy Blade template.

### 02-login--loggedout (desktop) — Page content width / margins  [layout]

- **OLD (correct):** Content (logo, panels) is centered in a narrower container with ~185px side margins; the two panels span roughly x=185 to x=1260 of a 1440px-wide viewport.
- **NEW (SPA):** Content uses a wider container with ~73px side margins; the two panels span roughly x=73 to x=1368, making both panels and the whole layout noticeably wider/closer to the viewport edges than old.

### 03-register--loggedout (desktop) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A fixed dark banner at the bottom of the viewport reads 'We use cookies to help our website function...' with 'Cookie Settings' and an 'OK' button.
- **NEW (SPA):** No cookie consent banner is present anywhere on the page.
- **Likely cause:** Cookie-consent component not implemented/mounted in the new SPA, or consent was already recorded for the dev browser profile used to capture the NEW shot (state-dependent — worth confirming with a clean profile).

### 03-register--loggedout (mobile) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A sticky dark cookie-consent banner is pinned at the bottom of the viewport with 'Cookie settings' and 'OK' buttons.
- **NEW (SPA):** No cookie consent banner appears anywhere in the full-page screenshot.
- **Likely cause:** Cookie consent banner component not implemented/mounted in the Nuxt SPA, or not triggered on this route.

### 03-register--loggedout (desktop) — Header / global chrome  [missing-feature]

- **OLD (correct):** Header shows the logo plus a persistent impact-stats bar: '0 Items fixed', '0 kg CO2e emissions prevented', '0 kg Waste prevented', '0 Events held', with vertical divider rules between each stat.
- **NEW (SPA):** Header shows only the logo on the left and 'Sign in' / 'Join Restarters' text links on the right. The entire impact-stats bar (Items fixed / CO2e / Waste prevented / Events held) is absent.
- **Likely cause:** The Nuxt guest/registration layout does not include the ImpactStatsBar/header-stats component that the legacy header renders on every page.

### 04-dashboard (desktop) — "Your Groups" panel — view-all-groups link  [data]

- **OLD (correct):** The link reads as normal text: "You can also view all groups."
- **NEW (SPA):** The equivalent link renders the raw, untranslated i18n key text "groups.all_groups" instead of the localized link label.
- **Likely cause:** Missing or broken i18n translation key binding for the 'view all groups' link in the new Your Groups panel.

### 04-dashboard (desktop) — Header — username and language selector placement  [layout]

- **OLD (correct):** Header shows only chat/bell icon counts and a plain avatar icon; no username text and no language dropdown in the header itself (a language selector instead sits as a separate fixed control at the bottom-right of the page).
- **NEW (SPA):** Header shows an "English" language dropdown and the logged-in user's name "Jane Bloggs" next to the avatar, with the "English" label sitting tightly against/overlapping the chat icon and count.
- **Likely cause:** Header component was redesigned to surface username and language selector inline in the top nav, and spacing between the new elements and existing icons was not adjusted.

### 04-dashboard (mobile) — 'What's happening' section  [missing-feature]

- **OLD (correct):** A distinct 'What's happening' heading with a dashed divider and a 'see all' link is present near the bottom of the page (activity/events feed section).
- **NEW (SPA):** No 'What's happening' section exists anywhere on the page — it is entirely absent.
- **Likely cause:** Dashboard activity-feed panel/component not yet ported to the Nuxt SPA dashboard.

### 04-dashboard (mobile) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A cookie-consent banner ('We use cookies to help our website function...' with 'Cookie settings' and 'OK' buttons) is overlaid mid-page, obscuring part of the 'Getting started' panel.
- **NEW (SPA):** No cookie-consent banner is shown anywhere on the page.
- **Likely cause:** Likely session/cookie state difference (consent already accepted in the NEW browser session before the screenshot was taken) rather than a genuine missing feature — but worth confirming the SPA implements an equivalent consent prompt for first-time visitors.

### 04-dashboard (desktop) — "What's happening" activity feed section  [missing-feature]

- **OLD (correct):** A "What's happening" section is present below the two-column panels: heading with chat-bubble icon, dashed divider, placeholder chat and clock icons (empty-state), and a "see all" link.
- **NEW (SPA):** No "What's happening" section exists anywhere on the page. The page goes straight from the Getting Started / Groups near you panels to the footer.
- **Likely cause:** The recent-activity/dashboard-feed component was not ported to the new Nuxt dashboard page.

### 04-dashboard (mobile) — 'Your Groups' panel — broken translation  [missing-content]

- **OLD (correct):** Link text reads 'view all groups' (fully translated, human-readable).
- **NEW (SPA):** Link text literally reads 'groups.all_groups' — a raw, unresolved i18n translation key is being rendered instead of translated text.
- **Likely cause:** Missing or mis-keyed translation string in the client i18n locale file for 'groups.all_groups', so the key falls through untranslated.

### 04-dashboard (mobile) — 'Getting started' panel — icon row  [layout]

- **OLD (correct):** Panel heading has a single decorative hand-pointing icon to the right of 'Getting started'.
- **NEW (SPA):** Panel shows a row of five small icons (chat bubble, wrench, calendar, people, box) overlapping the top of the orange panel, including what appears to be a broken/placeholder image icon in the middle — no equivalent element exists in OLD.
- **Likely cause:** A carousel/tab icon strip or broken image component is rendering on top of the Getting Started panel in the SPA; likely a mis-positioned or unstyled element / failed image load.

### 04-dashboard (mobile) — Header/nav chrome  [styling]

- **OLD (correct):** Top bar has a light lavender/purple background, shows only the power-icon logo (no 'restarters' wordmark), a red 'UNKNOWN' badge top-left, and greyed-out circular icon buttons for chat/notifications/people each showing a '--' placeholder count.
- **NEW (SPA):** Top bar has a white background, shows a 'restarters' text wordmark next to the icon logo, an 'English' label, and icon buttons for chat/bell/people showing '0' as the loaded count. No red 'UNKNOWN' badge.
- **Likely cause:** Header component redesigned in the SPA (wordmark added, background colour changed) and counts are fetched/rendered differently (loaded '0' vs. legacy placeholder '--').

### 05-fixometer (desktop) — Repair Records filter layout  [layout]

- **OLD (correct):** Filters are two collapsed accordion panels ("ITEM & REPAIR INFO", "EVENT INFO") in a narrow left-hand column, with a "+" to expand, sitting alongside the tabs+table on the right in a two-column layout.
- **NEW (SPA):** Filters are two panels ("ITEM & REPAIR INFO", "EVENT INFO") that are expanded by default, stacked full-width below the POWERED/UNPOWERED tabs (Category, Brand, Model, Status, Assessment, Group, From date, To date fields all visible), producing a single-column layout instead of the original two-column sidebar+table design.
- **Likely cause:** The accordion/collapse default state and side-by-side grid layout from the legacy Blade view were not replicated in the new Vue component (defaults to open, and container uses a stacked block layout instead of a two-column flex/grid).

### 05-fixometer (desktop) — POWERED/UNPOWERED tab active-state styling  [styling]

- **OLD (correct):** The whole tab bar + description panel has a teal/cyan outline framing it, and the description shown ("A powered item is anything that has or requires a power source") corresponds to the visually emphasized POWERED tab.
- **NEW (SPA):** The UNPOWERED tab is rendered with a solid black fill (appearing selected/active), while the POWERED tab is white with a black outline, yet the description text below still refers to powered items — the active-tab styling appears inconsistent with the displayed content.
- **Likely cause:** Tab component's active/inactive style classes may be swapped or the default-selected tab index doesn't match which content panel is rendered.

### 05-fixometer (mobile) — Repair Records filter panels (default state)  [layout]

- **OLD (correct):** "ITEM & REPAIR INFO" and "EVENT INFO" are collapsed accordions (teal bar + "+" icon, no fields visible); "POWERED" and "UNPOWERED" are separate plain collapsed rows below them, also with "+" icons. Page ends shortly after with a language selector.
- **NEW (SPA):** "ITEM & REPAIR INFO" and "EVENT INFO" are expanded by default, showing the full filter forms (Category, Brand, Model, Status, Assessment / Group, From date, To date). POWERED and UNPOWERED are no longer separate accordion rows at all — they became tab buttons above the panels. This makes the NEW page far taller and visually much busier than OLD by default.
- **Likely cause:** Nuxt port defaults the accordion/tab components to expanded/open instead of collapsed, and merged POWERED/UNPOWERED into a tab control instead of the legacy accordion rows.

### 05-fixometer (mobile) — POWERED / UNPOWERED sections  [layout]

- **OLD (correct):** Presented as two separate full-width collapsed accordion rows ("POWERED", "UNPOWERED") with plain white background and a "+" icon, no item counts shown.
- **NEW (SPA):** Presented as a two-segment tab control ("POWERED (0)", "UNPOWERED (0)") with counts, sitting directly under the two black CTA buttons instead of below the ITEM & REPAIR INFO / EVENT INFO group.
- **Likely cause:** Component redesign turning accordion rows into a tab bar; different position in the DOM/visual order relative to the filter panels.

### 05-fixometer (mobile) — Filter-panel header styling  [styling]

- **OLD (correct):** "ITEM & REPAIR INFO"/"EVENT INFO" bars are solid teal-filled with bold teal-on-teal text, grouped in one box with a thick teal outer border and a hard black offset drop-shadow, matching the panel style used for the stat cards.
- **NEW (SPA):** Bars are light grey with black bold text and a chevron icon; the panel has a thin teal left/side border only, without the hard black drop-shadow seen on OLD panels and on NEW's own stat-card panels.
- **Likely cause:** Accordion component was restyled without reusing the app's shared hard-shadow card/panel style.

### 06-groups-all (mobile) — Content panel container (tabs + list)  [styling]

- **OLD (correct):** The tabs and group list sit inside a single bordered card with a thick black outline and a hard offset drop-shadow (the site's signature neo-brutalist panel style), matching the shadow style used on buttons.
- **NEW (SPA):** No card/panel wrapper at all: tabs are bare text and the list/table sits directly on the page background with no border or shadow.
- **Likely cause:** The GroupList/panel component in the SPA was not wrapped in the shared bordered-card component used elsewhere (e.g. around buttons).

### 06-groups-all (mobile) — Filter panel default state  [layout]

- **OLD (correct):** Filters are collapsed by default behind a 'SHOW FILTERS +' toggle link; only the group count and list are shown until the user opts in.
- **NEW (SPA):** Filters are always expanded inline: a 'Search by name' box, 'Include archived groups' checkbox, and a 'Columns' checkbox group (Location/Hosts/Restarters/Next event) are shown unconditionally, with no collapse/expand control.
- **Likely cause:** The show/hide filters toggle from the legacy view was not implemented in the SPA; filters render unconditionally.

### 06-groups-all (desktop) — Groups list filters  [missing-feature]

- **OLD (correct):** Five filter controls are shown in a row: "Search name..." text box, "Tag" dropdown, "Search location..." text box, "Country..." dropdown, and "Network" dropdown.
- **NEW (SPA):** Only a single "Search by name" text box plus an "Include archived groups" checkbox are present. Tag, Location, Country, and Network filters are entirely missing.
- **Likely cause:** Filter controls for tag/location/country/network were not ported to the new GroupsAll page/filter component.

### 06-groups-all (mobile) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A dark cookie-consent banner is shown fixed at the bottom of the screen with 'Cookie settings' and an orange 'OK' button.
- **NEW (SPA):** No cookie consent banner is visible.
- **Likely cause:** Either the SPA does not implement the cookie-consent prompt, or the consent had already been recorded/dismissed for this session (should be verified against a clean-cookie session).

### 08-groups-nearby (mobile) — Filter tabs (Yours/Nearest/All)  [styling]

- **OLD (correct):** 3 tabs — YOURS / NEAREST / ALL — rendered as bordered boxes with a heavy black border and hard offset drop-shadow (site's neubrutalist tab style); active tab (NEAREST) bold with an accent underline.
- **NEW (SPA):** 4 tabs — YOUR GROUPS / OTHER GROUPS / ALL GROUPS / MAP — rendered as plain underlined text links with no border, box, or drop-shadow; an extra MAP tab not present in OLD.
- **Likely cause:** Tab component reimplemented in Nuxt without the box-shadow card treatment used elsewhere (e.g. the ADD button still has it); MAP tab appears to be a new addition not reflected in the legacy baseline.

### 08-groups-nearby (mobile) — Language selector placement  [layout]

- **OLD (correct):** A single language selector ("English" with chat-bubble icon and dropdown chevron) appears once, in its own bar below the tab/content panel.
- **NEW (SPA):** "English" appears twice: as plain text in the top header bar, and again as a label in the page footer — neither matches OLD's dedicated mid-page selector bar/dropdown.
- **Likely cause:** Language selector relocated into header/footer chrome and lost its dropdown affordance during the redesign.

### 08-groups-nearby (desktop) — Empty-state message logic (Other Groups tab)  [data]

- **OLD (correct):** Text reads: "You do not currently have a town/city set. You can set one in your profile. You can also view all groups." — implies the logic branch is "user has no town/city in their profile".
- **NEW (SPA):** Text reads: "There are no groups within 50 km of your location. You can see all groups here. Or why not start your own? Learn what running your own repair event involves." — implies the logic branch is "user has a location but no nearby groups", and adds an extra 'start your own group' CTA not present in OLD.
- **Likely cause:** Same seeded user/DB in both screenshots, so the two apps are evaluating different conditions (profile town/city presence vs. geo-radius query) and/or the NEW SPA isn't correctly detecting that this user has no town/city set on their profile, causing it to show the wrong branch of copy with different suggested actions.

### 08-groups-nearby (mobile) — Content panel wrapper  [styling]

- **OLD (correct):** The empty-state message sits inside a bordered white panel with a heavy black border and hard offset drop-shadow, text centered.
- **NEW (SPA):** The message is plain unboxed text, left-aligned, floating directly on the page background with no border, panel, or shadow.
- **Likely cause:** Panel/Card wrapper component not applied around this content block in the Nuxt port.

### 08-groups-nearby (mobile) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A dark, fixed-bottom cookie-consent banner is shown with body text, a "Cookie settings" button and an orange "OK" button.
- **NEW (SPA):** No cookie consent banner is present anywhere on the page.
- **Likely cause:** Cookie consent component not implemented/mounted in the Nuxt SPA (or its dismissed state is being persisted differently).


## LOW

### 01-landing--loggedout (desktop) — Footer language selector  [missing-feature]

- **OLD (correct):** Page ends with a thin divider line and a right-aligned 'English' language selector (globe icon + chevron) as the last element on the page.
- **NEW (SPA):** Page ends immediately after the blue 'Empower your network' panel; there is no divider or language selector at the bottom of the page.
- **Likely cause:** Footer/locale-switcher component present in the legacy Blade layout was not carried over to the Nuxt page (i18n switcher may only exist elsewhere in the SPA chrome, or was dropped).

### 01-landing--loggedout (mobile) — Header navigation  [layout]

- **OLD (correct):** Header contains only the 'restarters' logo/wordmark, no additional nav links.
- **NEW (SPA):** Header additionally shows 'Sign in' and 'Join Restarters' text links to the right of the logo, duplicating the 'JOIN US' / 'LOG IN' call-to-action buttons shown further down the page.

### 02-login--loggedout (desktop) — Header navigation links  [layout]

- **OLD (correct):** No 'Sign in' / 'Join Restarters' links in the header on this page (the page itself is the sign-in form).
- **NEW (SPA):** Header includes teal 'Sign in' and 'Join Restarters' text links at the top right, not present in the old header on this page.

### 02-login--loggedout (desktop) — Password input background colour  [styling]

- **OLD (correct):** Password field has a plain white (255,255,255) background.
- **NEW (SPA):** Password field has a light grey-blue (245,247,250) background, giving it a subtly different/disabled-looking fill compared to old.

### 02-login--loggedout (desktop) — Logo size  [styling]

- **OLD (correct):** 'restarters' wordmark + power-icon logo renders slightly larger (taller header lockup) in the taller header that also houses the stats bar.
- **NEW (SPA):** Logo renders slightly smaller in the more compact header.

### 02-login--loggedout (mobile) — Password input field styling  [styling]

- **OLD (correct):** Password field has the same bold black border style as the Email field above it, with a plain white fill.
- **NEW (SPA):** Password field has a noticeably thinner, lighter gray border and a pale blue-gray tinted fill, inconsistent with the bold-black-border/cream-fill style used on the Email field directly above it.
- **Likely cause:** Password input not using the same custom form-control styling/class as the Email input in the new form component.

### 02-login--loggedout (mobile) — Sign-in form links ('Forgot password', 'Create an account')  [styling]

- **OLD (correct):** Links render as dark/black underlined text, matching the page's black-on-white palette.
- **NEW (SPA):** Links render in teal/blue link color instead of black.
- **Likely cause:** Global link color/theme differs between legacy CSS and new SPA design tokens.

### 03-register--loggedout (mobile) — Header navigation on register page  [layout]

- **OLD (correct):** Header shows only the 'restarters' logo/icon centered; no sign-in or join links are visible on this registration screen.
- **NEW (SPA):** Header shows the logo plus 'Sign in' and 'Join Restarters' links, even though the user is already on the join/registration page.
- **Likely cause:** A shared site header component is being rendered on the register page without suppressing the auth nav links for this route.

### 04-dashboard (mobile) — Footer  [missing-content]

- **OLD (correct):** Captured footer only shows an 'English' language dropdown; no additional links, logo, or copyright are visible in the screenshot.
- **NEW (SPA):** Full footer present: 'restarters' logo, links for Talk, Wiki, Help & Feedback, FAQs, The Restart Project, Cookie Policy, plus 'English' and '© 2026 The Restart Project' copyright line.
- **Likely cause:** Legacy footer may render more content below the fold not captured in this particular full-page capture, or the legacy footer genuinely lacks these links while the SPA added a fuller footer component.

### 04-dashboard (desktop) — "Getting started" panel copy  [missing-content]

- **OLD (correct):** Panel appears to start directly with the "→ Get fixing" bullet (no intro paragraph visible) and has 3 bullets: Get fixing, Get chatting, Get analytical.
- **NEW (SPA):** Panel includes an added intro paragraph ("Restarters.net is a free, open source platform for a global community of people making local repair events happen and campaigning for our Right to Repair.") plus a 4th bullet "→ Get organising: learn how to run a repair event and/or ask the community for help on Talk." not present in old.
- **Likely cause:** Getting Started panel copy was expanded/updated in the new build without matching the legacy content the parity check expects.

### 04-dashboard (desktop) — Header nav link underlines  [styling]

- **OLD (correct):** Nav labels (TALK, FIXOMETER, EVENTS, GROUPS, WIKI) render without underlines.
- **NEW (SPA):** Nav labels render with a visible underline beneath each label.
- **Likely cause:** Default anchor underline not stripped for the new nav component's link styling.

### 04-dashboard (mobile) — 'Getting started' intro copy  [missing-content]

- **OLD (correct):** Text reads: 'We are a global community of people who run local repair events and campaign for our Right to Repair. Restarters.net is our free, open source toolkit.'
- **NEW (SPA):** Text reads: 'Restarters.net is a free, open source platform for a global community of people making local repair events happen and campaigning for our Right to Repair.' Wording/phrasing differs from OLD though the meaning is similar.
- **Likely cause:** Client-side translation string for this dashboard intro copy was independently rewritten rather than exported verbatim from the legacy lang file.

### 05-fixometer (desktop) — Global Impact intro copy  [data]

- **OLD (correct):** No extra line between the intro paragraph and the impact stat cards.
- **NEW (SPA):** An additional line "No repairs recorded yet." is shown directly under the intro paragraph, above the stat cards.
- **Likely cause:** New SPA adds an empty-state message when repair count is 0 that has no equivalent in the legacy page.

### 05-fixometer (mobile) — Repair Records action buttons (BROWSE REPAIR RECORDS)  [missing-feature]

- **OLD (correct):** Only one action button is present: "DOWNLOAD ALL DATA".
- **NEW (SPA):** A second button "BROWSE REPAIR RECORDS" appears alongside "DOWNLOAD ALL DATA", with no equivalent visible in OLD.
- **Likely cause:** New UI paradigm (tabs/explicit browse action) replacing OLD's implicit accordion-based browsing; not necessarily wrong but is a structural addition not present in the legacy target.

### 05-fixometer (mobile) — Header chrome  [styling]

- **OLD (correct):** Shows a red "UNKNOWN" status badge above the logo icon, no "restarters" wordmark text, and the chat/notification/group icons render as grey placeholder pills with no visible counts.
- **NEW (SPA):** Shows the "restarters" wordmark next to the logo icon, an "English" label, and loaded chat/bell icons showing "0" counts; no "UNKNOWN" badge.
- **Likely cause:** Possibly a pre-hydration/loading-state timing difference in OLD (connection-status widget not yet resolved) versus a genuinely different header layout that always shows the wordmark/label in NEW; worth confirming with a settled-state capture.

### 05-fixometer (desktop) — Repair Records action buttons  [layout]

- **OLD (correct):** Only a single "DOWNLOAD ALL DATA" button appears in the Repair Records header.
- **NEW (SPA):** Two buttons appear: "DOWNLOAD ALL DATA" and an additional "BROWSE REPAIR RECORDS" button not present in the old page.
- **Likely cause:** New SPA added an extra CTA button (possibly linking to a records browsing route) that doesn't exist in the legacy page.

### 05-fixometer (desktop) — Top navigation bar / user menu  [layout]

- **OLD (correct):** Top-right of the nav shows unresolved placeholder content: a chat icon with "--", a person icon with "--", and a generic silhouette avatar with no visible username or language selector inline in the nav.
- **NEW (SPA):** Top-right of the nav is fully populated: "English" language dropdown, chat icon "0", bell/notification icon "0", and avatar with username "Jane Bloggs" all inline in the header.
- **Likely cause:** Likely the legacy screenshot was captured before its asynchronous header widgets finished loading (counts/user still show loading placeholders), rather than a genuine feature gap.

### 06-groups-all (desktop) — Table header style  [styling]

- **OLD (correct):** Column headers use icons (A-Z, location pin, person, person+badge, calendar) each paired with a small up/down sort-arrow control, matching the icon-driven header style used elsewhere.
- **NEW (SPA):** Column headers are plain text ("Name", "Location", "Hosts", "Restarters", "Next event"); only the "Name" column shows a sort indicator (▲), the others have no visible sort affordance.
- **Likely cause:** Table header component was reimplemented with plain text labels and a single default-sort column instead of per-column icon+sort-arrow headers.

### 06-groups-all (mobile) — Group count message text  [data]

- **OLD (correct):** "There is 1 group."
- **NEW (SPA):** "There is 1 group. Zoom out to see more." - the 'Zoom out to see more' phrase (relevant to a map view) is shown even though this is the plain list/table view, not the map.
- **Likely cause:** Map-view helper text was hoisted into a shared count-message component and now leaks into the non-map ALL GROUPS tab.

### 06-groups-all (mobile) — 'Groups' page-title icon  [missing-feature]

- **OLD (correct):** A small steaming coffee-cup illustration appears next to the 'Groups' heading.
- **NEW (SPA):** No icon next to the 'Groups' heading.
- **Likely cause:** Decorative icon not ported to the SPA page header.

### 06-groups-all (mobile) — Page footer  [layout]

- **OLD (correct):** No footer content visible on this page (page ends after the language selector, before the cookie banner).
- **NEW (SPA):** A full footer is shown: restarters logo, links (Talk, Wiki, Help & Feedback, FAQs, The Restart Project, Cookie Policy), language selector, and copyright.
- **Likely cause:** New sitewide footer chrome added in the SPA rebuild; not present on this legacy page.

### 06-groups-all (desktop) — Table row group icon/avatar  [missing-content]

- **OLD (correct):** Each row shows a small bordered square icon (group avatar placeholder) to the left of the group name link.
- **NEW (SPA):** No avatar/icon is shown before the group name; the row starts directly with the text link.
- **Likely cause:** Avatar thumbnail column was dropped from the new table row markup.

### 06-groups-all (desktop) — Columns customization control  [layout]

- **OLD (correct):** No column-visibility controls exist above the table.
- **NEW (SPA):** A "Columns" section with checkboxes (Location, Hosts, Restarters, Next event, all checked) appears above the table, plus an "Include archived groups" checkbox not present in old.
- **Likely cause:** New feature added during the SPA rebuild that has no equivalent in the legacy page.

### 06-groups-all (desktop) — Top nav link styling  [styling]

- **OLD (correct):** Nav items (TALK, FIXOMETER, EVENTS, GROUPS, WIKI) render as plain uppercase black text with no underlines; active item (GROUPS) is simply bold.
- **NEW (SPA):** Nav items render as underlined links (TALK, EVENTS, GROUPS, WIKI underlined; FIXOMETER not underlined), giving an inconsistent default-hyperlink look rather than the legacy's plain nav-button style.
- **Likely cause:** Nav items use default anchor-tag underline styling instead of the legacy's unstyled nav-link CSS; FIXOMETER inconsistency suggests a routing/component mismatch for that one link.

### 06-groups-all (desktop) — Header account/notification icons  [data]

- **OLD (correct):** Chat and notification icons show placeholder "--" counts and the profile icon shows a generic gray avatar with no name, suggesting the header had not finished loading user data at capture time.
- **NEW (SPA):** Header fully shows "English" language selector, chat/bell counts as "0", and the logged-in user's name "Jane Bloggs" with avatar.
- **Likely cause:** Likely a timing/loading-state artifact in the OLD screenshot capture rather than a genuine app difference; flagged for completeness but low confidence this is a real defect.

### 06-groups-all (mobile) — Tab set / naming  [missing-feature]

- **OLD (correct):** Three tabs: YOURS, NEAREST, ALL.
- **NEW (SPA):** Four tabs: YOUR GROUPS, OTHER GROUPS, ALL GROUPS, MAP - 'NEAREST' has been renamed/replaced with 'OTHER GROUPS' and a new 'MAP' tab has been added.
- **Likely cause:** Tab set was redesigned during the rebuild; 'NEAREST' (location-sorted) semantics may not be preserved by 'OTHER GROUPS', and a Map tab was added that has no legacy equivalent on this page.

### 06-groups-all (mobile) — Pagination controls  [layout]

- **OLD (correct):** No pagination controls are shown (list is short).
- **NEW (SPA):** Previous / 'Page 1 of 1' / Next pagination bar is shown even though there is only one page of results.
- **Likely cause:** Pagination component renders unconditionally instead of hiding itself when there is only a single page.

### 06-groups-all (mobile) — Header notification/chat icon styling  [styling]

- **OLD (correct):** Chat and bell icons render as grey rounded pill buttons containing the icon plus a placeholder count ('--').
- **NEW (SPA):** Chat and bell icons render as plain small icons with a number ('0') next to them, no pill/button background.
- **Likely cause:** Header icon component in the SPA does not reuse the legacy pill-button chrome.

### 06-groups-all (mobile) — 'UNKNOWN' badge top-left of header  [missing-feature]

- **OLD (correct):** A red 'UNKNOWN' badge/ribbon is shown at the very top-left of the page.
- **NEW (SPA):** No such badge is present.
- **Likely cause:** Possibly a legacy environment/role debug indicator; may not need porting but is a visible difference between the two apps.

### 06-groups-all (mobile) — Button label wording  [styling]

- **OLD (correct):** 'ADD NEW' and 'FOLLOW' button labels.
- **NEW (SPA):** 'ADD A NEW GROUP' and 'FOLLOW GROUP' button labels (more verbose).
- **Likely cause:** Copy was expanded during the SPA rebuild for clarity; harmless but a wording mismatch from the legacy app.

### 08-groups-nearby (desktop) — Header structure / language selector placement  [layout]

- **OLD (correct):** Header is two-tier: a top row with logo, main nav (Talk/Fixometer/Events/Groups/Wiki) and account icons, then a full-width second row containing just the language selector ("English" with dropdown caret) on the right.
- **NEW (SPA):** Header is a single row: logo, main nav, then "English", chat count, bell count and username ("Jane Bloggs") all inline in one row; no separate full-width language-selector row.
- **Likely cause:** Header was restructured/simplified in the Nuxt port, merging the language-selector bar into the primary nav row instead of keeping it as a distinct sub-header.

### 08-groups-nearby (mobile) — Header user-status badge  [missing-feature]

- **OLD (correct):** A red "UNKNOWN" badge is shown top-left above the logo, presumably indicating account/role status.
- **NEW (SPA):** No equivalent badge/indicator is present in the header.
- **Likely cause:** Status/role badge component not ported to the new header.

### 08-groups-nearby (desktop) — Groups tabs (Your Groups / Other Groups / All Groups)  [styling]

- **OLD (correct):** Tabs are rendered as bordered, boxed neubrutalist buttons with a hard black border and offset drop-shadow around the whole tab strip and content panel; active tab has a solid black bar above it.
- **NEW (SPA):** Tabs are rendered as plain underlined text links (teal/blue), no borders, no boxes, no drop-shadow; active tab indicated only by a thin teal underline.
- **Likely cause:** Tab component was reimplemented with a different UI library/pattern (link-style tabs) instead of porting the legacy boxed-button tab component and its border/shadow treatment.

### 08-groups-nearby (mobile) — "Groups" heading decoration  [missing-content]

- **OLD (correct):** A decorative steaming coffee-mug icon appears next to the "Groups" heading.
- **NEW (SPA):** No icon next to the "Groups" heading.
- **Likely cause:** Decorative illustration/icon not ported to the new page.

### 08-groups-nearby (mobile) — Add-group button label  [styling]

- **OLD (correct):** Button reads "ADD NEW".
- **NEW (SPA):** Button reads "ADD A NEW GROUP".
- **Likely cause:** Button copy changed during the port (harmless but inconsistent with OLD baseline).

### 08-groups-nearby (mobile) — Header icon-count placeholders  [data]

- **OLD (correct):** Chat and notification pill buttons show "--" as their counts.
- **NEW (SPA):** Chat and notification icons show "0" as their counts.
- **Likely cause:** Different loading/placeholder state for unread counts between the two apps at capture time; low confidence this is a real defect.


## UNSPECIFIED

### 01-landing--loggedout (desktop) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A dark fixed bottom bar reads 'We use cookies to help our website function...' with 'Cookie settings' and 'OK' controls, overlaying the page content.
- **NEW (SPA):** No cookie consent banner is shown anywhere on the page.
- **Likely cause:** Cookie-consent banner/component not implemented in the Nuxt SPA (or gated on a cookie/localStorage flag that differs between the two test browser profiles) - worth confirming whether the SPA has an equivalent consent mechanism at all.

### 02-login--loggedout (desktop) — Header - impact stats bar  [missing-content]

- **OLD (correct):** Header shows logo plus a 4-metric impact stats bar to the right: '0 Items fixed', '0 kg CO2e emissions prevented', '0 kg Waste prevented', '0 Events held', separated by vertical dividers.
- **NEW (SPA):** Impact stats bar is completely absent. Header only contains the logo on the left and 'Sign in' / 'Join Restarters' text links on the right.

### 02-login--loggedout (desktop) — Auth link colour (Forgot password / Create an account)  [styling]

- **OLD (correct):** 'Forgot password' and 'Create an account' links render in black with an underline, matching body text colour.
- **NEW (SPA):** Same links render in teal/blue link colour with an underline, differing from old's black link styling.

### 02-login--loggedout (mobile) — Cookie consent banner  [missing-feature]

- **OLD (correct):** A dark cookie-consent banner is shown fixed at the bottom of the page: 'We use cookies to help our website function, and analytical cookies to improve our website. Please click Cookie Settings to amend cookie settings or click OK to accept all.' with 'Cookie settings' link and orange 'OK' button.
- **NEW (SPA):** No cookie consent banner is present anywhere on the page.
- **Likely cause:** Cookie consent component (GDPR banner) not implemented/mounted in the Nuxt SPA.

### 02-login--loggedout (mobile) — Header / top nav  [layout]

- **OLD (correct):** Header shows only the centered 'restarters' logo/wordmark with the power icon; no navigation links are shown while logged out on the sign-in page.
- **NEW (SPA):** Header logo is left-aligned (not centered) and is accompanied by 'Sign in' and 'Join Restarters' text links in teal, top-right of the header — redundant with the sign-in form directly below.
- **Likely cause:** Different header/nav component used for logged-out state in the SPA; logo alignment and nav visibility not matched to legacy header markup.

### 03-register--loggedout (mobile) — 'Organising skills' heading text  [missing-content]

- **OLD (correct):** "Organising skills - please select at least one if you'd like to host events"
- **NEW (SPA):** "Organising skills" only - the guidance clause "please select at least one if you'd like to host events" is missing.
- **Likely cause:** Heading text was truncated/simplified during the port, dropping the instructional clause.

### 04-dashboard (desktop) — "Groups near you" panel photo  [styling]

- **OLD (correct):** No equivalent panel or photo exists in this position.
- **NEW (SPA):** The photo in the new "Groups near you" panel has a small grey placeholder/broken-image watermark icon overlaid at the bottom-center of the image.
- **Likely cause:** Image component's loading/placeholder icon (e.g. NuxtImg) is not being cleanly replaced by the real image, or a fallback icon is rendering on top of it.

### 05-fixometer (mobile) — Repair Records action buttons styling  [styling]

- **OLD (correct):** "DOWNLOAD ALL DATA" is a white button with black border and a hard black offset drop-shadow — the same style used for "ADD DATA" elsewhere on the page.
- **NEW (SPA):** "DOWNLOAD ALL DATA" and the new "BROWSE REPAIR RECORDS" button are solid black filled buttons with white text, no border, no drop-shadow — inconsistent with the app's own "ADD DATA" button (which correctly uses the white/bordered/shadow style) visible higher up on the same NEW page.
- **Likely cause:** A different/unstyled button variant (e.g. plain btn-dark) was used for these two CTAs instead of the shared outlined-with-shadow button component.

### 05-fixometer (mobile) — Footer  [missing-feature]

- **OLD (correct):** Page content ends after the collapsed filter rows with just a bare "English ▾" language selector; no logo, nav links, or copyright are visible in the captured full-page screenshot.
- **NEW (SPA):** A full footer is present: "restarters" logo, links (Talk, Wiki, Help & Feedback, FAQs, The Restart Project, Cookie Policy), "English" label, and "© 2026 The Restart Project" copyright line.
- **Likely cause:** Either the legacy page genuinely renders a much sparser footer on this view, or the OLD capture ended before the full footer rendered/loaded — but as captured, the two pages' footers do not match.

### 05-fixometer (mobile) — "No repairs recorded yet." message  [missing-content]

- **OLD (correct):** No such line appears; the impact intro paragraph is followed directly by the waste-prevented stat panel.
- **NEW (SPA):** An extra line "No repairs recorded yet." is inserted between the intro paragraph and the stat panels.
- **Likely cause:** NEW adds a zero-state message not present in the legacy template for this view.

### 06-groups-all (desktop) — Content panel chrome  [styling]

- **OLD (correct):** The groups list (filters + table) sits inside a boxed panel with a thick black border and a hard offset drop-shadow, the signature panel style used throughout the legacy app.
- **NEW (SPA):** The filters and table sit directly on the plain page background with no border, box, or drop-shadow of any kind.
- **Likely cause:** The bordered/shadowed panel wrapper component (used elsewhere in the SPA) was not applied to this page's content container.

### 06-groups-all (desktop) — Tabs (Your Groups / Other Groups / All Groups)  [layout]

- **OLD (correct):** Tabs render as a boxed, bordered segmented control (button-like cells with borders); label reads "OTHER GROUPS NEARBY"; only 3 tabs exist (Your Groups, Other Groups Nearby, All Groups).
- **NEW (SPA):** Tabs render as plain text links with only the active tab underlined; label reads "OTHER GROUPS" (shortened); a 4th tab "MAP" has been added.
- **Likely cause:** Tabs use a generic link-style nav component instead of the legacy boxed tab component; label text and tab set were changed during the port.

### 06-groups-all (desktop) — Pagination controls  [layout]

- **OLD (correct):** No pagination controls are shown below the single-page result (only 1 group in the seeded data).
- **NEW (SPA):** PREVIOUS / "Page 1 of 1" / NEXT controls are shown even though there is only one page of results.
- **Likely cause:** Pagination component is rendered unconditionally instead of being hidden when there is only one page.

### 06-groups-all (mobile) — Tab navigation style  [styling]

- **OLD (correct):** Tabs (YOURS / NEAREST / ALL) render as boxed tab buttons with grey background for inactive tabs and a bold black box for the active tab.
- **NEW (SPA):** Tabs render as plain underlined text links with a teal underline on the active tab, no box/background at all.
- **Likely cause:** New SPA uses a generic underline-tab component instead of porting the legacy boxed-tab styling.

### 06-groups-all (mobile) — Group list row format  [layout]

- **OLD (correct):** Each group is a simple row: avatar icon, group name as an underlined link, and a FOLLOW button. No extra columns of data are shown by default.
- **NEW (SPA):** Groups render in a full data table with Name, Location, Hosts, Restarters, and Next event columns (e.g. 'London', 'Hosts: 2', 'Restarters: 0', 'Next event: None planned'), plus a sortable Name header and a FOLLOW GROUP button.
- **Likely cause:** SPA replaced the legacy simple list with a data-table component; while this exposes more data, it is a structural departure from the legacy default view.

### 06-groups-all (mobile) — Header 'English' label overlapping chat icon  [styling]

- **OLD (correct):** N/A - OLD header has a separate language selector lower on the page, not adjacent to the chat icon.
- **NEW (SPA):** The word 'English' in the header runs directly into the chat-bubble icon with no gap/padding, producing a visually cramped/overlapping label.
- **Likely cause:** Missing margin/padding between the language-selector text and the adjacent icon in the header component.

### 07-events-all (desktop) — Whole page / page load  [missing-content]

- **OLD (correct):** The OLD (legacy) screenshot is not the events listing page at all — it is the legacy app's generic 500 error page ("Unfortunately, an error has occurred", toaster/bread cartoon image, 'Please let us know that you encountered this issue...', User: Jane Bloggs, Error: 500, Previous URL: http://restarters_legacy_nginx/group/all). A cookie-consent banner overlays the bottom of the content. No events list, search box, or 'All upcoming events' heading is visible.
- **NEW (SPA):** The NEW (Nuxt SPA) screenshot shows the actual intended page: an 'All upcoming events' heading, a 'Search by title or venue' search box, and the message 'There are currently no other upcoming events.' Header nav and footer render normally.
- **Likely cause:** The legacy app threw a 500 error while generating this page in the seeded local-dev environment used for the parity capture (the error page's 'Previous URL' references /group/all rather than an events-listing URL, suggesting the legacy route being screenshotted itself failed server-side). Because OLD never rendered the real target page, no direct layout/feature/data comparison against NEW is possible from this pair of screenshots — the OLD-side capture needs to be redone against a working legacy URL before a genuine parity check of the events-all page can be performed.

### 08-groups-nearby (desktop) — Content panel wrapper for tab body  [styling]

- **OLD (correct):** The message/content area under the tabs sits inside a bordered white panel with a black border and hard offset shadow, matching the site's overall boxed panel style, and the message text is centered and bold.
- **NEW (SPA):** The message text sits directly on the page background with no panel, border, or shadow, and is left-aligned, plain weight.
- **Likely cause:** Panel/card wrapper component not applied to this tab's content in the new implementation.

### 08-groups-nearby (desktop) — Extra "MAP" tab  [layout]

- **OLD (correct):** Only three tabs are shown: Your Groups, Other Groups Nearby, All Groups.
- **NEW (SPA):** A fourth tab "MAP" is present alongside Your Groups, Other Groups, All Groups.
- **Likely cause:** New Leaflet-based group map feature (recent commit 8f01ffcf27) was added as a tab on this page but has no equivalent tab in the legacy page being compared against.

### 08-groups-nearby (desktop) — "Groups" page heading icon  [missing-content]

- **OLD (correct):** A decorative steaming-mug icon appears immediately to the right of the "Groups" heading.
- **NEW (SPA):** No icon appears next to the "Groups" heading — just the plain text.
- **Likely cause:** Decorative SVG/icon next to the page heading was not carried over in the Nuxt port.

### 08-groups-nearby (mobile) — Empty-state message content (nearest groups)  [data]

- **OLD (correct):** Message reads: "You do not currently have a town/city set. You can set one in your profile. You can also view all groups." — indicates the logged-in user has NO location saved in their profile.
- **NEW (SPA):** Message reads: "There are no groups within 50 km of your location. You can see all groups here. Or why not start your own? Learn what running your own repair event involves." — implies the user DOES have a location on file and a 50km search was performed.
- **Likely cause:** Same seeded user/DB but the two apps disagree on whether the user has a town/city set — the Nuxt SPA's 'nearest groups' logic is likely defaulting to a location (e.g. 0,0/IP-based) instead of correctly detecting the empty profile field, or is reading a different field than legacy.


---

## 2026-07-20 — coverage gap closed: 9 pages render-compared for the first time

An audit of `client/app/pages` against the capture harness found **17 of 40
routes had never been render-compared**, including the event view page. The
user had already reported that page as not at parity, which is what prompted
the audit. Harness now covers every route that exists on both systems
(`24-group-index` … `34-forbidden`).

Three harness defects were fixing themselves into false passes and are worth
recording, because each one made missing verification look like verification:

1. **Hardcoded detail-page ids.** Every `migrate:fresh` renumbers events and
   users, so a stale id rendered a 404 on BOTH systems - a pair that diffs as
   a perfect match. Ids are now published by `parity-fixtures.php` to
   `parity-fixtures.json` and read by the spec.
2. **Unbounded `waitForLoadState('networkidle')`.** One stalled page burned
   ~4 min and the 300s per-test timeout then killed the rest of the desktop
   run, so every page after it looked *absent* rather than *failed*.
3. **Blank captures were emitted silently.** `26-event-view` and
   `31-profile-edit` produced completely blank desktop shots. The harness now
   asserts a floor of body text per page.

### Real bug found by (3): share-stats embed URLs — FIXED

`26-event-view`'s blank desktop shot was caused by an unhandled H3Error
blanking the SPA: `EventShareStatsModal.vue` built `/party/stats/{id}/wide`
and `/outbound/info/party/{id}/leaf` root-relative. Those routes are served by
**Laravel**, not Nuxt, so they resolved against the SPA's own origin, 404'd,
and Nuxt logged `error caught during app initialization` on every event page.
The copyable embed code was broken for the same reason - it is meant to be
pasted on someone else's site, where a root-relative path can never resolve.
develop uses `env('APP_URL')`. Fixed to use `runtimeConfig.public.apiBase`;
`GroupShareStatsModal.vue` had the identical defect. Specs had pinned the
root-relative form, so they passed throughout.

### 26-event-view (desktop) — remaining diffs, NOT yet fixed

Verified by direct comparison of `26-event-view__{new,old}.png`:

- **Attendance panel has lost its box.** develop renders the
  Confirmed/Invited tabs, the volunteer list and "Add volunteer" inside a
  bordered white panel. Ours renders them flat on the page background.
- **Same for "Items at this event".** develop wraps it in a teal-bordered
  panel with the tabs as real tabs and the add button inside; ours is flat,
  with plain-text tabs.
- **Participants/Volunteers controls restructured.** develop: an icon plus a
  bold label ABOVE each -/+ stepper. Ours: stepper first, small label beneath,
  no icons.
- **"Attendance" heading gained a count** - ours reads "Attendance (1)",
  develop just "Attendance".
- **First "Items fixed" card differs** - develop fills it teal with the count
  only; ours is white with a "Fixed" label.
- **Inline map.** develop shows only a "View map" link; ours renders a Leaflet
  map inline as well.
- **Extra CO2 line.** Ours adds "that's like growing 0 tree seedlings for 10
  years" under the CO2 card; develop's card does not carry it here.

Not investigated yet: the other 8 newly-covered pages. `12-profile` and
`30-profile-view` are both ~30% shorter than develop's at both viewports,
which is a content difference rather than a render artifact and should be the
next thing checked.
