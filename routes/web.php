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
Route::get('/user/register', 'UserController@getRegister');
Route::get('/user/register/{invite}', 'UserController@getRegister');
Route::post('/user/register', 'UserController@postRegister');

Route::get('/user/forbidden', function () {
    return view('user.forbidden', [
      'title' => 'Oops'
    ]);
});

Auth::routes();
Route::get('/logout', 'UserController@logout');

Route::group(['middleware' => ['auth', 'verifyUserConsent']], function () {

  Route::get('/', 'HomeController@index')->name('home');

  // Route::get('/', 'UserController@index');

  //User Controller
  Route::get('/profile', 'UserController@index')->name('profile');
  Route::get('/profile/{id}', 'UserController@index');
  Route::get('/profile/edit/{id}', 'UserController@getProfileEdit');
  Route::post('profile/edit-info', 'UserController@postProfileInfoEdit');
  Route::post('profile/edit-password', 'UserController@postProfilePasswordEdit');
  Route::post('profile/edit-preferences', 'UserController@postProfilePreferencesEdit');
  Route::post('profile/edit-tags', 'UserController@postProfileTagsEdit');
  Route::post('profile/edit-photo', 'UserController@postProfilePictureEdit');
  Route::post('/edit-user', 'UserController@postEdit');
  Route::get('/user/create', 'UserController@create');
  Route::post('/user/create', 'UserController@create');
  Route::get('/user/all', 'UserController@all')->name('users');
  Route::get('/user/edit/{id}', 'UserController@edit');
  Route::post('/user/edit/{id}', 'UserController@edit');
  Route::post('user/soft-delete', 'UserController@postSoftDeleteUser');

  //Test NB: Remove after testing!!
  //Route::get('/test', 'PartyController@test');

  //Admin Controller
  Route::get('/admin', 'AdminController@index');
  Route::get('/admin/stats', 'AdminController@stats');

  //Category Controller
  Route::get('/category', 'CategoryController@index')->name('category');

  //Dashboard Controller
  Route::get('/dashboard', 'DashboardController@index')->name('dashboard');
  Route::get('/dashboard/host', 'DashboardController@getHostDash');

  //Device Controller
  Route::get('/device', 'DeviceController@index')->name('devices');
  Route::get('/device/search', 'DeviceController@index');
  // Route::get('/device/edit/{id}', 'DeviceController@edit');
  // Route::post('/device/edit/{id}', 'DeviceController@edit');
  Route::post('/device/edit/{id}', 'DeviceController@ajaxEdit');
  // Route::get('/device/create', 'DeviceController@create');
  // Route::post('/device/create', 'DeviceController@create');
  Route::post('/device/create', 'DeviceController@ajaxCreate');
  Route::get('/device/delete/{id}', 'DeviceController@delete');
  Route::post('/device/image-upload/{id}', 'DeviceController@imageUpload');

  //Group Controller
  Route::get('/group', 'GroupController@index')->name('groups');
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
  Route::get('/party', 'PartyController@index')->name('events');
  Route::get('/party/create', 'PartyController@create');
  Route::post('/party/create', 'PartyController@create');
  Route::get('/party/manage/{id}', 'PartyController@manage');
  Route::post('/party/manage/{id}', 'PartyController@manage');
  Route::get('/party/edit/{id}', 'PartyController@edit');
  Route::post('/party/edit/{id}', 'PartyController@edit');
  Route::get('/party/deleteimage', 'PartyController@deleteimage');
  Route::post('/party/invite', 'PartyController@postSendInvite');
  Route::get('/accept-invite/party-{id}/{hash}', 'PartyController@confirmInvite');
  Route::get('/party/view/{id}', 'PartyController@view');
  Route::post('/party/get-group-emails', 'PartyController@getGroupEmails');
  Route::post('/party/update-quantity', 'PartyController@updateQuantity');

  //Role Controller
  Route::get('/role', 'RoleController@index')->name('roles');
  Route::get('/role/edit/{id}', 'RoleController@edit');
  Route::post('/role/edit/{id}', 'RoleController@edit');

  //Brand Controller
  Route::get('/brands', 'BrandsController@index')->name('brands');
  Route::get('/brands/create', 'BrandsController@getCreateBrand');
  Route::post('/brands/create', 'BrandsController@postCreateBrand');
  Route::get('/brands/edit/{id}', 'BrandsController@getEditBrand');
  Route::post('/brands/edit/{id}', 'BrandsController@postEditBrand');
  Route::get('/brands/delete/{id}', 'BrandsController@getDeleteBrand');

  //Skills Controller
  Route::get('/skills', 'SkillsController@index')->name('skills');
  Route::get('/skills/create', 'SkillsController@getCreateSkill');
  Route::post('/skills/create', 'SkillsController@postCreateSkill');
  Route::get('/skills/edit/{id}', 'SkillsController@getEditSkill');
  Route::post('/skills/edit/{id}', 'SkillsController@postEditSkill');
  Route::get('/skills/delete/{id}', 'SkillsController@getDeleteSkill');

  //GroupTags Controller
  Route::get('/tags', 'GroupTagsController@index')->name('tags');
  Route::get('/tags/create', 'GroupTagsController@getCreateTag');
  Route::post('/tags/create', 'GroupTagsController@postCreateTag');
  Route::get('/tags/edit/{id}', 'GroupTagsController@getEditTag');
  Route::post('/tags/edit/{id}', 'GroupTagsController@postEditTag');
  Route::get('/tags/delete/{id}', 'GroupTagsController@getDeleteTag');

  //Search Controller
  Route::get('/search', 'SearchController@index');

  //AJAX Controller
  Route::get('/ajax/restarters_in_group', 'AjaxController@restarters_in_group');

  //Export Controller
  Route::get('/export/devices', 'ExportController@devices');
  Route::get('/export/parties', 'ExportController@parties');

});
