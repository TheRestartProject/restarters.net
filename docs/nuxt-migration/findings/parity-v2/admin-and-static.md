# Visual-parity gaps: admin-and-static (28)

## 1. [high] /brands, /category, /skills, /tags (AdminCrudTable edit)
Editing a record opens an in-page BModal in Nuxt; develop navigates to a full separate page (e.g. /brands/edit/{id}) with its own breadcrumb trail (FIXOMETER > Brand > Edit brand). No breadcrumb/page-navigation equivalent exists anywhere in the Nuxt CRUD flow.
- nuxt: client/app/components/admin/AdminCrudTable.vue:503-568
- develop: resources/views/brands/edit.blade.php:8-18 (breadcrumb) and 39 (full-page form); same pattern in category/edit.blade.php:8-17, skills/edit.blade.php:9-16, tags/edit.blade.php
- FIX: Either add a real breadcrumb (Dashboard > <Entity> > Edit <Entity>) inside the modal/page, or route ?editId=N to an actual dedicated edit page/route with breadcrumb navigation instead of a modal, to preserve the page-navigation affordance.

## 2. [high] /brands
Nuxt's brands page shows a per-row Delete button plus a confirm-delete modal (AdminCrudTable defaults allowCreate/allowDelete to true and brands.vue never overrides allow-delete). Develop's brand list has only one column ('Brand name') and brands/edit.blade.php has no delete link at all — the /brands/delete/{id} route is unused by any template in develop, so brand deletion has literally no UI entry point in the legacy app.
- nuxt: client/app/pages/brands.vue:63-75 (no :allow-delete override) + AdminCrudTable.vue:421-430,570-590
- develop: resources/views/brands/index.blade.php:38-54 (single 'Brand name' column, no delete UI); resources/views/brands/edit.blade.php (Save button only, no delete link)
- FIX: Pass :allow-delete="false" on pages/brands.vue's <AdminCrudTable> to match develop (brand deletion has no UI in the legacy app), or confirm with product owner this is an intentional new capability before keeping it.

## 3. [high] /about/cookie-policy
The 'cookie settings' link that reopens the on-page GDPR consent control is entirely dropped (confirmed by the component's own comment) and the sentence is reworded to point users at their 'browser's cookie settings' instead. Once a visitor has accepted the CookieConsent banner, this page gives them no way to reopen/change consent — a genuine functional regression on a GDPR compliance page.
- nuxt: client/app/pages/about/cookie-policy.vue:10-14 (comment), 29 (managing_text)
- develop: resources/views/features/cookie-policy.blade.php:28
- FIX: Add a link/button on this page that reopens the CookieConsent UI (e.g. reset the useCookieConsent 'accepted' state) so users can revisit their choice, matching the legacy gdpr-cookie-notice-settings-button behaviour.

## 4. [high] /about/cookie-policy
Two full bullet lists are missing: 'First Party'/'Third Party' cookie definitions and 'Persistent cookies'/'Session cookies' definitions (4 <li> total across 2 <ul> blocks in develop, each after its own intro paragraph). Nuxt collapses this into a single summary paragraph (kinds_text) with no lists at all.
- nuxt: client/app/pages/about/cookie-policy.vue:39-40
- develop: resources/views/features/cookie-policy.blade.php:45-61
- FIX: Restore the two <ul> definition lists (First Party / Third Party, then Persistent / Session cookies) as separate i18n keys and render them as bullet lists, matching develop's structure.

## 5. [medium] /skills
Nuxt's skills table adds a 'Category' column between 'Skill name' and 'Description'. Develop's skills table has only two columns: 'Skill name' and 'Description' — category is only editable on the edit page, never shown in the list.
- nuxt: client/app/pages/skills.vue:47-51
- develop: resources/views/skills/index.blade.php:39-43
- FIX: Remove the 'category' entry from skills.vue's tableFields so the list shows only Skill name and Description, matching develop; keep the category select on the create/edit form only.

## 6. [medium] /user/all
The filters sidebar is always visible/stacked in Nuxt with no mobile collapse. Develop's filter <aside> is a Bootstrap collapse panel (id=collapseFilter) toggled by a 'Reveal filters' button (shown only below md breakpoint) plus an inline mobile close (X) button inside the panel.
- nuxt: client/app/pages/user/all.vue:249-259 (no reveal button, plain <aside class="panel p-3">)
- develop: resources/views/user/all.blade.php:26 (Reveal filters button), 39 (collapse aside), 43 (mobile close button)
- FIX: Add a 'Reveal filters' toggle button visible only on small screens, wrap the filter <aside> in a Bootstrap collapse (or BCollapse) hidden by default on mobile, and add a close control inside it, matching develop's mobile UX.

## 7. [medium] /user/all
Pagination is reduced to Previous/Next buttons plus a 'Page X of Y' label. Develop uses Laravel's full numbered paginator ($userlist->links()), letting users jump directly to any page.
- nuxt: client/app/pages/user/all.vue:396-416
- develop: resources/views/user/all.blade.php:215-223
- FIX: Replace the prev/next-only pager with a numbered page-link control (or add page-number buttons alongside prev/next) so users can jump to an arbitrary page as in develop.

## 8. [medium] /user/all
Nuxt adds a per-row 'Edit role' button/column that opens the AdminSettingsTab in a modal — a control with no equivalent in develop's user list, which offers no per-row quick-role-edit action at all (only the Name link to a full /user/edit/{id} page).
- nuxt: client/app/pages/user/all.vue:382-391
- develop: resources/views/user/all.blade.php:161-170 (row has no action column)
- FIX: Drop the extra 'Edit role' column (or confirm intentionally as a UX improvement with product), so the row only exposes the Name-link edit path as in develop.

## 9. [medium] /user/all
Email cell is plain, untruncated text. Develop truncates to 15 chars (Str::limit) and wraps it in a hover popover offering 'Click/press to copy' the full address to clipboard.
- nuxt: client/app/pages/user/all.vue:375
- develop: resources/views/user/all.blade.php:171-182
- FIX: Truncate the email display to ~15 chars and add a hover/click-to-copy affordance (title/tooltip + clipboard write) matching develop's behaviour.

## 10. [medium] /user/all
Groups cell is a bare count number. Develop wraps the same count in a hover popover that lists the actual group names (partials/usergroups-popover.blade.php).
- nuxt: client/app/pages/user/all.vue:379
- develop: resources/views/user/all.blade.php:194-200
- FIX: Add a hover popover/tooltip on the groups count showing the member's group names, matching develop.

## 11. [medium] /role
Role table headers (ID/Role/Permissions) are static, non-sortable text. Develop's role list (RolesTable.vue, a b-table) marks all three columns sortable with client-side click-to-sort.
- nuxt: client/app/pages/role.vue:136-140
- develop: resources/js/components/RolesTable.vue:36-50
- FIX: Add click-to-sort behaviour (like AdminCrudTable's sortByColumn/sortIndicator pattern) to role.vue's ID/Role/Permissions headers.

## 12. [medium] /brands, /category, /skills, /tags (create/edit forms)
AdminCrudTable lays out every form field in a single vertical column inside the modal. Develop's edit pages use a two-column layout (e.g. category: col-lg-4 for Name/Weight/CO2/Reliability/Cluster beside offset-lg-1 col-lg-7 for Description+Save; tags: col-lg-4 name beside offset-lg-1 col-lg-7 description).
- nuxt: client/app/components/admin/AdminCrudTable.vue:503-568
- develop: resources/views/category/edit.blade.php:33-96; resources/views/tags/edit.blade.php:34-51
- FIX: Introduce an optional two-column layout mode in AdminCrudTable's form (or a slot) for pages that need it, matching develop's field grouping.

## 13. [medium] /skills, /tags
Delete is a row-level button + 'are you sure' confirm modal in Nuxt (AdminCrudTable). In develop, deletion is an unconfirmed plain link ('Delete skill'/'Delete tag') located inside the edit page itself, not a table-row action, and has no confirmation dialog.
- nuxt: client/app/components/admin/AdminCrudTable.vue:421-430,570-590
- develop: resources/views/skills/edit.blade.php:64-67; resources/views/tags/edit.blade.php:53-56
- FIX: Confirm this UX change (moving delete to the row + adding a confirmation step) is an intentional, agreed improvement; if strict parity is required, move Delete into the edit form instead of the table row and drop the confirmation step.

## 14. [medium] /admin/preview-deploy
Page title and H1 wording differ substantially: develop's <title> is 'Deploy Preview Branch' and H1 is 'Deploy Preview Branch to restarters.dev'; Nuxt's are 'Preview deploy' and 'PR preview deploy'.
- nuxt: client/app/pages/admin/preview-deploy.vue:10,94
- develop: resources/views/admin/preview-deploy.blade.php:4,10
- FIX: Set useHead title to 'Deploy Preview Branch' and the H1 to 'Deploy Preview Branch to restarters.dev' to match develop.

## 15. [medium] /admin/preview-deploy
The branch <select> has no associated label at all. Develop shows a bold 'Branch to deploy' label directly above the select.
- nuxt: client/app/pages/admin/preview-deploy.vue:110-116
- develop: resources/views/admin/preview-deploy.blade.php:35-36
- FIX: Add a '<strong>Branch to deploy</strong>' label above (or via aria-label on) the BFormSelect.

## 16. [medium] /about/cookie-policy
The three 'types of cookies' bullets lose develop's bold category-name lead-in (e.g. '<strong>strictly necessary cookies.</strong> These are cookies that are required...'); Nuxt renders each as one plain, unstyled sentence with no bold emphasis.
- nuxt: client/app/pages/about/cookie-policy.vue:34-36
- develop: resources/views/features/cookie-policy.blade.php:38-40
- FIX: Split each list-item translation into a bold label + description (or wrap the leading phrase in <strong>) to restore the visual emphasis.

## 17. [low] /user/all
AdminCrudTable-family tables lose the row striping/hover styling present on every corresponding develop table: user/all uses table-striped; brands/skills/tags use table-hover table-striped; category/role use bootstrap-vue's striped+hover b-tables. Nuxt renders plain <table class="table"> everywhere.
- nuxt: client/app/pages/user/all.vue:347; client/app/components/admin/AdminCrudTable.vue:379; client/app/pages/role.vue:134
- develop: resources/views/user/all.blade.php:130; resources/views/brands/index.blade.php:38; resources/views/skills/index.blade.php:37; resources/views/tags/index.blade.php:38; resources/js/components/CategoriesTable.vue:6-7; resources/js/components/RolesTable.vue:6-7
- FIX: Add table-striped (and table-hover where develop used it) classes to the users table, AdminCrudTable's table, and role.vue's table.

## 18. [low] /role (edit modal)
The edit modal drops the disabled 'Name:' text input that develop shows above the permissions checklist (a read-only field displaying the role's name, distinct from the modal/page title).
- nuxt: client/app/pages/role.vue:154-171
- develop: resources/views/role/edit.blade.php:26-31
- FIX: Add a disabled text input showing the role name at the top of the edit modal body, matching develop's form structure.

## 19. [low] /category
The Reliability column is marked sortable in Nuxt; develop's CategoriesTable.vue explicitly sets sortable: false for this column (it holds pre-rendered badge HTML, not a plain sortable value).
- nuxt: client/app/pages/category.vue:81-86 (footprint_reliability field, sortable: true)
- develop: resources/js/components/CategoriesTable.vue:66-70
- FIX: Set sortable: false on the footprint_reliability tableFields entry in category.vue.

## 20. [low] /category
Column label text differs from develop: Nuxt shows 'Category name' (admin.category_name); develop's actual rendered column header is just 'Name' (CategoriesTable.vue hardcodes its own English labels, ignoring the admin.php lang file entirely).
- nuxt: client/app/pages/category.vue:77
- develop: resources/js/components/CategoriesTable.vue:45-47
- FIX: Change the name column label to 'Name' to match develop's actually-rendered header text.

## 21. [low] /category
A category with no cluster renders a blank Cluster cell. Develop explicitly falls back to the literal text 'N/A' when cluster_name is empty.
- nuxt: client/app/pages/category.vue:78 (cluster_name field has no formatter/fallback)
- develop: resources/js/components/CategoriesTable.vue:17-19
- FIX: Add a formatter on the cluster_name tableFields entry that renders 'N/A' when the value is null/empty.

## 22. [low] /skills
Description field label reads 'Description' in Nuxt; develop's create-skill modal explicitly labels it 'Description (optional):' (admin.description_optional, a key removed from the migrated lang file).
- nuxt: client/app/pages/skills.vue:56
- develop: resources/views/includes/modals/create-skill.blade.php:21-22
- FIX: Restore an '(optional)' suffix on the Description field label for skills (and tags, which had the same wording) to match develop.

## 23. [low] /admin/preview-deploy
The deploy confirmation uses a custom BModal instead of develop's blocking native confirm() dialog. The confirmation message text itself matches (admin.preview_deploy_confirm), but the interaction pattern (inline browser dialog vs. app modal with separate Cancel/Deploy buttons) differs.
- nuxt: client/app/pages/admin/preview-deploy.vue:127-143
- develop: resources/views/admin/preview-deploy.blade.php:53-56
- FIX: Acceptable modernization if agreed; otherwise use window.confirm() to match develop exactly.

## 24. [low] /admin/preview-deploy
Descriptive paragraph is reworded/shortened, dropping mention of the 'develop container' rebuild and DB-restore detail; the PR dropdown option text also adds an em-dash and '@author' not present in develop's plain '#N Title (branch)' format.
- nuxt: client/app/pages/admin/preview-deploy.vue:42,95
- develop: resources/views/admin/preview-deploy.blade.php:11,44-46
- FIX: Align the intro paragraph wording and PR option format with develop's text if strict parity is required (or confirm the extra author info is an accepted improvement).

## 25. [low] /admin/preview-deploy
Missing the lead-in sentence 'Deploys are queued as GitHub Actions runs.' before the 'View running workflows →' link.
- nuxt: client/app/pages/admin/preview-deploy.vue:146-149
- develop: resources/views/admin/preview-deploy.blade.php:60-65
- FIX: Add the missing lead-in sentence before the workflows link.

## 26. [low] /about/cookie-policy
The cookie table loses develop's visible outline: style="border: 1px solid gray;" around the whole table.
- nuxt: client/app/pages/about/cookie-policy.vue:44
- develop: resources/views/features/cookie-policy.blade.php:67
- FIX: Add a matching border style (or an equivalent utility class) to the cookie-policy table.

## 27. [low] /user/all
Location cell shows nothing when a user has no location; develop explicitly falls back to the text 'N/A'.
- nuxt: client/app/pages/user/all.vue:377
- develop: resources/views/user/all.blade.php:186-192
- FIX: Render 'N/A' (via the existing users.never-style pattern or a new key) when row.location is empty.

## 28. [low] /user/all (filters)
Default option text for the Role filter differs: Nuxt shows 'Any role'; develop shows 'Choose role'.
- nuxt: client/app/pages/user/all.vue:75-78
- develop: resources/views/user/all.blade.php:96
- FIX: Change the default role filter option label to 'Choose role' to match develop (or confirm 'Any role' as an accepted wording improvement).
