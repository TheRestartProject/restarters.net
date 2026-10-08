# Visual-parity gaps: profile-notifs-auth (18)

## 1. [high] /notifications
Nuxt /notifications is a bare standalone page (h1 + mark-all button only). Legacy /profile/notifications reuses the full profile-edit chrome: 'Profile & Preferences' h1 + View-Profile button, and the entire list-group tab sidebar (Profile/Account/Email/Calendars/Notifications-active/Repair Directory) down the left, with the notification cards on the right in a col-lg-8. All of that surrounding structure is missing in Nuxt.
- nuxt: client/app/pages/notifications.vue:68-93
- develop: resources/views/user/notifications.blade.php:6-47 (heading/view-profile lines 6-18, sidebar lines 21-39)
- FIX: Wrap the notifications list in the same ProfileTabs-style two-column layout (list-group sidebar + col-lg-8 content) with the 'Profile & Preferences' heading and View Profile button, matching profile-edit.blade.php/notifications.blade.php.

## 2. [high] /profile, /profile/{id}
PublicProfileView adds an entire 'Groups' panel (col-md-4, order-md-3) that does not exist in the legacy public profile page at all - legacy only ever renders two panels (Skills col-md-4, Biography col-md-6). The extra column changes the whole row layout from 2 columns to 3.
- nuxt: client/app/components/profile/PublicProfileView.vue:118,126-134
- develop: resources/views/user/profile-new.blade.php:41-69
- FIX: Remove the Groups panel (or confirm product wants it as a deliberate enhancement and update the develop spec/tests instead) so the row layout matches the 2-column legacy structure.

## 3. [high] /user/register
Legacy's email field on step 2 is a dedicated `emailvalidation` component that checks availability on blur (POST /user/register/check-valid-email) and shows an inline 'is-invalid' error immediately if the address is taken. Nuxt's register.vue step-2 email field is a plain BFormInput with no live check - duplicate/invalid emails are only surfaced after completing all 4 steps and submitting.
- nuxt: client/app/pages/user/register.vue:265-272
- develop: resources/js/components/EmailValidation.vue:1-19; resources/views/auth/register-new.blade.php:80-96
- FIX: Add an on-blur availability check for the email field on step 2 (calling the equivalent v2 endpoint) with inline invalid feedback, so users learn about a taken email before reaching the final step.

## 4. [high] /user/consent
Legacy handles this exact 'already logged in, needs to give consent' case inside the SAME 4-step registration wizard (register-new.blade.php, Auth::check() branches): step 1 skills selection, step 2 personal info (including a gender field, with name/email disabled), step 3 newsletter+invites checkboxes, step 4 consent - each step shown as its own panel with a 'Step X of 4' badge and Prev/Next navigation. Nuxt's consent.vue collapses all of this into a single flat one-page form and entirely drops the skills-selection step, the gender field, and the 'invites' checkbox from step 3 (only 'newsletter' is present).
- nuxt: client/app/pages/user/consent.vue:87-168
- develop: resources/views/auth/register-new.blade.php:34-253 (Auth::check() branches at 72-96, 141-162; step 3 at 177-212 has both newsletter and invites)
- FIX: Rebuild /user/consent as the same multi-step wizard used for anonymous registration (with name/email pre-filled and disabled, password step skipped), including the skills step, gender field and invites checkbox, rather than a single flat form.

## 5. [high] /forbidden
Nuxt forces the bare 'plain' layout (no main site navigation) on the forbidden page. Legacy's forbidden.blade.php extends layouts.app, which shows the FULL site header/nav for any already-authenticated visitor (only guests get the plain header) - and most people who hit a 403 wall are logged in.
- nuxt: client/app/pages/forbidden.vue:12
- develop: resources/views/user/forbidden.blade.php:1; resources/views/layouts/app.blade.php:1-4
- FIX: Use the default (logged-in) app layout for /forbidden when the user is authenticated, falling back to the plain layout only for guests, matching layouts.app's Auth::guest() branch.

## 6. [medium] /notifications
Card highlighting/type-accent is inverted and re-styled versus legacy: legacy gives UNREAD cards an orange (#FFEED7) background and a left-aligned category icon image (restart/parties/groups/devices svg); Nuxt gives READ items a grey 'list-group-item-light' background and uses a coloured left border instead of an icon for category. The 'mark as read' link is also always visible per-card in legacy (toggling in place to a green-tick 'Marked as read' state via AJAX) whereas Nuxt hides the button entirely once read.
- nuxt: client/app/pages/notifications.vue:95-124,138-158
- develop: resources/views/partials/notification.blade.php:1-13; resources/sass/_notifications.scss:64-100
- FIX: Highlight unread cards (not read ones) and reuse the category icon-image treatment instead of a border colour; keep the mark-as-read control visible and toggle it to a 'Marked as read' confirmation instead of removing it.

## 7. [medium] /profile/edit, /profile/edit/{id}
Legacy Profile tab shows a different heading when an Administrator edits someone else's profile: `<h4>{name}'s profile</h4>` instead of the own-profile `<h3>Profile</h3>`. ProfileInfoTab.vue always renders the same '<h3>Profile</h3>' regardless of isOwnProfile.
- nuxt: client/app/components/profile/ProfileInfoTab.vue:111-114
- develop: resources/views/user/profile/profile.blade.php:1-12
- FIX: Use isOwnProfile to switch between t('general.profile') (h3) and an 'X's profile' (h4, general.other_profile) heading, matching the legacy conditional.

## 8. [medium] /profile/edit, /profile/edit/{id}
Every legacy profile-edit panel (Profile info, Skills, Change Photo, Password, Language, Admin, Email preferences, Calendars, Repair Directory) is wrapped in a distinctive `.edit-panel` box: white background, brand-coloured border, and a solid offset drop-shadow (`box-shadow: 5px 5px $brand-black`). None of the Nuxt tab components use this class anywhere, so the whole profile-edit page loses its bordered/shadowed card look across all tabs.
- nuxt: client/app/components/profile/ProfileTabs.vue:150-185
- develop: resources/views/user/profile/profile.blade.php:1; resources/views/user/profile/account.blade.php:1,46,86; resources/views/user/profile/email-preferences.blade.php:1; resources/views/user/profile/calendars.blade.php:1; resources/views/user/profile/repair-directory.blade.php:1; resources/sass/_edit.scss:1-16
- FIX: Wrap each tab panel's root element in a `.edit-panel`-equivalent class (or port the _edit.scss rule) so the bordered/shadowed card look is restored across the profile-edit page.

## 9. [medium] /login
Order of the auth-failure alert differs: legacy places the 'alert-danger' message AFTER the password field and BEFORE the forgot-password/create-account/submit row; Nuxt places its BAlert at the very top, above the email field. Also the responsive column split for the links/submit row is `col-6 col-md-8`/`col-6 col-md-4` in legacy (50/50 on mobile) vs a fixed `col-8`/`col-4` in Nuxt at all breakpoints.
- nuxt: client/app/pages/login.vue:86-88,114-133
- develop: resources/js/components/LoginPage.vue:16-45
- FIX: Move the error alert to sit between the password field and the actions row, and use responsive col-6/col-md-8 + col-6/col-md-4 classes for the links/submit split.

## 10. [medium] /user/recover, /user/reset
The shared 'plain' layout always renders the full LogoStatsHeader (logo + items-fixed/CO2/waste/events figures) on every guest page. Legacy only shows that full stats bar on /login and /user/register (`includes.info`); the recover and reset-password pages show only a small plain logo (`includes.logo`), no stats bar at all.
- nuxt: client/app/layouts/plain.vue:17-21; client/app/pages/user/reset.vue:6
- develop: resources/views/auth/forgot-password.blade.php:1-6; resources/views/auth/reset-password.blade.php:1 (both use includes.logo, not includes.info)
- FIX: Split the plain layout's header into a logo-only variant and a logo+stats variant, and use the stats variant only for /login and /user/register (guest home too), the logo-only variant for /user/recover and /user/reset.

## 11. [medium] /user/consent
consent_past_data ('Historical Repair Data') is rendered as a visible, interactive, required checkbox in Nuxt. In legacy's Auth::check() branch (the equivalent flow), this consent is auto-granted via a hidden always-checked input with no visible checkbox or user interaction at all - the checkbox markup is commented out.
- nuxt: client/app/pages/user/consent.vue:146-152
- develop: resources/views/auth/register-new.blade.php:233-241
- FIX: Confirm intent: either keep the visible checkbox as a deliberate consent-flow improvement (and document the divergence), or match legacy by auto-granting consent_past_data silently without a checkbox.

## 12. [medium] /forbidden
Legacy shows a 'broken-toaster' illustration image directly below the h1 heading; Nuxt has no image at all on the page.
- nuxt: client/app/pages/forbidden.vue:44-45
- develop: resources/views/user/forbidden.blade.php:9-11
- FIX: Add the broken-toaster illustration (or an equivalent asset) below the heading.

## 13. [low] /notifications
Pagination control differs: legacy renders Laravel's full numbered paginator; Nuxt shows only Prev/Next buttons with a 'page / lastPage' text indicator.
- nuxt: client/app/pages/notifications.vue:126-134
- develop: resources/views/user/notifications.blade.php:58-60
- FIX: Render numbered page links (BPagination) to match the legacy paginator widget rather than a bare prev/next pair.

## 14. [low] /profile, /profile/{id}
'[Not on Talk]' is rendered as a plain unwrapped text node in legacy (no <p>, no class) but Nuxt wraps it in `<p class="text-muted">`, giving it paragraph spacing and muted-grey styling it doesn't have in develop.
- nuxt: client/app/components/profile/PublicProfileView.vue:103-105
- develop: resources/views/user/profile-new.blade.php:20-22
- FIX: Render the string inline without the <p>/text-muted wrapper, or confirm the styled version is an accepted improvement.

## 15. [low] /profile/edit, /profile/edit/{id}
ProfilePhotoTab only renders the '<h4>Change Photo</h4>' heading; legacy also repeats the same string as a description paragraph underneath (`<p>@lang('profile.change_photo')</p>`).
- nuxt: client/app/components/profile/ProfilePhotoTab.vue:46-47
- develop: resources/views/user/profile/profile.blade.php:136-137
- FIX: Add the duplicate descriptive paragraph beneath the heading to match legacy, unless intentionally dropped as a copy fix.

## 16. [low] /profile/edit, /profile/edit/{id}
Delete-account danger alert is a 2-column row in legacy (col-md-8 text / col-md-4 button, each vertically centred in its own column) but a single flex row with `justify-content-between` in Nuxt - different responsive column split.
- nuxt: client/app/components/profile/DeleteAccountTab.vue:58-63
- develop: resources/views/user/profile/account.blade.php:181-190
- FIX: Use the same col-md-8/col-md-4 row split as legacy for the delete-account alert.

## 17. [low] /user/register
Legacy hides the whole newsletter checkbox (and its legend) on step 3 when the active session's network isn't the default 'restarters' network (`$showNewsletterSignup` false for white-labelled partner networks). Nuxt always renders the newsletter checkbox unconditionally.
- nuxt: client/app/pages/user/register.vue:358-368
- develop: resources/views/auth/register-new.blade.php:182-200; app/Http/Controllers/UserController.php:850
- FIX: Thread a showNewsletterSignup flag (from session config) through to step 3 and hide the newsletter checkbox/legend when it's false.

## 18. [low] /forbidden
Legacy's recourse options ('go back', 'return to dashboard', 'log out and back in') are two prose paragraphs with inline links; Nuxt turns them into a bulleted list and adds an extra 'Back to home' link that has no equivalent anywhere in the legacy page.
- nuxt: client/app/pages/forbidden.vue:69-88
- develop: resources/views/user/forbidden.blade.php:33-40
- FIX: Render the recovery options as prose sentences (matching legacy's two paragraphs) and drop the extra 'Back to home' link, or confirm it's an accepted addition.
