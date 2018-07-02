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

Route::get('/outbound/info/group/{id}', function($id) {
  return App\Http\Controllers\OutboundController::info('group', $id);
});

Route::get('/group/stats/{id}', function($id) {
  return App\Http\Controllers\GroupController::stats($id);
});

Route::get('/admin/stats/1', function() {
  return App\Http\Controllers\AdminController::stats();
});

Route::get('/admin/stats/2', function() {
  return App\Http\Controllers\AdminController::stats(2);
});

Route::get('/homepage_data', function() {
  return App\Http\Controllers\ApiController::homepage_data();
});

Route::get('/party/stats/{id}/wide', function($id) {
  return App\Http\Controllers\PartyController::stats($id);
});

Route::get('/outbound/info/party/{id}', function($id) {
  return App\Http\Controllers\OutboundController::info('party', $id);
});
