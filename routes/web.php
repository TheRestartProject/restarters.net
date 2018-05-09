<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware('auth')->group(function () {

  Route::get('/home', 'HomeController@index')->name('home');

  // Route::get('/', 'UserController@index');

  //User Controller
  Route::get('/profile', 'UserController@index')->name('profile');
  Route::post('/edit-user', 'UserController@postEdit');
  Route::get('/user/create', 'UserController@create');
  Route::get('/user/all', 'UserController@all');
  Route::get('/user/edit/{id}', 'UserController@edit');

  //Test NB: Remove after testing!!
  Route::get('/test', 'PartyController@test');

  //Admin Controller
  Route::get('/admin', 'AdminController@index');
  Route::get('/admin/stats', 'AdminController@stats');

  //Category Controller
  Route::get('/category', 'CategoryController@index');

  //Dashboard Controller
  Route::get('/dashboard', 'DashboardController@index');

  //Device Controller
  Route::get('/device', 'DeviceController@index');
  Route::get('/device/edit/{id}', 'DeviceController@edit');
  Route::get('/device/create', 'DeviceController@create');

  //Group Controller
  Route::get('/group', 'GroupController@index');
  Route::get('/group/create', 'GroupController@create');
  Route::get('/group/edit/{id}', 'GroupController@edit');

  //Host Controller
  Route::get('/host', 'HostController@index');

  //Outbound Controller
  Route::get('/outbound', 'OutboundController@index');

  //Party Controller
  Route::get('/party', 'PartyController@index');
  Route::get('/party/create', 'PartyController@create');
  Route::get('/party/manage/{id}', 'PartyController@manage');


  //Role Controller
  Route::get('/role', 'RoleController@index');
  Route::get('/role/edit/{id}', 'RoleController@edit');

  //Search Controller
  Route::get('/search', 'SearchController@index');

});
