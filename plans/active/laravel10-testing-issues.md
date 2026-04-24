# Laravel 10 Restarters — Outstanding Testing Issues

Source: `C:\Users\edwar\Downloads\Laravel 10 Restarters testing.docx`
Reviewed: 2026-04-24 against develop branch.

## Status key
- ✅ Resolved
- ❌ Not fixed — needs work
- 🤔 Deferred / acceptable for now

---

## ❌ Unresolved bugs (need fixing)

### 1. Confirmed group member shown when inviting volunteers to event
**Area:** Events → Invite volunteers  
**Steps:** Given Neil M is already attending an event for Ulverston Repair Cafe, when inviting members to the event, Neil M should not appear as an option.  
**Status:** Still broken as of doc review.  
**Where to look:** `EventController` invite logic / volunteer query — filter out users already confirmed for the event.

### 2. Mark notifications as read does nothing
**Area:** General → Notifications  
**Steps:** Click "mark as read" on a notification.  
**Expected:** Notification marked read.  
**Actual:** Nothing happens (no request or request fails silently).  
**Status:** Not resolved as of 2026-01-22. Needs JS/backend investigation.

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
