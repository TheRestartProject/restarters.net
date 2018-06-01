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

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/user/reset', 'UserController@reset');
Route::post('/user/reset', 'UserController@reset');
Route::get('/user/recover', 'UserController@recover');
Route::post('/user/recover', 'UserController@recover');


Route::get('/user/forbidden', function () {
    return view('user.forbidden', [
      'title' => 'Oops'
    ]);
});

Auth::routes();


Route::middleware('auth')->group(function () {

  Route::get('/', 'HomeController@index')->name('home');

  // Route::get('/', 'UserController@index');

  //User Controller
  Route::get('/profile', 'UserController@index')->name('profile');
  Route::post('/edit-user', 'UserController@postEdit');
  Route::get('/user/create', 'UserController@create');
  Route::post('/user/create', 'UserController@create');
  Route::get('/user/all', 'UserController@all');
  Route::get('/user/edit/{id}', 'UserController@edit');
  Route::post('/user/edit/{id}', 'UserController@edit');

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
  Route::get('/device/search', 'DeviceController@index');
  Route::get('/device/edit/{id}', 'DeviceController@edit');
  Route::post('/device/edit/{id}', 'DeviceController@edit');
  Route::get('/device/create', 'DeviceController@create');
  Route::post('/device/create', 'DeviceController@create');
  Route::get('/device/delete/{id}', 'DeviceController@delete');

  //Group Controller
  Route::get('/group', 'GroupController@index');
  Route::get('/group/create', 'GroupController@create');
  Route::post('/group/create', 'GroupController@create');
  Route::get('/group/edit/{id}', 'GroupController@edit');
  Route::post('/group/edit/{id}', 'GroupController@edit');


  //Host Controller
  Route::get('/host', 'HostController@index');
  Route::get('/host/index/{id}', 'HostController@index');

  //Outbound Controller
  Route::get('/outbound', 'OutboundController@index');

  //Party Controller
  Route::get('/party', 'PartyController@index');
  Route::get('/party/create', 'PartyController@create');
  Route::post('/party/create', 'PartyController@create');
  Route::get('/party/manage/{id}', 'PartyController@manage');
  Route::post('/party/manage/{id}', 'PartyController@manage');
  Route::get('/party/edit/{id}', 'PartyController@edit');
  Route::post('/party/edit/{id}', 'PartyController@edit');
  Route::get('/party/deleteimage', 'PartyController@deleteimage');

  //Role Controller
  Route::get('/role', 'RoleController@index');
  Route::get('/role/edit/{id}', 'RoleController@edit');
  Route::post('/role/edit/{id}', 'RoleController@edit');

  //Search Controller
  Route::get('/search', 'SearchController@index');

  //AJAX Controller
  Route::get('/ajax/restarters_in_group', 'AjaxController@restarters_in_group');

  //Export Controller
  Route::get('/export/devices', 'ExportController@devices');
  Route::get('/export/parties', 'ExportController@parties');

});
