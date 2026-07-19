# Final re-diff findings (post-fix, non-by-design)

Confirmed 98, non-by-design 61.


## CRITICAL

### 01-landing--loggedout (mobile) - Footer language selector [missing-feature]
- OLD: Language switcher sits in a full-width white bar with a top border, left-aligned, showing a teal speech-bubble/globe icon, 'English' text, and a dropdown chevron arrow — clearly a clickable dropdown c
- NEW: Only plain 'English' text is shown, right-aligned, floating directly on the page background with no icon, no dropdown arrow, and no surrounding white bar/border.
- FIX: Restore the language-switcher component (icon + dropdown chevron) in its own bordered footer bar, left-aligned, matching OLD; currently it appears to be reduced to plain unstyled text with no dropdown

### 06-groups-all (mobile) - Group count on ALL tab [data]
- OLD: "There is 1 group." (ALL tab, card/list view)
- NEW: "There are 2 groups. Zoom out to see more." (ALL GROUPS tab, table view)
- FIX: Same seeded DB/user should yield the same group count on the ALL tab; investigate why the Nuxt query returns 2 groups while legacy returns 1 (check archived/tag/network scoping and any accidental map-

### 08-groups-nearby (desktop) - Other Groups Nearby — result message logic/copy [data]
- OLD: "You do not currently have a town/city set. You can set one in your profile. You can also view all groups." (links: 'your profile', 'view all groups')
- NEW: "There are no groups within 50 km of your location. You can see all groups here. Or why not start your own? Learn what running your own repair event involves." (links: 'see all groups here', 'Learn wh
- FIX: The legacy page detects that the user has no town/city set on their profile and prompts them to set one; the SPA instead always runs the 50km-radius query (implying a location exists) and never shows 


## HIGH

### 03-register--loggedout (desktop) - Header impact-stats bar [data]
- OLD: Not visible — OLD never rendered past the error page, so no baseline value is available for comparison.
- NEW: Header shows '0 Items fixed / 0 kg CO2e emissions prevented / 0 kg Waste prevented / 0 Events held' — all four site-wide stats read zero on the seeded dev DB.
- FIX: Cannot be confirmed against legacy in this run (OLD errored before rendering). Given this is a seeded dev DB that should have recorded parties/devices, verify the stats widget's data source/API call o

### 05-fixometer (desktop) - Table sort indicators [missing-feature]
- OLD: Each column header (Item, Category, Brand, Assessment, Group, Status, Date) has an up/down sort-arrow icon, and helper text reads: "Press the 'i' icons for details. Click a column head to sort by that
- NEW: Column headers are plain text with no sort icons, and the 'Press the i icons...' sentence is missing entirely (only the 'A powered item is...' sentence remains).
- FIX: Add sortable-column affordance (icons + click-to-sort) to the results table header, and restore the missing helper sentence about sorting/info icons.

### 06-groups-all (desktop) - Groups tab strip [styling]
- OLD: Your Groups / Other Groups Nearby / All Groups are rendered as bordered boxed tabs, and the active tab plus the whole results table sit inside a black-bordered panel with a hard offset drop-shadow (th
- NEW: Tabs are plain underlined text links (no box borders) and the results section has no enclosing panel border or shadow at all — it sits directly on the page background.
- FIX: Wrap the group list (tabs + filters + table) in the bordered/hard-shadow panel component used elsewhere on the site, and restore the boxed-tab styling.

### 06-groups-all (desktop) - Tag Test Group host/restarter counts [data]
- OLD: For "Tag Test Group": person-icon column = 1, person+star-icon column = 0.
- NEW: For "Tag Test Group": Hosts = 5, Restarters = 0.
- FIX: Reconcile the hosts/restarters aggregation for the group listing — counts for the same group on the same seeded DB don't match legacy under either plausible icon-to-column mapping.


## MEDIUM

### 01-landing--loggedout (mobile) - Cookie consent banner - Cookie settings control [styling]
- OLD: 'Cookie settings' is rendered as bold white plain text styled like a button, right-aligned next to the OK button, no underline.
- NEW: 'Cookie settings' is rendered as an underlined hyperlink in a lighter/blue-ish color, left-aligned and separated from the OK button.
- FIX: Restyle the Cookie settings control to match OLD's bold, non-underlined button-like text and align it next to the OK button

### 01-landing--loggedout (mobile) - Cookie consent banner - OK button styling [styling]
- OLD: OK button is a solid orange, padded, rounded rectangular button with bold white 'OK' text, matching the site's button style.
- NEW: OK button renders as an unstyled/tiny white box with plain black text, with no orange fill or button padding — looks broken compared to the site's button styling.
- FIX: Apply the standard button component/class to the cookie-banner OK action so it gets the orange fill, padding and white bold text used elsewhere (e.g. JOIN US/LOG IN/START ORGANISING buttons)

### 02-login--loggedout (mobile) - Cookie consent banner [styling]
- OLD: 'Cookie Settings' is bold within the paragraph text; below it, 'Cookie settings' (bold, plain, non-underlined) and a solid orange filled 'OK' button sit right-aligned at the end of the banner.
- NEW: 'Cookie Settings' is not bold in the paragraph; 'Cookie settings' renders as an underlined hyperlink positioned at the left margin, and 'OK' is a small white button with black border/text (styled like
- FIX: Restyle the cookie-consent banner to match legacy: bold the inline 'Cookie Settings' mention, right-align the action row, and render OK as a solid brand-orange filled button rather than reusing the ou

### 03-register--loggedout (mobile) - Header logo placement [layout]
- OLD: Logo ("restarters" wordmark + power icon) is roughly centered in the header bar (measured bbox x=125–328 of a 390px-wide viewport, i.e. centred).
- NEW: Logo is left-aligned, sitting flush against the left edge of the header bar (measured bbox x=14–193), leaving a large empty gap on the right.
- FIX: Center the header logo container on the mobile layout (e.g. justify-content:center / mx-auto) to match legacy.

### 03-register--loggedout (mobile) - Skills step — heading/body typography [styling]
- OLD: "What skills would you like to share with others?" heading wraps to 3 lines; the intro paragraph wraps to 3 lines, at a compact font size, keeping the card short.
- NEW: Same heading and paragraph render at a visibly larger font size, wrapping to 4 and 5 lines respectively, making the card much taller and shrinking the whitespace gap before the cookie banner (card-to-
- FIX: Reduce the heading/body font-size on this step to match the legacy type scale instead of the larger default Bootstrap sizing.

### 03-register--loggedout (mobile) - Cookie banner — OK/accept button styling [styling]
- OLD: "OK" is a solid orange/amber filled button with bold white text.
- NEW: "OK" renders as a plain white box with black text and a thin border — no fill colour, unstyled compared to legacy (visible above the ignored DevTools badge overlapping its lower half).
- FIX: Apply the site's accent-orange button theme to the Nuxt cookie-consent "OK" button so it matches the legacy filled/bold styling.

### 04-dashboard (desktop) - Your Groups panel - body text width [layout]
- OLD: The paragraph text inside the white 'Your Groups' card fills the full width of the card (dashed divider spans the whole card width; card is a compact ~430px tall box).
- NEW: The paragraph text inside the 'Your Groups' card is constrained to roughly half the card's width (~340px), wrapping much earlier than the card border. This leaves a large blank white area on the right
- FIX: Remove the max-width/column constraint on the text block inside the Your Groups panel component so it fills the card the same way the legacy dashboard partial does.

### 04-dashboard (desktop) - What's happening - content/empty-state icons [missing-content]
- OLD: Below the dashed divider, two icons are shown (a chat/message icon and a clock icon), presumably empty-state placeholders for two content columns (talk activity / timeline).
- NEW: Nothing is rendered below the divider except blank space before the 'see all' link.
- FIX: Restore the two empty-state icons (or the underlying content columns they represent) in the What's happening section.

### 04-dashboard (mobile) - Getting Started card - dashed separator placement [layout]
- OLD: Dashed rule sits AFTER the intro paragraph, separating the intro text ('We are a global community...toolkit.') from the bulleted action list ('Get fixing', 'Get organising', ...).
- NEW: Dashed rule sits immediately UNDER the heading, BEFORE the intro paragraph - the paragraph and bullet list run on with no separator between them.
- FIX: Move the <hr>/dashed-divider element in the Getting Started card to sit after the intro paragraph and before the bullet list, matching legacy markup. The 'Your Groups' card already places its divider 

### 05-fixometer (desktop) - Cookie consent banner [missing-feature]
- OLD: A fixed bottom banner is shown: "We use cookies to help our website function... Please click Cookie Settings to amend cookie settings or click OK to accept all." with 'Cookie settings' link and orange
- NEW: No cookie consent banner appears anywhere on the page.
- FIX: Confirm the Nuxt SPA has an equivalent cookie-consent component and that it fires on this route/session; if consent was already stored for this capture, re-verify with cleared cookies before treating 

### 05-fixometer (desktop) - Accordion expand icon and container style [styling]
- OLD: ITEM & REPAIR INFO / EVENT INFO rows sit in a narrow left-hand column with a '+' expand icon on the right.
- NEW: The same two rows are full-width bars with a down-chevron icon instead of '+'.
- FIX: Match the accordion component's icon ('+') and narrow-column placement to legacy, consistent with the two-column layout fix above.

### 05-fixometer (mobile) - Cookie consent banner [styling]
- OLD: Banner text bolds the phrase "Cookie Settings"; controls are right-aligned with a bold plain-text "Cookie settings" label next to a solid orange filled "OK" button.
- NEW: Banner text has no bold emphasis anywhere; controls are left-aligned with an underlined "Cookie settings" link next to a plain white/outlined "OK" button (brand orange fill lost).
- FIX: Restore the orange-filled OK button, right-align both controls, and bold the "Cookie Settings" phrase in the banner copy to match the legacy consent UI.

### 06-groups-all (desktop) - Table column sorting [missing-feature]
- OLD: All four data columns (Location, Hosts-icon, Restarters-icon, Next-event/calendar-icon) show up/down sort-arrow controls in the header, in addition to the A-Z name sort.
- NEW: Only the Name column shows a sort indicator ("Name ▲"); Location, Hosts, Restarters and Next event headers are static text with no sort control.
- FIX: Add clickable sort toggles to the Location, Hosts, Restarters and Next event column headers.

### 08-groups-nearby (desktop) - Header account widgets state [data]
- OLD: Chat and bell icons are greyed-out placeholders showing '--', and no username/avatar label is shown next to the account icon.
- NEW: Chat and bell icons are active showing '0'/'0' counts, with avatar and 'Jane Bloggs' username shown.
- FIX: Confirm the OLD capture was taken fully logged in as the same seeded user; if so, the legacy header widget isn't populating unread counts/username before the screenshot is taken and the capture script


## LOW

### 01-landing--loggedout (desktop) - screenshot capture / test infrastructure [data]
- OLD: OLD shot shows a Laravel 500 error page ("Unfortunately, an error has occurred." with the broken-toaster illustration, Error: 500, URL/Previous URL both show http://restarters_legacy_nginx). This is t
- NEW: NEW shot shows a fully rendered Restarters landing page: header with logo + stats bar (Items fixed / CO2e emissions prevented / Waste prevented / Events held), "Welcome to Restarters!" hero with Join 
- FIX: Not a real SPA/legacy visual discrepancy — the OLD capture hit a 500 error (legacy app/nginx failed to serve the logged-out landing route at capture time). Re-run the parity screenshot capture for 01-

### 01-landing--loggedout (mobile) - Hero spacing (logo to heading) [layout]
- OLD: Small, tight vertical gap between the logo and the 'Welcome to Restarters!' heading.
- NEW: Noticeably larger vertical gap between the logo and the heading, pushing the heading and all following content down.
- FIX: Reduce top margin/padding on the hero heading or logo container to match OLD spacing

### 01-landing--loggedout (mobile) - Cookie consent banner - body text emphasis [styling]
- OLD: "Please click **Cookie Settings** to amend cookie settings or click OK to accept all." — 'Cookie Settings' is bold within the sentence.
- NEW: Same sentence but 'Cookie Settings' is not bold — plain regular weight text throughout.
- FIX: Add bold/strong emphasis around 'Cookie Settings' in the banner copy to match OLD

### 01-landing--loggedout (mobile) - Section divider (horizontal rule before 'Need more?') [styling]
- OLD: Divider is a thick black dashed horizontal rule with long dashes.
- NEW: Divider is a thin, light-gray dashed horizontal rule with short dashes (default/unstyled hr appearance).
- FIX: Style the hr/divider element with the same thick black dashed border used in OLD (likely a global CSS reset removed the custom hr styling)

### 02-login--loggedout (desktop) - Whole page (OLD baseline capture) [data]
- OLD: OLD capture shows the legacy app's generic 500 server-error page ('Unfortunately, an error has occurred', toaster/bread illustration, Error: 500, URL: http://restarters_legacy_nginx/login, Previous UR
- NEW: NEW renders a complete, apparently functional Nuxt login page: header with restarters logo and an impact-stats bar (0 Items fixed / 0kg CO2e emissions prevented / 0kg Waste prevented / 0 Events held),
- FIX: Not a NEW-side defect. Re-capture the OLD baseline after confirming the legacy app's /login route renders successfully in the seeded local dev environment (it currently throws an HTTP 500 - check lega

### 03-register--loggedout (desktop) - Cookie consent banner — OK button styling [styling]
- OLD: The 'OK' accept-all button in the cookie banner is a solid orange filled rectangle with dark text.
- NEW: The 'OK' button in the cookie banner is white-filled with a black border and black text (no orange fill).
- FIX: Update the shared cookie-consent component's OK button to use the legacy brand orange fill/colour instead of the current white/black-outline treatment.

### 03-register--loggedout (desktop) - Overall page — capture validity [data]
- OLD: OLD capture of http://restarters_legacy_nginx/user/register shows a generic Laravel 500 error page ("Unfortunately, an error has occurred", toaster graphic, Error: 500, Time: 2026-07-19 12:53:21, URL,
- NEW: NEW SPA renders a working registration wizard: header with logo + impact-stats bar, and a card titled 'What skills would you like to share with others? Step 1 of 4' with a 'NEXT STEP' button.
- FIX: This is not a SPA parity defect per se — the legacy dev instance errored on /user/register (500) before it could render the real target design, so no true visual baseline exists for this diff. Investi

### 03-register--loggedout (mobile) - Skills selection content [data]
- OLD: Shows two section headings, "Organising skills - please select at least one if you'd like to host events" and "Technical skills", as the skill-category structure for this step (no items render beneath
- NEW: The category headers are absent entirely; instead a single fallback message is shown: "No skills are available to select yet. You can add them later from your profile."
- FIX: Confirm whether the skills API returns the same category/skill data for this seeded user as legacy. If skills exist, the client should render the "Organising skills" / "Technical skills" groupings lik

### 03-register--loggedout (mobile) - Cookie banner — "Cookie settings" control & inline emphasis [styling]
- OLD: "Cookie settings" control is bold white text with no underline (button-styled); the inline "Cookie Settings" mention inside the paragraph is bold.
- NEW: "Cookie settings" control is a plain, underlined link (default anchor styling, not bold); the inline "Cookie Settings" mention in the paragraph is plain weight, not bold.
- FIX: Match font-weight/underline styling of the cookie-settings control and inline emphasis to legacy.

### 04-dashboard (desktop) - Orphaned broken image below Your Groups panel [data]
- OLD: No element present in the blank space between the 'Your Groups' card and the 'What's happening' section.
- NEW: A stray broken-image placeholder (grey 'image failed to load' mountain glyph in a small white box) floats in the page background beneath the Your Groups card, not inside any panel/container.
- FIX: Find the orphaned <img> (likely from a lazy-loaded/avatar component with an empty or unresolved src) rendering outside any card in the dashboard layout and fix its src or remove the element.

### 04-dashboard (desktop) - What's happening - heading icon [missing-content]
- OLD: The 'What's happening' heading has a small decorative speech-bubble-with-dots icon next to it, matching the icon treatment used on 'Your Groups' (mug) and 'Getting started' (hand).
- NEW: The 'What's happening' heading has no icon at all.
- FIX: Add the matching heading icon component to the What's happening section header for consistency with the other panel headings.

### 04-dashboard (desktop) - Header notification icons (chat/bell) styling [styling]
- OLD: The unread-messages and notifications icons sit inside solid grey rounded-pill capsule buttons with a muted-blue '0' count, and the message icon uses a two-bubble 'chat' glyph.
- NEW: The icons are bare (no pill/capsule background) sitting directly on the page background, and the message icon glyph has changed to a rectangular note/speech-bubble icon (same glyph as the TALK nav ico
- FIX: Wrap the message/notification icon+count pairs in the grey rounded-pill button style used in the legacy header, and reuse the original two-bubble chat glyph for the unread-messages icon.

### 04-dashboard (desktop) - Top nav item underline [styling]
- OLD: TALK / FIXOMETER / EVENTS / GROUPS / WIKI nav labels render without underlines.
- NEW: The same nav labels are permanently underlined.
- FIX: Remove the default anchor text-decoration/underline from the nav-link style (or restrict it to hover/active state) to match the legacy header.

### 04-dashboard (desktop) - 'Getting started' list item spacing [styling]
- OLD: The four '→ Get X' lines are tightly packed with no paragraph gap between them.
- NEW: Each '→ Get X' line has a full paragraph-height gap before the next one, spreading the list out noticeably more than legacy.
- FIX: Reduce the margin-bottom on the list paragraphs (or switch to line breaks) inside the Getting Started panel to match the legacy tight spacing.

### 04-dashboard (desktop) - 'see all' link color [styling]
- OLD: The 'see all' link at the bottom of What's happening is black, underlined, matching all other body links on the page.
- NEW: The 'see all' link renders in blue, inconsistent with the black-underlined link style used everywhere else on the page (including the Getting Started links).
- FIX: Apply the site's standard link class/color (black, underlined) to the 'see all' link instead of the default anchor blue.

### 04-dashboard (mobile) - What's happening section header [missing-feature]
- OLD: Heading row shows 'What's happening' with a '-' (minus) collapse/toggle icon on the right, consistent with the Getting Started and Your Groups section headers.
- NEW: Heading row shows only 'What's happening' with no collapse/toggle icon - the icon present on the other two section headers is missing here.
- FIX: Add the same collapse-toggle icon component used on the Getting Started / Your Groups section headers to the What's happening section header.

### 04-dashboard (mobile) - Header notification/message badges [data]
- OLD: Chat and bell icons are muted/greyed with a '--' placeholder value; group icon is a faint, low-contrast placeholder circle.
- NEW: Chat and bell icons are solid black with a resolved '0' value; group icon placeholder is crisp/full-contrast.
- FIX: Likely a load-timing artifact in the old screenshot (badge counts hadn't resolved yet when captured) rather than a genuine app difference - the new counts of '0' look correct/complete. Worth re-captur

### 05-fixometer (desktop) - Header user/notification state [data]
- OLD: Right-hand header icons show placeholder '--' values with a generic silhouette avatar and no visible username.
- NEW: Right-hand header shows real values ('0' unread chat, '0' notifications) and the logged-in name 'Jane Bloggs' next to the avatar.
- FIX: Likely a load-timing artifact in the OLD capture (async counts/user fetch not yet resolved) rather than a NEW-side defect — re-capture OLD after full hydration to confirm; if OLD genuinely never resol

### 05-fixometer (desktop) - Footer presence [layout]
- OLD: Screenshot ends abruptly right after the table header row/cookie banner, with no footer (logo, links, language selector, copyright) visible.
- NEW: A full footer is present at the bottom of the page (restarters logo, Talk/Wiki/Help & Feedback/FAQs/The Restart Project/Cookie Policy links, language selector, copyright).
- FIX: Likely a capture artifact from the OLD screenshot (fixed cookie banner may have truncated the full-page capture height) rather than a real missing footer — re-capture OLD with the cookie banner dismis

### 05-fixometer (desktop) - Repair Records panel layout [layout]
- OLD: The whole 'Repair Records' search UI (ITEM & REPAIR INFO / EVENT INFO accordion + Powered/Unpowered tabs + results table) is a single two-column card with a continuous teal border: narrow accordion fi
- NEW: The same controls are stacked full-width and un-boxed: Powered/Unpowered pill tabs on their own row, then two full-width accordion bars below (no shared border with the tabs), then a plain unbordered 
- FIX: Rebuild the Repair Records section markup/CSS as the legacy two-column bordered card (accordion filter rail + tabbed table together inside one teal-bordered container) instead of stacked full-width bl

### 05-fixometer (desktop) - Broken image placeholder near Repair Records buttons [data]
- OLD: No stray image/icon appears in this area.
- NEW: A small broken-image placeholder icon (grey box with mountain glyph) renders between the 'Browse or search...' text and the DOWNLOAD ALL DATA / BROWSE REPAIR RECORDS buttons.
- FIX: Find and fix the <img>/asset reference producing this broken image on the Fixometer page (bad src, missing file, or leftover placeholder element).

### 05-fixometer (desktop) - Powered/Unpowered tab styling [styling]
- OLD: Both tabs are white with the active tab (POWERED) outlined in cyan; visually a light, bordered toggle.
- NEW: The inactive tab (UNPOWERED) renders as a solid black block next to a white active tab — an inverted, heavier look.
- FIX: Restyle the powered/unpowered toggle so the inactive tab is white/bordered like legacy rather than solid black fill.

### 05-fixometer (desktop) - Header nav link styling [styling]
- OLD: Nav labels (TALK/FIXOMETER/EVENTS/GROUPS/WIKI) have no underline; the active tab (Fixometer) is indicated by a small black bar above its icon.
- NEW: All nav labels appear underlined (default anchor text-decoration), and there is no bar-above active-state indicator.
- FIX: Remove default link underline on header nav items and reinstate the active-tab bar indicator used in legacy.

### 05-fixometer (mobile) - Repair Records results table (mobile) [layout]
- OLD: Table is not visible on this screenshot because the Powered/Unpowered accordions are collapsed by default.
- NEW: Table renders immediately ("0 items found" / column headers Item, Category, Brand, Assessment, Group, Status / "No items match your search.") but the last column header "Status" is clipped to "Stat" a
- FIX: Wrap the results table in a horizontal-scroll container or switch to a responsive stacked-card layout on mobile so no column is clipped.

### 05-fixometer (mobile) - Repair Records section header [layout]
- OLD: A dashed horizontal rule sits above the section; the "Repair Records" heading and the download button share one row (button top-right of heading).
- NEW: No dashed rule above the section; heading, description paragraph, and buttons are stacked in separate full-width rows instead of heading+button sharing a row.
- FIX: Add the dashed separator rule above the section and place the download button inline with the heading as in legacy.

### 06-groups-all (desktop) - All Groups filters [missing-feature]
- OLD: Filter row has five controls: Search name, Tag dropdown, Search location, Country dropdown, Network dropdown.
- NEW: Filter row has only "Search by name" plus an "Include archived groups" checkbox; Tag, Search-location, Country and Network filters are all gone, replaced by column-visibility checkboxes (Location/Host
- FIX: Add Tag, Country and Network filter dropdowns and a location search field to the All Groups panel to restore legacy filtering capability.

### 06-groups-all (desktop) - Group count / dataset [data]
- OLD: "There is 1 group." — only "Tag Test Group" is listed under All Groups.
- NEW: "There are 2 groups. Zoom out to see more." — lists "Nikolaus PLC" and "Tag Test Group", and the message implies the All Groups result set is being constrained by a map viewport/zoom level.
- FIX: Investigate why the All Groups query returns a different group set/count than legacy on the same seeded DB; the All Groups tab should return the full unfiltered list as legacy does, not a map-viewport

### 06-groups-all (desktop) - Header logged-in state [data]
- OLD: Chat and bell icons show "--" placeholders; no username/avatar name is visible next to the profile icon.
- NEW: Chat and bell icons show "0"/"0"; "Jane Bloggs" is shown next to the profile avatar.
- FIX: Likely a screenshot-timing artifact (legacy captured before header AJAX/hydration populated counts and name) rather than a real defect — reverify legacy header once fully loaded.

### 06-groups-all (desktop) - Main nav link styling [styling]
- OLD: Talk / Fixometer / Events / Groups / Wiki nav captions render as plain bold black text with no underline.
- NEW: Talk / Fixometer / Events / Groups nav captions render as underlined, blue-tinted link text (default anchor styling).
- FIX: Strip default anchor underline/color from the main nav bar items so they match legacy's plain bold-black caption style.

### 07-events-all (desktop) - Page content / capture validity [data]
- OLD: OLD screenshot shows a Laravel fatal error page ('Unfortunately, an error has occurred.', 500, bug-report template showing Previous URL: http://restarters_legacy_nginx/group/all) instead of the events
- NEW: NEW screenshot shows a correctly rendered 'All upcoming events' page with a search box ('Search by title or venue') and empty-state text 'There are currently no other upcoming events.', plus a normal 
- FIX: Not a NEW-app defect based on this evidence — the OLD capture is broken/unusable for parity. Check the parity-shot script's legacy URL for '07-events-all' (it recorded Previous URL /group/all, which l

### 07-events-all (mobile) - page load / capture validity [data]
- OLD: The legacy app returned a 500 server error when loading this page (URL http://restarters_legacy_nginx/party/all). The screenshot shows the site's generic "Unfortunately, an error has occurred." toaste
- NEW: The new SPA renders successfully: header with "restarters" logo, chat icon (0), bell icon (0) and avatar; an "All upcoming events" heading; a "Search by title or venue" input; and the message "There a
- FIX: The OLD capture is invalid for parity purposes because legacy /party/all 500'd on this seeded DB/user — check legacy logs to find and fix the underlying error (or whatever broke the seed for that rout

### 08-groups-nearby (mobile) - Cookie consent banner buttons [styling]
- OLD: "Cookie settings" is a plain white-text button; "OK" is a filled orange/yellow button.
- NEW: "Cookie settings" is an underlined link; "OK" is a white button with black border/shadow (no orange fill).
- FIX: Match cookie banner button styling to legacy (filled orange OK button, plain button — not link — for Cookie settings), unless the flatter style is an intentional design refresh.

### 08-groups-nearby (desktop) - Other Groups tab panel — container styling [styling]
- OLD: Tab strip and message are wrapped in a bordered card with a thick black border and hard offset drop-shadow, matching the site's neo-brutalist component style (same as the ADD A NEW GROUP button).
- NEW: Tabs are plain underlined text links with a thin grey hairline underneath; the message text sits directly on the page background with no border, box, or shadow.
- FIX: Wrap the group-list tab strip and content panel in the same bordered/shadowed card component used elsewhere in the design system instead of plain unstyled text.

### 08-groups-nearby (desktop) - Top nav link underlines [styling]
- OLD: TALK / FIXOMETER / EVENTS / GROUPS / WIKI render with no underline.
- NEW: All top-nav items (TALK, FIXOMETER, EVENTS, GROUPS, WIKI) render permanently underlined.
- FIX: Remove default text-decoration:underline from the top-nav link component (or restrict it to hover/active state) so it matches the legacy nav's unstyled links.

### 08-groups-nearby (desktop) - Unexplained 'UNKNOWN' badge, top-left of OLD capture [layout]
- OLD: Small red 'UNKNOWN' text badge in the extreme top-left corner.
- NEW: No equivalent element present.
- FIX: Likely a screenshot/test-harness artifact from the legacy capture pipeline rather than real app UI — confirm it isn't a genuine environment-banner bug in the legacy dev instance; no action needed on t

### 08-groups-nearby (mobile) - Header - icon buttons [styling]
- OLD: Chat/bell/people icons sit inside solid grey circular pill backgrounds.
- NEW: Icons are rendered plain with no circular background fill.
- FIX: Add the grey circular background to the header icon buttons in the Nuxt header component.

### 08-groups-nearby (mobile) - Groups heading decoration [missing-content]
- OLD: A decorative coffee-mug-with-steam icon sits beside the "Groups" heading.
- NEW: No decorative icon next to the "Groups" heading.
- FIX: Add the coffee mug SVG/icon next to the page heading to match legacy.

### 08-groups-nearby (mobile) - Content panel container [styling]
- OLD: The status message sits inside a white card with a black border and a hard offset drop-shadow, matching the tab group container below it.
- NEW: The status message is plain unboxed text with no border, background card, or shadow.
- FIX: Wrap the message content in the bordered/shadowed card component used elsewhere in the design system.

### 08-groups-nearby (mobile) - Groups empty-state message copy/logic [data]
- OLD: "You do not currently have a town/city set. You can set one in your profile. You can also view all groups." — implies the message is driven by the user's profile having no town/city configured.
- NEW: "There are no groups within 50 km of your location. You can see all groups here. Or why not start your own? Learn what running your own repair event involves." — implies a location IS set and a 50km r
- FIX: Since this is the same seeded user/DB, check why the Nuxt "nearest groups" logic assumes a location is set (falls back to a default rather than checking for an absent town/city) — the underlying condi

### 08-groups-nearby (mobile) - Language selector [missing-feature]
- OLD: Chat-bubble icon + "English" label with dropdown caret, in its own bar with white background above the empty grey area.
- NEW: Plain "English" text, no icon, no dropdown caret, positioned at the very bottom next to the copyright line.
- FIX: Add the icon and dropdown affordance to the language selector, and verify whether it should stay a top-of-content bar rather than a footer line.

