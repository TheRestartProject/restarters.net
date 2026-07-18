<?php

use App\Http\Controllers\CalendarEventsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The frontend is the separate Nuxt SPA (client/); Laravel serves the API
| (routes/api.php) plus this deliberately small web surface:
|   (a) the SSO bridge that turns an API-issued ticket into a web session,
|   (b) emailed / shared deep-link redirectors into the SPA,
|   (c) anonymous data exports and calendar feeds,
|   (d) the embeddable stats widgets partners iframe,
|   (e) a catch-all that sends any other browser navigation to the SPA.
| (Admin preview-deploy tooling moved to /api/v2 + the SPA.)
| See docs/nuxt-migration/cutover-checklist.md for what died here and why.
|
*/

// SSO bridge: exchanges a one-time API-issued ticket for a Laravel web session
// during a top-level navigation, so Discourse SSO and MediaWiki silent login
// keep working for the token-authenticated SPA. GET only (CSRF-exempt by verb).
Route::get('/auth/bridge', [App\Http\Controllers\Auth\BridgeController::class, 'bridge']);

// Emailed / shared deep links: thin redirectors into the SPA (design §5, F2-5).
// The URLs are frozen because they live in already-sent emails and shared
// invite links; the DB side-effects moved to /api/v2 (invites/claim etc.).
Route::get('user/reset', [UserController::class, 'reset']);
Route::get('user/recover', [UserController::class, 'recover']);
Route::get('user/register/{hash?}', [UserController::class, 'getRegister']);
Route::get('group/accept-invite/{id}/{hash}', [GroupController::class, 'confirmInvite']);
Route::get('party/accept-invite/{id}/{hash}', [PartyController::class, 'confirmInvite']);
Route::get('party/invite/{code}', [PartyController::class, 'confirmCodeInvite']);
Route::get('group/invite/{code}', [GroupController::class, 'confirmCodeInvite']);

// Device export is also called from https://therestartproject.org/download-dataset,
// so we allow anonymous access.
Route::prefix('export')->group(function () {
    Route::get('/devices/event/{id}', [ExportController::class, 'devicesEvent']);
    Route::get('/devices/group/{id}', [ExportController::class, 'devicesGroup']);
    Route::get('/devices', [ExportController::class, 'devices']);
    Route::get('/groups/{id}/events', [ExportController::class, 'groupEvents']);
    Route::get('/networks/{id}/events', [ExportController::class, 'networkEvents']);
});

// Calendar routes do not require authentication.
// (You would not be able to subscribe from a calendar application if they did.)
Route::prefix('calendar')->group(function () {
    Route::get('/user/{calendar_hash}', [CalendarEventsController::class, 'allEventsByUser'])->name('calendar-events-by-user');
    Route::get('/group/{group}', [CalendarEventsController::class, 'allEventsByGroup'])->name('calendar-events-by-group');
    Route::get('/network/{network}', [CalendarEventsController::class, 'allEventsByNetwork'])->name('calendar-events-by-network');
    Route::get('/group-area/{area}', [CalendarEventsController::class, 'allEventsByArea'])->name('calendar-events-by-area');
    Route::get('/all-events/{hash_env}', [CalendarEventsController::class, 'allEvents'])->name('calendar-events-all');
});

// Embeddable stats widgets (iframed by partner sites; layouts header_plain/
// header_nocookie/footer and the retained Blade views exist for these).
// NOTE: /group-tag/stats/{id} is deliberately gone — it had been broken since
// 455bf46ec3 (GroupController::statsByGroupTag never existed).
Route::get('/outbound/info/{type}/{id}/{format?}', function ($type, $id, $format = 'fixometer') {
    return App\Http\Controllers\OutboundController::info($type, $id, $format);
});

Route::get('/group/stats/{id}/{format?}', function ($id, $format = 'row') {
    return App\Http\Controllers\GroupController::stats($id, $format);
});

Route::get('/admin/stats/1', function () {
    return App\Http\Controllers\AdminController::stats();
});

Route::get('/admin/stats/2', function () {
    return App\Http\Controllers\AdminController::stats(2);
});

Route::get('/party/stats/{id}/wide', function ($id) {
    return App\Http\Controllers\PartyController::stats($id);
});

// (Admin PR-preview-deploy tooling moved to GET/POST /api/v2/admin/
// preview-deploys + client/app/pages/admin/preview-deploy.vue - the Blade page
// couldn't authenticate the post-cutover SPA admin, who has a bearer token but
// no web session.)

// Everything else: 404. The Nuxt Node server is the origin (nginx routes every
// non-API, non-widget path to it — see docker/nginx-fly.conf), so browsers
// never reach Laravel for SPA paths; the SPA serves and deep-links them itself.
// This route must NOT redirect to config('restarters.frontend_url'): that URL is
// now this same host, so a browser that did reach Laravel here (an unknown
// sub-path of a retained prefix like /export or /calendar, or a misroute) would
// bounce back to itself forever. api/ is excluded so unknown API paths still
// 404 as JSON. The dead research-tool prefixes (FaultCat etc.) and /workbench
// 301 to the Talk archive in nginx (docker/nginx.conf, docker/nginx-fly.conf).
Route::get('/{any?}', function ($any = null) {
    abort(404);
})->where('any', '^(?!api/).*');
