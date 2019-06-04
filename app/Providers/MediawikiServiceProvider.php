<?php

namespace App\Providers;

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
        $this->app->singleton(MediawikiFactory::class, function ($app) {
            $api = new MediawikiApi(env('WIKI_URL').'/api.php');
            $api->login(new ApiUser(env('WIKI_APIUSER'), env('WIKI_APIPASSWORD')));

            return new MediawikiFactory($api);
        });

        $this->app->bind(UserCreator::class, function ($app) {
            return $app->make(MediawikiFactory::class)->newUserCreator();
        });
    }
}
