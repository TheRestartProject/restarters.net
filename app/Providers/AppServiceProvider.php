<?php

namespace App\Providers;

use App\Models\EventsUsers;
use App\Helpers\Geocoder;
use App\Helpers\RobustTranslator;
use App\Observers\EventsUsersObserver;
use Auth;
use Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use OwenIt\Auditing\Models\Audit;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The admin area is unusable without this
        if (app()->isLocal()) {
            error_reporting(E_ALL ^ E_NOTICE);
        }

        Schema::defaultStringLength(191);

        // Don't create Audit entries when nothing that we want to audit has changed.
        // see: https://github.com/owen-it/laravel-auditing/issues/263#issuecomment-330695869
        Audit::creating(function (Audit $model) {
            if (empty($model->old_values) && empty($model->new_values)) {
                return false;
            }
        });

        \Illuminate\Pagination\Paginator::useBootstrapThree();

        EventsUsers::observe(EventsUsersObserver::class);
        
        $this->registerEvents();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Geocoder::class, function () {
            return new Geocoder();
        });

        // Override the existing translator with our own robust one.
        $this->app->extend('translator', function (Translator $translator) {
            $trans = new RobustTranslator($translator->getLoader(), $translator->getLocale());
            $trans->setFallback($translator->getFallback());
            return $trans;
        });

        $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);

        // Register exception handler (recommended for Laravel 11)
        $this->app->bind(\Illuminate\Contracts\Debug\ExceptionHandler::class, \App\Exceptions\Handler::class);
    }
    
    /**
     * Register all events
     */
    protected function registerEvents(): void
    {
        // Login events
        Event::listen('Illuminate\Auth\Events\Login', \App\Listeners\LogSuccessfulLogin::class);
        
        // Event approval events
        Event::listen(\App\Events\ApproveEvent::class, [
            \App\Listeners\CreateWordpressPostForEvent::class,
            \App\Listeners\CreateDiscourseThreadForEvent::class,
            \App\Listeners\NotifyApprovedEvent::class
        ]);
        
        // Event edit events
        Event::listen(\App\Events\EditEvent::class, \App\Listeners\EditWordpressPostForEvent::class);
        
        // Event deleted events
        Event::listen(\App\Events\EventDeleted::class, \App\Listeners\DeleteEventFromWordPress::class);
        
        // Group approval events
        Event::listen(\App\Events\ApproveGroup::class, [
            \App\Listeners\CreateWordpressPostForGroup::class,
            \App\Listeners\CreateDiscourseGroupForGroup::class
        ]);
        
        // Group edit events
        Event::listen(\App\Events\EditGroup::class, \App\Listeners\EditWordpressPostForGroup::class);
        
        // Password events
        Event::listen(\App\Events\PasswordChanged::class, \App\Listeners\ChangeWikiPassword::class);
        
        // User events
        Event::listen(\App\Events\UserUpdated::class, \App\Listeners\SyncUserProperties::class);
        Event::listen(\App\Events\UserFollowedGroup::class, \App\Listeners\AddUserToDiscourseGroup::class);
        Event::listen(\App\Events\UserDeleted::class, [
            \App\Listeners\RemoveSoftDeletedUserFromAllGroups::class,
            \App\Listeners\SendAdminUserDeletedNotification::class,
            \App\Listeners\AnonymiseSoftDeletedUser::class
        ]);
        
        // Event images events
        Event::listen(\App\Events\EventImagesUploaded::class, \App\Listeners\SendAdminModerateEventPhotosNotification::class);
        
        // Logout events
        Event::listen(\Illuminate\Auth\Events\Logout::class, \App\Listeners\LogOutOfWiki::class);
        
        // Device events
        Event::listen(\App\Events\DeviceCreatedOrUpdated::class, \App\Listeners\DeviceUpdatedAt::class);
        
        // User event confirmation events
        Event::listen(\App\Events\UserConfirmedEvent::class, \App\Listeners\AddUserToDiscourseThreadForEvent::class);
        Event::listen(\App\Events\UserLeftEvent::class, \App\Listeners\RemoveUserFromDiscourseThreadForEvent::class);
        
        // Subscribe to event subscribers
        Event::subscribe(\App\Listeners\DiscourseUserEventSubscriber::class);
        
        // Feature-specific events
        if (env('FEATURE__WIKI_INTEGRATION') === true) {
            Event::listen('Illuminate\Auth\Events\Login', \App\Listeners\LogInToWiki::class);
        }
    }
}
