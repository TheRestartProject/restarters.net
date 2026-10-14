# Visual-parity gaps: group-forms (17)

## 1. [high] /group/create and /group/edit/[id]
Develop lays the whole form out on a CSS grid that becomes two columns at the lg breakpoint (name/website/email/phone/description/image stacked in a left column; location+map+timezone stacked as a tall right column; admin panel and buttons span both columns below). Nuxt's GroupForm is a single always-stacked column at every viewport width, so on desktop the form is roughly twice as tall and the location/map/timezone fields no longer sit beside the contact-detail fields.
- nuxt: client/app/components/groups/GroupForm.vue:284-467 (no grid/columns at all)
- develop: resources/js/components/GroupAddEdit.vue:559-660 (.layout grid-template-columns:1fr 1fr @lg, per-field grid-row/grid-column placement)
- FIX: Wrap GroupForm's fields in a CSS grid matching GroupAddEdit.vue's .layout rules: name/website/email/phone/description/image in column 1 (rows 1-6), location+map preview+timezone spanning column 2 (rows 1-6) at lg and above, with the admin card and submit row spanning both columns beneath.

## 2. [high] /group/create
Develop's create page wraps the entire h1+intro text+form in a `b-card.box` (white background, hard 5px/5px black box-shadow, 1px solid black border, 0 border-radius, mt-4/p-4 padding) - a distinctive 'brutalist' bordered panel used site-wide for this kind of primary form. Nuxt's create.vue renders the same content directly inside a bare `.container.py-4` with no card/box chrome at all.
- nuxt: client/app/pages/group/create.vue:24-32
- develop: resources/views/group/create.blade.php:8-12 (b-card class="box mt-4") + resources/js/components/GroupAddEditPage.vue:3-7,53-58 (.box style)
- FIX: Wrap the create page's h1/paragraphs/GroupForm in a card styled like GroupAddEditPage.vue's `.box` class (white bg, box-shadow: 5px 5px black, 1px solid black border, border-radius 0, mt-4/p-4).

## 3. [high] /group/edit/[id]
Develop's edit page wraps the form in `.edit-panel` (white background, 1px solid $brand border, box-shadow 5px 5px $brand-black, 20-30px padding) and that class also forces all form `<label>` elements to font-size:16px/font-weight:700 (bold). Nuxt's edit/[id].vue renders the heading, image field and GroupForm directly in a bare `.container.py-4` with none of this panel chrome or label boldening.
- nuxt: client/app/pages/group/edit/[id].vue:109-171
- develop: resources/views/group/edit.blade.php:17 (<div class="edit-panel">) + resources/sass/_edit.scss:1-16,66-68 (background/border/box-shadow, label font-weight:700/font-size:16px)
- FIX: Wrap the edit page's form content in a panel replicating `.edit-panel` (white bg, bordered, box-shadow, padding) and bold/16px form labels within it, matching resources/sass/_edit.scss.

## 4. [high] /group/edit/[id]
Develop's edit page has a Bootstrap tab strip above the form: an always-visible 'Group details' tab, plus (for Administrators when the group has audit records) a second 'Group log' tab showing an audit-trail accordion. Nuxt's edit page has no tab navigation at all - just the bare form, and the audit log feature doesn't exist anywhere in the client.
- nuxt: client/app/pages/group/edit/[id].vue:109-171 (no tabs, no audit log)
- develop: resources/views/group/edit.blade.php:7-16 (ul.nav.nav-tabs with 'Group details'/'Group log' items, gated on $audits && Administrator)
- FIX: Add a nav-tabs strip at the top of the edit page ('Group details' always, 'Group log' for Administrators when the group has audits) even if the log tab can only render once a v2 audits endpoint exists; at minimum add the always-present 'Group details' tab so the page's visual chrome matches.

## 5. [high] /group/edit/[id] (admin-only panel)
Develop lets Administrators add arbitrary network-specific custom data fields to a group via NetworkData.vue (renders each existing key/value pair as an editable field, plus an 'Add new field' link that reveals a label input + 'Add field' button). Nuxt only round-trips the existing network_data object unchanged on save - there is no UI to view, edit, or add fields at all.
- nuxt: client/app/components/groups/GroupForm.vue:107-109,236 (networkData held opaque, never rendered)
- develop: resources/js/components/GroupAddEdit.vue:149 (<NetworkData :network-data.sync="networkData" />) + resources/js/components/NetworkData.vue:1-73
- FIX: Port NetworkData.vue as a component in the admin-only card: render each network_data key as an editable field, plus an 'Add new field' link/label-input/'Add field' button, matching the source component.

## 6. [medium] /group/create and /group/edit/[id] (admin-only panel)
Develop's Networks and Tags selectors are vue-multiselect widgets: a searchable dropdown with removable chip-style selected items; tags are additionally grouped under network-name headings (with 'Global' shown first). Nuxt renders both as flat BFormCheckboxGroup vertical checkbox lists with no search, no chips, and no network grouping (tag labels just append '(NetworkName)' as plain text).
- nuxt: client/app/components/groups/GroupForm.vue:430-446
- develop: resources/js/components/GroupAddEdit.vue:91-131 (vue-multiselect for networks/tags) + :342-398 (groupedTagOptions network grouping logic)
- FIX: Replace the checkbox groups with a multiselect-style control (e.g. vue-multiselect or an equivalent BS5 combobox) that supports search + chip removal, and group the tag options by network name (with 'Global' first) as groupedTagOptions does.

## 7. [medium] /group/create and /group/edit/[id]
Develop performs a live client-side duplicate-group-name check against the already-fetched group list: as soon as a duplicate name is typed, bold red text appears directly under the Name field's help text ('That group name (X) already exists...'), before any submit attempt. Nuxt has no client-side check at all - a duplicate name is only caught after submit via a generic 422 response.
- nuxt: client/app/components/groups/GroupForm.vue:296-309 (no duplicate-name check anywhere in validate()/template)
- develop: resources/js/components/GroupAddEdit.vue:19-21 (duplicateName paragraph) + :304-321 (duplicateName/duplicateError computed)
- FIX: Fetch the group list (or reuse the already-fetched groups store) and add a duplicateName computed + inline red bold warning under the Name field, matching GroupAddEdit.vue's behaviour.

## 8. [medium] /group/create and /group/edit/[id]
The group-image picker's position in the field order differs from develop. Develop places Image in column 1 after Phone and Description (position 6 of 6 in that column, still above the whole Location/Map/Timezone column). Nuxt's create form puts the Image field first, before Name; the edit page puts it even further out, above the entire GroupForm (before Name, Website, etc.).
- nuxt: client/app/components/groups/GroupForm.vue:291-294 (create, first field) and client/app/pages/group/edit/[id].vue:139-142 (edit, rendered before <GroupForm>)
- develop: resources/js/components/GroupAddEdit.vue:80-84 (<GroupImage>) + :594-597 (.group-image grid-row 6, after phone/description)
- FIX: Move the image picker to after the Description field (and before Location) in both create and edit flows, matching GroupAddEdit.vue's field order/grid position.

## 9. [medium] /group/create
Develop shows 'Group submissions need to be approved by an administrator' inline, immediately to the left of the Save button in a flex row at the very bottom of the form (create mode only). Nuxt shows the same text as a standalone muted paragraph at the very top of the page, directly under the h1/intro text, well before the form starts.
- nuxt: client/app/pages/group/create.vue:28
- develop: resources/js/components/GroupAddEdit.vue:159-169 (d-flex justify-content-between row containing the approval text and the Save SpinButton)
- FIX: Move the 'groups.groups_approval_text' paragraph out of the page header and into a flex row beside the submit button at the bottom of GroupForm (create mode only), matching GroupAddEdit.vue.

## 10. [medium] /group/create and /group/edit/[id]
The create/edit failure message is rendered differently and in a different place. Develop shows it as plain bold red text (no box/border) inside the group-buttons area at the very bottom of the form, right above the Save button. Nuxt renders it as a bordered/coloured Bootstrap BAlert at the very top of the form, before the image/name fields.
- nuxt: client/app/components/groups/GroupForm.vue:286-289
- develop: resources/js/components/GroupAddEdit.vue:153-157 (plain <p class="text-danger font-weight-bold">, positioned inside .group-buttons at the bottom)
- FIX: Move the general error message to the bottom of the form, next to the submit button, and render it as plain bold red text rather than a boxed alert, to match GroupAddEdit.vue.

## 11. [medium] /group/edit/[id]
An 'Archive group' button/confirmation flow has been added to the bottom of the Nuxt edit form (below the submit button, behind a border-top divider). In develop, GroupAddEdit.vue has no archive UI at all - archiving a group is only accessible from the group VIEW page's 'Group Actions' dropdown (GroupActions.vue), never from the edit form.
- nuxt: client/app/pages/group/edit/[id].vue:152-168
- develop: resources/js/components/GroupAddEdit.vue (no archive control anywhere in the 699-line file) vs resources/js/components/GroupActions.vue:32-33 (archive is a dropdown item on the group-view page instead)
- FIX: Remove the archive section from the edit form (or confirm this is a deliberate relocation); if kept, note it as an intentional UX improvement rather than parity, since develop never exposes archive from this page.

## 12. [medium] /group/create
The postcode field is editable by any user creating a group in Nuxt (`can-edit-postcode="canModerate || creating"` is true whenever creating, regardless of role). In develop, the create page never passes can-approve/can-network/can-edit-tags props at all, so canApprove defaults to false and the postcode field is always read-only on create for every user, including admins.
- nuxt: client/app/components/groups/GroupForm.vue:369 (:can-edit-postcode="canModerate || creating")
- develop: resources/js/components/GroupAddEdit.vue:56 (:can-edit-postcode="canApprove") + resources/views/group/create.blade.php:10 (<GroupAddEditPage box /> - no can-approve prop passed, so it defaults false)
- FIX: Drop the `|| creating` clause so postcode stays read-only on the create form regardless of role, matching develop; only allow manual postcode edits in edit mode for canModerate users.

## 13. [medium] /group/create and /group/edit/[id]
The image-picker widget looks completely different. Develop's GroupImage.vue is a compact 100x100px dropzone thumbnail with the current/placeholder image shown inline and a small round overlaid 'x' delete button when an image is set. Nuxt's TusImageUpload renders a full-width Uppy Dashboard drag-and-drop panel (200px tall, with Uppy's own file-list/progress UI) plus a separate 100x100 preview image above it when editing.
- nuxt: client/app/components/forms/TusImageUpload.vue:90-95 (Uppy Dashboard, 200px tall)
- develop: resources/js/components/GroupImage.vue:1-15 (100x100 dropzone thumbnail + overlaid delete cross) and :101-123 (sizing CSS)
- FIX: If keeping Uppy for the tus upload flow, restyle the Dashboard to a compact 100x100 thumbnail-style picker with an overlaid delete control, rather than the default full-size Uppy panel, to match the visual footprint of the legacy dropzone.

## 14. [low] /group/create and /group/edit/[id]
The Save/Create submit button has no icon and no loading spinner in Nuxt (just plain text, disabled while submitting). Develop's SpinButton always shows a 'save' icon to the left of the label, and swaps it for a spinner-border animation while the request is in flight.
- nuxt: client/app/components/groups/GroupForm.vue:461-465
- develop: resources/js/components/GroupAddEdit.vue:163-169,171-178 (SpinButton icon-name="save") + resources/js/components/SpinButton.vue:9-14 (icon/spinner markup)
- FIX: Add a save icon before the button label and swap to a spinner while `submitting` is true, matching SpinButton's rendering.

## 15. [low] /group/create and /group/edit/[id]
The small location-preview map zooms to level 13 in Nuxt versus level 11 in develop, making the preview noticeably more zoomed-in than the legacy equivalent.
- nuxt: client/app/components/groups/GroupForm.vue:373-381 (:zoom="13")
- develop: resources/js/components/GroupLocationMap.vue:5 (:zoom="11")
- FIX: Change the LMap zoom prop from 13 to 11 to match GroupLocationMap.vue.

## 16. [low] /group/create and /group/edit/[id]
Both pages lose the Bootstrap `.row.justify-content-center > .col-lg-12` centering wrapper that develop uses inside `.container`; Nuxt just uses a plain `.container.py-4`. Low visual impact at full width but changes gutter/centring behaviour at intermediate breakpoints.
- nuxt: client/app/pages/group/create.vue:25 and client/app/pages/group/edit/[id].vue:110
- develop: resources/views/group/create.blade.php:4-6 and resources/views/group/edit.blade.php:4-6
- FIX: Nest page content in `.row.justify-content-center > .col-lg-12` inside the container, matching the Blade layout.

## 17. [low] /group/create and /group/edit/[id]
Location autocomplete suggestions render as a plain Bootstrap list-group dropdown built from a not-yet-implemented `/api/v2/maps/autocomplete` endpoint (so it silently shows no suggestions today), instead of develop's native Google Places Autocomplete widget UI.
- nuxt: client/app/components/forms/LocationPicker.vue:112-123,199-209
- develop: resources/js/components/GroupLocation.vue:5-15 (<places-autocomplete>, Google's native dropdown)
- FIX: Tracked as an API gap elsewhere (docs/nuxt-migration/api-gaps.md B6) - once /api/v2/maps/autocomplete exists, verify the resulting suggestion-list styling is brought closer to Google's native widget, or accept the custom list-group style as the new baseline.
