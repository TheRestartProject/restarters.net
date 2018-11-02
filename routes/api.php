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


Route::get('/homepage_data', function() {
  return App\Http\Controllers\ApiController::homepage_data();
});

Route::get('/party/{id}/stats', function($id) {
    return App\Http\Controllers\ApiController::partyStats($id);
});

Route::get('/group/{id}/stats', function($id) {
    return App\Http\Controllers\ApiController::groupStats($id);
});

Route::get('/{api_key}/event/group-tag/{group_tag_id}', function($api_key, $group_tag_id) {

    if ( $api_key == env('API_KEY') ) {

      return App\Http\Controllers\ApiController::getEventsByGroupTag($group_tag_id);

    } else {

      return response()->json([
        'message' => 'Invalid API key',
      ]);

    }

});
