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

Route::middleware('auth:api')->group(function () {
    Route::get('/users/me', [ApiController::class, 'getUserInfo']); // Not used but worth keeping and tested.
    Route::get('/users', [ApiController::class, 'getUserList']);  // Not used but worth keeping and tested.
    Route::get('/users/changes', [API\UserController::class, 'changes']); // Used by Zapier

    Route::get('/networks/{network}/stats/', [API\NetworkController::class, 'stats']); // Used by RepairTogether.

    Route::prefix('/groups')->group(function() {

        Route::get('/', [API\GroupController::class, 'getGroupList']); // Not used but worth keeping and tested.
        Route::get('/changes', [API\GroupController::class, 'getGroupChanges']); // Used by Zapier
        Route::get('{id}/volunteers', [API\GroupController::class, 'listVolunteers']);
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

Route::get('/groups/{group}/events', [API\GroupController::class, 'getEventsForGroup']); // Used by old JS client.

Route::get('/devices/{page}/{size}', [App\Http\Controllers\ApiController::class, 'getDevices']); // Used by Vue client.

// Notifications info.  We don't authenticate this, as API keys don't exist for all users.  There's no real privacy
// issue with exposing the number of outstanding notifications.
Route::get('/users/{id}/notifications', [API\UserController::class, 'notifications']);

// Top Talk topics.  Doesn't need authentication either.
Route::get('/talk/topics/{tag?}', [API\DiscourseController::class, 'discussionTopics']);

// Timezones
Route::get('/timezones', [App\Http\Controllers\ApiController::class, 'timezones']);

// We are working towards a new and more coherent API.
Route::prefix('v2')->group(function() {
    Route::prefix('/groups')->group(function() {
        Route::get('/names', [API\GroupController::class, 'listNamesv2']);
        Route::get('/tags', [API\GroupController::class, 'listTagsv2']);
        Route::get('{id}/events', [API\GroupController::class, 'getEventsForGroupv2']);
        Route::get('{id}', [API\GroupController::class, 'getGroupv2']);
        Route::post('', [API\GroupController::class, 'createGroupv2']);
        Route::patch('{id}', [API\GroupController::class, 'updateGroupv2']);
    });

    Route::prefix('/events')->group(function() {
        Route::get('{id}', [API\EventController::class, 'getEventv2']);
    });

    Route::prefix('/networks')->group(function() {
        Route::get('/', [API\NetworkController::class, 'getNetworksv2']);
        Route::get('{id}', [API\NetworkController::class, 'getNetworkv2']);
        Route::get('{id}/groups', [API\NetworkController::class, 'getNetworkGroupsv2']);
        Route::get('{id}/events', [API\NetworkController::class, 'getNetworkEventsv2']);
    });

    Route::prefix('/moderate')->group(function() {
        Route::middleware('auth:api')->group(function ()
        {
            Route::get('/groups', [API\GroupController::class, 'moderateGroupsv2']);
            Route::get('/events', [API\EventController::class, 'moderateEventsv2']);
        });
    });
});