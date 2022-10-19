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

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/users/me', 'ApiController@getUserInfo'); // Not used but worth keeping and tested.
    Route::get('/users', 'ApiController@getUserList');  // Not used but worth keeping and tested.
    Route::get('/users/changes', 'API\UserController@changes'); // Used by Zapier

    Route::get('/networks/{network}/stats/', 'API\NetworkController@stats'); // Used by RepairTogether.

    Route::prefix('/groups')->group(function() {

        Route::get('/', 'API\GroupController@getGroupList'); // Not used but worth keeping and tested.
        Route::get('/changes', 'API\GroupController@getGroupChanges'); // Used by Zapier
        Route::get('{id}/volunteers', 'API\GroupController@listVolunteers');
        Route::get('/network/', 'API\GroupController@getGroupsByUsersNetworks'); // Used by Repair Together.
    });

    Route::prefix('/events')->group(function() {
        Route::get('/network/{date_from?}/{date_to?}', 'API\EventController@getEventsByUsersNetworks'); // Used by Repair Together.
        Route::get('{id}/volunteers', 'API\EventController@listVolunteers');
        Route::put('{id}/volunteers', 'API\EventController@addVolunteer');
    });

    Route::get('/usersgroups/changes', 'API\UserGroupsController@changes'); // Used by Zapier
    Route::delete('/usersgroups/{id}', 'API\UserGroupsController@leave'); // Used by Vue client.
});

Route::get('/groups/{group}/events', 'API\GroupController@getEventsForGroup'); // Used by old JS client.

Route::get('/devices/{page}/{size}', [App\Http\Controllers\ApiController::class, 'getDevices']); // Used by Vue client.

// Notifications info.  We don't authenticate this, as API keys don't exist for all users.  There's no real privacy
// issue with exposing the number of outstanding notifications.
Route::get('/users/{id}/notifications', 'API\UserController@notifications');

// Top Talk topics.  Doesn't need authentication either.
Route::get('/talk/topics/{tag?}', 'API\DiscourseController@discussionTopics');

// Timezones
Route::get('/timezones', [App\Http\Controllers\ApiController::class, 'timezones']);

// We are working towards a new and more coherent API.
Route::group(['prefix' => 'v2'], function() {
    Route::prefix('/groups')->group(function() {
        Route::get('/', 'API\GroupController@listv2');
        Route::get('{id}/events', 'API\GroupController@getEventsForGroupv2');
        Route::get('{id}', 'API\GroupController@getGroupv2');
        Route::post('', 'API\GroupController@createGroupv2');
        Route::patch('{id}', 'API\GroupController@updateGroupv2');
    });

    Route::prefix('/events')->group(function() {
        Route::get('{id}', 'API\EventController@getEventv2');
    });

    Route::prefix('/networks')->group(function() {
        Route::get('/', 'API\NetworkController@getNetworksv2');
        Route::get('{id}', 'API\NetworkController@getNetworkv2');
        Route::get('{id}/groups', 'API\NetworkController@getNetworkGroupsv2');
        Route::get('{id}/events', 'API\NetworkController@getNetworkEventsv2');
    });

    Route::prefix('/moderate')->group(function() {
        Route::group(['middleware' => 'auth:api'], function ()
        {
            Route::get('/groups', 'API\GroupController@moderateGroupsv2');
            Route::get('/events', 'API\EventController@moderateEventsv2');
        });
    });
});