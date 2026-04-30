# Laravel 10 Restarters — Outstanding Testing Issues

Source: `C:\Users\edwar\Downloads\Laravel 10 Restarters testing.docx`
Reviewed: 2026-04-24 against develop branch.

## Status key
- ✅ Resolved
- ❌ Not fixed — needs work
- 🤔 Deferred / acceptable for now

---

## ❌ Unresolved bugs (need fixing)

### 1. ✅ Confirmed group member shown when inviting volunteers to event
**Area:** Events → Invite volunteers  
**Root cause:** `EventActions.vue` used `data-toggle="modal" data-target="#event-invite-to"` (jQuery) to open the old Blade modal, but that modal was removed from `view.blade.php`. Clicking the button did nothing. The new `EventInviteModal.vue` (which correctly calls `GET /api/v2/groups/{id}/volunteers?exclude_event={event_id}`) was only reachable via EventAttendance's "invite to join" link.  
**Fix:** Wired `EventActions.vue` to import and render `EventInviteModal`, triggered via `$refs.inviteModal.show()`. Also added `whereNotNull('user')` guard to `GroupController::getVolunteersForGroupv2` to prevent MySQL `NOT IN` with NULL silently emptying the list.  
**Test:** `tests/Integration/event.test.js` — "Invite volunteers modal opens from Event Actions dropdown"

### 2. ✅ Mark notifications as read does nothing
**Area:** General → Notifications  
**Status:** Verified working as of 2026-04-30 on restarters-dev. Tested both from the notifications page (/profile/notifications) and from the navbar sidebar popup. AJAX call fires correctly, card toggles to "✓ MARKED AS READ", and badge counter decrements. Bug must have been resolved implicitly by prior work.

---

## 🤔 Deferred / cosmetic (acceptable pre-release)

### 3. Skills multiselect — spurious down-arrow
**Area:** User profiles → Skills  
**Issue:** A downward chevron (▼) appears next to "Technical skills" in the multiselect, which is confusing. Should be removed. Also add hint text: "Ctrl-click to select multiple skills".  
**Decision:** Native multiselect is fine, but remove the arrow and add the hint. Low priority.

### 4. Admin user→group assignment lists all 1000+ groups
**Area:** Admin → Users → assign to group  
**Issue:** Search shows every group (1000+) rather than a scoped subset. Unusable.  
**Decision:** Kick the can — uncertain how much the feature is used. Open for discussion whether this feature is needed at all.

---

## ✅ Already resolved (for reference)

- Landing page layout
- Registration: duplicate email error
- Group view: translations singular/plural, description, environmental impact, past events date/time, invite toggle text
- Group edit: map marker, admin tag list
- Event view: title/details/map/description, environmental impact, add volunteer, invite all members at once
- Category page, role page

---

## Dev environment note

`restarters-dev` at https://restarters-dev.fly.dev/ now has a copy of the production database (imported 2026-04-24). Use production credentials to log in. Images won't load (stored in Tigris S3, not copied) but all data is present.
