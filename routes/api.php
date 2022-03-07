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

    Route::get('/networks/{network}/stats/', 'API\NetworkController@stats');  // Used by Repair Together.

    Route::get('/groups', 'API\GroupController@getGroupList'); // Not used but worth keeping and tested.
    Route::get('/groups/changes', 'API\GroupController@getGroupChanges'); // Used by Zapier

    Route::get('/groups/network/', 'API\GroupController@getGroupsByUsersNetworks'); // Used by Repair Together.
    Route::get('/events/network/{date_from?}/{date_to?}', 'API\EventController@getEventsByUsersNetworks'); // Used by Repair Together.

    Route::get('/usersgroups/changes', 'API\UserGroupsController@changes'); // Used by Zapier
    Route::delete('/usersgroups/{id}', 'API\UserGroupsController@leave'); // Used by Vue client.
});

Route::get('/groups/{group}/events', 'API\GroupController@getEventsForGroup'); // Used by old JS client.

Route::get('/devices/{page}/{size}', [App\Http\Controllers\ApiController::class, 'getDevices']); // Used by Vue client.
