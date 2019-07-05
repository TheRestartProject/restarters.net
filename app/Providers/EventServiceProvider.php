<?php

namespace App\Providers;

use App\Events\UserDeleted;
use App\Listeners\RemoveSoftDeletedUserFromAllGroups;
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

        // Notify When Approve Event Occurs
        'App\Events\ApproveEvent' => [
            'App\Listeners\CreateWordPressApproveEventPost',
        ],

        // Notify When Edit Event Occurs
        'App\Events\EditEvent' => [
            'App\Listeners\CreateWordPressEditEventPost',
        ],

        // Notify When Approve Group Occurs
        'App\Events\ApproveGroup' => [
            'App\Listeners\CreateWordPressApproveGroupPost',
        ],

        // Notify When Edit Group Occurs
        'App\Events\EditGroup' => [
            'App\Listeners\CreateWordPressEditGroupPost',
        ],

        UserDeleted::class => [
            RemoveSoftDeletedUserFromAllGroups::class,
        ],

        'App\Events\PasswordChanged' => [
            'App\Listeners\ChangeWikiPassword',
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
