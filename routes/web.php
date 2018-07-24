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
Route::prefix('user')->group(function () {
    Route::get('/', 'HomeController@index');
    Route::get('reset', 'UserController@reset');
    Route::post('reset', 'UserController@reset');
    Route::get('recover', 'UserController@recover');
    Route::post('recover', 'UserController@recover');
    Route::get('register/{hash?}', 'UserController@getRegister')->name('registration');
    Route::post('register/{hash?}', 'UserController@postRegister');
});

Route::get('/user/forbidden', function () {
    return view('user.forbidden', [
      'title' => 'Oops'
    ]);
});

Auth::routes();
Route::get('/logout', 'UserController@logout');

Route::get('/about', function() {
    return View::make('features.index');
})->name('features');

// Route::get('/ui', function() {
//     return View::make('ui.index');
// })->name('ui');;

Route::get('/party/view/{id}', 'PartyController@view');

// Device export is also called from https://therestartproject.org/download-dataset,
// so we allow anonymous access.
Route::get('/export/devices', 'ExportController@devices');

Route::group(['middleware' => ['auth', 'verifyUserConsent']], function () {

  Route::get('/', 'HomeController@index')->name('home');

  //User Controller
  Route::prefix('profile')->group(function () {
    Route::get('/', 'UserController@index')->name('profile');
    Route::get('/{id}', 'UserController@index');
    Route::get('/edit/{id?}', 'UserController@getProfileEdit');
    Route::post('/edit-info', 'UserController@postProfileInfoEdit');
    Route::post('/edit-password', 'UserController@postProfilePasswordEdit');
    Route::post('/edit-preferences', 'UserController@postProfilePreferencesEdit');
    Route::post('/edit-tags', 'UserController@postProfileTagsEdit');
    Route::post('/edit-photo', 'UserController@postProfilePictureEdit');
    Route::post('/edit-admin-settings', 'UserController@postAdminEdit');
  });

  Route::post('/edit-user', 'UserController@postEdit');

  Route::prefix('user')->group(function () {
    Route::get('/create', 'UserController@create');
    Route::post('/create', 'UserController@create');
    Route::get('/all', 'UserController@all')->name('users');
    Route::get('/all/search', 'UserController@search');
    Route::get('/edit/{id}', 'UserController@getProfileEdit');
    Route::post('/edit/{id}', 'UserController@edit');
    Route::post('/soft-delete', 'UserController@postSoftDeleteUser');
    Route::get('/onboarding-complete', 'UserController@getOnboardingComplete');
  });

  //Admin Controller
  Route::prefix('admin')->group(function () {
    Route::get('/', 'AdminController@index');
    Route::get('/stats', 'AdminController@stats');
  });

  //Category Controller
  Route::prefix('category')->group(function () {
    Route::get('/', 'CategoryController@index')->name('category');
    Route::get('/edit/{id}', 'CategoryController@getEditCategory');
    Route::post('/edit/{id}', 'CategoryController@postEditCategory');
  });

  //Dashboard Controller
  Route::prefix('dashboard')->group(function () {
    Route::get('/', 'DashboardController@index')->name('dashboard');
    Route::get('/host', 'DashboardController@getHostDash');
  });

  //Device Controller
  Route::prefix('device')->group(function () {
    Route::get('/', 'DeviceController@index')->name('devices');
    Route::get('/search', 'DeviceController@search');
    Route::get('/page-edit/{id}', 'DeviceController@edit');
    Route::post('/page-edit/{id}', 'DeviceController@edit');
    Route::post('/edit/{id}', 'DeviceController@ajaxEdit');
    Route::post('/create', 'DeviceController@ajaxCreate');
    Route::get('/delete/{id}', 'DeviceController@delete');
    Route::post('/image-upload/{id}', 'DeviceController@imageUpload');
    Route::get('/image/delete/{iddevices}/{id}/{path}', 'DeviceController@deleteImage');
  });

  //Group Controller
  Route::prefix('group')->group(function () {
    Route::get('/create', 'GroupController@create')->name('create-group');
    Route::post('/create', 'GroupController@create');
    Route::get('/edit/{id}', 'GroupController@edit');
    Route::post('/edit/{id}', 'GroupController@edit');
    Route::get('/view/{id}', 'GroupController@view');
    Route::post('/invite', 'GroupController@postSendInvite');
    Route::get('/accept-invite/{id}/{hash}', 'GroupController@confirmInvite');
    Route::get('/join/{id}', 'GroupController@getJoinGroup');
    Route::post('/image-upload/{id}', 'GroupController@imageUpload');
    Route::get('/image/delete/{idgroups}/{id}/{path}', 'GroupController@ajaxDeleteImage');
    Route::get('/{all?}', 'GroupController@index')->name('groups');
    Route::get('/all/search', 'GroupController@search');
  });

  //Outbound Controller
  Route::get('/outbound', 'OutboundController@index');

  //Party Controller
  Route::prefix('party')->group(function () {
    Route::get('/', 'PartyController@index')->name('events');
    Route::get('/all', 'PartyController@allUpcoming')->name('all-upcoming-events');
    Route::get('/group/{group_id?}', 'PartyController@index')->name('group-events');
    Route::get('/create', 'PartyController@create');
    Route::post('/create', 'PartyController@create');
    Route::get('/manage/{id}', 'PartyController@manage');
    Route::post('/manage/{id}', 'PartyController@manage');
    Route::get('/edit/{id}', 'PartyController@edit');
    Route::post('/edit/{id}', 'PartyController@edit');
    Route::get('/deleteimage', 'PartyController@deleteimage');
    Route::get('/join/{id}', 'PartyController@getJoinEvent');
    Route::post('/invite', 'PartyController@postSendInvite');
    Route::get('/accept-invite/{id}/{hash}', 'PartyController@confirmInvite');
    Route::get('/cancel-invite/{id}', 'PartyController@cancelInvite');
    Route::post('/remove-volunteer', 'PartyController@removeVolunteer');
    Route::post('/add-volunteer', 'PartyController@addVolunteer');
    Route::get('/get-group-emails/{event_id}', 'PartyController@getGroupEmails');
    Route::post('/update-quantity', 'PartyController@updateQuantity');
    Route::post('/image-upload/{id}', 'PartyController@imageUpload');
    Route::get('/image/delete/{idevents}/{id}/{path}', 'PartyController@deleteImage');
    Route::get('/contribution/{id}', 'PartyController@getContributions');
  });

  //Role Controller
  Route::prefix('role')->group(function () {
    Route::get('/', 'RoleController@index')->name('roles');
    Route::get('/edit/{id}', 'RoleController@edit');
    Route::post('/edit/{id}', 'RoleController@edit');
  });

  //Brand Controller
  Route::prefix('brands')->group(function () {
    Route::get('/', 'BrandsController@index')->name('brands');
    Route::get('/create', 'BrandsController@getCreateBrand');
    Route::post('/create', 'BrandsController@postCreateBrand');
    Route::get('/edit/{id}', 'BrandsController@getEditBrand');
    Route::post('/edit/{id}', 'BrandsController@postEditBrand');
    Route::get('/delete/{id}', 'BrandsController@getDeleteBrand');
  });

  //Skills Controller
  Route::prefix('skills')->group(function () {
    Route::get('/', 'SkillsController@index')->name('skills');
    Route::get('/create', 'SkillsController@getCreateSkill');
    Route::post('/create', 'SkillsController@postCreateSkill');
    Route::get('/edit/{id}', 'SkillsController@getEditSkill');
    Route::post('/edit/{id}', 'SkillsController@postEditSkill');
    Route::get('/delete/{id}', 'SkillsController@getDeleteSkill');
  });

  //GroupTags Controller
  Route::prefix('tags')->group(function () {
    Route::get('/', 'GroupTagsController@index')->name('tags');
    Route::get('/create', 'GroupTagsController@getCreateTag');
    Route::post('/create', 'GroupTagsController@postCreateTag');
    Route::get('/edit/{id}', 'GroupTagsController@getEditTag');
    Route::post('/edit/{id}', 'GroupTagsController@postEditTag');
    Route::get('/delete/{id}', 'GroupTagsController@getDeleteTag');
  });

  //Search Controller
  Route::get('/search', 'SearchController@index');

  //AJAX Controller
  Route::get('/ajax/restarters_in_group', 'AjaxController@restarters_in_group');

  //Export Controller
  Route::get('/export/parties', 'ExportController@parties');
  Route::get('/export/time-volunteered', 'ExportController@exportTimeVolunteered');
  Route::get('/reporting/time-volunteered', 'ExportController@getTimeVolunteered');
  Route::get('/reporting/time-volunteered/{search}', 'ExportController@getTimeVolunteered');

});

//iFrames
Route::get('/outbound/info/group/{id}', function($id) {
  return App\Http\Controllers\OutboundController::info('group', $id);
});

Route::get('/outbound/info/party/{id}', function($id) {
    return App\Http\Controllers\OutboundController::info('party', $id);
});

Route::get('/group/stats/{id}/{format?}', function($id, $format = 'row') {
    return App\Http\Controllers\GroupController::stats($id, $format);
});

Route::get('/admin/stats/1', function() {
  return App\Http\Controllers\AdminController::stats();
});

Route::get('/admin/stats/2', function() {
  return App\Http\Controllers\AdminController::stats(2);
});

Route::get('/party/stats/{id}/wide', function($id) {
  return App\Http\Controllers\PartyController::stats($id);
});
