<?php

namespace App\Providers;

use App\Events\DeviceCreatedOrUpdated;
use App\Events\EventDeleted;
use App\Events\EventImagesUploaded;
use App\Events\UserDeleted;
use App\Events\UserEmailUpdated;
use App\Events\UserFollowedGroup;
use App\Events\UserLanguageUpdated;
use App\Events\UserRegistered;
use App\Events\UserUpdated;
use App\Listeners\AddUserToDiscourseGroup;
use App\Listeners\AnonymiseSoftDeletedUser;
use App\Listeners\DeleteEventFromWordPress;
use App\Listeners\DeviceUpdatedAt;
use App\Listeners\RemoveSoftDeletedUserFromAllGroups;
use App\Listeners\SendAdminModerateEventPhotosNotification;
use App\Listeners\SendAdminUserDeletedNotification;
use App\Listeners\SyncLanguageSettingsToDiscourse;
use App\Listeners\SyncUserProperties;
use App\Listeners\SyncUserToDiscourse;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Illuminate\Auth\Events\Login' => [
            \App\Listeners\LogSuccessfulLogin::class,
        ],

        \App\Events\ApproveEvent::class => [
            \App\Listeners\CreateWordpressPostForEvent::class,
            \App\Listeners\CreateDiscourseThreadForEvent::class,
        ],

        \App\Events\EditEvent::class => [
            \App\Listeners\EditWordpressPostForEvent::class,
        ],

        EventDeleted::class => [
            DeleteEventFromWordPress::class,
        ],

        \App\Events\ApproveGroup::class => [
            \App\Listeners\CreateWordpressPostForGroup::class,
            \App\Listeners\CreateDiscourseGroupForGroup::class,
        ],

        \App\Events\EditGroup::class => [
            \App\Listeners\EditWordpressPostForGroup::class,
        ],

        \App\Events\PasswordChanged::class => [
            \App\Listeners\ChangeWikiPassword::class,
        ],

        UserUpdated::class => [
            \App\Listeners\SyncUserProperties::class,
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

        DeviceCreatedOrUpdated::class => [
            DeviceUpdatedAt::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \App\Listeners\DiscourseUserEventSubscriber::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {

        if (env('FEATURE__WIKI_INTEGRATION') === true) {
            Event::listen('Illuminate\Auth\Events\Login', \App\Listeners\LogInToWiki::class);
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}

