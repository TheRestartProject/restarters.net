<?php

use App\Http\Controllers\DeviceUrlController;
use App\Http\Controllers\InformationAlertCookieController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BattcatOraController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\CalendarEventsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DustupOraController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FaultcatController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupTagsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MicrotaskingController;
use App\Http\Controllers\MisccatController;
use App\Http\Controllers\MobifixController;
use App\Http\Controllers\MobifixOraController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\OutboundController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PrintcatOraController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SkillsController;
use App\Http\Controllers\StyleController;
use App\Http\Controllers\TabicatOraController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Route::middleware('ensureAPIToken')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/', [HomeController::class, 'index']);
        Route::get('reset', [UserController::class, 'reset']);
        Route::post('reset', [UserController::class, 'reset']);
        Route::get('recover', [UserController::class, 'recover']);
        Route::post('recover', [UserController::class, 'recover']);
        Route::get('register/{hash?}', [UserController::class, 'getRegister'])->name('registration');
        Route::post('register/check-valid-email', [UserController::class, 'postEmail']);
        Route::post('register/{hash?}', [UserController::class, 'postRegister']);
        Route::get('/thumbnail/', [UserController::class, 'getThumbnail']);
        Route::get('/menus/', [UserController::class, 'getUserMenus']);
    });

    Route::get('/user/forbidden', function () {
        return view('user.forbidden', [
            'title' => 'Oops',
        ]);
    });

// We use the Laravel login route.
    Auth::routes();

    Route::middleware('guest')->group(function ()
    {
        Route::get('/', [HomeController::class, 'index'])->name('home');
    });

// We are not using Laravel's default registration methods. So we redirect /register to /user/register.
    Route::redirect('register', '/user/register');
    Route::get('/logout', [UserController::class, 'logout']);

    Route::get('/about/cookie-policy', function () {
        return View::make('features.cookie-policy');
    });

// Temp
    Route::get('/visualisations', function () {
        return View::make('visualisations');
    });

    Route::get('/party/view/{id}', [PartyController::class, 'view']);

    // Device export is also called from https://therestartproject.org/download-dataset,
    // so we allow anonymous access.
    Route::prefix('export')->group(function() {
        Route::get('/devices/event/{id}', [ExportController::class, 'devicesEvent']);
        Route::get('/devices/group/{id}', [ExportController::class, 'devicesGroup']);
        Route::get('/devices', [ExportController::class, 'devices']);
        Route::get('/groups/{id}/events', [ExportController::class, 'groupEvents']);
        Route::get('/networks/{id}/events', [ExportController::class, 'networkEvents']);
    });

    // Calendar routes do not require authentication.
    // (You would not be able to subscribe from a calendar application if they did.)
    Route::prefix('calendar')->group(function () {
        Route::get('/user/{calendar_hash}', [CalendarEventsController::class, 'allEventsByUser'])->name('calendar-events-by-user');
        Route::get('/group/{group}', [CalendarEventsController::class, 'allEventsByGroup'])->name('calendar-events-by-group');
        Route::get('/network/{network}', [CalendarEventsController::class, 'allEventsByNetwork'])->name('calendar-events-by-network');
        Route::get('/group-area/{area}', [CalendarEventsController::class, 'allEventsByArea'])->name('calendar-events-by-area');
        Route::get('/all-events/{hash_env}', [CalendarEventsController::class, 'allEvents'])->name('calendar-events-all');
    });

    Route::get('workbench', [MicrotaskingController::class, 'index'])->name('workbench');

    Route::prefix('FaultCat')->group(function () {
        Route::get('/', [FaultcatController::class, 'index']);
        Route::post('/', [FaultcatController::class, 'index']);
        Route::get('/status', [FaultcatController::class, 'status']);
        Route::get('/demographics', [FaultcatController::class, 'demographics']);
        Route::post('/demographics', [FaultcatController::class, 'storeDemographics']);
    });

    Route::prefix('faultcat')->group(function () {
        Route::get('/', [FaultcatController::class, 'index']);
        Route::post('/', [FaultcatController::class, 'index']);
        Route::get('/status', [FaultcatController::class, 'status']);
        Route::get('/demographics', [FaultcatController::class, 'demographics']);
        Route::post('/demographics', [FaultcatController::class, 'storeDemographics']);
    });

    Route::prefix('MiscCat')->group(function () {
        Route::get('/', [MisccatController::class, 'index']);
        Route::post('/', [MisccatController::class, 'index']);
        Route::get('/cta', [MisccatController::class, 'cta']);
        Route::get('/status', [MisccatController::class, 'status']);
    });

    Route::prefix('misccat')->group(function () {
        Route::get('/', [MisccatController::class, 'index']);
        Route::post('/', [MisccatController::class, 'index']);
        Route::get('/cta', [MisccatController::class, 'cta']);
        Route::get('/status', [MisccatController::class, 'status']);
    });

    Route::prefix('MobiFix')->group(function () {
        Route::get('/', [MobifixController::class, 'index']);
        Route::post('/', [MobifixController::class, 'index']);
        Route::get('/cta', [MobifixController::class, 'cta']);
        Route::get('/status', [MobifixController::class, 'status']);
    });
    Route::prefix('mobifix')->group(function () {
        Route::get('/', [MobifixController::class, 'index']);
        Route::post('/', [MobifixController::class, 'index']);
        Route::get('/cta', [MobifixController::class, 'cta']);
        Route::get('/status', [MobifixController::class, 'status']);
    });

    Route::prefix('MobiFixOra')->group(function () {
        Route::get('/', [MobifixOraController::class, 'index']);
        Route::post('/', [MobifixOraController::class, 'index']);
        Route::get('/cta', [MobifixOraController::class, 'cta']);
        Route::get('/status', [MobifixOraController::class, 'status']);
    });
    Route::prefix('mobifixora')->group(function () {
        Route::get('/', [MobifixOraController::class, 'index']);
        Route::post('/', [MobifixOraController::class, 'index']);
        Route::get('/cta', [MobifixOraController::class, 'cta']);
        Route::get('/status', [MobifixOraController::class, 'status']);
    });

    Route::prefix('TabiCat')->group(function () {
        Route::get('/', [TabicatOraController::class, 'index']);
        Route::post('/', [TabicatOraController::class, 'index']);
        Route::get('/cta', [TabicatOraController::class, 'cta']);
        Route::get('/status', [TabicatOraController::class, 'status']);
        Route::get('/survey', [TabicatOraController::class, 'survey']);
    });
    Route::prefix('tabicat')->group(function () {
        Route::get('/', [TabicatOraController::class, 'index']);
        Route::post('/', [TabicatOraController::class, 'index']);
        Route::get('/cta', [TabicatOraController::class, 'cta']);
        Route::get('/status', [TabicatOraController::class, 'status']);
        Route::get('/survey', [TabicatOraController::class, 'survey']);
    });

    Route::prefix('PrintCat')->group(function () {
        Route::get('/', [PrintcatOraController::class, 'index']);
        Route::post('/', [PrintcatOraController::class, 'index']);
        Route::get('/cta', [PrintcatOraController::class, 'cta']);
        Route::get('/status', [PrintcatOraController::class, 'status']);
    });
    Route::prefix('printcat')->group(function () {
        Route::get('/', [PrintcatOraController::class, 'index']);
        Route::post('/', [PrintcatOraController::class, 'index']);
        Route::get('/cta', [PrintcatOraController::class, 'cta']);
        Route::get('/status', [PrintcatOraController::class, 'status']);
    });

    Route::prefix('BattCat')->group(function () {
        Route::get('/', [BattcatOraController::class, 'index']);
        Route::post('/', [BattcatOraController::class, 'index']);
        Route::get('/survey', [BattcatOraController::class, 'survey']);
        Route::get('/status', [BattcatOraController::class, 'status']);
    });
    Route::prefix('battcat')->group(function () {
        Route::get('/', [BattcatOraController::class, 'index']);
        Route::post('/', [BattcatOraController::class, 'index']);
        Route::get('/survey', [BattcatOraController::class, 'survey']);
        Route::get('/status', [BattcatOraController::class, 'status']);
    });

    Route::prefix('DustUp')->group(function () {
        Route::get('/', [DustupOraController::class, 'index']);
        Route::post('/', [DustupOraController::class, 'index']);
        Route::get('/cta', [DustupOraController::class, 'cta']);
        Route::get('/status', [DustupOraController::class, 'status']);
    });
    Route::prefix('dustup')->group(function () {
        Route::get('/', [DustupOraController::class, 'index']);
        Route::post('/', [DustupOraController::class, 'index']);
        Route::get('/cta', [DustupOraController::class, 'cta']);
        Route::get('/status', [DustupOraController::class, 'status']);
    });

    Route::middleware('guest')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/about', [HomeController::class, 'index'])->name('home');
    });
});

Route::middleware('auth', 'verifyUserConsent', 'ensureAPIToken')->group(function () {
    //User Controller
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('profile');
        Route::get('/notifications', [UserController::class, 'getNotifications'])->name('notifications');
        Route::get('/edit/{id?}', [UserController::class, 'getProfileEdit'])->name('edit-profile');
        Route::get('/{id}', [UserController::class, 'index']);
        Route::post('/edit-info', [UserController::class, 'postProfileInfoEdit']);
        Route::post('/edit-password', [UserController::class, 'postProfilePasswordEdit']);
        Route::post('/edit-language', [UserController::class, 'storeLanguage']);
        Route::post('/edit-preferences', [UserController::class, 'postProfilePreferencesEdit']);
        Route::post('/edit-tags', [UserController::class, 'postProfileTagsEdit']);
        Route::post('/edit-photo', [UserController::class, 'postProfilePictureEdit']);
        Route::post('/edit-admin-settings', [UserController::class, 'postAdminEdit']);
        Route::post('/edit-repair-directory', [UserController::class, 'postProfileRepairDirectory']);
    });

    Route::prefix('user')->group(function () {
        Route::post('/create', [UserController::class, 'create']);
        Route::get('/all', [UserController::class, 'all'])->name('users');
        Route::get('/all/search', [UserController::class, 'search']);
        Route::get('/edit/{id}', [UserController::class, 'getProfileEdit']);
        Route::post('/edit/{id}', [UserController::class, 'edit']);
        Route::post('/soft-delete', [UserController::class, 'postSoftDeleteUser']);
        Route::get('/onboarding-complete', [UserController::class, 'getOnboardingComplete']);
    });

    //Admin Controller
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
    });

    //Category Controller
    Route::prefix('category')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('category');
        Route::get('/edit/{id}', [CategoryController::class, 'getEditCategory']);
        Route::post('/edit/{id}', [CategoryController::class, 'postEditCategory']);
    });

    //Dashboard Controller
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('AcceptUserInvites');
        Route::get('/host', [DashboardController::class, 'getHostDash']);
    });

    Route::prefix('fixometer')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('devices');
    });

    // TODO: the rest of these to be redirected properly.
    Route::prefix('device')->group(function () {
        Route::get('/', function () {
            return redirect('/fixometer');
        });
        Route::get('/search', [DeviceController::class, 'search']);
        Route::post('/edit/{id}', [DeviceController::class, 'ajaxEdit']);
        Route::post('/create', [DeviceController::class, 'ajaxCreate']);
        Route::get('/delete/{id}', [DeviceController::class, 'delete']);
        Route::post('/image-upload/{id}', [DeviceController::class, 'imageUpload']);
        Route::get('/image/delete/{iddevices}/{idxref}', [DeviceController::class, 'deleteImage']);
    });

    Route::resource('networks', NetworkController::class)->only([
        'index', 'show', 'edit', 'update'
                                                           ]);
    Route::prefix('networks')->group(function () {
        Route::post('/{network}/groups', [NetworkController::class, 'associateGroup'])->name('networks.associate-group');
    });

    //Group Controller
    Route::prefix('group')->group(function () {
        Route::get('/create', [GroupController::class, 'create'])->name('create-group');
        Route::post('/create', [GroupController::class, 'create']);
        Route::get('/edit/{id}', [GroupController::class, 'edit']);
        Route::post('/edit/{id}', [GroupController::class, 'edit']);
        Route::get('/view/{id}', [GroupController::class, 'view'])->name('group.show');
        Route::post('/invite', [GroupController::class, 'postSendInvite']);
        Route::get('/accept-invite/{id}/{hash}', [GroupController::class, 'confirmInvite']);
        Route::get('/join/{id}', [GroupController::class, 'getJoinGroup']);
        Route::post('/image-upload/{id}', [GroupController::class, 'imageUpload']);
        Route::get('/image/delete/{idgroups}/{id}/{path}', [GroupController::class, 'ajaxDeleteImage']);
        Route::get('/', [GroupController::class, 'mine'])->name('groups');
        Route::get('/all', [GroupController::class, 'all']);
        Route::get('/mine', [GroupController::class, 'mine']);
        Route::get('/nearby', [GroupController::class, 'nearby']);
        Route::get('/network/{id}', [GroupController::class, 'network']);
        Route::get('/make-host/{group_id}/{user_id}', [GroupController::class, 'getMakeHost']);
        Route::get('/remove-volunteer/{group_id}/{user_id}', [GroupController::class, 'getRemoveVolunteer']);
        Route::get('/nearby/{id}', [GroupController::class, 'volunteersNearby']);
        Route::get('/nearbyinvite/{groupId}/{userId}', [GroupController::class, 'inviteNearbyRestarter']);
        Route::get('/delete/{id}', [GroupController::class, 'delete']);
    });

    //Outbound Controller
    Route::get('/outbound', [OutboundController::class, 'index']);

    //Party Controller
    Route::prefix('party')->group(function () {
        Route::get('/', [PartyController::class, 'index'])->name('events');
        Route::get('/all', [PartyController::class, 'allUpcoming'])->name('all-upcoming-events');
        Route::get('/all-past', [PartyController::class, 'allPast'])->name('all-past-events');
        Route::get('/group/{group_id?}', [PartyController::class, 'index'])->name('group-events');
        Route::get('/create/{group_id?}', [PartyController::class, 'create']);
        Route::get('/edit/{id}', [PartyController::class, 'edit']);
        Route::post('/edit/{id}', [PartyController::class, 'edit']);
        Route::get('/duplicate/{id}', [PartyController::class, 'duplicate']);
        Route::post('/delete/{id}', [PartyController::class, 'deleteEvent']);
        Route::get('/deleteimage', [PartyController::class, 'deleteimage']);
        Route::get('/join/{id}', [PartyController::class, 'getJoinEvent']);
        Route::post('/invite', [PartyController::class, 'postSendInvite']);
        Route::get('/accept-invite/{id}/{hash}', [PartyController::class, 'confirmInvite']);
        Route::get('/cancel-invite/{id}', [PartyController::class, 'cancelInvite']);
        Route::post('/remove-volunteer', [PartyController::class, 'removeVolunteer']);
        Route::get('/get-group-emails-with-names/{event_id}', [PartyController::class, 'getGroupEmailsWithNames']);
        Route::post('/update-quantity', [PartyController::class, 'updateQuantity']);
        Route::post('/image-upload/{id}', [PartyController::class, 'imageUpload']);
        Route::get('/image/delete/{idevents}/{id}/{path}', [PartyController::class, 'deleteImage']);
        Route::get('/contribution/{id}', [PartyController::class, 'getContributions']);
        Route::post('/update-volunteerquantity', [PartyController::class, 'updateVolunteerQuantity']);
    });

    //Role Controller
    Route::prefix('role')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('roles');
        Route::get('/edit/{id}', [RoleController::class, 'edit']);
        Route::post('/edit/{id}', [RoleController::class, 'edit']);
    });

    //Brand Controller
    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandsController::class, 'index'])->name('brands');
        Route::get('/create', [BrandsController::class, 'getCreateBrand']);
        Route::post('/create', [BrandsController::class, 'postCreateBrand']);
        Route::get('/edit/{id}', [BrandsController::class, 'getEditBrand']);
        Route::post('/edit/{id}', [BrandsController::class, 'postEditBrand']);
        Route::get('/delete/{id}', [BrandsController::class, 'getDeleteBrand']);
    });

    //Skills Controller
    Route::prefix('skills')->group(function () {
        Route::get('/', [SkillsController::class, 'index'])->name('skills');
        Route::post('/create', [SkillsController::class, 'postCreateSkill']);
        Route::get('/edit/{id}', [SkillsController::class, 'getEditSkill']);
        Route::post('/edit/{id}', [SkillsController::class, 'postEditSkill']);
        Route::get('/delete/{id}', [SkillsController::class, 'getDeleteSkill']);
    });

    //GroupTags Controller
    Route::prefix('tags')->group(function () {
        Route::get('/', [GroupTagsController::class, 'index'])->name('tags');
        Route::post('/create', [GroupTagsController::class, 'postCreateTag']);
        Route::get('/edit/{id}', [GroupTagsController::class, 'getEditTag']);
        Route::post('/edit/{id}', [GroupTagsController::class, 'postEditTag']);
        Route::get('/delete/{id}', [GroupTagsController::class, 'getDeleteTag']);
    });
});

Route::middleware('ensureAPIToken')->group(function () {
    Route::get('/party/invite/{code}', [PartyController::class, 'confirmCodeInvite']);
    Route::get('/group/invite/{code}', [GroupController::class, 'confirmCodeInvite']);

//iFrames
    Route::get('/outbound/info/{type}/{id}/{format?}', function ($type, $id, $format = 'fixometer') {
        return App\Http\Controllers\OutboundController::info($type, $id, $format);
    });

    Route::get('/group/stats/{id}/{format?}', function ($id, $format = 'row') {
        return App\Http\Controllers\GroupController::stats($id, $format);
    });

    Route::get('/group-tag/stats/{group_tag_id}/{format?}', function ($group_tag_id, $format = 'row') {
        return App\Http\Controllers\GroupController::statsByGroupTag($group_tag_id, $format);
    });

    Route::get('/admin/stats/1', function () {
        return App\Http\Controllers\AdminController::stats();
    });

    Route::get('/admin/stats/2', function () {
        return App\Http\Controllers\AdminController::stats(2);
    });

    Route::get('/party/stats/{id}/wide', function ($id) {
        return App\Http\Controllers\PartyController::stats($id);
    });

    Route::get('markAsRead/{id?}', function ($id = null) {
        $notifications = auth()->user()->unReadNotifications;

        if ($id) {
            $notifications = $notifications->where('id', $id);
        }

        $notifications->markAsRead();

        return redirect()->back();
    })->name('markAsRead');

    Route::get('/set-lang/{locale}', [LocaleController::class, 'setLang']);

    Route::post('/set-cookie', InformationAlertCookieController::class);

    Route::get('/test/check-auth', function () {
        return new \App\Services\CheckAuthService;
    });

    Route::prefix('style')->group(function () {
        Route::get('/', [StyleController::class, 'index']);
        Route::get('/guide', [StyleController::class, 'guide']);
        Route::get('/find', [StyleController::class, 'find']);
    });
});

// Useful code to log all queries.  This is particularly useful when trying to reduce the number of queries; if
// Laravel debug is turned on then the Queries tab on the client shows them briefly and then gets reset.  That's
// long enough to spot pages with too many queries, but not long enough to see what they are.
//\DB::listen(function($sql) {
//    \Log::info($sql->sql);
//    \Log::info($sql->bindings);
//    \Log::info($sql->time);
//});