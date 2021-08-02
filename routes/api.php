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

Route::get('/{api_token}/event/group-tag/{group_tag_id}', function ($api_token, $group_tag_id) {
    if ($api_token == env('API_KEY')) {
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
    Route::get('/{api_token}/group/{group}/{date_from?}/{date_to?}', 'GroupController@getGroupByKeyAndId');
    Route::get('/{api_token}/groups/group-tag/', 'GroupController@getGroupsByKey');

    Route::get('/{api_token}/event/{party}/', 'PartyController@getEventByKeyAndId');
    Route::get('/{api_token}/events/group-tag/{date_from?}/{date_to?}', 'PartyController@getEventsByKey');
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/users/me', 'ApiController@getUserInfo');
    Route::get('/users', 'ApiController@getUserList');
    Route::get('/users/changes', 'API\UserController@changes');
    Route::put('/users/{id}', 'API\UserController@update');

    Route::get('/networks/{network}/stats/', 'API\NetworkController@stats');

    Route::get('/groups', 'API\GroupController@getGroupList');
    Route::get('/groups/changes', 'API\GroupController@getGroupChanges');
    Route::get('/groups/group-tag/', 'API\GroupController@getGroupsByUserGroupTag');
    Route::get('/groups/network/', 'API\GroupController@getGroupsByUsersNetworks');

    Route::get('/events/network/{date_from?}/{date_to?}', 'API\EventController@getEventsByUsersNetworks');

    Route::get('/usersgroups/changes', 'API\UserGroupsController@changes');
    Route::delete('/usersgroups/{id}', 'API\UserGroupsController@leave');
});

Route::get('/groups/{group}/events', 'API\GroupController@getEventsForGroup');

Route::get('/devices/{page}/{size}', [App\Http\Controllers\ApiController::class, 'getDevices']);
