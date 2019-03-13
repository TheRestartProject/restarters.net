<?php

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/homepage_data', function () {
    return App\Http\Controllers\ApiController::homepage_data();
});

Route::get('/party/{id}/stats', function ($id) {
    return App\Http\Controllers\ApiController::partyStats($id);
});

Route::get('/group/{id}/stats', function ($id) {
    return App\Http\Controllers\ApiController::groupStats($id);
});

Route::get('/{api_key}/event/group-tag/{group_tag_id}', function ($api_key, $group_tag_id) {
    if ($api_key == env('API_KEY')) {
        return App\Http\Controllers\ApiController::getEventsByGroupTag($group_tag_id);
    }

    return response()->json([
        'message' => 'Invalid API key',
    ]);
});

Route::get('/outbound/info/{type}/{id}/{format?}', function ($type, $id, $format = 'fixometer') {
    return App\Http\Controllers\OutboundController::info($type, $id, $format);
});

Route::get('/group-tag/stats/{group_tag_id}/{format?}', function ($group_tag_id, $format = 'row') {
    return App\Http\Controllers\GroupController::statsByGroupTag($group_tag_id, $format);
});

// API calls to get Group(s)/Event(s) Info relevant
Route::group(['middleware' => 'checkAPIAccess'], function () {
    Route::get('/{api_key}/groups/group-tag/', function ($api_key) {
        return App\Http\Controllers\GroupController::getGroupsByapi_key($api_key);
    });
    Route::get('/{api_key}/events/group-tag/', function ($api_key) {
        return App\Http\Controllers\EventController::getEventsByapi_key($api_key);
    });
    Route::get('/{api_key}/group/{id}', function ($api_key, $id) {
        return App\Http\Controllers\GroupController::getGroupByapi_keyAndId($api_key, $id);
    });
    Route::get('/{api_key}/event/upcoming/{id}', function ($api_key, $id) {
        return App\Http\Controllers\EventController::getUpcomingEventByapi_keyAndId($api_key, $id);
    });
    Route::get('/{api_key}/event/past/{id}', function ($api_key, $id) {
        return App\Http\Controllers\EventController::getPastEventByapi_keyAndId($api_key, $id);
    });
});
