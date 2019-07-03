<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

use Mediawiki\Api\ApiUser;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\MediawikiFactory;
use Mediawiki\Api\Service\UserCreator;

class MediawikiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if (empty(env('WIKI_URL'))) {
            return;
        }

        $this->app->singleton(MediawikiFactory::class, function ($app) {
            try {
                $api = new MediawikiApi(env('WIKI_URL').'/api.php');
                $api->login(new ApiUser(env('WIKI_APIUSER'), env('WIKI_APIPASSWORD')));

                return new MediawikiFactory($api);
            } catch (\Exception $ex) {
                Log::error("Failed to instantiation Wiki API classes: " . $ex->getMessage());
            }
        });

        $this->app->bind(UserCreator::class, function ($app) {
            return $app->make(MediawikiFactory::class)->newUserCreator();
        });
    }
}
