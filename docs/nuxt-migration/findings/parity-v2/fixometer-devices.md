# Visual-parity gaps: fixometer-devices (15)

## 1. [high] /fixometer
Legacy's 'Add Data' button is unconditional (always rendered, even logged-out) and clicking it opens AddDataModal (an in-page group/event picker). Nuxt only renders the button when `loggedIn` is true, and it is a plain link straight to /dashboard instead of opening any picker modal.
- nuxt: client/app/pages/fixometer.vue:69-71
- develop: resources/js/components/FixometerHeading.vue:9-11,22 (b-btn @click="addData" -> AddDataModal, unconditional)
- FIX: Render the Add Data button unconditionally (not gated on loggedIn), and either port AddDataModal's group/event picker or, if kept as a simplification, document/flag that logged-out visitors now see a button that leads nowhere useful instead of matching legacy's always-visible modal-launcher.

## 2. [high] /device/search (and /fixometer's embedded table)
Legacy's FixometerRecordsTable gives admins exactly ONE control per row - an edit-pencil icon (info icon for non-admins) that toggles the row open. Nuxt's DevicesSearchTable instead shows THREE separate always-visible controls for admins: the 'i' info toggle plus standalone 'Edit' and 'Delete' text-link buttons.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:494-524
- develop: resources/js/components/FixometerRecordsTable.vue:54-66 (show_details slot: single edit_ico_green.svg for isAdmin, else single info_ico_green.svg)
- FIX: Collapse the admin affordance to a single icon/control that opens the row into edit mode (matching legacy's one edit-pencil icon), rather than exposing separate always-visible Edit and Delete text links alongside the unrelated info toggle.

## 3. [high] /device/search (and /fixometer's embedded table)
Legacy's info/edit toggle opens the row into the FULL EventDevice form (disabled when non-admin) - three cards showing item type, category, brand, model, age, weight, images, repair status, next steps, spare parts, barrier, problem AND notes. Nuxt's 'i' toggle instead reveals a small custom <dl> with only Model/Age/Spare-parts/Assessment plus a 'view event' link - most fields are absent.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:531-553
- develop: resources/js/components/FixometerRecordsTable.vue:67-80 (row-details -> EventDevice.vue, full 3-card form) resources/js/components/EventDevice.vue:1-60
- FIX: Render the disabled/read-only DeviceForm (or an equivalent full field set: repair status, next steps, spare parts, barrier, notes, weight, images) inside the expanded row instead of the abbreviated <dl>.

## 4. [high] /device/search (and /fixometer's embedded table)
Legacy paginates with <b-pagination>, a numbered page-jump control (First/Prev/1/2/3/.../Next/Last), only rendered when there is more than one page. Nuxt renders a simple 'Previous page / Page X of Y / Next page' bar with no way to jump to an arbitrary page, and it stays visible even with a single page of results.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:561-583
- develop: resources/js/components/FixometerRecordsTable.vue:82-89 (b-pagination, v-if="total > perPage")
- FIX: Replace the prev/next-only bar with a numbered pagination control matching bootstrap-vue's b-pagination affordance, and hide it entirely when there's only one page.

## 5. [high] /party/view/[id] (device list panel)
Legacy wraps the ENTIRE 'Items at event' block (both tabs, both tables, add buttons) inside one top-level <CollapsibleSection collapsed>, so the whole devices section starts collapsed and requires a click to expand. Nuxt's EventDevicesPanel renders a plain, always-expanded <h2> heading with no collapse behaviour at all.
- nuxt: client/app/components/devices/EventDevicesPanel.vue:69-73
- develop: resources/js/components/EventDevices.vue:2,125 (outer <CollapsibleSection ... collapsed> wrapping the whole component)
- FIX: Wrap EventDevicesPanel's content in a collapsed-by-default collapsible section (same component used elsewhere, e.g. group page sections) instead of a bare heading.

## 6. [medium] /device/search filter rail ('Item & Repair Info' panel)
Field order differs: legacy shows Category, then Model, then Brand (for powered items). Nuxt shows Category, then Brand, then Model - Brand and Model are swapped.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:317-333
- develop: resources/js/components/FixometerFilters.vue:27-32 (Model at 27-29, Brand at 30-32)
- FIX: Reorder the Brand and Model fields in the powered branch so Model comes before Brand, matching FixometerFilters.vue.

## 7. [medium] /device/search filter rail ('Event Info' panel)
Legacy's From/To date filters use <b-form-datepicker>, a styled calendar-button widget (brand-orange trigger, popup calendar). Nuxt uses plain native <input type="date">, which renders as the browser's default date-picker chrome - a visibly different control.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:382-389
- develop: resources/js/components/FixometerFilters.vue:87-92 (b-form-datepicker)
- FIX: If a themed date-picker widget isn't in scope, at minimum note this as an accepted simplification; otherwise port a styled date-picker component to match the calendar-button affordance.

## 8. [medium] /party/view/[id] (device list panel tabs)
Legacy's Powered/Unpowered tab titles show waste(kg) and CO2(kg) figures next to trash_brand.svg / co2_brand.svg icons in a flex row. Nuxt's EventDevicesPanel tabs show the same numbers as plain inline text via a single i18n string, with no icons at all.
- nuxt: client/app/components/devices/EventDevicesPanel.vue:95-98,109-112
- develop: resources/js/components/EventDevices.vue:13-24,38-49 (trash_brand.svg / co2_brand.svg b-img icons beside each figure)
- FIX: Add the waste and CO2 icons beside their respective figures in the tab label, matching the legacy icon+number pairing instead of a single plain-text string.

## 9. [medium] /party/view/[id] (device list panel rows)
Legacy's per-row edit/delete controls are small icon buttons (edit_ico_green.svg / delete_ico_red.svg) and delete goes through a ConfirmModal dialog. Nuxt's DeviceRow uses text links ('Edit' / 'Delete device') and an inline 'confirm_delete / Yes / Cancel' row swap instead of a modal.
- nuxt: client/app/components/devices/DeviceRow.vue:99-131
- develop: resources/js/components/EventDeviceSummary.vue:36-43,60-69 (icon buttons + ConfirmModal)
- FIX: Swap the text-link Edit/Delete controls for icon buttons matching edit_ico_green.svg/delete_ico_red.svg, and use a modal confirm dialog rather than an inline row-replacement confirm.

## 10. [medium] /device/search and /party/view/[id] device tables
Legacy hides Brand/Assessment/Group columns (FixometerRecordsTable) and Brand/Age/Assessment/Status/Spare-parts columns (EventDeviceList) below the md breakpoint via `d-none d-md-table-cell`, and additionally restyles rows into a compact 2-column key/value grid on mobile. Nuxt's DevicesSearchTable and EventDevicesPanel/DeviceRow tables render every column unconditionally at all viewport widths, producing a wide horizontally-scrolling table on mobile instead of legacy's compact card layout.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:453-461; client/app/components/devices/EventDevicesPanel.vue:131-138,176-183
- develop: resources/js/components/FixometerRecordsTable.vue:222,225-226 (+ @include media-breakpoint-down(sm) grid rule); resources/js/components/EventDeviceList.vue:12-28
- FIX: Add responsive column-hiding (or an equivalent compact mobile card layout) to both tables so mobile rendering matches legacy instead of scrolling a full-width table.

## 11. [medium] /fixometer's embedded Repair Records section
Legacy always mounts BOTH FixometerRecordsTable instances (powered+unpowered) simultaneously - a desktop b-tabs block (`d-none d-md-block`, filters + tabs) plus an entirely separate mobile-only pair of CollapsibleSections with NO filter rail at all (`d-block d-md-none`). Nuxt's DevicesSearchTable instead renders one unified responsive grid (filter rail + single active-tab table) at every breakpoint, so mobile users see filters legacy hides there, and the inactive tab's shown count falls back to the (possibly stale, unfiltered) impact-data prop rather than a live filtered total, since only one table is ever mounted.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:258-263,289-395
- develop: resources/js/components/FixometerPage.vue:60-67,69-93,95-112,115-164
- FIX: If keeping a single unified layout is intentional, at minimum hide the filter rail on mobile to match legacy; consider mounting both powered/unpowered result sets so the inactive tab's count stays live rather than falling back to the aggregate prop.

## 12. [medium] /device/search filters and device add/edit form
Legacy uses vue-multiselect dropdown widgets (with icon-variant chrome and a 'suggested' highlight box-shadow) for category/status/item-type/brand/next-steps/spare-parts/barrier fields. Nuxt uses plain native <select>/<input list=datalist> throughout - a genuinely different control appearance (no multiselect dropdown chrome/clear-affordance), not just a BS4-vs-BS5 style change.
- nuxt: client/app/components/devices/DeviceForm.vue:274-397; client/app/components/fixometer/DevicesSearchTable.vue:305-352
- develop: resources/js/components/EventDevice.vue:12-20 (DeviceType/DeviceCategorySelect/DeviceBrand); resources/js/components/FixometerFilters.vue:24-50 (DeviceCategorySelect/DeviceBrand/status multiselect)
- FIX: Documented as a deliberate cross-cutting simplification (vue-multiselect dropped, design.md); flagged here for completeness since it is a real, visible control-type difference across every device-related form field, not merely a framework skin change.

## 13. [low] /fixometer
Legacy's 'Download all data' export button is variant="primary" (solid brand button). Nuxt renders it as btn-outline-primary (outline button).
- nuxt: client/app/pages/fixometer.vue:115-122
- develop: resources/js/components/FixometerPage.vue:13-15 (b-btn variant="primary" href="/export/devices/?")
- FIX: Change the export button's variant to solid primary to match legacy.

## 14. [low] /fixometer
Nuxt adds a 'Browse records' button linking to /device/search that has no legacy counterpart on this page at all (the code comment documents this as a deliberate addition since /device/search is a dead legacy route being repurposed).
- nuxt: client/app/pages/fixometer.vue:123-125
- develop: resources/js/components/FixometerPage.vue:1-30 (no equivalent second CTA next to the export button)
- FIX: Acceptable deliberate addition per the code's own doc comment; flagged for completeness only - no action needed unless strict 1:1 parity is required.

## 15. [low] /device/search
Legacy clamps the Assessment/short_problem cell to 3 lines via v-line-clamp="3" (with ellipsis overflow). Nuxt renders the raw text with no truncation, so long assessment text can grow the row/table height unpredictably instead of staying fixed-height.
- nuxt: client/app/components/fixometer/DevicesSearchTable.vue:486
- develop: resources/js/components/FixometerRecordsTable.vue:25-28 (v-line-clamp="3")
- FIX: Apply a 3-line CSS line-clamp (line-clamp: 3 / -webkit-line-clamp) to the Assessment cell to match legacy's fixed-height truncation.
