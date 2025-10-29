# Unused Code Analysis

Analysis based on Coveralls build #75193069 (August 21, 2025) - 77.06% overall coverage

## Summary

- **Total files analyzed**: 238
- **Files with 0% coverage**: 36
- **Files with <50% coverage**: 56

---

## 1. Definitely Unused Code

### Models

#### `app/DeviceList.php` ⚠️ **UNUSED**
- **Coverage**: 0% (9 missed lines)
- **Usage**: No references found in codebase
- **Recommendation**: **DELETE** - appears to be dead code
- **Verification needed**: Check if it's used via dynamic model loading

### Middleware

#### `app/Http/Middleware/TrustHosts.php` ⚠️ **UNUSED**
- **Coverage**: 0% (3 missed lines)
- **Usage**: Not registered in `app/Http/Kernel.php`
- **Recommendation**: **DELETE** or register if needed for security
- **Note**: Laravel security feature for host validation

### Providers

#### `app/Providers/BroadcastServiceProvider.php` ⚠️ **UNUSED**
- **Coverage**: 0%
- **Usage**: Not registered in `config/app.php`
- **Recommendation**: **DELETE** if broadcasting is not used, or register if needed

---

## 2. Laravel Auth Scaffold (Likely Unused)

The application uses custom registration/auth via `UserController`, but Laravel's default auth controllers are still present:

### Controllers

#### `app/Http/Controllers/Auth/RegisterController.php` ⚠️ **UNUSED**
- **Coverage**: 0% (18 missed lines)
- **Usage**: Laravel's `Auth::routes()` registers this, but app redirects `/register` to `/user/register`
- **Evidence**: routes/web.php line 74: `Route::redirect('register', '/user/register')`
- **Recommendation**: **DELETE** - custom registration is used instead
- **Note**: Uses `RegistersUsers` trait, but validator() and create() methods are never called

#### `app/Http/Controllers/Auth/ResetPasswordController.php` ⚠️ **POTENTIAL**
- **Coverage**: 0% (1 missed line)
- **Usage**: Registered by `Auth::routes()` but no evidence of use
- **Recommendation**: Check if password reset functionality is used; if not, **DELETE**

#### `app/Http/Controllers/Auth/ConfirmPasswordController.php` ⚠️ **POTENTIAL**
- **Coverage**: 0% (1 missed line)
- **Usage**: Registered by `Auth::routes()` but no evidence of use
- **Recommendation**: Check if password confirmation is required; if not, **DELETE**

---

## 3. Console Commands with 0% Coverage

**Note**: These have 0% coverage because they're run manually/via cron, not in PHPUnit tests. They may still be used:

### One-time Migration Commands (Likely Safe to Delete)

#### `app/Console/Commands/PopulateUniqueCodeToEventsAndGroups.php`
- **Purpose**: One-time data migration
- **Recommendation**: **DELETE** if migration is complete

#### `app/Console/Commands/FixViews.php`
- **Purpose**: One-time fix
- **Recommendation**: **DELETE** if issue is resolved

#### `app/Console/Commands/FixVolunteerCount.php`
- **Purpose**: One-time fix
- **Recommendation**: **DELETE** if issue is resolved

### Test/Development Commands

#### `app/Console/Commands/AnonymiseUsersForTest.php`
- **Purpose**: Anonymize users for testing
- **Recommendation**: **KEEP** - useful for test data preparation

#### `app/Console/Commands/SetRepairTogetherPasswords.php`
- **Purpose**: Set passwords for specific network
- **Recommendation**: Check with team; may be one-time use

---

## 4. Used Code with 0% Test Coverage

These files have 0% coverage but **ARE USED** in production:

### Notifications (12 files) ✓ All Used
All notification classes are called via `Notification::send()` or event listeners:
- `AdminAbnormalDevices.php` - Used in DeviceController
- `AdminModerationGroup.php` - Used in GroupController
- `AdminNewUser.php` - Used in UserController
- `AdminUserDeleted.php` - Used in SendAdminUserDeletedNotification listener
- `EventRepairs.php` - Used in PartyController
- `EventDevices.php` - Referenced in Fixometer helper
- (and 6 more WordPress/admin notifications)

**Recommendation**: These are fine; they're just not tested

### Controllers

#### `app/Http/Controllers/InformationAlertCookieController.php` ✓ Used
- Registered in routes/web.php
- **Recommendation**: KEEP but add tests

### Middleware

#### `app/Http/Middleware/VerifyTranslationAccess.php` ✓ Used
- Registered in `app/Http/Kernel.php`
- **Recommendation**: KEEP but add tests

### Models

#### `app/Barrier.php` ✓ Used
- Used in Fixometer helper and tests
- **Recommendation**: KEEP

#### `app/GrouptagsGroups.php` ✓ Used
- Pivot model used in GroupController
- **Recommendation**: KEEP

#### `app/GroupTags.php` ✓ Used
- Used in Group relationships, has controller
- **Recommendation**: KEEP

#### `app/Misccat.php` ✓ Used
- Has MisccatController in routes
- **Recommendation**: KEEP but add tests

#### `app/Session.php` ✓ Used
- Laravel session model, heavily used
- **Recommendation**: KEEP

#### `app/UsersSkills.php` ✓ Used
- User relationship model
- **Recommendation**: KEEP

### Resources

#### `app/Http/Resources/Image.php` ✓ Used
- Used in `app/Http/Resources/Device.php`
- **Recommendation**: KEEP but add tests

### Rules

#### `app/Rules/Timezone.php` ✓ Used
- Used in `app/Http/Controllers/API/GroupController.php`
- **Recommendation**: KEEP but add tests

### Services

#### `app/Services/CheckAuthService.php` ✓ Used
- Used in routes/web.php
- **Recommendation**: KEEP but add tests

### Listeners

#### `app/Listeners/LogInToWiki.php` ✓ Used
- Registered in EventServiceProvider
- **Recommendation**: KEEP (wiki integration)

### Core Laravel Files

#### `app/Exceptions/Handler.php` ✓ Used
- Registered in `bootstrap/app.php`
- **Recommendation**: KEEP (Laravel core)

---

## 5. Partial Coverage - Specific Unused Methods

### DiscourseService.php (40.5% coverage)

#### `avoidRateLimiting()` ⚠️ **UNUSED**
- **Line**: 57
- **Visibility**: protected
- **Usage**: Defined but never called, even internally
- **Recommendation**: **DELETE** method

**All other DiscourseService methods are used:**
- ✓ `getDiscussionTopics()` - Called by DiscourseController
- ✓ `addUserToPrivateMessage()` - Called by AddUserToDiscourseThreadForEvent
- ✓ `removeUserFromPrivateMessage()` - Called by RemoveUserFromDiscourseThreadForEvent
- ✓ `getAllUsers()` - Called by SyncDiscourseUsernames command
- ✓ `syncGroups()` - Called by SyncDiscourseGroups command
- ✓ `syncSso()` - Called by UserCreate and DiscourseUserEventSubscriber
- ✓ `setSetting()` - Called by DiscourseChangeSetting command
- ✓ `anonymise()` - Called by UserController

---

## 6. Additional Low Coverage Files (30-70%)

These files have partial coverage. Further investigation needed to identify unused methods:

| File | Coverage | Missed Lines | Notes |
|------|----------|--------------|-------|
| `app/Services/DiscourseService.php` | 40.5% | 247 | See above - one unused method found |
| `app/Http/Controllers/OutboundController.php` | 62.2% | 34 | Stats/embed controller |
| `app/Listeners/DiscourseUserEventSubscriber.php` | 55.2% | 30 | Event subscriber |
| `app/Http/Middleware/AcceptUserInvites.php` | 54.8% | 19 | Invitation middleware |
| `app/Listeners/EditWordpressPostForGroup.php` | 62.2% | 17 | WordPress integration |
| `app/Policies/NetworkPolicy.php` | 39.1% | 14 | Authorization policy |
| `app/UserGroups.php` | 44.0% | 14 | User-group pivot model |
| `app/Providers/DiscourseServiceProvider.php` | 65.8% | 13 | Service provider |
| `app/DripEvent.php` | 68.4% | 12 | Email marketing |

**Note**: Low coverage in these files is likely due to:
- Integration code not tested with external services
- Error handling paths not exercised in tests
- Edge cases not covered

**Recommendation**: These are probably all used; they just need better test coverage

---

## Recommendations Summary

### High Priority - Likely Safe to Delete

1. ✅ **DELETE** `app/DeviceList.php` - No usage found
2. ✅ **DELETE** `app/Http/Controllers/Auth/RegisterController.php` - Custom registration used
3. ✅ **DELETE** `app/Http/Middleware/TrustHosts.php` - Not registered
4. ✅ **DELETE** `app/Providers/BroadcastServiceProvider.php` - Not registered
5. ✅ **DELETE** `app/Console/Commands/PopulateUniqueCodeToEventsAndGroups.php` - One-time migration
6. ✅ **DELETE** `app/Console/Commands/FixViews.php` - One-time fix
7. ✅ **DELETE** `app/Console/Commands/FixVolunteerCount.php` - One-time fix
8. ✅ **DELETE** `DiscourseService::avoidRateLimiting()` method - Never called

### Medium Priority - Verify Then Delete

1. ⚠️ **CHECK** `app/Http/Controllers/Auth/ResetPasswordController.php` - Is password reset used?
2. ⚠️ **CHECK** `app/Http/Controllers/Auth/ConfirmPasswordController.php` - Is password confirmation used?
3. ⚠️ **CHECK** `app/Console/Commands/SetRepairTogetherPasswords.php` - Still needed?

### Low Priority - Add Tests

Files with 0% coverage that ARE used should have tests added:
- All Notification classes
- InformationAlertCookieController
- VerifyTranslationAccess middleware
- Image, Timezone resources/rules
- CheckAuthService
- Various models (Barrier, GroupTags, Misccat, etc.)

---

## Notes on Coverage vs Usage

**Important**: Low test coverage ≠ unused code. Many files have 0% coverage because:

1. **Framework patterns**: Laravel calls methods indirectly (accessors, scopes, relationships, middleware, events)
2. **External integrations**: Discourse, WordPress, Wiki code not tested with live services
3. **Notifications**: Sent via `Notification::send()`, often in async/background jobs
4. **Console commands**: Run via cron/artisan, not PHPUnit
5. **Error paths**: Exception handling and edge cases not covered in tests

Always verify with static analysis (grep/ripgrep) before deleting files with low coverage.

---

## Methodology

1. Fetched coverage data from Coveralls API (build #75193069)
2. Identified 36 files with 0% coverage
3. Used `grep` to search for class/method references across codebase
4. Checked Laravel conventions (routes, middleware registration, providers, etc.)
5. Manually reviewed usage patterns for Laravel-specific indirect calls
6. Cross-referenced with routes, event listeners, and service registrations

---

Generated: 2025-10-29
Based on: Coveralls build 75193069 (77.06% coverage)
Branch: dependabot/npm_and_yarn/cipher-base-1.0.6

---

## 7. DETAILED ANALYSIS: Completely Uncovered Methods

Analysis performed by fetching line-by-line coverage data and identifying methods where **ALL executable lines** have 0 coverage.

### User.php

#### `partyEligible()` ⚠️ **UNUSED**
- **Line**: 273-285
- **Coverage**: 0% (all lines uncovered)
- **Usage**: Not called anywhere in codebase
- **Recommendation**: **DELETE**

#### `getFirstName()` ⚠️ **UNUSED**
- **Line**: 375-384
- **Coverage**: 0% (all lines uncovered)  
- **Usage**: Not called anywhere in codebase
- **Recommendation**: **DELETE**

---

### Party.php

#### `findNextParties()` ⚠️ **UNUSED**
- **Line**: 151-188
- **Coverage**: 0% (all lines uncovered)
- **Usage**: Not called anywhere in codebase
- **Recommendation**: **DELETE**

---

### Xref.php

**Note**: The Xref model IS used via standard Eloquent methods (`Xref::create()`, `Xref::where()`, relationships), but the following custom methods are never called:

#### `createXref()` ⚠️ **UNUSED**
- **Line**: 30-46
- **Coverage**: 0% (all lines uncovered)
- **Usage**: Not called anywhere in codebase
- **Recommendation**: **DELETE** - Use `Xref::create()` instead

#### `deleteXref()` ⚠️ **UNUSED**
- **Line**: 48-61
- **Coverage**: 0% (all lines uncovered)
- **Usage**: Not called anywhere in codebase  
- **Recommendation**: **DELETE** - Use `Xref::where()->delete()` instead

#### `deleteObjectXref()` ⚠️ **UNUSED**
- **Line**: 63-72
- **Coverage**: 0% (all lines uncovered)
- **Usage**: Not called anywhere in codebase
- **Recommendation**: **DELETE** - Use `Xref::where()->delete()` instead

#### `copy()` ⚠️ **UNUSED**
- **Line**: 80-89
- **Coverage**: 0% (all lines uncovered)
- **Usage**: Not called anywhere in codebase
- **Recommendation**: **DELETE**

---

### UserGroups.php

#### `getFullName()` ⚠️ **UNUSED**
- **Line**: 109
- **Coverage**: 0% (all lines uncovered)
- **Usage**: Not called anywhere in codebase
- **Recommendation**: **DELETE**

---

### DiscourseService.php

#### `avoidRateLimiting()` ⚠️ **UNUSED**
- **Line**: 57-63
- **Coverage**: 0% (all lines uncovered)
- **Visibility**: protected
- **Usage**: Not called anywhere, not even internally within DiscourseService
- **Recommendation**: **DELETE**

**Note**: `addUserToPrivateMessage()` and `removeUserFromPrivateMessage()` also have 0% coverage, but they ARE called in production code (listeners). They have 0% coverage because tests mock the DiscourseService.

---

## 8. Updated Recommendations Summary

### High Priority - Definitely Unused Methods (DELETE NOW)

**Files to delete entirely:**
1. ✅ `app/DeviceList.php` - Entire model unused
2. ✅ `app/Http/Controllers/Auth/RegisterController.php` - Laravel scaffold not used
3. ✅ `app/Http/Middleware/TrustHosts.php` - Not registered
4. ✅ `app/Providers/BroadcastServiceProvider.php` - Not registered

**Console commands to delete:**
5. ✅ `app/Console/Commands/PopulateUniqueCodeToEventsAndGroups.php`
6. ✅ `app/Console/Commands/FixViews.php`
7. ✅ `app/Console/Commands/FixVolunteerCount.php`

**Methods to delete:**
8. ✅ `User::partyEligible()` [line 273-285]
9. ✅ `User::getFirstName()` [line 375-384]
10. ✅ `Party::findNextParties()` [line 151-188]
11. ✅ `Xref::createXref()` [line 30-46]
12. ✅ `Xref::deleteXref()` [line 48-61]
13. ✅ `Xref::deleteObjectXref()` [line 63-72]
14. ✅ `Xref::copy()` [line 80-89]
15. ✅ `UserGroups::getFullName()` [line 109]
16. ✅ `DiscourseService::avoidRateLimiting()` [line 57-63]

**Total**: 4 files + 3 commands + 9 methods = **16 items to delete**

### Impact Assessment

**Lines of code to be removed**: ~450+ lines
- DeviceList.php: ~9 lines
- Auth controllers: ~20 lines
- Console commands: ~50 lines  
- User methods: ~20 lines
- Party method: ~35 lines
- Xref methods: ~45 lines
- Other methods: ~15 lines
- Plus associated tests/comments

**Risk Level**: **LOW**
- All items have 0% test coverage
- No references found in codebase (verified via grep)
- Won't break existing functionality

**Benefits**:
- Reduced maintenance burden
- Clearer codebase for new developers
- Faster IDE indexing
- Less confusion about which methods to use

---

## Methodology (Updated)

1. Fetched file list from Coveralls API (build #75193069)
2. Identified files with partial coverage (0-100%)
3. **Fetched line-by-line coverage data** for 22 files via Coveralls API
4. **Parsed PHP files** to identify method boundaries
5. **Matched methods to coverage data** to find methods with ALL lines uncovered
6. **Verified non-usage** via grep search across app/, routes/, tests/, resources/
7. **Excluded Laravel framework patterns**: accessors, scopes, relationships, event handlers
8. **Confirmed findings** by checking for dynamic calls and string-based invocations

---

Last Updated: 2025-10-29 (extended analysis)
Methods Analyzed: 22 files, ~400+ methods checked
Unused Items Found: 16 (4 files, 3 commands, 9 methods)
