<?php

use App\Http\Controllers\API;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/homepage_data', function () { // Used from DeviceController, tested.
    return App\Http\Controllers\ApiController::homepage_data();
});

Route::get('/party/{id}/stats', function ($id) { // Used from TRP.org.
    return App\Http\Controllers\ApiController::partyStats($id);
});

Route::get('/group/{id}/stats', function ($id) { // Used from TRP.org.
    return App\Http\Controllers\ApiController::groupStats($id);
});

Route::get('/outbound/info/{type}/{id}/{format?}', function ($type, $id, $format = 'fixometer') { // Used from share plugins, tested.
    return App\Http\Controllers\OutboundController::info($type, $id, $format);
});

// Tus resumable-upload protocol endpoint. Deliberately NOT behind auth:api - see
// TusController for why. It lives in routes/api.php, so it uses the "api" middleware
// group which does not include CSRF verification at all (tus clients like Uppy issue
// POST/PATCH/HEAD/DELETE requests that cannot carry a Laravel CSRF token anyway). The
// uploaded file only becomes "attached" to a user once a separate, authenticated call
// (e.g. POST /api/v2/users/me/photo) references its upload key.
Route::match(['get', 'post', 'patch', 'head', 'delete', 'options'], '/tus', [App\Http\Controllers\TusController::class, 'serve']);
Route::match(['get', 'post', 'patch', 'head', 'delete', 'options'], '/tus/{any}', [App\Http\Controllers\TusController::class, 'serve'])->where('any', '.*');

Route::middleware('auth:sanctum,api')->group(function () {
    Route::get('/users/me', [ApiController::class, 'getUserInfo']); // Not used but worth keeping and tested.
    Route::get('/users', [ApiController::class, 'getUserList']);  // Not used but worth keeping and tested.
    Route::get('/users/changes', [API\UserController::class, 'changes']); // Used by Zapier

    Route::get('/networks/{network}/stats/', [API\NetworkController::class, 'stats']); // Used by RepairTogether.

    Route::prefix('/groups')->group(function() {

        Route::get('/', [API\GroupController::class, 'getGroupList']); // Not used but worth keeping and tested.
        Route::get('/changes', [API\GroupController::class, 'getGroupChanges']); // Used by Zapier
        Route::get('/network/', [API\GroupController::class, 'getGroupsByUsersNetworks']); // Used by Repair Together.
    });

    Route::prefix('/events')->group(function() {
        Route::get('/network/{date_from?}/{date_to?}', [API\EventController::class, 'getEventsByUsersNetworks']); // Used by Repair Together.
        Route::get('{id}/volunteers', [API\EventController::class, 'listVolunteers']);
        Route::put('{id}/volunteers', [API\EventController::class, 'addVolunteer']);
    });

    Route::get('/usersgroups/changes', [API\UserGroupsController::class, 'changes']); // Used by Zapier
    Route::delete('/usersgroups/{id}', [API\UserGroupsController::class, 'leave']); // Used by Vue client.
});

Route::get('/devices/{page}/{size}', [App\Http\Controllers\ApiController::class, 'getDevices']); // Used by Vue client.

// Notifications info.  We don't authenticate this, as API keys don't exist for all users.  There's no real privacy
// issue with exposing the number of outstanding notifications.
Route::get('/users/{id}/notifications', [API\UserController::class, 'notifications']);

// Top Talk topics.  Doesn't need authentication either.
Route::get('/talk/topics/{tag?}', [API\DiscourseController::class, 'discussionTopics']);

// Timezones
Route::get('/timezones', [App\Http\Controllers\ApiController::class, 'timezones']);

// We are working towards a new and more coherent API.
// VerifyUserConsentApi gates v2 mutations for authenticated-but-unconsented
// users (self-exempts reads, the auth family and /session).
Route::prefix('v2')->middleware(\App\Http\Middleware\VerifyUserConsentApi::class)->group(function() {
    Route::middleware(\App\Http\Middleware\APISetLocale::class)->group(function() {
        // Client bootstrap: current user + config + flags.
        Route::get('/session', [API\SessionController::class, 'getSessionv2']);
        Route::middleware('auth:sanctum,api')->patch('/session', [API\SessionController::class, 'patchSessionv2']);

        // Dashboard: the current user's groups, nearby groups and upcoming events.
        Route::middleware('auth:sanctum,api')->get('/dashboard', [API\DashboardController::class, 'indexv2']);

        // Maps proxy — keeps the Google API key server-side. Same controller/params as the
        // session-auth /maps/* routes in routes/web.php (kept working until cutover).
        Route::middleware('auth:sanctum,api')->prefix('/maps')->group(function() {
            Route::get('/autocomplete', [App\Http\Controllers\MapsProxyController::class, 'autocomplete']);
            Route::get('/place-details', [App\Http\Controllers\MapsProxyController::class, 'placeDetails']);
        });

        // Token-based auth for the SPA. No session, no CSRF — see AuthController.
        Route::prefix('/auth')->group(function() {
            Route::middleware('auth:sanctum,api')->post('/consent', [API\AuthController::class, 'consentv2']);
            Route::middleware('throttle:auth')->group(function() {
                Route::post('/login', [API\AuthController::class, 'loginv2']);
                Route::post('/register', [API\AuthController::class, 'registerv2']);
                Route::post('/password/forgot', [API\AuthController::class, 'forgotPasswordv2']);
                Route::post('/password/reset', [API\AuthController::class, 'resetPasswordv2']);
                Route::get('/password/recovery/{token}', [API\AuthController::class, 'recoveryInfov2']);
            });
            Route::get('/email-available', [API\AuthController::class, 'emailAvailablev2']);
            Route::middleware('auth:sanctum,api')->group(function() {
                Route::post('/logout', [API\AuthController::class, 'logoutv2']);
                Route::post('/sso-ticket', [API\AuthController::class, 'ssoTicketv2']);
            });
        });

        Route::middleware('auth:sanctum,api')->post('/invites/claim', [API\AuthController::class, 'claimInvitev2']);

        Route::prefix('/groups')->group(function() {
            // Must be registered before the {id} routes below, or "nearby"/"summary"
            // would be captured as an {id} value.
            Route::middleware('auth:sanctum,api')->get('/nearby', [API\GroupMembershipController::class, 'nearbyv2']);
            Route::get('/summary', [API\GroupController::class, 'listSummaryv2']);
            Route::get('/names', [API\GroupController::class, 'listNamesv2']);
            Route::get('/tags', [API\GroupController::class, 'listTagsv2']);
            Route::get('{id}/events', [API\GroupController::class, 'getEventsForGroupv2']);
            Route::get('{id}/stats', [API\GroupMembershipController::class, 'statsv2']);
            Route::get('{id}', [API\GroupController::class, 'getGroupv2']);
            Route::post('', [API\GroupController::class, 'createGroupv2']);
            Route::patch('{id}', [API\GroupController::class, 'updateGroupv2']);

            Route::get('{id}/volunteers', [API\GroupController::class, 'getVolunteersForGroupv2']);
            Route::middleware('auth:sanctum,api')->group(function ()
            {
                Route::patch('{id}/volunteers/{iduser}', [API\GroupController::class, 'patchVolunteerForGroupv2']);
                Route::delete('{id}/volunteers/{iduser}', [API\GroupController::class, 'deleteVolunteerForGroupv2']);

                Route::post('{id}/members/me', [API\GroupMembershipController::class, 'joinv2']);
                Route::delete('{id}/members/me', [API\GroupMembershipController::class, 'leavev2']);
                Route::post('{id}/invites', [API\GroupMembershipController::class, 'invitesv2']);
                Route::delete('{id}', [API\GroupMembershipController::class, 'archivev2']);
                Route::post('{id}/images', [API\GroupMembershipController::class, 'uploadImagev2']);
                Route::delete('{id}/images/{idimages}', [API\GroupMembershipController::class, 'deleteImagev2']);
            });
        });

        Route::prefix('/events')->group(function() {
            Route::get('{id}/attendees', [API\EventController::class, 'attendeesv2']);
            // Audit trail for the edit page's Event log tab - Administrator
            // only, matching edit.blade.php's `$audits && hasRole(Administrator)`.
            Route::get('{id}/audits', [API\EventController::class, 'auditsv2']);
            Route::get('{id}/devices', [API\EventController::class, 'devicesv2']);
            Route::get('{id}', [API\EventController::class, 'getEventv2']);
            Route::post('', [API\EventController::class, 'createEventv2']);
            Route::patch('{id}', [API\EventController::class, 'updateEventv2']);

            Route::middleware('auth:sanctum,api')->group(function() {
                Route::post('{id}/attendees/me', [API\EventAttendanceController::class, 'rsvpv2']);
                Route::delete('{id}/attendees/me', [API\EventAttendanceController::class, 'cancelRsvpv2']);
                Route::patch('{id}/volunteers/{iduser}', [API\EventAttendanceController::class, 'patchVolunteerv2']);
                Route::delete('{id}/volunteers/{idevents_users}', [API\EventAttendanceController::class, 'deleteVolunteerv2']);
                Route::post('{id}/request-review', [API\EventAttendanceController::class, 'requestReviewv2']);
                Route::post('{id}/invites', [API\EventAttendanceController::class, 'invitesv2']);
                Route::post('{id}/images', [API\EventAttendanceController::class, 'uploadImagev2']);
                Route::delete('{id}/images/{idimages}', [API\EventAttendanceController::class, 'deleteImagev2']);
                Route::delete('{id}', [API\EventAttendanceController::class, 'deleteEventv2']);
            });
        });

        Route::prefix('/users')->group(function() {
            Route::middleware('auth:sanctum,api')->group(function() {
                Route::get('', [API\UserController::class, 'listUsersv2']);
                Route::post('', [API\UserController::class, 'createUserv2']);
                // PII-safe public profile - any logged-in user may view any
                // profile (mirrors the legacy UserController::index, whose
                // only gate was the route's auth middleware).
                Route::get('/{id}', [API\UserController::class, 'getPublicProfilev2']);

                // Admin id-scoped mirrors of the /users/me/* self-service endpoints below -
                // each authorises via UserPolicy::update/delete (self-or-Administrator), so
                // the underlying logic can never drift between the self and admin paths (see
                // the shared private apply*()/*Data() methods on UserController).
                // whereNumber('id') keeps these from shadowing the /users/me/*
                // self-service group below - without it, 'me' matches {id} and
                // every /users/me/* request is misrouted here (and 500s on the
                // int $id type hint).
                Route::get('/{id}/profile', [API\UserController::class, 'getUserProfilev2'])->whereNumber('id');
                Route::patch('/{id}/profile', [API\UserController::class, 'updateUserProfilev2'])->whereNumber('id');
                Route::patch('/{id}/password', [API\UserController::class, 'updateUserPasswordv2'])->whereNumber('id');
                Route::get('/{id}/skills', [API\UserController::class, 'getUserSkillsv2'])->whereNumber('id');
                Route::patch('/{id}/skills', [API\UserController::class, 'updateUserSkillsv2'])->whereNumber('id');
                Route::get('/{id}/preferences', [API\UserController::class, 'getUserPreferencesv2'])->whereNumber('id');
                Route::patch('/{id}/preferences', [API\UserController::class, 'updateUserPreferencesv2'])->whereNumber('id');
                Route::delete('/{id}', [API\UserController::class, 'deleteUserv2'])->whereNumber('id');
            });
        });

        Route::prefix('/users/me')->middleware('auth:sanctum,api')->group(function() {
            Route::post('/onboarding-complete', [API\UserController::class, 'onboardingCompletev2']);
            Route::get('/groups', [API\UserController::class, 'getMyGroupsv2']);
            Route::get('/events', [API\UserController::class, 'getMyEventsv2']);
            Route::get('/preferences', [API\UserController::class, 'getMyEmailPreferencesv2']);
            Route::patch('/preferences', [API\UserController::class, 'updateMyEmailPreferencesv2']);
            Route::get('/calendars', [API\UserController::class, 'getMyCalendarsv2']);
            Route::get('/language', [API\UserController::class, 'getMyLanguagev2']);
            Route::patch('/language', [API\UserController::class, 'updateMyLanguagev2']);
            Route::get('/notifications', [API\UserController::class, 'getMyNotificationsv2']);
            Route::post('/notifications/read', [API\UserController::class, 'markMyNotificationsReadv2']);
            Route::get('/profile', [API\UserController::class, 'getMyProfilev2']);
            Route::patch('/profile', [API\UserController::class, 'updateMyProfilev2']);
            Route::get('/skills', [API\UserController::class, 'getMySkillsv2']);
            Route::patch('/skills', [API\UserController::class, 'updateMySkillsv2']);
            Route::patch('/password', [API\UserController::class, 'updateMyPasswordv2']);
            Route::post('/photo', [API\UserController::class, 'updateMyPhotov2']);
            Route::delete('/', [API\UserController::class, 'deleteMyAccountv2']);
        });

        Route::middleware('auth:sanctum,api')->group(function() {
            Route::get('/users/{id}/repair-directory-options', [API\UserController::class, 'getRepairDirOptionsv2']);
            Route::patch('/users/{id}/repair-directory-role', [API\UserController::class, 'updateRepairDirRolev2']);
            Route::get('/users/{id}/admin-settings', [API\UserController::class, 'getAdminSettingsv2']);
            Route::patch('/users/{id}/admin-settings', [API\UserController::class, 'updateAdminSettingsv2']);
        });

        Route::prefix('/networks')->group(function() {
            Route::get('/', [API\NetworkController::class, 'getNetworksv2']);
            Route::get('{id}', [API\NetworkController::class, 'getNetworkv2']);
            Route::get('{id}/groups', [API\NetworkController::class, 'getNetworkGroupsv2']);
            Route::get('{id}/events', [API\NetworkController::class, 'getNetworkEventsv2']);
            Route::get('{id}/tags', [API\NetworkController::class, 'getNetworkTagsv2']);
            Route::get('{id}/stats', [API\NetworkController::class, 'getNetworkStatsv2']);
            Route::middleware('auth:sanctum,api')->group(function() {
                Route::post('{id}/tags', [API\NetworkController::class, 'createNetworkTagv2']);
                Route::put('{id}/tags/{tagId}', [API\NetworkController::class, 'updateNetworkTagv2']);
                Route::delete('{id}/tags/{tagId}', [API\NetworkController::class, 'deleteNetworkTagv2']);
                Route::post('{id}/logo', [API\NetworkController::class, 'uploadLogov2']);
                Route::post('{id}/groups', [API\NetworkController::class, 'associateGroupsv2']);
            });
        });

        Route::prefix('/moderate')->group(function() {
            Route::middleware('auth:sanctum,api')->group(function ()
            {
                Route::get('/groups', [API\GroupController::class, 'moderateGroupsv2']);
                Route::get('/events', [API\EventController::class, 'moderateEventsv2']);
            });
        });

        Route::get('/items', [API\ItemController::class, 'listItemsv2']);

        Route::prefix('/alerts')->group(function() {
            Route::get('/', [API\AlertController::class, 'listAlertsv2']);
            Route::put('/', [API\AlertController::class, 'addAlertv2']);
            Route::patch('/{id}', [API\AlertController::class, 'updateAlertv2']);
        });

        Route::prefix('/devices')->group(function() {
            // Must be registered before the {id} routes below, or these would be captured as an
            // {id} value.
            Route::get('/options', [API\DeviceController::class, 'optionsv2']);
            Route::get('', [API\DeviceController::class, 'listDevicesv2']);

            Route::get('{id}', [API\DeviceController::class, 'getDevicev2']);
            Route::post('', [API\DeviceController::class, 'createDevicev2']);
            Route::patch('{id}', [API\DeviceController::class, 'updateDevicev2']);
            Route::delete('{id}', [API\DeviceController::class, 'deleteDevicev2']);

            Route::middleware('auth:sanctum,api')->group(function() {
                Route::post('{id}/images', [API\DeviceController::class, 'uploadImagev2']);
                Route::delete('{id}/images/{idimages}', [API\DeviceController::class, 'deleteImagev2']);
            });
        });

        Route::prefix('/stats')->group(function() {
            Route::get('/latest-repaired-event', [API\DeviceController::class, 'latestRepairedEventv2']);
            // CORS-enabled (this whole tree is under /api/*) proxy for the
            // canvas-painted social-share image's background/decoration
            // PNGs - see StatsShareImageController's own doc comment for why
            // this can't just be a plain public/ static asset.
            Route::get('/share-image/{filename}', [API\StatsShareImageController::class, 'shareImagev2']);
        });

        Route::prefix('/brands')->group(function() {
            Route::get('/', [API\BrandController::class, 'listBrandsv2']);
            Route::get('{id}', [API\BrandController::class, 'getBrandv2']);
            Route::middleware('auth:sanctum,api')->group(function() {
                Route::post('/', [API\BrandController::class, 'createBrandv2']);
                Route::put('{id}', [API\BrandController::class, 'updateBrandv2']);
                Route::delete('{id}', [API\BrandController::class, 'deleteBrandv2']);
            });
        });

        Route::prefix('/skills')->group(function() {
            Route::get('/', [API\SkillController::class, 'listSkillsv2']);
            Route::get('{id}', [API\SkillController::class, 'getSkillv2']);
            Route::middleware('auth:sanctum,api')->group(function() {
                Route::post('/', [API\SkillController::class, 'createSkillv2']);
                Route::put('{id}', [API\SkillController::class, 'updateSkillv2']);
                Route::delete('{id}', [API\SkillController::class, 'deleteSkillv2']);
            });
        });

        Route::prefix('/group-tags')->group(function() {
            Route::get('/', [API\GroupTagController::class, 'listGroupTagsv2']);
            Route::get('{id}', [API\GroupTagController::class, 'getGroupTagv2']);
            Route::middleware('auth:sanctum,api')->group(function() {
                Route::post('/', [API\GroupTagController::class, 'createGroupTagv2']);
                Route::put('{id}', [API\GroupTagController::class, 'updateGroupTagv2']);
                Route::delete('{id}', [API\GroupTagController::class, 'deleteGroupTagv2']);
            });
        });

        Route::prefix('/categories')->group(function() {
            Route::get('/', [API\CategoryController::class, 'listCategoriesv2']);
            Route::get('{id}', [API\CategoryController::class, 'getCategoryv2']);
            Route::middleware('auth:sanctum,api')->group(function() {
                Route::put('{id}', [API\CategoryController::class, 'updateCategoryv2']);
            });
        });

        Route::get('/category-clusters', [API\CategoryController::class, 'listCategoryClustersv2']);

        Route::middleware('auth:sanctum,api')->group(function() {
            Route::get('/roles', [API\RoleController::class, 'listRolesv2']);
            Route::get('/roles/{id}', [API\RoleController::class, 'getRolev2']);
            Route::put('/roles/{id}/permissions', [API\RoleController::class, 'updateRolePermissionsv2']);
            Route::get('/permissions', [API\RoleController::class, 'listPermissionsv2']);
        });

        // Admin PR-preview-deploy tooling (moved off the Blade web route so the
        // bearer-authenticated SPA admin can drive it). Administrator-gated in
        // the controller.
        Route::middleware('auth:sanctum,api')->group(function() {
            Route::get('/admin/preview-deploys', [API\PreviewDeployController::class, 'index']);
            Route::post('/admin/preview-deploys', [API\PreviewDeployController::class, 'deploy']);
        });
    });
});