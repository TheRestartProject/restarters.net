<?php

namespace App\Providers;

use App\Events\EventDeleted;
use App\Events\EventImagesUploaded;
use App\Events\UserFollowedGroup;
use App\Events\UserUpdated;
use App\Events\UserEmailUpdated;
use App\Events\UserLanguageUpdated;
use App\Events\UserDeleted;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\AnonymiseSoftDeletedUser;
use App\Listeners\DeleteEventFromWordPress;
use App\Listeners\RemoveSoftDeletedUserFromAllGroups;
use App\Listeners\SendAdminModerateEventPhotosNotification;
use App\Listeners\SendAdminUserDeletedNotification;
use App\Listeners\SyncUserProperties;
use App\Listeners\SyncUserToDiscourse;
use App\Listeners\SyncLanguageSettingsToDiscourse;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Auth\Events\Login' => [
            'App\Listeners\LogSuccessfulLogin',
        ],

        'App\Events\ApproveEvent' => [
            'App\Listeners\CreateWordPressApproveEventPost',
        ],

        'App\Events\EditEvent' => [
            'App\Listeners\CreateWordPressEditEventPost',
        ],

        EventDeleted::class => [
            DeleteEventFromWordPress::class,
        ],

        'App\Events\ApproveGroup' => [
            'App\Listeners\CreateWordPressApproveGroupPost',
        ],

        'App\Events\EditGroup' => [
            'App\Listeners\CreateWordPressEditGroupPost',
        ],

        'App\Events\PasswordChanged' => [
            'App\Listeners\ChangeWikiPassword',
        ],

        UserUpdated::class => [
            'App\Listeners\SyncUserProperties',
        ],

        UserEmailUpdated::class => [
            SyncUserToDiscourse::class,
        ],

        'App\Events\UserLanguageUpdated' => [
            'App\Listeners\SyncLanguageSettingsToDiscourse',
        ],

        UserFollowedGroup::class => [
            AddUserToDiscourseGroup::class,
        ],

        UserDeleted::class => [
            RemoveSoftDeletedUserFromAllGroups::class,
            SendAdminUserDeletedNotification::class,
            AnonymiseSoftDeletedUser::class,
        ],

        EventImagesUploaded::class => [
            SendAdminModerateEventPhotosNotification::class,
        ],

        \Illuminate\Auth\Events\Logout::class => [
            \App\Listeners\LogOutOfWiki::class,
        ],

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        if (env('FEATURE__WIKI_INTEGRATION') === true) {
            Event::listen('Illuminate\Auth\Events\Login', 'App\Listeners\LogInToWiki');
        }
    }
}
